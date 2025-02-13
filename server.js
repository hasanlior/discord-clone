const express = require('express');
const http = require('http');
const WebSocket = require('ws');
const cors = require('cors');

const app = express();
app.use(cors());

// HTTP sunucusu oluştur
const server = http.createServer(app);

// WebSocket sunucusunu HTTP sunucusuna bağla
const wss = new WebSocket.Server({ server });

const rooms = new Map();
const gameStates = new Map();

class GameState {
    constructor(roomId) {
        this.roomId = roomId;
        this.ball = { 
            x: 500, // Topu yeni harita ortasına al (1000/2)
            y: 300, 
            vx: 0, 
            vy: 0,
            radius: 10,
            friction: 0.985,
            airResistance: 0.995,
            momentum: 0.3,
            minSpeed: 0.1,
            maxSpeed: 4
        };
        
        // Harita boyutlarını güncelle
        this.mapWidth = 1000; // 800'den 1000'e çıkarıldı
        this.mapHeight = 600;
        
        // Kale direkleri - sadece üst ve alt direkler için collider tanımla
        this.posts = [
            // Sol kale
            { x: -5, y: 200, width: 8, height: 8, isGoalPost: true },     // Sol üst yatay
            { x: -5, y: 400, width: 8, height: 8, isGoalPost: true },     // Sol alt yatay
            // Sağ kale
            { x: 997, y: 200, width: 8, height: 8, isGoalPost: true },    // Sağ üst yatay
            { x: 997, y: 400, width: 8, height: 8, isGoalPost: true }     // Sağ alt yatay
        ];
        this.players = new Map(); // Oyuncu verilerini tutan map
        this.scores = { red: 0, blue: 0 };
        this.gameStarted = false;
        this.lastUpdate = Date.now();
        this.throwInTeam = null;
        this.maxPlayers = {
            red: 3,
            blue: 3,
            spectator: 6
        };
        this.countdownTimer = null;
        this.goalAnimation = false;
        this.goalTimeout = null;
        this.resetPositions();

        // Duvar sınırları güncellendi
        this.boundaries = {
            left: -10,     // Gol çizgisi
            right: 1010,   // Gol çizgisi
            top: 10,
            bottom: 590
        };
    }

    getTeamStartPositions(team) {
        if (team === 'red') {
            return [
                { x: 150, y: 200 }, // Sol üst
                { x: 150, y: 300 }, // Orta
                { x: 150, y: 400 }  // Sol alt
            ];
        } else if (team === 'blue') {
            return [
                { x: 850, y: 200 }, // Sağ üst
                { x: 850, y: 300 }, // Orta
                { x: 850, y: 400 }  // Sağ alt
            ];
        }
        return [{ x: 500, y: 50 }]; // İzleyici
    }

    addPlayer(username, team, cosmetics = {}) {
        const teamPlayers = Array.from(this.players.values()).filter(p => p.team === team);
        if (teamPlayers.length >= this.maxPlayers[team]) return false;

        const positions = this.getTeamStartPositions(team);
        const pos = positions[teamPlayers.length] || positions[0];

        // Kostüm bilgilerini dahil ederek oyuncu ekle
        this.players.set(username, {
            x: pos.x,
            y: pos.y,
            team: team,
            username: username,
            radius: 20,
            cosmeticId: cosmetics.cosmeticId || null,
            cosmeticClass: cosmetics.cosmeticClass || null
        });
        
        console.log(`Added player ${username} with cosmetics:`, cosmetics);
        return true;
    }
    
    // Oyuncu kostümünü güncelleme fonksiyonu
    updatePlayerCosmetic(username, cosmeticId, cosmeticClass) {
        const player = this.players.get(username);
        if (player) {
            player.cosmeticId = cosmeticId;
            player.cosmeticClass = cosmeticClass;
            this.broadcastGameState();
        }
    }

    checkGoal() {
        const goalY = 300;
        const goalHeight = 100;
        
        // Gol çizgilerini güncelle
        if (this.ball.x < -5 && Math.abs(this.ball.y - goalY) < goalHeight && !this.goalAnimation) {
            this.scores.blue++;
            this.goalAnimation = true;
            this.broadcastMessage('goal', 'blue');
            this.resetBall('blue');
            return true;
        }
        
        if (this.ball.x > 1005 && Math.abs(this.ball.y - goalY) < goalHeight && !this.goalAnimation) {
            this.scores.red++;
            this.goalAnimation = true;
            this.broadcastMessage('goal', 'red');
            this.resetBall('red');
            return true;
        }
        return false;
    }

    resetBall(scoringTeam) {
        // Topu ortala
        this.ball = { 
            x: 500, 
            y: 300, 
            vx: 0, 
            vy: 0,
            radius: 10,
            friction: 0.98
        };


        // Animasyon bitince gol durumunu resetle
        setTimeout(() => {
            this.goalAnimation = false;
            this.checkCollisions();
            broadcastGameState(this.roomId);
        }, 3000);
    }

    update() {
        const now = Date.now();
        const dt = (now - this.lastUpdate) / 1000; // Delta time in seconds
        this.lastUpdate = now;

        if (this.gameStarted && !this.throwInTeam && !this.goalAnimation) {
            // Top hareketi
            this.ball.x += this.ball.vx;
            this.ball.y += this.ball.vy;

            // Önce gol kontrolü yap
            if (this.checkGoal()) return;

            // Duvar kontrolleri (gol bölgesi hariç)
            const inGoalArea = this.ball.y > 200 && this.ball.y < 400;
            
            if (this.ball.x < this.boundaries.left && !inGoalArea) {
                this.ball.x = this.boundaries.left;
                this.ball.vx *= -0.8;
            }
            if (this.ball.x > this.boundaries.right && !inGoalArea) {
                this.ball.x = this.boundaries.right;
                this.ball.vx *= -0.8;
            }
            if (this.ball.y < this.boundaries.top) {
                this.ball.y = this.boundaries.top;
                this.ball.vy *= -0.8;
            }
            if (this.ball.y > this.boundaries.bottom) {
                this.ball.y = this.boundaries.bottom;
                this.ball.vy *= -0.8;
            }

            // Çarpışma kontrolleri
            this.checkCollisions();

            // Sürtünme
            this.ball.vx *= this.ball.friction;
            this.ball.vy *= this.ball.friction;

            // Minimum hız kontrolü
            const speed = Math.sqrt(this.ball.vx * this.ball.vx + this.ball.vy * this.ball.vy);
            if (speed < 0.1) {
                this.ball.vx = 0;
                this.ball.vy = 0;
            }

            // Gol kontrolü
            if (this.checkGoal()) {
                this.goalAnimation = true;
                this.broadcastMessage('goal', scoringTeam);
                
                clearTimeout(this.goalTimeout);
                this.goalTimeout = setTimeout(() => {
                    this.goalAnimation = false;
                    this.resetPositions();
                    this.broadcastGameState(this.roomId);
                }, 3000);
            }
        }
    }

    checkPostCollision() {
        const posts = [
            { x: 0, y: 250 },   // Sol üst direk
            { x: 0, y: 350 },   // Sol alt direk
            { x: 800, y: 250 }, // Sağ üst direk
            { x: 800, y: 350 }  // Sağ alt direk
        ];

        posts.forEach(post => {
            const dx = this.ball.x - post.x;
            const dy = this.ball.y - post.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            
            if (dist < 15) { // Direk yarıçapı
                // Sekme vektörü hesaplama
                const nx = dx / dist;
                const ny = dy / dist;
                const speed = Math.sqrt(this.ball.vx * this.ball.vx + this.ball.vy * this.ball.vy);
                this.ball.vx = nx * speed * 0.8; // Enerji kaybı
                this.ball.vy = ny * speed * 0.8;
            }
        });
    }

    startCountdown(callback) {
        let count = 3;
        this.countdownTimer = setInterval(() => {
            if (count > 0) {
                this.broadcastMessage('countdown', count);
                count--;
            } else {
                clearInterval(this.countdownTimer);
                callback();
            }
        }, 1000);
    }

    resetPositions() {
        // Top pozisyonunu resetle
        this.ball = {
            x: 500,
            y: 300,
            vx: 0,
            vy: 0,
            radius: 10,
            friction: 0.98
        };

        // Oyuncuları başlangıç pozisyonlarına gönder
        const startPositions = {
            red: [
                { x: 150, y: 200 },
                { x: 150, y: 300 },
                { x: 150, y: 400 }
            ],
            blue: [
                { x: 850, y: 200 },
                { x: 850, y: 300 },
                { x: 850, y: 400 }
            ]
        };

        let redIndex = 0;
        let blueIndex = 0;

        this.players.forEach((player, username) => {
            if (player.team === 'red') {
                const pos = startPositions.red[redIndex % 3];
                player.x = pos.x;
                player.y = pos.y;
                redIndex++;
            } else if (player.team === 'blue') {
                const pos = startPositions.blue[blueIndex % 3];
                player.x = pos.x;
                player.y = pos.y;
                blueIndex++;
            }
        });

        // Tüm oyunculara resetPosition eventi gönder
        this.broadcastMessage('resetPositions');
    }

    getStartPosition(team) {
        switch(team) {
            case 'red':
                return { x: 100, y: 300 };
            case 'blue':
                return { x: 700, y: 300 };
            default:
                return { x: 400, y: 50 };
        }
    }

    handleKick(player, kickData) {
        const dx = this.ball.x - player.x;
        const dy = this.ball.y - player.y;
        const dist = Math.sqrt(dx * dx + dy * dy);

        if (dist < 50) { // Vuruş mesafesi
            // Hız vektörlerini ayarla
            this.ball.vx = kickData.vx;
            this.ball.vy = kickData.vy;

            // Maksimum hız sınırlaması
            const speed = Math.sqrt(this.ball.vx * this.ball.vx + this.ball.vy * this.ball.vy);
            if (speed > this.ball.maxSpeed) {
                const ratio = this.ball.maxSpeed / speed;
                this.ball.vx *= ratio;
                this.ball.vy *= ratio;
            }
        } else {
            // Topa vurulmadıysa, topu oyuncunun hareket yönünde hafifçe it
            this.ball.vx = player.vx * 0.5;
            this.ball.vy = player.vy * 0.5;
        }
    }

    checkCollisions() {
        // Top-Oyuncu çarpışması
        this.players.forEach(player => {
            const dx = this.ball.x - player.x;
            const dy = this.ball.y - player.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const minDist = this.ball.radius + player.radius;

            if (dist < minDist) {
                // Çarpışma çözümleme
                const angle = Math.atan2(dy, dx);
                const targetX = player.x + Math.cos(angle) * minDist;
                const targetY = player.y + Math.sin(angle) * minDist;
                
                this.ball.x = targetX;
                this.ball.y = targetY;
                
                // Topun hızını güncelle
                const speed = Math.sqrt(this.ball.vx * this.ball.vx + this.ball.vy * this.ball.vy);
                this.ball.vx = Math.cos(angle) * speed;
                this.ball.vy = Math.sin(angle) * speed;
            }
        });

        // Top-Direk çarpışması (sadece yatay direkler için)
        this.posts.forEach(post => {
            // Sadece direklerin üst ve altıyla çarpışma kontrolü yap
            if (this.ball.x + this.ball.radius > post.x && 
                this.ball.x - this.ball.radius < post.x + post.width &&
                this.ball.y + this.ball.radius > post.y && 
                this.ball.y - this.ball.radius < post.y + post.height) {
                
                // Çarpışma yönüne göre sekme
                const dx = this.ball.x - (post.x + post.width/2);
                const dy = this.ball.y - (post.y + post.height/2);
                const angle = Math.atan2(dy, dx);
                
                // Hız vektörünü güncelle
                const speed = Math.sqrt(this.ball.vx * this.ball.vx + this.ball.vy * this.ball.vy);
                this.ball.vx = Math.cos(angle) * speed * 0.8;
                this.ball.vy = Math.sin(angle) * speed * 0.8;
            }
        });

        // Oyuncu-Direk çarpışması
        this.players.forEach(player => {
            this.posts.forEach(post => {
                if (player.x + player.radius > post.x &&
                    player.x - player.radius < post.x + post.width &&
                    player.y + player.radius > post.y &&
                    player.y - player.radius < post.y + post.height) {
                    
                    // Çarpışma yönüne göre pozisyonu düzelt
                    if (player.x < post.x) player.x = post.x - player.radius;
                    else if (player.x > post.x + post.width) player.x = post.x + post.width + player.radius;
                    if (player.y < post.y) player.y = post.y - player.radius;
                    else if (player.y > post.y + post.height) player.y = post.y + post.height + player.radius;
                }
            });
        });

        // Top-Oyuncu çarpışması
        this.players.forEach(player => {
            const dx = this.ball.x - player.x;
            const dy = this.ball.y - player.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const minDist = this.ball.radius + player.radius;

            if (dist < minDist) {
                // Çarpışma çözümleme
                const angle = Math.atan2(dy, dx);
                const targetX = player.x + Math.cos(angle) * minDist;
                const targetY = player.y + Math.sin(angle) * minDist;
                
                this.ball.x = targetX;
                this.ball.y = targetY;
                
                // Topun hızını güncelle
                const speed = Math.sqrt(this.ball.vx * this.ball.vx + this.ball.vy * this.ball.vy);
                this.ball.vx = Math.cos(angle) * speed;
                this.ball.vy = Math.sin(angle) * speed;
            }
        });

        // Top-Direk çarpışması
        this.posts.forEach(post => {
            if (this.ball.x + this.ball.radius > post.x && 
                this.ball.x - this.ball.radius < post.x + post.width &&
                this.ball.y + this.ball.radius > post.y && 
                this.ball.y - this.ball.radius < post.y + post.height) {
                
                // Çarpışma yönüne göre sekme
                if (this.ball.x < post.x || this.ball.x > post.x + post.width) {
                    this.ball.vx *= -0.8;
                }
                if (this.ball.y < post.y || this.ball.y > post.y + post.height) {
                    this.ball.vy *= -0.8;
                }
            }
        });
    }

    broadcastMessage(type, data) {
        const room = rooms.get(this.roomId);
        if (room) {
            room.forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(JSON.stringify({ type, data }));
                }
            });
        }
    }
}

wss.on('listening', () => {
    console.log('\x1b[32m%s\x1b[0m', '🚀 WebSocket Server started on port 8080');
    console.log('\x1b[36m%s\x1b[0m', '👥 Waiting for connections...');
});

wss.on('connection', (ws, req) => {
    const clientIp = req.socket.remoteAddress;
    console.log('\x1b[33m%s\x1b[0m', `📡 New connection from ${clientIp}`);
    
    let currentRoom = null;
    let playerData = null;

    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);

            switch (data.type) {
                case 'join':
                    handleJoin(ws, data);
                    currentRoom = data.roomId;
                    playerData = {
                        username: data.username,
                        team: data.team || 'spectator',
                        x: data.team === 'red' ? 200 : 600,
                        y: 300
                    };
                    break;

                case 'move':
                    if (currentRoom && playerData) {
                        handleMove(currentRoom, playerData.username, data);
                    }
                    break;

                case 'kick':
                    if (currentRoom && playerData) {
                        handleKick(currentRoom, playerData.username, data);
                    }
                    break;

                case 'startGame':
                    if (currentRoom) {
                        handleStartGame(currentRoom);
                    }
                    break;

                case 'throwIn':
                    if (currentRoom && playerData) {
                        handleThrowIn(currentRoom, playerData.username, data);
                    }
                    break;

                case 'playerCharging':
                    if (currentRoom) {
                        broadcastToRoom(currentRoom, {
                            type: 'playerCharging',
                            username: data.username
                        });
                    }
                    break;

                case 'playerStopCharging':
                    if (currentRoom) {
                        broadcastToRoom(currentRoom, {
                            type: 'playerStopCharging',
                            username: data.username
                        });
                    }
                    break;

                case 'collision':
                    if (currentRoom) {
                        broadcastToRoom(currentRoom, {
                            type: 'collision',
                            target: data.target,
                            pushX: data.pushX,
                            pushY: data.pushY
                        });
                    }
                    break;

                case 'ping':
                    ws.send(JSON.stringify({ type: 'pong' }));
                    break;

                case 'updateCosmetic':
                    if (currentRoom && playerData) {
                        const gameState = gameStates.get(data.roomId);
                        if (gameState) {
                            const player = gameState.players.get(playerData.username);
                            if (player) {
                                player.cosmeticId = data.cosmeticId;
                                player.cosmeticClass = data.cosmeticClass;
                                // Tüm oyunculara bildir
                                broadcastToRoom(currentRoom, {
                                    type: 'playerCosmeticUpdate',
                                    username: playerData.username,
                                    cosmeticId: data.cosmeticId,
                                    cosmeticClass: data.cosmeticClass
                                });
                            }
                        }
                    }
                    break;
            }
        } catch (error) {
            console.error('\x1b[31m%s\x1b[0m', '❌ Error processing message:', error.message);
        }
    });

    ws.on('close', () => {
        console.log('\x1b[31m%s\x1b[0m', `❌ Connection closed ${playerData ? `for ${playerData.username}` : ''}`);
        if (currentRoom && playerData) {
            handleLeave(currentRoom, playerData.username);
        }
    });

    ws.on('error', (error) => {
        console.error('\x1b[31m%s\x1b[0m', '🔥 WebSocket error:', error.message);
    });
});

// handleJoin fonksiyonunu güncelle
function handleJoin(ws, data) {
    if (!rooms.has(data.roomId)) {
        rooms.set(data.roomId, new Map());
        gameStates.set(data.roomId, new GameState(data.roomId));
    }

    const room = rooms.get(data.roomId);
    room.set(data.username, ws);

    const gameState = gameStates.get(data.roomId);
    // Kostüm bilgilerini koruyarak oyuncuyu ekle
    const success = gameState.addPlayer(data.username, data.team, {
        cosmeticId: data.cosmeticId,
        cosmeticClass: data.cosmeticClass
    });
    
    if (!success) {
        ws.send(JSON.stringify({
            type: 'error',
            message: 'Team is full'
        }));
        return;
    }

    // Oyuncunun kostüm bilgisini ayarla
    const player = gameState.players.get(data.username);
    if (player) {
        if (data.cosmeticId) {
            player.cosmeticId = data.cosmeticId;
            player.cosmeticClass = data.cosmeticClass;
            console.log(`Player ${data.username} joined with cosmetic:`, {
                id: data.cosmeticId,
                class: data.cosmeticClass
            });
        }
    }

    broadcastGameState(data.roomId);
}

function handleMove(roomId, username, data) {
    const gameState = gameStates.get(roomId);
    if (gameState && gameState.players.has(username)) {
        const player = gameState.players.get(username);
        player.x = data.x;
        player.y = data.y;
        broadcastGameState(roomId);
    }
}

function handleKick(roomId, username, data) {
    const gameState = gameStates.get(roomId);
    if (gameState && gameState.gameStarted) {
        const player = gameState.players.get(username);
        if (player) {
            gameState.handleKick(player, data);
            broadcastGameState(roomId);
        }
    }
}

function handleStartGame(roomId) {
    const gameState = gameStates.get(roomId);
    if (gameState) {
        // Eski broadcastMessage yerine room.forEach kullan
        const room = rooms.get(roomId);
        if (room) {
            const message = {
                type: 'countdown',
                count: 3
            };
            
            // Geri sayım için interval
            let count = 3;
            const countInterval = setInterval(() => {
                if (count > 0) {
                    room.forEach((client) => {
                        if (client.readyState === WebSocket.OPEN) {
                            client.send(JSON.stringify({
                                type: 'countdown',
                                count: count
                            }));
                        }
                    });
                    count--;
                } else {
                    clearInterval(countInterval);
                    gameState.gameStarted = true;
                    gameState.resetPositions();
                    broadcastGameState(roomId);
                }
            }, 1000);
        }
    }
}

function handleThrowIn(roomId, username, data) {
    const gameState = gameStates.get(roomId);
    if (gameState && gameState.throwInTeam) {
        const player = gameState.players.get(username);
        if (player && player.team === gameState.throwInTeam) {
            gameState.ball.x = data.x;
            gameState.ball.y = data.y;
            gameState.ball.vx = 0;
            gameState.ball.vy = 0;
            gameState.throwInTeam = null;
            broadcastGameState(roomId);
        }
    }
}

function handleLeave(roomId, username) {
    console.log('\x1b[35m%s\x1b[0m', `👋 Player ${username} left room ${roomId}`);
    const room = rooms.get(roomId);
    if (room) {
        room.delete(username);
        if (room.size === 0) {
            console.log('\x1b[35m%s\x1b[0m', `🏁 Room ${roomId} closed - no players left`);
            rooms.delete(roomId);
            gameStates.delete(roomId);
        } else {
            broadcastGameState(roomId);
        }
    }
}

function broadcastGameState(roomId) {
    const room = rooms.get(roomId);
    const gameState = gameStates.get(roomId);
    
    if (room && gameState) {
        // Debug log ekle
        const state = {
            type: 'gameState',
            ball: gameState.ball,
            players: Array.from(gameState.players.entries()).map(([username, data]) => ({
                username,
                x: data.x,
                y: data.y,
                team: data.team,
                cosmeticId: data.cosmeticId,
                cosmeticClass: data.cosmeticClass // cosmeticClass'ı da ekleyelim
            })),
            scores: gameState.scores,
            gameStarted: gameState.gameStarted,
            throwInTeam: gameState.throwInTeam
        };

        room.forEach((client) => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(JSON.stringify(state));
            }
        });
    }
}

// Game loop
const TICK_RATE = 64;
const TICK_INTERVAL = 1000 / TICK_RATE;
let lastTick = Date.now();

setInterval(() => {
    const now = Date.now();
    const delta = now - lastTick;
    
    if (delta >= TICK_INTERVAL) {
        lastTick = now - (delta % TICK_INTERVAL);
        
        // Oyun durumunu güncelle
        gameStates.forEach((state) => {
            state.update();
            broadcastGameState(state.roomId);
        });
    }
}, 1); // Sık kontrol et ama sadece tick rate'e göre güncelle

// HTTP sunucusunu başlat
const PORT = process.env.PORT || 3000; // 8080 yerine 3000 kullan
server.listen(PORT, '0.0.0.0', () => {
    console.log('\x1b[32m%s\x1b[0m', `🚀 Server running on:`);
    console.log('\x1b[36m%s\x1b[0m', `   Local: ws://localhost:${PORT}`);
    console.log('\x1b[36m%s\x1b[0m', `   LAN: ws://${getLocalIP()}:${PORT}`);
    console.log('\x1b[36m%s\x1b[0m', '👥 Waiting for connections...');
});

// Local IP adresini al
function getLocalIP() {
    const { networkInterfaces } = require('os');
    const nets = networkInterfaces();
    for (const name of Object.keys(nets)) {
        for (const net of nets[name]) {
            if (net.family === 'IPv4' && !net.internal) {
                return net.address;
            }
        }
    }
    return 'localhost';
}

// Hata yakalama
process.on('uncaughtException', (error) => {
    console.error('\x1b[31m%s\x1b[0m', '❌ Uncaught Exception:', error.message);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('\x1b[31m%s\x1b[0m', '❌ Unhandled Rejection at:', promise, 'reason:', reason);
});

function broadcastToRoom(roomId, message) {
    const room = rooms.get(roomId);
    if (room) {
        room.forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(JSON.stringify(message));
            }
        });
    }
}
