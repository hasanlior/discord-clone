// AJAX İşlemleri
const ajax = {
    // Mesajları getir
    getMessages: function(channelId, serverId, lastMessageId = 0) {
        const messagesContainer = document.querySelector('.chat-messages');
        
        // Sadece ilk yüklemede loading göster
        if (!lastMessageId) {
            showLoading(messagesContainer);
            messagesContainer.innerHTML = ''; // Container'ı temizle
        }

        fetch(`../includes/get_messages.php?channel_id=${channelId}&server_id=${serverId}&last_message_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Mesaj getirme hatası:', data.error);
                    if (!lastMessageId) hideLoading(messagesContainer); // Sadece ilk yüklemede loading'i kaldır
                    return;
                }
                
                this.updateMessages(data);
                
                // Sadece ilk yüklemede loading'i kaldır
                if (!lastMessageId) {
                    hideLoading(messagesContainer);
                }
            })
            .catch(error => {
                console.error('Mesaj yükleme hatası:', error);
                if (!lastMessageId) hideLoading(messagesContainer); // Sadece ilk yüklemede loading'i kaldır
            });
    },

    // Mesajları güncelle
    updateMessages: function(messages) {
        const messagesContainer = document.querySelector('.chat-messages');
        
        // Yeni mesajlar varsa ekle
        messages.forEach(message => {
            // Mesaj zaten varsa ekleme
            if (document.querySelector(`.message[data-message-id="${message.id}"]`)) {
                return;
            }

            const messageHtml = `
                <div class="message" data-message-id="${message.id}">
                    <div class="message-avatar">
                        ${message.avatar ? 
                            `<img src="../uploads/avatars/${message.avatar}" alt="${message.username}">` :
                            `<div class="default-avatar">${message.username.charAt(0).toUpperCase()}</div>`
                        }
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <span class="username">${message.username}</span>
                            <span class="timestamp">${message.created_at}</span>
                        </div>
                        <div class="message-text">${message.content}</div>
                    </div>
                </div>
            `;
            messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
        });

        // Yeni mesaj varsa aşağı kaydır
        if (messages.length > 0) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    },

    // Mesaj gönder
    sendMessage: function(channelId, serverId, message) {
        const formData = new FormData();
        formData.append('channel_id', channelId);
        formData.append('server_id', serverId);
        formData.append('message', message);

        fetch('../includes/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Mesaj gönderme hatası:', data.error);
                return;
            }
            // Mesaj başarıyla gönderildi, yeni mesajları al
            this.getMessages(channelId, serverId);
        })
        .catch(error => console.error('Mesaj gönderme hatası:', error));
    },

    // Üyeleri getir
    getMembers: function(serverId) {
        const membersContainer = document.querySelector('.members-list');
        showLoading(membersContainer);  // Loading başlat

        fetch(`../includes/get_members.php?server_id=${serverId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Üye getirme hatası:', data.error);
                    hideLoading(membersContainer);  // Hata durumunda loading'i kaldır
                    return;
                }
                this.updateMembers(data);
                hideLoading(membersContainer);  // Başarılı durumda loading'i kaldır
            })
            .catch(error => {
                console.error('Üye yükleme hatası:', error);
                hideLoading(membersContainer);  // Hata durumunda loading'i kaldır
            });
    },

    // Üye listesini güncelle
    updateMembers: function(members) {
        const membersContainer = document.querySelector('.members-list');
        const memberCount = document.querySelector('.member-count');
        
        membersContainer.innerHTML = '';  // Container'ı temizle
        memberCount.textContent = members.length;

        // Üyeleri role göre grupla
        const roleGroups = {};
        members.forEach(member => {
            const role = member.role || 'Üye';
            if (!roleGroups[role]) {
                roleGroups[role] = [];
            }
            roleGroups[role].push(member);
        });

        // Her rol grubu için üyeleri listele
        Object.entries(roleGroups).forEach(([role, roleMembers]) => {
            const roleHtml = `
                <div class="member-group">
                    <div class="member-role">${role.toUpperCase()} - ${roleMembers.length}</div>
                    ${roleMembers.map(member => `
                        <div class="member-item">
                            <div class="member-avatar">
                                ${member.avatar ? 
                                    `<img src="../uploads/avatars/${member.avatar}" alt="${member.username}">` :
                                    `<div class="default-avatar">${member.username.charAt(0).toUpperCase()}</div>`
                                }
                            </div>
                            <div class="member-name">${member.username}</div>
                        </div>
                    `).join('')}
                </div>
            `;
            membersContainer.insertAdjacentHTML('beforeend', roleHtml);
        });
    }
};