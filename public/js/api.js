// js/api.js

class ApiService {
    constructor() {
        // 初始化任何需要的屬性，例如基礎URL
        this.baseURL = 'http://192.168.0.106:8001/api/'; // 假設您的API位於此處
        this.debugMode = true;

        // 初始化 mockData 作為模擬的資料庫
        this.mockData = {
            users: {
                'testuser': {
                    password: 'password123',
                    notes: {
                        '123456': ['第一次注意事項', '第二次注意事項'],
                        '789012': ['另一筆注意事項'],
                        '345678': []
                        // 根據需要添加更多訂單的注意事項
                    }
                }
                // 可以根據需要添加更多用戶
            },
            orders: [
                {
                    id: '123456',
                    createdAt: '2024-04-27 10:30'
                },
                {
                    id: '789012',
                    createdAt: '2024-04-28 14:45'
                },
                {
                    id: '345678',
                    createdAt: '2024-05-01 09:15'
                },
                // {
                //     id: '333333',
                //     createdAt: '2024-05-01 09:16'
                // },
                // {
                //     id: '444444',
                //     createdAt: '2024-05-01 09:14'
                // },
                // {
                //     id: '555555',
                //     createdAt: '2024-05-01 09:15'
                // }
                // 可以根據需要添加更多訂單
            ]
        };
    }

    async post(apiName, params, config = null) {
        try {
            const api = this.baseURL + apiName
            const res = await axios.post(api, params, config)

            if (this.debugMode)
            {
                console.log(api, {
                    params: params,
                    response: res
                })
            }

            if (res.data[0])
                return JSON.parse(JSON.stringify(res.data[0].message));
            else
                return JSON.parse(JSON.stringify(res.data.message));
        }
        catch (error) {
            console.log(error)
            if (error.response) {
                if (error.response.status === 415) {
                    alert("無法取得資訊，請重新登入");
                    logout();
                    // location.href = "login.html";
                } else {
                    console.log("An error occurred:", error.message);
                }
            } else if (error.request) {
                console.log("No response received.");
            } else {
                console.log('Error', error.message);
            }

            loader_hide();
            return null;
        }
    }

    /**
     * 登入
     * @param {string} username
     * @param {string} password
     * @returns {Promise<Object>} 返回使用者資料或錯誤
     */
    login(username, password) {
        return new Promise(async (resolve, reject) => {
            const res = await this.post("staff_login", {
                account: username,
                password: password
            });
            if (res && res.account == username) {
                this.mockData.users[username] = {
                    password : password
                }
                console.log('this.mockData',this.mockData)
                resolve({ username: username});
            } else {
                reject(new Error('Invalid username or password'));
            }
        });

        // 真實應用中應使用axios進行HTTP請求
        /*
        return axios.post(`${this.baseURL}/login`, { username, password })
            .then(response => response.data)
            .catch(error => {
                console.error('Login failed:', error);
                throw error;
            });
        */
    }

    /**
     * 取得訂單
     * @returns {Promise<Array>} 返回訂單資料陣列
     */
    getOrders(account) {
        return new Promise(async (resolve) => {
            const res = await this.post("get_orderList", {
                account: account
            });
            console.log('res',res)
            this.mockData.orders = res;
            resolve(this.mockData.orders);
        });
    }

    /**
     * 上傳文件
     * @param {string} username - 使用者名稱
     * @param {string} orderId - 訂單 ID
     * @param {File} model - 要上傳的模型
     * @param {File} texture - 要上傳的素材
     * @returns {Promise<Object>} 返回上傳結果
     */
    uploadFile(username, orderId, model, texture) {
        return new Promise(async (resolve, reject) => {
            if (!model && !texture) {
                reject(new Error('No file provided'));
                return;
            }
            const texture_allowedExtensions = ['jpg', 'png'];
            const model_allowedExtensions = ['obj', 'fbx'];

            if (model) {
                const filename = model.name;
                const extension = filename.split('.').pop().toLowerCase();

                if (model_allowedExtensions.includes(extension)) {
                    console.log("文件格式正确");
                } else {
                    console.log("不支持的文件格式");
                }
            }

            if (texture) {
                const filename = texture.name;
                const extension = filename.split('.').pop().toLowerCase();

                if (texture_allowedExtensions.includes(extension)) {
                    console.log("文件格式正确");
                } else {
                    console.log("不支持的文件格式");
                }
            }
            var formData = new FormData();
            formData.append('order',orderId);
            formData.append('account', username);
            formData.append('model', model);
            formData.append('texture', texture);

            const res = await this.post("uploadModel", formData);
            console.log('res',res)

            setTimeout(() => {
                if (!res) {
                    reject(new Error('No file provided'));
                    return;
                }
                console.log('res',res)

                // 模擬文件上傳成功
                resolve({ message: `File "${file.name}" uploaded successfully.` });
            }, 1000); // 模擬延遲
        });

        // 真實應用中應使用axios進行HTTP請求
        /*
        const formData = new FormData();
        formData.append('file', file);

        return axios.post(`${this.baseURL}/orders/${orderId}/upload`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then(response => response.data)
            .catch(error => {
                console.error('File upload failed:', error);
                throw error;
            });
        */
    }

    /**
     * 下載文件
     * @param {string} orderId - 訂單 ID
     * @param {string} fileName - 文件名稱
     * @returns {Promise<Blob>} 返回文件Blob
     */
    downloadFile(orderId, account) {
        return new Promise((resolve, reject) => {
            setTimeout(async () => {
                // 模擬一個簡單的文本文件
                // const blob = new Blob([`這是文件 ${fileName} 的內容。`], { type: 'application/zip' });
                // resolve(blob);
                const res = await this.post("downloadPicture", {
                    model: orderId,
                    account: account
                });

                if (res) {
                    resolve(res);
                } else {
                    reject(new Error('User not found'));
                }
            }, 500); // 模擬延遲
        });

        // 真實應用中應使用axios進行HTTP請求
        /*
        return axios.get(`${this.baseURL}/orders/${orderId}/download/${fileName}`, {
            responseType: 'blob'
        })
            .then(response => response.data)
            .catch(error => {
                console.error('File download failed:', error);
                throw error;
            });
        */
    }

    /**
     * 下載文件
     * @param {string} orderId - 訂單 ID
     * @param {string} fileName - 文件名稱
     * @returns {Promise<Blob>} 返回文件Blob
     */
    downloadModelFile(orderId, fileName, account) {
        return new Promise(async (resolve, reject) => {
            const res = await this.post("downloadModel", {
                model: orderId,
                account: account
            });
            if (res) {
                console.log(res.files)
                resolve(res.files);
            } else {
                reject(new Error('User not found'));
            }
            
            // setTimeout(() => {
            //     // 模擬一個簡單的文本文件
            //     const blob = new Blob([`這是文件 ${fileName} 的內容。`], { type: 'application/zip' });
            //     resolve(blob);
            // }, 500); // 模擬延遲
        });

        // 真實應用中應使用axios進行HTTP請求
        /*
        return axios.get(`${this.baseURL}/orders/${orderId}/download/${fileName}`, {
            responseType: 'blob'
        })
            .then(response => response.data)
            .catch(error => {
                console.error('File download failed:', error);
                throw error;
            });
        */
    }

    /**
     * 變更密碼
     * @param {string} username - 使用者名稱
     * @param {string} newPassword - 新密碼
     * @returns {Promise<Object>} 返回變更結果
     */
    changePassword(username, newPassword) {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                const user = this.mockData.users[username];
                if (!user) {
                    reject(new Error('User not found'));
                    return;
                }

                if (newPassword.length < 6) {
                    reject(new Error('Password must be at least 6 characters long.'));
                    return;
                }

                // 更新密碼
                user.password = newPassword;
                resolve({ message: 'Password changed successfully.' });
            }, 300); // 模擬延遲
        });

        // 真實應用中應使用axios進行HTTP請求
        /*
        return axios.post(`${this.baseURL}/change-password`, { username, newPassword })
            .then(response => response.data)
            .catch(error => {
                console.error('Change password failed:', error);
                throw error;
            });
        */
    }

    /**
     * 取得注意事項
     * @param {string} username - 使用者名稱
     * @param {string} orderId - 訂單 ID
     * @returns {Promise<Array>} 返回注意事項陣列
     */
    getNotes(username, orderId) {
        return new Promise(async (resolve, reject) => {
            console.log(username,orderId);
            const res = await this.post("getModelRemark", {
                username: username,
                modelId: orderId
            });
    
            if (res) {
                resolve(res || []);
            } else {
                reject(new Error('User not found'));
            }
        });

        // 真實應用中應使用axios進行HTTP請求
        /*
        return axios.get(`${this.baseURL}/orders/${orderId}/notes`)
            .then(response => response.data)
            .catch(error => {
                console.error('Fetching notes failed:', error);
                throw error;
            });
        */
    }

    /**
     * 新增注意事項
     * @param {string} username - 使用者名稱
     * @param {string} orderId - 訂單 ID
     * @param {string} note - 注意事項內容
     * @returns {Promise<Object>} 返回新增結果
     */
    addNote(account, orderId, note) {
        return new Promise(async (resolve, reject) => {
            const res = await this.post("addRemark", {
                // token: localStorage.getItem("scanningPod_token"),
                account: currentUser.username,
                orderId: orderId,
                note: note
            });
    
            if (res) {
                resolve({ message: 'Note added successfully.' });
            } else {
                reject(new Error('User not found'));
            }
            
            // setTimeout(() => {
            //     const user = this.mockData.users[username];
            //     if (!user) {
            //         reject(new Error('User not found'));
            //         return;
            //     }

            //     if (!note || note.trim() === '') {
            //         reject(new Error('Note cannot be empty.'));
            //         return;
            //     }

            //     if (!user.notes[orderId]) {
            //         user.notes[orderId] = [];
            //     }

            //     user.notes[orderId].push(note.trim());
            //     resolve({ message: 'Note added successfully.' });
            // }, 300); // 模擬延遲
        });

        // 真實應用中應使用axios進行HTTP請求
        /*
        return axios.post(`${this.baseURL}/orders/${orderId}/notes`, { note })
            .then(response => response.data)
            .catch(error => {
                console.error('Add note failed:', error);
                throw error;
            });
        */
    }

    /**
     * 刪除注意事項
     * @param {string} username - 使用者名稱
     * @param {string} orderId - 訂單 ID
     * @param {number} noteIndex - 注意事項索引
     * @returns {Promise<Object>} 返回刪除結果
     */
    deleteNote(username, orderId, noteIndex) {        
        console.log(username)
        return new Promise(async (resolve, reject) => {
            const res = await this.post("removeRemark", {
                account: username,
                noteId: noteIndex
            });
    
            if (res) {
                resolve({ message: 'Note deleted successfully.' });
            } else {
                reject(new Error('Note not found'));
            }

            // setTimeout(() => {
            //     const user = this.mockData.users[username];
            //     if (!user) {
            //         reject(new Error('User not found'));
            //         return;
            //     }

            //     const notes = user.notes[orderId];
            //     if (!notes || noteIndex < 0 || noteIndex >= notes.length) {
            //         reject(new Error('Note not found'));
            //         return;
            //     }

            //     // 刪除指定索引的注意事項
            //     notes.splice(noteIndex, 1);
            //     resolve({ message: 'Note deleted successfully.' });
            // }, 300); // 模擬延遲
        });

        // 真實應用中應使用axios進行HTTP請求
        /*
        return axios.delete(`${this.baseURL}/orders/${orderId}/notes/${noteIndex}`)
            .then(response => response.data)
            .catch(error => {
                console.error('Delete note failed:', error);
                throw error;
            });
        */
    }
}
