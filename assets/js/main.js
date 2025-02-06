// Önce fonksiyonları tanımlayalım
function loadServers() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: '../includes/get_servers.php',
            type: 'GET',
            success: function(response) {
                try {
                    const servers = JSON.parse(response);
                    const serversList = document.querySelector('.servers-list');
                    
                    if (serversList) {
                        serversList.innerHTML = '';
                        servers.forEach(server => {
                            const serverIcon = document.createElement('div');
                            serverIcon.className = 'server-item';
                            serverIcon.dataset.serverId = server.id;
                            serverIcon.title = server.name;

                            serverIcon.innerHTML = server.icon ?
                                `<img src="../uploads/server_icons/${server.icon}" alt="${server.name}">` :
                                server.name.charAt(0).toUpperCase();

                            serversList.appendChild(serverIcon);
                        });
                    }
                    resolve();
                } catch (error) {
                    console.error('Sunucular yüklenirken hata:', error);
                    reject(error);
                }
            },
            error: function(error) {
                reject(error);
            }
        });
    });
}

// Sonra event listener'ları ekleyelim
$(document).ready(function() {
    // Loading screen kontrolü
    const loadingScreen = document.getElementById('loadingScreen');
    const appContainer = document.querySelector('.app-container');
    
    // Sayfa yüklenme işlemleri
    Promise.all([
        loadServers(),
        // Diğer Promise'ler buraya eklenebilir
    ])
    .then(() => {
        setTimeout(() => {
            if (loadingScreen) loadingScreen.style.display = 'none';
            if (appContainer) appContainer.style.display = 'flex';
        }, 1000);
    })
    .catch(error => {
        console.error('Yükleme hatası:', error);
    });

    // Error handling
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        console.error('Hata:', { msg, url, lineNo, columnNo, error });
        return false;
    };
});

// Event Listeners
document.addEventListener('DOMContentLoaded', function () {
    // Sunucu tıklama olayını dinle
    document.querySelectorAll('.server-item').forEach(item => {
        item.addEventListener('click', function () {
            if (this.classList.contains('home')) {
                showHome();
            } else if (this.classList.contains('add-server')) {
                showCreateServerModal();
            } else {
                const serverId = this.dataset.serverId;
                if (serverId) {
                    showServer(serverId);
                }
            }
        });
    });

    // Mesaj gönderme formunu dinle
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const input = this.querySelector('input');
            const message = input.value.trim();

            if (message) {
                const channelId = document.querySelector('.channel-item.active')?.dataset.channelId;
                const serverId = document.querySelector('.server-panel')?.dataset.serverId;

                if (channelId && serverId) {
                    ajax.sendMessage(channelId, serverId, message);
                    input.value = '';
                }
            }

            return false;
        });
    }

    // Custom Select için event listener'ları ekle
    const selectSelected = document.querySelector('.select-selected');
    if (selectSelected) {
        selectSelected.addEventListener('click', function (e) {
            e.stopPropagation();
            const items = this.nextElementSibling;
            this.classList.toggle('active');
            items.classList.toggle('show');
        });
    }

    const selectOptions = document.querySelectorAll('.select-option');
    selectOptions.forEach(option => {
        option.addEventListener('click', function (e) {
            e.stopPropagation();
            const value = this.dataset.value;
            const text = this.textContent;
            const icon = this.querySelector('i').cloneNode(true);

            const selected = this.closest('.custom-select').querySelector('.select-selected');
            const input = this.closest('.custom-select').querySelector('input');

            selected.innerHTML = '';
            selected.appendChild(icon);
            selected.appendChild(document.createTextNode(text));
            selected.appendChild(document.createElement('i')).className = 'fas fa-chevron-down';

            input.value = value;

            this.closest('.select-items').classList.remove('show');
            selected.classList.remove('active');
        });
    });

    // Sayfa tıklamalarında açık dropdown'ları kapat
    document.addEventListener('click', function (e) {
        const dropdowns = document.querySelectorAll('.select-items.show');
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
                dropdown.previousElementSibling.classList.remove('active');
            }
        });
    });

    // Sunucu ayarları formu için event listener
    const serverSettingsForm = document.getElementById('serverSettingsForm');
    if (serverSettingsForm) {
        serverSettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveServerSettings(e);
        });
    }

    // Rol yönetimi fonksiyonları
    initializeRoleManagement();
});

// Loading göster/gizle fonksiyonları
function showLoading(container) {
    container.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-2x"></i>
        </div>
    `;
    container.classList.add('loading');
}

function hideLoading(container) {
    container.classList.remove('loading');
}

// Sunucuyu göster
function showServer(serverId) {
    // Ana sayfayı gizle
    document.querySelector('.main-content').style.display = 'none';

    // Sunucu panelini göster
    const serverPanel = document.querySelector('.server-panel');
    serverPanel.style.display = 'flex';
    serverPanel.dataset.serverId = serverId;

    // Sunucu adını güncelle
    const serverName = document.querySelector('.server-header h3');
    const selectedServer = document.querySelector(`.server-item[data-server-id="${serverId}"]`);
    if (serverName && selectedServer) {
        serverName.textContent = selectedServer.title;
    }

    // Aktif sunucuyu güncelle
    document.querySelectorAll('.server-item').forEach(item => {
        item.classList.remove('active');
    });
    selectedServer?.classList.add('active');

    // Yükleme durumlarını göster
    const channelsContainer = document.querySelector('.channels-container');
    const messagesContainer = document.querySelector('.chat-messages');
    const membersContainer = document.querySelector('.members-list');

    showLoading(channelsContainer);
    showLoading(messagesContainer);
    showLoading(membersContainer);

    // Üyeleri getir
    ajax.getMembers(serverId);

    // Kanalları getir
    fetch(`../includes/get_channels.php?server_id=${serverId}`)
        .then(response => response.json())
        .then(data => {
            hideLoading(channelsContainer);
            channelsContainer.innerHTML = '';

            // İlk kanalı takip etmek için
            let firstChannelId = null;

            // Kategorili kanalları ekle
            if (data.categories) {
                data.categories.forEach(category => {
                    const categoryHtml = `
                        <div class="channels-category">
                            <div class="category-header" onclick="toggleCategory(this)">
                                <i class="fas fa-chevron-down"></i>
                                <span>${category.name}</span>
                                <i class="fas fa-plus" onclick="showCreateChannelModal(event)"></i>
                            </div>
                            <div class="channel-list">
                                ${category.channels.map(channel => `
                                    <div class="channel-item" data-channel-id="${channel.id}" onclick="showChannel(${channel.id})">
                                        <i class="fas ${channel.type === 'voice' ? 'fa-volume-up' : 'fa-hashtag'}"></i>
                                        <span>${channel.name}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                    channelsContainer.insertAdjacentHTML('beforeend', categoryHtml);
                    if (!firstChannelId && category.channels.length > 0) {
                        firstChannelId = category.channels[0].id;
                    }
                });
            }

            // Kategorisiz kanalları ekle
            if (data.channels && data.channels.length > 0) {
                const uncategorizedHtml = `
                    <div class="channels-category">
                        <div class="category-header" onclick="toggleCategory(this)">
                            <i class="fas fa-chevron-down"></i>
                            <span>GENEL KANALLAR</span>
                            <i class="fas fa-plus" onclick="showCreateChannelModal(event)"></i>
                        </div>
                        <div class="channel-list">
                            ${data.channels.map(channel => `
                                <div class="channel-item" data-channel-id="${channel.id}" onclick="showChannel(${channel.id})">
                                    <i class="fas ${channel.type === 'voice' ? 'fa-volume-up' : 'fa-hashtag'}"></i>
                                    <span>${channel.name}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                channelsContainer.insertAdjacentHTML('beforeend', uncategorizedHtml);
                if (!firstChannelId) {
                    firstChannelId = data.channels[0].id;
                }
            }

            // İlk kanalı seç ve mesajları yükle
            if (firstChannelId) {
                showChannel(firstChannelId);
                const firstChannel = document.querySelector(`[data-channel-id="${firstChannelId}"]`);
                if (firstChannel) {
                    firstChannel.classList.add('active');
                }
            }
        })
        .catch(error => {
            console.error('Kanal yükleme hatası:', error);
            hideLoading(channelsContainer);
        });
}

// Kanal oluştur
function handleCreateChannel(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const serverId = document.querySelector('.server-panel').dataset.serverId;
    formData.append('server_id', serverId);

    fetch('../includes/create_channel.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Kanal oluşturma hatası:', data.error);
                return;
            }

            closeModal('createChannelModal');
            // Sayfayı yenile
            window.location.reload();
        })
        .catch(error => console.error('Kanal oluşturma hatası:', error));
}

// Kategori oluştur
function handleCreateCategory(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const serverId = document.querySelector('.server-panel').dataset.serverId;
    formData.append('server_id', serverId);

    fetch('../includes/create_category.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Kategori oluşturma hatası:', data.error);
                return;
            }

            closeModal('createCategoryModal');
            // Sayfayı yenile
            window.location.reload();
        })
        .catch(error => console.error('Kategori oluşturma hatası:', error));
}

// Kanalı göster
function showChannel(channelId) {
    // Aktif kanalı güncelle
    document.querySelectorAll('.channel-item').forEach(item => {
        item.classList.remove('active');
    });
    const selectedChannel = document.querySelector(`.channel-item[data-channel-id="${channelId}"]`);
    selectedChannel?.classList.add('active');

    // Kanal başlığını güncelle
    const channelHeader = document.querySelector('.chat-header .channel-info span');
    if (channelHeader && selectedChannel) {
        channelHeader.textContent = selectedChannel.querySelector('span').textContent;
    }

    // Mesajları getir
    const serverId = document.querySelector('.server-panel').dataset.serverId;
    ajax.getMessages(channelId, serverId);

    // Önceki interval'i temizle
    if (window.messageInterval) {
        clearInterval(window.messageInterval);
    }

    // Yeni mesajları kontrol et (her 3 saniyede bir)
    window.messageInterval = setInterval(() => {
        const lastMessage = document.querySelector('.message:last-child');
        const lastMessageId = lastMessage ? lastMessage.dataset.messageId : 0;
        ajax.getMessages(channelId, serverId, lastMessageId);
    }, 3000);
}

// Kategori toggle fonksiyonunu ekleyelim
function toggleCategory(header) {
    const category = header.parentElement;
    const channelList = category.querySelector('.channel-list');
    const icon = header.querySelector('.fa-chevron-down');

    if (channelList.style.display === 'none') {
        channelList.style.display = 'block';
        icon.style.transform = 'rotate(0deg)';
    } else {
        channelList.style.display = 'none';
        icon.style.transform = 'rotate(-90deg)';
    }
}

// Kanal oluşturma modalını açarken event propagation'ı engelleyelim
function showCreateChannelModal(event) {
    if (event) {
        event.stopPropagation();
    }
    document.getElementById('createChannelModal').style.display = 'block';
}

// Ana sayfayı göster
function showHome() {
    // Sunucu panelini gizle
    document.querySelector('.server-panel').style.display = 'none';

    // Ana sayfayı göster
    document.querySelector('.main-content').style.display = 'flex';

    // Aktif sunucuyu temizle
    document.querySelectorAll('.server-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector('.server-item.home').classList.add('active');

    // Mesaj yenileme interval'ini temizle
    if (window.messageInterval) {
        clearInterval(window.messageInterval);
    }
}

// Sunucu header'ına tıklama olayını ekle
document.querySelector('.server-header').addEventListener('click', function (e) {
    const menu = document.querySelector('.server-settings-menu');
    if (!menu) return;

    if (menu.classList.contains('show')) {
        menu.classList.remove('show');
    } else {
        menu.classList.add('show');
    }
});

// Menü dışına tıklandığında menüyü kapat
document.addEventListener('click', function (e) {
    if (!e.target.closest('.server-header')) {
        const menu = document.querySelector('.server-settings-menu');
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
        }
    }
});

// Sunucu ayarları fonksiyonları
function initializeSettingsTabs() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function () {
            // Aktif sekmeyi değiştir
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            // İlgili içeriği göster
            const section = this.dataset.section;
            document.querySelectorAll('.settings-section').forEach(s => {
                s.style.display = 'none';  // Önce tüm sekmeleri gizle
            });

            const activeSection = document.getElementById(section);
            if (activeSection) {
                activeSection.style.display = 'block';  // Seçili sekmeyi göster
            }
        });
    });
}

function openServerSettings() {
    document.getElementById('serverSettingsModal').style.display = 'block';

    // Sekme yönetimini başlat
    initializeSettingsTabs();

    // Varsayılan olarak overview sekmesini göster
    document.querySelector('.nav-item[data-section="overview"]').click();

    // ESC tuşu ile kapatma
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeServerSettings();
        }
    });

    // Mevcut sunucu bilgilerini yükle
    loadServerSettings();
}

function closeServerSettings() {
    document.getElementById('serverSettingsModal').style.display = 'none';
}

// Sunucu ayarları sekmelerini yönet
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function () {
        // Aktif sekmeyi değiştir
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');

        // İlgili içeriği göster
        const section = this.dataset.section;
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
        document.getElementById(section).classList.add('active');
    });
});

// Sunucu ayarları modalını göster
function showServerSettings() {
    const modal = document.getElementById('serverSettingsModal');
    if (modal) {
        modal.style.display = 'flex';
        loadServerSettings();
    }
}

// Mevcut sunucu bilgilerini yükle
function loadServerSettings() {
    const serverId = document.querySelector('.server-panel').dataset.serverId;

    // Debug için log
    console.log('Sunucu ayarları yükleniyor:', serverId);

    fetch(`../includes/get_server_settings.php?server_id=${serverId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Sunucu ayarları yükleme hatası:', data.error);
                return;
            }

            console.log('Yüklenen sunucu ayarları:', data);

            // Form alanlarını doldur
            const serverNameInput = document.getElementById('serverName');
            if (serverNameInput) {
                serverNameInput.value = data.name;
                // Input değerinin değiştiğini doğrula
                console.log('Input değeri güncellendi:', serverNameInput.value);
            } else {
                console.error('serverName input bulunamadı!');
            }

            if (data.icon) {
                const iconPreview = document.getElementById('serverIconPreview');
                if (iconPreview) {
                    iconPreview.src = `../uploads/server_icons/${data.icon}`;
                }
            }
        })
        .catch(error => {
            console.error('Sunucu ayarları yükleme hatası:', error);
        });
}

// Davet linki oluştur
function createInviteLink() {
    // Debug için
    console.log('Modal açılıyor...');

    // Önce server settings menüsünü kapat
    closeServerSettings();

    const serverId = document.querySelector('.server-panel').dataset.serverId;

    // Modal HTML'ini oluştur
    const modalHtml = `
        <div class="invite-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Arkadaşlarını Davet Et</h2>
                    <span class="close" onclick="closeModal('modalContainer')">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="invite-description">
                        <p>Arkadaşlarını sunucuna davet et, sohbete katılsınlar!</p>
                    </div>
                    
                    <div class="invite-settings">
                        <h3>DAVET LİNKİ AYARLARI</h3>
                        <div class="invite-setting-row">
                            <div class="setting-label">
                                <i class="fas fa-clock"></i>
                                <span>Süre Sınırı</span>
                            </div>
                            <select id="inviteExpire">
                                <option value="never">Süresiz</option>
                                <option value="1">24 saat</option>
                                <option value="7">7 gün</option>
                                <option value="30">30 gün</option>
                            </select>
                        </div>
                        <div class="invite-setting-row">
                            <div class="setting-label">
                                <i class="fas fa-user-friends"></i>
                                <span>Maksimum Kullanım</span>
                            </div>
                            <select id="inviteMaxUses">
                                <option value="0">Sınırsız</option>
                                <option value="1">1 kullanım</option>
                                <option value="5">5 kullanım</option>
                                <option value="10">10 kullanım</option>
                                <option value="25">25 kullanım</option>
                                <option value="50">50 kullanım</option>
                                <option value="100">100 kullanım</option>
                            </select>
                        </div>
                    </div>

                    <div class="invite-link-section">
                        <h3>SUNUCU DAVET BAĞLANTISI</h3>
                        <div class="invite-link-container">
                            <input type="text" id="inviteLink" readonly placeholder="Davet linki oluşturmak için aşağıdaki butona tıklayın">
                            <button onclick="copyInviteLink()" class="copy-button" disabled>
                                <i class="fas fa-copy"></i>
                                <span>Kopyala</span>
                            </button>
                        </div>
                        <button onclick="generateNewInvite()" class="generate-button">
                            <i class="fas fa-sync-alt"></i>
                            <span>Davet Linki Oluştur</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Modalı göster
    const modalContainer = document.getElementById('modalContainer');
    if (!modalContainer) {
        console.error('Modal container bulunamadı!');
        return;
    }

    modalContainer.innerHTML = modalHtml;
    modalContainer.style.display = 'flex';
    console.log('Modal gösterildi');
}

// Server settings menüsünü kapat
function closeServerSettings() {
    const modal = document.getElementById('serverSettingsModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Yeni davet linki oluştur
function generateNewInvite() {
    const serverId = document.querySelector('.server-panel').dataset.serverId;
    const expireTime = document.getElementById('inviteExpire').value;
    const maxUses = document.getElementById('inviteMaxUses').value;

    console.log('Davet linki oluşturuluyor...', { serverId, expireTime, maxUses });

    fetch('../includes/generate_invite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            server_id: serverId,
            expire: expireTime,
            max_uses: maxUses
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            const inviteLinkInput = document.getElementById('inviteLink');
            const copyButton = document.querySelector('.copy-button');

            inviteLinkInput.value = `${window.location.origin}/join/${data.code}`;
            copyButton.disabled = false;

            console.log('Davet linki oluşturuldu:', data.code);
        })
        .catch(error => {
            console.error('Davet linki oluşturma hatası:', error);
            alert('Davet linki oluşturulurken bir hata oluştu!');
        });
}

// Davet linkini kopyala
function copyInviteLink() {
    const inviteLinkInput = document.getElementById('inviteLink');
    inviteLinkInput.select();
    document.execCommand('copy');

    const copyButton = document.querySelector('.copy-button');
    copyButton.innerHTML = '<i class="fas fa-check"></i> Kopyalandı';

    setTimeout(() => {
        copyButton.innerHTML = '<i class="fas fa-copy"></i> Kopyala';
    }, 2000);
}

function leaveServer() {
    if (confirm('Bu sunucudan çıkmak istediğinize emin misiniz?')) {
        const serverId = document.querySelector('.server-panel').dataset.serverId;
        fetch(`../includes/leave_server.php?server_id=${serverId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Sunucudan çıkma hatası:', data.error);
                    return;
                }
                window.location.reload();
            })
            .catch(error => console.error('Sunucudan çıkma hatası:', error));
    }
}

// Sunucu ayarlarını kaydet
function saveServerSettings(e) {
    e.preventDefault();

    const form = document.getElementById('serverSettingsForm');
    const serverId = document.querySelector('.server-panel').dataset.serverId;

    // Debug için log
    console.log('Form submit edildi');

    const formData = new FormData(form);
    formData.append('server_id', serverId);

    // Debug için form verilerini kontrol et
    for (let [key, value] of formData.entries()) {
        console.log('Form verisi:', key, value);
    }

    fetch('../includes/update_server_settings.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(text => {
            console.log('PHP yanıtı:', text);
            return JSON.parse(text);
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            // UI güncelleme
            const serverName = formData.get('name');
            document.querySelector('.server-header h3').textContent = serverName;
            document.querySelector(`.server-item[data-server-id="${serverId}"]`).title = serverName;

            // Modalı kapat
            const modal = document.getElementById('serverSettingsModal');
            if (modal) {
                modal.style.display = 'none';
            }

            alert('Ayarlar başarıyla kaydedildi!');
            window.location.reload();
        })
        .catch(error => {
            console.error('Hata:', error);
            alert('Ayarlar kaydedilirken bir hata oluştu: ' + error.message);
        });
}

// Sunucuyu sil
function deleteServer() {
    if (!confirm('Bu sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
        return;
    }

    const serverId = document.querySelector('.server-panel').dataset.serverId;
    fetch(`../includes/delete_server.php?server_id=${serverId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Sunucu silinirken bir hata oluştu: ' + data.error);
                return;
            }
            window.location.href = '/'; // Ana sayfaya yönlendir
        })
        .catch(error => {
            console.error('Sunucu silinirken bir hata oluştu:', error);
            alert('Sunucu silinirken bir hata oluştu!');
        });
}

// Server icon preview
document.getElementById('serverIconInput')?.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('serverIconPreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Custom Select Functions
function toggleDropdown(element) {
    const items = element.nextElementSibling;
    const chevron = element.querySelector('.fa-chevron-down');

    // Diğer açık dropdownları kapat
    document.querySelectorAll('.select-items.show').forEach(dropdown => {
        if (dropdown !== items) {
            dropdown.classList.remove('show');
            dropdown.previousElementSibling.classList.remove('active');
        }
    });

    // Tıklanan dropdown'ı aç/kapat
    element.classList.toggle('active');
    items.classList.toggle('show');
}

function selectRegion(option) {
    const value = option.dataset.value;
    const text = option.textContent;
    const icon = option.querySelector('i').cloneNode(true);

    const selected = option.closest('.custom-select').querySelector('.select-selected');
    const input = option.closest('.custom-select').querySelector('input');

    // Seçili değeri güncelle
    selected.innerHTML = '';
    selected.appendChild(icon);
    selected.appendChild(document.createTextNode(text));
    selected.appendChild(document.createElement('i')).className = 'fas fa-chevron-down';

    // Hidden input değerini güncelle
    input.value = value;

    // Dropdown'ı kapat
    option.closest('.select-items').classList.remove('show');
    selected.classList.remove('active');
}

// Modal kapatma fonksiyonu
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        modal.innerHTML = ''; // Modal içeriğini temizle
    }

    // Server settings menüsünü de kapat
    const serverSettingsMenu = document.querySelector('.server-settings-menu');
    if (serverSettingsMenu) {
        serverSettingsMenu.classList.remove('show');
    }
}

// ESC tuşu ile modalı kapatma
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const modalContainer = document.getElementById('modalContainer');
        if (modalContainer && modalContainer.style.display === 'flex') {
            closeModal('modalContainer');
        }
    }
});

// Modal dışına tıklayınca kapatma
document.addEventListener('click', function (event) {
    const modalContainer = document.getElementById('modalContainer');
    if (modalContainer && event.target === modalContainer) {
        closeModal('modalContainer');
    }
});

// Rol yönetimi fonksiyonları
function initializeRoleManagement() {
    // Rol sekmesine tıklama
    document.querySelectorAll('.nav-item[data-section="roles"]').forEach(item => {
        item.addEventListener('click', function () {
            // Aktif sekmeyi değiştir
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            // Rol bölümünü göster
            document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
            document.getElementById('roles').classList.add('active');
        });
    });

    // Yeni rol oluşturma butonu
    document.querySelector('.create-role-btn')?.addEventListener('click', function () {
        showCreateRoleModal();
    });

    // Rol düzenleme butonları
    document.querySelectorAll('.role-edit-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const roleItem = this.closest('.role-item');
            const roleId = roleItem.dataset.roleId;
            showEditRoleModal(roleId);
        });
    });

    // Rol silme butonları
    document.querySelectorAll('.role-delete-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const roleItem = this.closest('.role-item');
            const roleId = roleItem.dataset.roleId;
            if (confirm('Bu rolü silmek istediğinize emin misiniz?')) {
                deleteRole(roleId);
            }
        });
    });
}

// Rol oluşturma modalını göster
function showCreateRoleModal() {
    const modalContent = `
        <div class="modal-header">
            <h2>Yeni Rol Oluştur</h2>
            <button class="close-btn" onclick="closeModal('modalContainer')">×</button>
        </div>
        <div class="modal-body">
            <form id="createRoleForm" onsubmit="handleCreateRole(event)">
                <div class="form-group">
                    <label for="roleName">Rol Adı</label>
                    <input type="text" id="roleName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="roleColor">Rol Rengi</label>
                    <input type="color" id="roleColor" name="color" value="#99aab5">
                </div>
                <div class="permissions-section">
                    <h3>İzinler</h3>
                    <div class="permissions-grid">
                        <div class="permission-item">
                            <input type="checkbox" id="sendMessages" name="permissions[]" value="send_messages">
                            <label for="sendMessages">Mesaj Gönderme</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="manageChannels" name="permissions[]" value="manage_channels">
                            <label for="manageChannels">Kanalları Yönet</label>
                        </div>
                        <!-- Diğer izinler buraya eklenebilir -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="primary-btn">Rol Oluştur</button>
                </div>
            </form>
        </div>
    `;

    const modalContainer = document.getElementById('modalContainer');
    modalContainer.innerHTML = modalContent;
    modalContainer.style.display = 'flex';
}

// Rol oluşturma işlemi
function handleCreateRole(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const serverId = document.querySelector('.server-panel').dataset.serverId;
    formData.append('server_id', serverId);

    fetch('../includes/create_role.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('modalContainer');
                // Rolleri yeniden yükle
                loadRoles();
            } else {
                alert('Rol oluşturulurken bir hata oluştu: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Rol oluşturma hatası:', error);
            alert('Rol oluşturulurken bir hata oluştu!');
        });
}

// Sayfa yüklendiğinde rol yönetimini başlat
document.addEventListener('DOMContentLoaded', function () {
    initializeRoleManagement();
}); 