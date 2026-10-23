const __ = ( window.wp && window.wp.i18n && window.wp.i18n.__ ) ? window.wp.i18n.__ : ( text ) => text;
/**
 * Skaaa Frontend Engine
 * Bộ não Alpine.js Controller cho Skaaa Form Builder.
 *
 * Tính năng:
 * 1. Data Binding (x-model tự inject từ render.php)
 * 2. Validation (blur-based, required check)
 * 3. Loading State (isSubmitting)
 * 4. Success/Error Handling (status, message)
 * 5. Honeypot (bot_trap field)
 * 6. Draft Save (Alpine $persist plugin)
 * 7. Reset Form (auto-reset sau submit thành công)
 * 8. Multi-step (step variable)
 * 9. Conditional Logic (dùng x-show trực tiếp trên block)
 * 10. Auto-Format (dùng x-mask plugin trên attribute)
 */
/**
 * Đăng ký skaaaForm Controller vào Alpine.
 * Hỗ trợ 2 kịch bản:
 * - Alpine đã load rồi (script defer) → Gọi trực tiếp Alpine.data()
 * - Alpine chưa load (edge-case) → Lắng nghe alpine:init
 */
function _registerSkaaaForm() {

    /**
     * skaaaForm Controller
     *
     * @param {string} actionId - ID Workflow trong Skaaa Logic Engine
     * @param {Object} options  - Tùy chọn { persist: false }
     */
    Alpine.data('skaaaForm', (actionId = 'default', options = {}) => ({

        // === TRẠNG THÁI (State) ===
        fields: {},
        errors: {},
        isSubmitting: false,
        status: '', // '', 'success', 'error'
        message: '',
        step: 1,
        bot_trap: '', // Honeypot

        // === GETTERS DÀNH CHO UI ===
        get success() {
            return this.status === 'success';
        },
        get errorMessage() {
            return this.status === 'error' ? this.message : '';
        },

        init() {
            // Quét tất cả input bên trong form này, khởi tạo fields nếu chưa có
            const inputs = this.$el.querySelectorAll('input[name], select[name], textarea[name]');
            inputs.forEach((input) => {
                let name = input.getAttribute('name');
                if (!name) return;

                let isArray = false;
                if (name.endsWith('[]')) {
                    name = name.slice(0, -2);
                    isArray = true;
                }

                if (typeof this.fields[name] === 'undefined') {
                    // NẾU CÓ currentData TRONG PORTAL CONTEXT, HÃY ƯU TIÊN LẤY TỪ currentData!
                    const portalStore = Alpine.store('skaaaPortal');
                    if (portalStore && portalStore.currentData && typeof portalStore.currentData[name] !== 'undefined') {
                        let val = portalStore.currentData[name];
                        
                        // Nếu val là chuỗi và trông giống JSON array/object, thử parse nó trước
                        if (typeof val === 'string' && (val.startsWith('[') || val.startsWith('{'))) {
                            try {
                                const parsed = JSON.parse(val);
                                if (parsed !== null) {
                                    val = parsed;
                                }
                            } catch (e) {
                                // Bỏ qua nếu parse lỗi
                            }
                        }

                        if (Array.isArray(val)) {
                            if (val.length > 0 && typeof val[0] === 'object' && val[0] !== null && typeof val[0].id !== 'undefined') {
                                let ids = val.map(item => item.id.toString());
                                this.fields[name] = isArray ? ids : (ids[0] || '');
                            } else {
                                let ids = val.map(item => (typeof item === 'object' && item !== null && typeof item.id !== 'undefined') ? item.id.toString() : item.toString());
                                this.fields[name] = isArray ? ids : (ids[0] || '');
                            }
                        } else if (typeof val === 'object' && val !== null && typeof val.id !== 'undefined') {
                            this.fields[name] = isArray ? [val.id.toString()] : val.id.toString();
                        } else {
                            this.fields[name] = val;
                        }
                    } else {
                        // Khởi tạo giá trị mặc định dựa theo loại input
                        if (isArray) {
                            this.fields[name] = [];
                        } else if (input.type === 'checkbox') {
                            this.fields[name] = input.checked || false;
                        } else {
                            this.fields[name] = input.value || '';
                        }
                    }
                }

                // Nếu là mảng và input này được check/select mặc định, push vào mảng
                if (isArray) {
                    if ((input.type === 'checkbox' || input.type === 'radio') && input.checked) {
                        if (!this.fields[name].includes(input.value)) {
                            this.fields[name].push(input.value);
                        }
                    } else if (input.type === 'select-multiple') {
                        Array.from(input.selectedOptions).forEach(opt => {
                            if (!this.fields[name].includes(opt.value)) {
                                this.fields[name].push(opt.value);
                            }
                        });
                    }
                }
            });
        },

        // === VALIDATION ===
        validate(fieldName) {
            const input = this.$el.querySelector(`[name="${fieldName}"]`);
            if (!input) return true;

            // Reset lỗi cũ
            delete this.errors[fieldName];

            // Kiểm tra Required
            if (input.hasAttribute('required') && !this.fields[fieldName]?.toString().trim()) {
                this.errors[fieldName] = __( 'This field is required.', 'skaaa-no-code-design' );
                return false;
            }

            // Kiểm tra Email format
            if (input.type === 'email' && this.fields[fieldName]) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.fields[fieldName])) {
                    this.errors[fieldName] = __( 'Invalid email.', 'skaaa-no-code-design' );
                    return false;
                }
            }

            // Kiểm tra Number min/max
            if (input.type === 'number' && this.fields[fieldName]) {
                const val = parseFloat(this.fields[fieldName]);
                if (input.min && val < parseFloat(input.min)) {
                    this.errors[fieldName] = `Giá trị tối thiểu là ${input.min}.`;
                    return false;
                }
                if (input.max && val > parseFloat(input.max)) {
                    this.errors[fieldName] = `Giá trị tối đa là ${input.max}.`;
                    return false;
                }
            }

            return true;
        },

        // Kiểm tra toàn bộ form trước khi submit
        validateAll() {
            let isValid = true;
            const inputs = this.$el.querySelectorAll('input[required], select[required], textarea[required]');
            inputs.forEach((input) => {
                const name = input.getAttribute('name');
                if (name && !this.validate(name)) {
                    isValid = false;
                }
            });
            return isValid;
        },

        // === GỬI FORM (Submit) ===
        async submitForm() {
            // 1. Chặn Bot (Honeypot check)
            if (this.bot_trap) {
                console.warn('[SkaaaForm] Bot detected via honeypot. Aborting.');
                return;
            }

            // 2. Validate toàn bộ
            if (!this.validateAll()) {
                this.status = 'error';
                this.message = __( 'Please review required fields.', 'skaaa-no-code-design' );
                return;
            }

            // 3. Bật Loading State
            this.isSubmitting = true;
            this.status = '';
            this.message = '';

            // 4. Phát sự kiện (cho Alpine/UI bắt nếu cần)
            this.$el.dispatchEvent(new CustomEvent('skaaa-form-submitting', { bubbles: true }));

            try {
                // 5. Gửi Fetch POST tới Skaaa Logic Engine
                const payload = {
                    ...this.fields,
                    skaaa_form_id: actionId,
                };

                const restUrl = (window.skaaaAppEnv && window.skaaaAppEnv.restUrl) ? window.skaaaAppEnv.restUrl : '/wp-json/skaaa-logic/v1/submit';
                const response = await fetch(restUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.skaaaAppEnv ? window.skaaaAppEnv.nonce : ''
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (data.success) {
                    this.status = 'success';
                    this.message = data.message || __( 'Submitted successfully!', 'skaaa-no-code-design' );

                    // KÍCH HOẠT EVENT BUS - HỆ TUẦN HOÀN CHO PHÉP BACKEND RA LỆNH NGƯỢC LẠI FRONTEND
                    if (data.data && data.data._skaaa_events && window.$skaaa && window.$skaaa.processEventBus) {
                        window.$skaaa.processEventBus(data.data._skaaa_events, this.$el);
                    }

                    // Reset Form (chỉ reset nếu không phải là cập nhật - update)
                    const portalStore = Alpine.store('skaaaPortal');
                    const isPortalUpdate = portalStore && portalStore.currentData && !Array.isArray(portalStore.currentData) && portalStore.currentData.id;
                    const isUpdate = (typeof actionId === 'string' && actionId.startsWith('update_')) || 
                                     (this.fields && this.fields.id) || 
                                     isPortalUpdate;
                    if (!isUpdate) {
                        Object.keys(this.fields).forEach((key) => {
                            this.fields[key] = Array.isArray(this.fields[key]) ? [] : '';
                        });
                        this.step = 1;
                    }
                    this.errors = {};


                    // Phát sự kiện thành công
                    this.$el.dispatchEvent(new CustomEvent('skaaa-form-success', {
                        bubbles: true,
                        detail: data,
                    }));
                } else {
                    this.status = 'error';
                    this.message = data.message || __( 'An error occurred. ', 'skaaa-no-code-design' );

                    // Phát sự kiện lỗi
                    this.$el.dispatchEvent(new CustomEvent('skaaa-form-error', {
                        bubbles: true,
                        detail: data,
                    }));
                }
            } catch (err) {
                this.status = 'error';
                this.message = __( 'Network connection error. ', 'skaaa-no-code-design' );
                console.error('[SkaaaForm] Network error:', err);

                this.$el.dispatchEvent(new CustomEvent('skaaa-form-error', {
                    bubbles: true,
                    detail: { error: err.message },
                }));
            } finally {
                // 6. Tắt Loading
                this.isSubmitting = false;
            }
        },

        // === MULTI-STEP ===
        nextStep() {
            this.step++;
        },
        prevStep() {
            if (this.step > 1) this.step--;
        },

        resetForm() {
            Object.keys(this.fields).forEach((key) => {
                this.fields[key] = Array.isArray(this.fields[key]) ? [] : '';
            });
            this.errors = {};
            this.status = '';
            this.message = '';
            this.step = 1;
        },
    }));
}

/**
 * Đăng ký skaaaTheme Store vào Alpine để quản lý Dark Mode.
 */
function _registerSkaaaTheme() {
    if (!window.Alpine) return;
    
    // Sử dụng $persist nếu có, nếu không fallback đọc từ localStorage
    let isDarkInit = false;
    if (typeof Alpine.$persist !== 'undefined') {
        isDarkInit = Alpine.$persist(false).as('skaaa_dark_mode');
    } else {
        try {
            const stored = localStorage.getItem('skaaa_dark_mode');
            if (stored !== null) {
                isDarkInit = JSON.parse(stored);
            }
        } catch(e) {}
    }

    Alpine.store('skaaaTheme', {
        isDark: isDarkInit,
        init() {
            this.applyTheme();
            
            // Theo dõi thay đổi từ storage ở tab khác nếu cần
            window.addEventListener('storage', (e) => {
                if (e.key === 'skaaa_dark_mode') {
                    try {
                        this.isDark = JSON.parse(e.newValue);
                        this.applyTheme();
                    } catch (err) {}
                }
            });
        },
        toggle() {
            this.isDark = !this.isDark;
            this.applyTheme();
            
            // Fallback save nếu $persist không tồn tại
            if (typeof Alpine.$persist === 'undefined') {
                localStorage.setItem('skaaa_dark_mode', JSON.stringify(this.isDark));
            }
        },
        applyTheme() {
            if (this.isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    });
}

/**
 * // FIX: Đăng ký nav Store vào Alpine để quản lý điều hướng Mobile.
 */
function _registerSkaaaNav() {
    if (!window.Alpine) return;

    Alpine.store('nav', {
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        }
    });
}

/**
 * Đăng ký skaaaPortal Store vào Alpine để quản lý Dữ liệu trang hiện tại (Current Page Data).
 */
function _registerSkaaaPortal() {
    if (!window.Alpine) return;

    Alpine.store('skaaaPortal', {
        config: {},
        columns: {},
        currentData: null, // Dữ liệu của trang hiện tại (Mảng List hoặc Object Detail)
        isLoading: false,
        
        init() {
            if (window.SkaaaPortalContext) {
                this.config = window.SkaaaPortalContext.portal || {};
                this.columns = window.SkaaaPortalContext.columns || {};
                this.currentData = window.SkaaaPortalContext.currentData || null;
            }
        }
    });
}



/**
 * Đăng ký skaaaScratchpad Controller vào Alpine để quản lý Rich Text & Gutenberg Iframe
 */
function _registerSkaaaScratchpad() {
    Alpine.data('skaaaScratchpad', (fieldName) => ({
        isOpen: false,
        isLoading: false,
        iframeUrl: '',
        postId: null,

        getBuilderRestUrl() {
            let restUrl = '/wp-json/skaaaaa-builder/v1';
            if (window.skaaaAppEnv && window.skaaaAppEnv.restUrl) {
                const rawUrl = window.skaaaAppEnv.restUrl;
                if (rawUrl.includes('skaaa-logic/v1/submit')) {
                    restUrl = rawUrl.replace('skaaa-logic/v1/submit', 'skaaaaa-builder/v1');
                } else if (rawUrl.includes('skaaa-logic%2Fv1%2Fsubmit')) {
                    restUrl = rawUrl.replace('skaaa-logic%2Fv1%2Fsubmit', 'skaaaaa-builder%2Fv1');
                } else {
                    restUrl = rawUrl.replace('skaaa-logic', 'skaaaaa-builder');
                }
            }
            return restUrl;
        },
        
        init() {
            const editorId = 'skaaa_editor_' + fieldName.replace(/-/g, '_').toLowerCase();
            const textarea = document.getElementById(editorId);

            // 1. Đồng bộ trực tiếp với textarea thô (chế độ Code/HTML)
            if (textarea) {
                if (this.fields[fieldName]) {
                    textarea.value = this.fields[fieldName];
                }
                textarea.addEventListener('input', (e) => {
                    this.fields[fieldName] = e.target.value;
                });
                textarea.addEventListener('change', (e) => {
                    this.fields[fieldName] = e.target.value;
                });
                this.$watch(`fields.${fieldName}`, (val) => {
                    if (textarea.value !== val) {
                        textarea.value = val || '';
                    }
                });
            }

            // 2. Đồng bộ với TinyMCE
            const bindTinyMCE = (ed) => {
                if (ed.id !== editorId) return;

                // Lắng nghe thay đổi từ TinyMCE để update Alpine
                ed.on('change keyup NodeChange', () => {
                    this.fields[fieldName] = ed.getContent();
                });

                // Lắng nghe thay đổi từ Alpine để đồng bộ lại TinyMCE
                this.$watch(`fields.${fieldName}`, (val) => {
                    if (ed.getContent() !== val) {
                        ed.setContent(val || '');
                    }
                });

                // Set giá trị ban đầu nếu Alpine đã có data (ví dụ lúc Edit)
                if (this.fields[fieldName]) {
                    ed.setContent(this.fields[fieldName]);
                }
            };

            if (window.tinymce) {
                const existingEd = window.tinymce.get(editorId);
                if (existingEd) {
                    bindTinyMCE(existingEd);
                }
                // Lắng nghe nếu editor được tạo sau (ví dụ click chuyển tab Visual)
                window.tinymce.on('AddEditor', (e) => {
                    bindTinyMCE(e.editor);
                });
            }
        },

        async openDesigner() {
            this.isOpen = true;
            this.isLoading = true;

            try {
                // Gọi API lấy Iframe URL
                const restUrl = this.getBuilderRestUrl();
                const currentHtml = this.fields[fieldName] || '';
                
                const response = await fetch(`${restUrl}/scratchpad/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.skaaaAppEnv ? window.skaaaAppEnv.nonce : ''
                    },
                    body: JSON.stringify({ content: currentHtml })
                });

                const data = await response.json();
                
                if (data.success) {
                    this.postId = data.post_id;
                    this.iframeUrl = data.iframe_url;
                } else {
                    alert('Lỗi khởi tạo Trình thiết kế: ' + data.message);
                    this.isOpen = false;
                }
            } catch (err) {
                console.error(err);
                alert(__( 'Connection error when initializing Designer.', 'skaaa-no-code-design' ));
                this.isOpen = false;
            }
        },

        async closeDesigner(saveChanges = false) {
            // Lấy nội dung từ Iframe trước khi đóng chỉ khi saveChanges là true
            if (this.postId) {
                try {
                    const iframe = document.getElementById('skaaa_iframe_' + fieldName);
                    if (iframe && iframe.contentWindow && iframe.contentWindow.wp && iframe.contentWindow.wp.data) {
                        if (saveChanges) {
                            const content = iframe.contentWindow.wp.data.select('core/editor').getEditedPostContent();
                            if (content !== undefined) {
                                this.fields[fieldName] = content;
                                
                                // Cập nhật lại TinyMCE nếu đang hiển thị
                                const editorId = 'skaaa_editor_' + fieldName.replace(/-/g, '_').toLowerCase();
                                if (window.tinymce && window.tinymce.get(editorId)) {
                                    window.tinymce.get(editorId).setContent(content);
                                }
                            }
                        }

                        // Reset dirty state to prevent beforeunload prompt
                        try {
                            const wp = iframe.contentWindow.wp;
                            const type = wp.data.select('core/editor').getCurrentPostType();
                            const id = wp.data.select('core/editor').getCurrentPostId();
                            wp.data.dispatch('core').clearEntityRecordEdits('postType', type, id);
                        } catch(err) {
                            console.warn("Could not clear Gutenberg entity record edits", err);
                        }

                        // Đợi React trong iframe re-render và gỡ bỏ trước khi huỷ iframe
                        await new Promise(resolve => setTimeout(resolve, 150));
                    }
                } catch(e) {
                    console.warn("Could not retrieve content from Gutenberg iframe", e);
                }
            }

            this.isOpen = false;
            this.iframeUrl = '';
            
            if (!this.postId) return;

            // Xóa Scratchpad để dọn rác
            try {
                const restUrl = this.getBuilderRestUrl();
                await fetch(`${restUrl}/scratchpad/destroy`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.skaaaAppEnv ? window.skaaaAppEnv.nonce : ''
                    },
                    body: JSON.stringify({ post_id: this.postId })
                });
                this.postId = null;
            } catch (err) {
                console.error(__( 'Error cleaning Scratchpad', 'skaaa-no-code-design' ), err);
            }
        }
    }));
}

// Script này load TRƯỚC Alpine.js (thứ tự enqueue trong render.php).
// Khi Alpine load sau → phát alpine:init → hàm này hứng → đăng ký skaaaForm & skaaaTheme.
// Fallback: Nếu Alpine đã load trước (edge-case HTML Attributes) → gọi trực tiếp.
if (window.Alpine) {
    _registerSkaaaForm();
    _registerSkaaaTheme();
    _registerSkaaaNav();
    _registerSkaaaPortal();
    _registerSkaaaScratchpad();
} else {
    document.addEventListener('alpine:init', () => {
        _registerSkaaaForm();
        _registerSkaaaTheme();
        _registerSkaaaNav();
        _registerSkaaaPortal();
        _registerSkaaaScratchpad();
    });
}
