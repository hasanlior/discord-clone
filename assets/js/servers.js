function loadServers() {
    $.ajax({
        url: '../includes/get_servers.php',
        type: 'GET',
        success: function(response) {
            const servers = JSON.parse(response);
            const serversList = document.querySelector('.servers-list');
            
            servers.forEach(server => {
                const serverIcon = document.createElement('div');
                serverIcon.className = 'server-icon';
                serverIcon.innerHTML = server.icon ? 
                    `<img src="../assets/images/servers/${server.icon}" alt="${server.name}">` :
                    server.name.charAt(0).toUpperCase();
                
                serverIcon.addEventListener('click', () => loadChannels(server.id));
                serversList.appendChild(serverIcon);
            });
        }
    });
}

function loadChannels(serverId) {
    $.ajax({
        url: '../includes/get_channels.php',
        type: 'GET',
        data: { server_id: serverId },
        success: function(response) {
            const channels = JSON.parse(response);
            const channelsList = document.querySelector('.channels-list');
            channelsList.innerHTML = '';
            
            channels.forEach(channel => {
                const channelItem = document.createElement('div');
                channelItem.className = 'channel-item';
                channelItem.innerHTML = `
                    <i class="fas fa-hashtag"></i>
                    <span>${channel.name}</span>
                `;
                channelItem.addEventListener('click', () => switchChannel(channel.id));
                channelsList.appendChild(channelItem);
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', loadServers); 