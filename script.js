// 切換標題顯示/隱藏
function toggleTitle() {
    const isChecked = document.getElementById("toggleButton").textContent === "隱藏標題";
    const comicTitles = document.getElementsByClassName("comic-title");

    // 切換標題顯示/隱藏
    for (var i = 0; i < comicTitles.length; i++) {
        comicTitles[i].style.display = isChecked ? "none" : "block";
    }

    // 切換按鈕文字
    document.getElementById("toggleButton").textContent = isChecked ? "顯示標題" : "隱藏標題";

    // 更新 Cookie 狀態
    setButtonState('toggleButton', isChecked ? 'hidden' : 'visible');
}

// 根據 cookie 設置標題顯示/隱藏
function initializeTitleState() {
    const buttonState = getButtonState('toggleButton');
    
    // 如果 cookie 設置為 hidden，則隱藏標題
    if (buttonState === 'hidden') {
        toggleTitle(); // 隱藏標題
    } else {
        // 設置初始按鈕為「顯示標題」
        document.getElementById("toggleButton").textContent = "顯示標題";
    }
}

// 切換導航欄顯示/隱藏
function toggleNavbar() {
    const navbar = document.querySelector('.navbar');
    const toggleNavbarBtn = document.getElementById('toggleNavbarBtn');

    // 切換 navbar 的 display 狀態
    if (navbar.style.display === 'none' || navbar.style.display === '') {
        navbar.style.display = 'block';  // 顯示
        toggleNavbarBtn.textContent = '隱藏';
        toggleNavbarBtn.style.color = 'blue';  // 設定顯示狀態的按鈕顏色
    } else {
        navbar.style.display = 'none';  // 隱藏
        toggleNavbarBtn.textContent = '顯示';
        toggleNavbarBtn.style.color = 'rgba(236, 10, 10, 0)';  // 設定隱藏狀態的按鈕顏色
    }

    // 更新 Cookie 狀態
    const navbarState = navbar.style.display === 'none' ? 'hidden' : 'visible';
    setButtonState('toggleNavbarBtn', navbarState);
}

// 設置 Cookie 的狀態
function setButtonState(buttonId, state) {
    document.cookie = `${buttonId}=${state}; path=/; max-age=31536000; SameSite=Lax`; // 設置 cookie，有效期為一年
}

// 讀取 Cookie 中的狀態
function getButtonState(buttonId) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${buttonId}=`); // 查找 cookie
    if (parts.length === 2) return parts.pop().split(';').shift(); // 返回 cookie 的值
    return null;
}

// 頁面加載後，根據 Cookie 設置狀態
window.onload = function() {
    // 預設狀態設置
    const navbarState = getButtonState('toggleNavbarBtn');
    
    // 初始化標題的顯示狀態
    initializeTitleState();

    // 根據 Cookie 設置導航欄顯示/隱藏
    if (navbarState === 'hidden') {
        document.querySelector('.navbar').classList.add('hidden');
    }
};

// 表單提交處理：發送訊息

// 加載聊天訊息

