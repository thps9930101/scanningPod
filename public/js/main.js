// js/main.js

// 確保 ApiService 類別已被定義
const apiService = new ApiService();

// 當前使用者資訊
let currentUser = null;

// 當前選擇的訂單 ID
let currentOrderId = null;

// 當前的排序順序，默認為 'desc'（降冪）
let currentSortOrder = 'desc';

let addimg = false

function swal (title, message, icon) {
    Swal.fire({
        icon: icon,
        title: title,
        text: message,
    });
}

/**
 * 登入功能
 */
function signIn() {
    const usernameInput = document.querySelector('#loginModal input[type="text"]');
    const passwordInput = document.querySelector('#loginModal input[type="password"]');
    const username = usernameInput.value;
    const password = passwordInput.value;

    loader_show(); // 顯示載入動畫

    apiService.login(username, password)
        .then(userData => {
            currentUser = userData;
            loader_hide(); // 隱藏載入動畫
            $('#loginModal').addClass('hidden');
            $('#mainContent').removeClass('hidden');
            // 顯示使用者名稱
            document.getElementById('user_account').textContent = currentUser.username;

            fetchAndRenderOrders(); // 獲取訂單資料
        })
        .catch(error => {
            loader_hide(); // 隱藏載入動畫
            swal("錯誤", error.message, "error");
        });
}

/**
 * 登出功能
 */
function logout() {
    // 清除使用者資訊
    currentUser = null;

    // 隱藏主內容並顯示登入模態視窗
    $('#mainContent').addClass('hidden');
    $('#loginModal').removeClass('hidden');

    hideSetting();

    // 清空使用者名稱
    document.getElementById('user_account').textContent = '';
}

/**
 * 變更密碼功能
 */
function changePassword() {
    const newPassword = prompt("請輸入新密碼:");
    if (newPassword && newPassword.trim() !== "") {
        loader_show(); // 顯示載入動畫
        apiService.changePassword(currentUser.username, newPassword.trim())
            .then(response => {
                loader_hide(); // 隱藏載入動畫
                swal("成功", response.message, "success");
            })
            .catch(error => {
                loader_hide(); // 隱藏載入動畫
                swal("錯誤", error.message, "error");
            });

        hideSetting()
    }
}

/**
 * 更改排序順序
 */
function changeSortOrder() {
    const sortSelect = document.getElementById('sortOrder');
    console.log(currentSortOrder)
    if (currentSortOrder == 'desc') {
        currentSortOrder = 'asc'
        sortSelect.innerHTML = "建立時間 ▲";
    }
    else {
        currentSortOrder = 'desc'
        sortSelect.innerHTML = "建立時間 ▼";
    }
    // currentSortOrder = sortSelect.value; // 更新當前排序順序

    fetchAndRenderOrders(); // 重新獲取並渲染訂單
}

/**
 * 顯示注意事項模態視窗
 * @param {string} orderId - 訂單 ID
 */
function showNotesModal(orderId) {
    currentOrderId = orderId;
    notesModal.classList.remove('hidden');
    renderNotes();
}

/**
 * 隱藏注意事項模態視窗
 */
function hideNotesModal() {
    notesModal.classList.add('hidden');
    currentOrderId = null;
}

function showSetting() {
    const dropdown = document.getElementById('settingsDropdown');
    dropdown.classList.remove('hidden');
    back.classList.remove('hidden');
}

function hideSetting() {
    const dropdown = document.getElementById('settingsDropdown');
    dropdown.classList.add('hidden');
    back.classList.add('hidden');
}

function toggleSetting() {
    const dropdown = document.getElementById('settingsDropdown');
    back.classList.toggle('hidden');
    dropdown.classList.toggle('hidden');
}

/**
 * 新增注意事項
 */
function addNewNote() {
    const newNote = prompt("請輸入新的注意事項:");
    if (newNote && newNote.trim() !== "") {
        loader_show(); // 顯示載入動畫
        apiService.addNote(currentUser.username, currentOrderId, newNote.trim())
            .then(() => {
                renderNotes();
                loader_hide(); // 隱藏載入動畫
                swal("成功", "注意事項已新增！", "success");
            })
            .catch(error => {
                loader_hide(); // 隱藏載入動畫
                swal("錯誤", error.message, "error");
            });
    }
}

/**
 * 渲染注意事項列表
 */
function renderNotes() {
    if (!currentOrderId) return;
    notesList.innerHTML = '';
    loader_show(); // 顯示載入動畫
    apiService.getNotes(currentUser.username, currentOrderId)
        .then(notes => {
            notesList.innerHTML = '';
            if (notes.length === 0) {
                notesList.innerHTML = '<p class="text-gray-500">暫無注意事項。</p>';
            } else {
                notes.forEach((note, index) => {
                    const noteItem = document.createElement('div');
                    noteItem.className = 'flex justify-between items-center p-2 mb-2 bg-gray-100 rounded';

                    const noteText = document.createElement('span');
                    noteText.textContent = note.remark;
                    noteText.className = 'flex-1';

                    const deleteButton = document.createElement('button');
                    deleteButton.innerHTML = '<i class="fa-solid fa-trash"></i>';
                    deleteButton.className = 'text-red-500 hover:text-red-700 ml-2';
                    deleteButton.onclick = () => deleteNote(note.id);

                    noteItem.appendChild(noteText);
                    noteItem.appendChild(deleteButton);

                    notesList.appendChild(noteItem);
                });
            }
            loader_hide(); // 隱藏載入動畫
        })
        .catch(error => {
            loader_hide(); // 隱藏載入動畫
            notesList.innerHTML = '<p class="text-red-500">無法獲取注意事項，請稍後再試。</p>';
            console.error('獲取注意事項失敗:', error);
        });
}

/**
 * 刪除注意事項
 * @param {number} noteIndex - 注意事項索引
 */
function deleteNote(noteIndex) {
    if (confirm("您確定要刪除這個注意事項嗎？")) {
        loader_show(); // 顯示載入動畫
        apiService.deleteNote(currentUser.username, currentOrderId, noteIndex)
            .then(() => {
                renderNotes();
                loader_hide(); // 隱藏載入動畫
                swal("成功", "注意事項已刪除！", "success");
            })
            .catch(error => {
                loader_hide(); // 隱藏載入動畫
                swal("錯誤", error.message, "error");
            });
    }
}

/**
 * 顯示上傳模態視窗
 * @param {string} orderId - 訂單 ID
 */
function showUploadModal(orderId) {
    currentOrderId = orderId;
    uploadModal.classList.remove('hidden');
}

/**
 * 隱藏上傳模態視窗
 */
function hideUploadModal() {
    uploadModal.classList.add('hidden');
    currentOrderId = null;
    uploadForm.reset();
}

/**
 * 處理上傳表單提交
 */
uploadForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const modelInput = document.getElementById('modelInput');
    const model = modelInput.files[0];

    const textureInput = document.getElementById('textureInput');
    const texture = textureInput.files[0];

    if (model || texture) {
        loader_show(); // 顯示載入動畫
        apiService.uploadFile(currentUser.username, currentOrderId, model, texture)
            .then(response => {
                loader_hide(); // 隱藏載入動畫
                swal("成功", response.message, "success");
                hideUploadModal();
            })
            .catch(error => {
                loader_hide(); // 隱藏載入動畫
                swal("錯誤", error.message, "error");
            });
    } else {
        swal("錯誤", "請選擇一個文件進行上傳。", "error");
    }
});

/**
 * 獲取訂單資料並渲染
 */
function fetchAndRenderOrders() {
    loader_show(); // 顯示載入動畫
    console.log(currentUser)
    apiService.getOrders(currentUser.username)
        .then(orders => {

            // 根據當前排序順序進行排序
            if (currentSortOrder === 'asc') {
                orders.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
            } else {
                orders.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
            }

            renderOrders(orders);

            searchHistory_by_text($("#searchInput").val());

            loader_hide(); // 隱藏載入動畫
        })
        .catch(error => {
            loader_hide(); // 隱藏載入動畫
            swal("錯誤", "無法獲取訂單資料，請稍後再試。", "error");
            console.error('獲取訂單資料失敗:', error);
        });
}

/**
 * 根據訂單資料生成訂單項目
 * @param {Array} orders - 訂單資料陣列
 */

function renderOrders(orders) {
    const historyList = document.getElementById('history-list');
    historyList.innerHTML = ''; // 清空現有內容

    const ordersContainer = document.createElement('div');
    ordersContainer.className = 'flex flex-col space-y-4';

    orders.forEach((order, index) => {
        // 創建訂單項目元素
        const orderItem = document.createElement('div');
        orderItem.className = 'bg-white shadow-sm border border-gray-200 rounded-lg h-28 p-2 flex justify-between items-center';

        // 訂單資訊
        const orderInfo = document.createElement('div');
        orderInfo.className = 'flex flex-col justify-between h-full';

        const orderID = document.createElement('p');
        orderID.className = 'text-lg font-medium text-gray-800';
        orderID.textContent = `Order ID: ${order.id}`;

        const orderTime = document.createElement('p');
        orderTime.className = 'text-sm text-gray-500';
        orderTime.textContent = `建立時間: ${order.createdAt}`;

        orderInfo.appendChild(orderID);
        orderInfo.appendChild(orderTime);

        // 操作按鈕
        const actionButtons = document.createElement('div');
        actionButtons.className = 'flex space-x-5'; // 調整間距以適應更多按鈕

        // 注意事項按鈕
        const notesButton = document.createElement('button');
        notesButton.className = 'text-blue-500 hover:text-blue-700';
        notesButton.innerHTML = '<i class="fa-regular fa-clipboard text-4xl mr-4"></i>';
        notesButton.onclick = () => showNotesModal(order.id);
        notesButton.title = "查看注意事項"; // 添加 title 屬性

        // 下載模型圖片素材按鈕
        const downloadImageButton = document.createElement('button');
        downloadImageButton.className = 'text-green-500 hover:text-green-700';
        downloadImageButton.innerHTML = '<i class="fa-solid fa-download text-4xl mr-4"></i>';
        downloadImageButton.onclick = () => downloadFile(order.id); // 假設下載文件名
        downloadImageButton.title = "下載模型圖片素材"; // 添加 title 屬性

        // 上傳模型按鈕
        const uploadButton = document.createElement('button');
        uploadButton.className = 'text-blue-500 hover:text-blue-700';
        uploadButton.innerHTML = '<i class="fa-solid fa-cloud-arrow-up text-4xl mr-4"></i>';
        uploadButton.onclick = () => showUploadModal(order.id);
        uploadButton.title = "上傳模型"; // 添加 title 屬性

        // 下載模型檔案按鈕（新增）
        const downloadModelButton = document.createElement('button');
        downloadModelButton.className = 'text-purple-500 hover:text-purple-700'; // 使用不同顏色區分
        downloadModelButton.innerHTML = '<i class="fa-solid fa-file-download text-4xl mr-4"></i>'; // 使用適合的圖示
        downloadModelButton.onclick = () => downloadModelFile(order.id); // 新增的處理函數
        downloadModelButton.title = "下載模型檔案"; // 添加 title 屬性

        // 將所有按鈕添加到操作按鈕區
        actionButtons.appendChild(notesButton);
        actionButtons.appendChild(downloadImageButton);
        actionButtons.appendChild(uploadButton);
        actionButtons.appendChild(downloadModelButton); // 添加新的按鈕

        const info = document.createElement('div');
        info.className = 'flex h-full'

        if (addimg) {

            const img = document.createElement('img')

            img.className = 'h-full mr-3'
            img.src = 'https://4dbox.s3.ap-northeast-1.amazonaws.com/production/774/25000/27132/cover/3DCover.png?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&amp;X-Amz-Algorithm=AWS4-HMAC-SHA256&amp;X-Amz-Credential=AKIA467DQOLH6YLKASND%2F20241127%2Fap-northeast-1%2Fs3%2Faws4_request&amp;X-Amz-Date=20241127T111352Z&amp;X-Amz-SignedHeaders=host&amp;X-Amz-Expires=3600&amp;X-Amz-Signature=681c095eb078ad78739342e5da6f90085fe33a9855dd97bfcaaee2962d315220'
            
            info.appendChild(img);

        }

        info.appendChild(orderInfo);


        // 組合訂單項目
        orderItem.appendChild(info);
        orderItem.appendChild(actionButtons);

        // 添加到容器中
        ordersContainer.appendChild(orderItem);
    });

    historyList.appendChild(ordersContainer);
}

/**
 * 下載文件
 * @param {string} orderId
 * @param {string} fileName
 */
function downloadFile(orderId) {
    loader_show(); // 顯示載入動畫
    const fileName = `order_${orderId}_model_pics.zip`; // 假設模型檔案為 zip 格式

    apiService.downloadFile(orderId, currentUser.username)
        .then(blob => {
            loader_hide(); // 隱藏載入動畫
            // 創建一個URL並觸發下載
            console.log(blob.picture)
            const url = blob.picture;
            const a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            loader_hide(); // 隱藏載入動畫
            swal("錯誤", "無法下載文件，請稍後再試。", "error");
            console.error('下載文件失敗:', error);
        });
}

/**
 * 下載模型檔案
 * @param {string} orderId - 訂單 ID
 */
async function downloadModelFile(orderId) {
    loader_show(); // 顯示載入動畫
    const fileName = `order_${orderId}_model.zip`; // 假設模型檔案為 zip 格式
    console.log('orderId', orderId);

    try {
        const fileUrls = await apiService.downloadModelFile(orderId, fileName, currentUser.username);
        console.log(fileUrls);
    
        for (let i = 0; i < fileUrls.length; i++) {
            const url = fileUrls[i];
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`無法下載 ${url}`);
    
                const blob = await response.blob();
                const fileName = url.split('/').pop().split('?')[0]; // 取得檔案名並移除查詢參數
    
                // 確保只有二進位資料進行下載
                const downloadUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
    
                URL.revokeObjectURL(downloadUrl); // 釋放資源
            } catch (err) {
                console.error(`❌ 錯誤: ${url}`, err);
            }
        }
    
    } catch (error) {
        loader_hide(); // 隱藏載入動畫
        swal("錯誤", "無法下載模型檔案，請稍後再試。", "error");
        console.error('下載模型檔案失敗:', error);
    }
    
    loader_hide(); // 隱藏載入動畫

}

function searchHistory_by_text(text) {
    if (text == "" || text == undefined) return;
    const ordersContainer = document.querySelector('#history-list .flex.flex-col');
    if (!ordersContainer) return;
    
    text = text.toLowerCase();
    Array.from(ordersContainer.children).forEach(order => {
        const orderIDElement = order.querySelector('.text-lg');
        if (orderIDElement) {
            const orderID = orderIDElement.textContent.toLowerCase();
            if (orderID.includes(text)) {
                order.style.display = '';
            } else {
                order.style.display = 'none';
            }
        }
    });
}

/**
 * 搜尋歷史紀錄
 * @param {Event} event - 鍵盤事件
 */
function searchHistory(event) {
    const query = event.target.value.toLowerCase();
    const ordersContainer = document.querySelector('#history-list .flex.flex-col');
    if (!ordersContainer) return;

    Array.from(ordersContainer.children).forEach(order => {
        const orderIDElement = order.querySelector('.text-lg');
        if (orderIDElement) {
            const orderID = orderIDElement.textContent.toLowerCase();
            if (orderID.includes(query)) {
                order.style.display = '';
            } else {
                order.style.display = 'none';
            }
        }
    });
}

// Settings dropdown toggle
document.getElementById('settingsButton').addEventListener('click', function () {
    toggleSetting();
});

// 點擊模態背景關閉模態
notesModal.addEventListener('click', function (event) {
    if (event.target === notesModal) {
        hideNotesModal();
    }
});

uploadModal.addEventListener('click', function (event) {
    if (event.target === uploadModal) {
        hideUploadModal();
    }
});

// 頁面載入後顯示登入模態視窗（保持不變）
window.onload = function () {
    // 預設顯示登入模態視窗
    $('#loginModal').removeClass('hidden');
};

back.addEventListener('click', function (event) {
    if (event.target === back) hideSetting()
});

// 點擊模態背景關閉模態
notesModal.addEventListener('click', function (event) {
    if (event.target === notesModal) {
        hideNotesModal();
    }
});

uploadModal.addEventListener('click', function (event) {
    if (event.target === uploadModal) {
        hideUploadModal();
    }
});
