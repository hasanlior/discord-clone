let lastMessageId = 0;
let currentChannelId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Sunucuları yükle
    loadServers();
    
    // Sunucu ekleme butonu kontrolü
    const addServerBtn = document.getElementById('addServerBtn');
    if (addServerBtn) {
        addServerBtn.addEventListener('click', createServerModal);
    }

    // Ana sayfa butonu kontrolü
    const homeBtn = document.querySelector('.server-item.home');
    if (homeBtn) {
        homeBtn.addEventListener('click', () => switchServer('home'));
    }
});

// Sunucuları yükle
function loadServers() {
    $.ajax({
        url: '../includes/get_servers.php',
        method: 'GET',
        success: function(response) {
            try {
                const servers = JSON.parse(response);
                const serversList = document.querySelector('.servers-sidebar');
                const addServerBtn = document.getElementById('addServerBtn');
                const divider = serversList.querySelector('.servers-divider');
                
                // Önce mevcut sunucuları temizle
                const existingServers = serversList.querySelectorAll('.server-item:not(.home):not(.add-server)');
                existingServers.forEach(server => server.remove());
                
                // Yeni sunucuları ekle
                if (serversList && addServerBtn) {
                    servers.forEach(server => {
                        const serverElement = createServerElement(server);
                        serversList.insertBefore(serverElement, addServerBtn);
                    });
                }
            } catch (error) {
                console.error('Sunucular yüklenirken hata:', error);
            }
        }
    });
}

// Sunucu elementi oluştur
function createServerElement(server) {
    const div = document.createElement('div');
    div.className = 'server-item';
    div.dataset.serverId = server.id;
    
    if (server.icon) {
        const img = document.createElement('img');
        img.src = `../uploads/server_icons/${server.icon}`;
        img.alt = server.name;
        img.onerror = function() {
            div.textContent = server.name.charAt(0).toUpperCase();
        };
        div.appendChild(img);
    } else {
        div.textContent = server.name.charAt(0).toUpperCase();
    }
    
    div.setAttribute('title', server.name);
    div.addEventListener('click', () => switchServer(server.id));
    
    return div;
}

// Sunucu değiştir
function switchServer(serverId) {
    // Aktif sunucuyu güncelle
    const serverButtons = document.querySelectorAll('.server-item');
    serverButtons.forEach(btn => btn.classList.remove('active'));

    const mainContent = document.querySelector('.main-content');
    const serverContent = document.querySelector('.channels-sidebar').parentElement;

    if (serverId === 'home') {
        // Ana sayfa görünümü
        document.querySelector('.server-item.home').classList.add('active');
        mainContent.style.display = 'block';
        serverContent.style.display = 'none';
    } else {
        // Sunucu görünümü
        const selectedServer = document.querySelector(`[data-server-id="${serverId}"]`);
        if (selectedServer) {
            selectedServer.classList.add('active');
            mainContent.style.display = 'none';
            serverContent.style.display = 'flex';
            
            // Sunucu başlığını güncelle
            const serverHeader = document.querySelector('.server-header h3');
            if (serverHeader) {
                serverHeader.textContent = selectedServer.getAttribute('title');
            }
        }
    }
}

// Ana sayfa görünümünü yükle
function loadHomeView() {
    $.ajax({
        url: '../includes/get_home_view.php',
        method: 'GET',
        success: function(response) {
            document.querySelector('.app-container').innerHTML = response;
            // Sunucuları tekrar yükle
            loadServers();
        }
    });
}

// Sunucu görünümünü yükle
function loadServerView(serverId) {
    $.ajax({
        url: '../includes/get_server_view.php',
        data: { server_id: serverId },
        method: 'GET',
        success: function(response) {
            document.querySelector('.app-container').innerHTML = response;
            // Sunucuları tekrar yükle
            loadServers();
        }
    });
}

// Sunucu oluşturma modalı
function createServerModal() {
    const modalHTML = `
        <div class="modal">
            <div class="modal-content">
                <h2>Yeni Sunucu Oluştur</h2>
                <form id="createServerForm">
                    <div class="server-icon-upload">
                        <div class="icon-preview">
                            <input type="file" id="serverIcon" name="server_icon" accept="image/*" style="display: none;">
                            <label for="serverIcon">
                                <i class="fas fa-camera"></i>
                                <img id="iconPreview" style="display: none;">
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="serverName">SUNUCU ADI</label>
                        <input type="text" id="serverName" name="server_name" required 
                               placeholder="Örn: Oyun Sunucusu" maxlength="100">
                    </div>
                    <div class="modal-buttons">
                        <button type="button" onclick="closeModal()">İptal</button>
                        <button type="submit">Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    const modalContainer = document.getElementById('modalContainer');
    if (modalContainer) {
        modalContainer.innerHTML = modalHTML;
        
        // Form ve dosya yükleme olaylarını dinle
        const form = document.getElementById('createServerForm');
        const fileInput = document.getElementById('serverIcon');
        
        if (fileInput) {
            fileInput.addEventListener('change', handleIconPreview);
        }
        
        if (form) {
            form.addEventListener('submit', handleServerCreate);
        }
    }
}

// Sunucu ikonu önizleme
function handleIconPreview(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('iconPreview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                document.querySelector('.icon-preview i').style.display = 'none';
            }
        }
        reader.readAsDataURL(file);
    }
}

// Modal kapat
function closeModal() {
    const modalContainer = document.getElementById('modalContainer');
    if (modalContainer) {
        modalContainer.innerHTML = '';
    }
}

// Sunucu oluşturma işlemi
function handleServerCreate(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);

    $.ajax({
        url: '../includes/create_server.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    closeModal();
                    loadServers();
                    // Yeni oluşturulan sunucuya yönlendir
                    if (result.server && result.server.id) {
                        switchServer(result.server.id);
                    }
                } else {
                    alert(result.error || 'Sunucu oluşturulurken bir hata oluştu');
                }
            } catch (error) {
                console.error('Sunucu oluşturma hatası:', error);
                alert('Sunucu oluşturulurken bir hata oluştu');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax hatası:', error);
            alert('Sunucu oluşturulurken bir hata oluştu');
        }
    });
} 