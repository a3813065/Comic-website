var currentPage = 1; // 當前頁碼，用於加載舊訊息
var isLoading = false; // 防止多次加載
var lastMessageTimestamp = 0; // 追踪最新顯示的消息時間戳

// 每秒檢測最新消息
function loadLatestMessages() {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "load_messages.php?page=1", true); // 固定為最新消息頁面（1）
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            var messages = response.messages;
            var chatWindow = document.getElementById("chatWindow");

            // 如果有新消息，檢查是否為新的
            if (messages.length > 0) {
                messages.forEach(function(msg) {
                    var messageTimestamp = new Date(msg.timestamp).getTime();

                    // 比對時間戳，避免顯示重複的消息
                    if (messageTimestamp > lastMessageTimestamp) {
                        var messageDiv = document.createElement("div");
                        messageDiv.classList.add("message");

                        // 格式化時間顯示
                        var time = new Date(msg.timestamp);
                        var hours = time.getHours().toString().padStart(2, '0');
                        var minutes = time.getMinutes().toString().padStart(2, '0');
                        var seconds = time.getSeconds().toString().padStart(2, '0');
                        var formattedTime = hours + ":" + minutes + ":" + seconds;

                        messageDiv.innerHTML = "<strong>" + msg.username + ":</strong> " +
                                               msg.message + " <small>(" + formattedTime + ")</small>";
                        chatWindow.appendChild(messageDiv);
                        
                        // 更新最新消息的時間戳
                        lastMessageTimestamp = messageTimestamp;
                    }
                });

                // 滾動到最底部
                
            }
        }
    };
    xhr.send();
}

// 加載聊天訊息，這裡使用當前頁碼來加載舊訊息
function loadMessages() {
    if (isLoading) return; // 防止重複請求
    isLoading = true;

    var xhr = new XMLHttpRequest();
    xhr.open("GET", "load_messages.php?page=" + currentPage, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            var messages = response.messages;

            // 如果訊息為空，保持當前頁碼不變
            if (messages.length === 0) {
                isLoading = false;
                return;
            }

            // 清空聊天區域並將新的訊息添加到聊天框的頂部
            var chatWindow = document.getElementById("chatWindow");

            // 逐一顯示訊息並將它們添加到頂部
            messages.forEach(function(msg) {
                var messageDiv = document.createElement("div");
                messageDiv.classList.add("message");


                // 格式化時間顯示，只顯示時間部分（不顯示日期）
                var time = new Date(msg.timestamp);
                var hours = time.getHours().toString().padStart(2, '0');
                var minutes = time.getMinutes().toString().padStart(2, '0');
                var seconds = time.getSeconds().toString().padStart(2, '0');
                var formattedTime = hours + ":" + minutes + ":" + seconds;

                messageDiv.innerHTML = "<strong>" + msg.username + ":</strong> " +
                                       msg.message + " <small>(" + formattedTime + ")</small>";
                chatWindow.insertBefore(messageDiv, chatWindow.firstChild); // 插入訊息到頂部
            });

            // 滾動條回到最下方，讓新加載的訊息顯示在底部
            chatWindow.scrollTop = chatWindow.scrollHeight;

            // 增加頁碼
            currentPage++;

            isLoading = false;
        }
    };
    xhr.send();
}

// 檢查用戶是否滾動到頂部來加載更多訊息
function checkScroll() {
    var chatWindow = document.getElementById("chatWindow");
    if (chatWindow.scrollTop === 0 && !isLoading) {
        loadMessages(); // 加載更多舊訊息
    }
}

// 監聽滾動事件
document.getElementById("chatWindow").addEventListener("scroll", checkScroll);

// 頁面加載後立即加載訊息
window.onload = function() {
    loadMessages();
};

// 設置定時器每秒加載最新消息
setInterval(loadLatestMessages, 5000); // 每秒加載最新消息

document.getElementById("chatForm").addEventListener("submit", function(event) {
    event.preventDefault(); // 防止表單刷新頁面

    var message = document.getElementById("message").value;

    // 檢查訊息是否為空
    if (message.trim() !== "") {
        // 使用 AJAX 發送訊息到 send_message.php
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "send_message.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                // 訊息發送成功後清空輸入框並重新加載訊息
                document.getElementById("message").value = "";
                loadLatestMessages(); // 重新加載聊天訊息
            }
        };
        // 傳遞訊息時，保持其原始格式（不進行 encodeURIComponent）
        xhr.send("message=" + message); // 直接傳遞訊息
    }
});
