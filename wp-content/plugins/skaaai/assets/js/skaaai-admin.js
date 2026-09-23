/**
 * Skaaai Admin Dashboard JavaScript
 *
 * Xử lý tabs, copy pairing key, bắt tay handshake và deploy code từ xa.
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // 1. Chuyển đổi Tabs
        $('.skaaai-tab-btn').on('click', function() {
            var targetTab = $(this).data('tab');
            $('.skaaai-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.skaaai-tab-pane').removeClass('active');
            $('#tab-' + targetTab).addClass('active');
        });

        // 2. Chuyển đổi Role (Sender vs Receiver)
        $('input[name="role"]').on('change', function() {
            var selectedRole = $(this).val();
            $('.skaaai-radio-card').removeClass('selected');
            $(this).closest('.skaaai-radio-card').addClass('selected');

            if (selectedRole === 'receiver') {
                $('#section-receiver').removeClass('hidden');
                $('#section-sender').addClass('hidden');
            } else {
                $('#section-receiver').addClass('hidden');
                $('#section-sender').removeClass('hidden');
            }
        });

        // 3. 1-Click Copy Pairing Key
        $('#btn-copy-pairing-key').on('click', function() {
            var input = document.getElementById('skaaai-pairing-key-input');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(function() {
                var $btn = $('#btn-copy-pairing-key');
                var originalHtml = $btn.html();
                $btn.text(skaaaiAdmin.i18n.copied);
                setTimeout(function() {
                    $btn.html(originalHtml);
                }, 2000);
            });
        });

        // 4. Tạo lại Secret Token
        $('#btn-regenerate-token').on('click', function() {
            if (!confirm('Regenerating this token will disconnect all currently paired local sites until updated. Continue?')) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.post(skaaaiAdmin.ajax_url, {
                action: 'skaaai_regenerate_token',
                nonce: skaaaiAdmin.nonce
            }, function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    $('#skaaai-pairing-key-input').val(response.data.pairing_uri);
                    $('#skaaai-secret-token-display').val(response.data.token);
                    alert(response.data.message);
                } else {
                    alert(response.data.message || 'Error regenerating token.');
                }
            });
        });

        // 5. Phân tích chuỗi Pairing URI khi dán vào Sender
        $('#btn-parse-key').on('click', function() {
            var rawUri = $('#skaaai-paste-key-input').val().trim();
            var prefix = 'skaaai_pair://';

            if (!rawUri.startsWith(prefix)) {
                alert('Invalid Pairing Key format. Must begin with ' + prefix);
                return;
            }

            try {
                var base64Part = rawUri.substring(prefix.length);
                var decodedJson = atob(base64Part);
                var parsed = JSON.parse(decodedJson);

                if (parsed.url && parsed.token) {
                    $('#remote_url').val(parsed.url);
                    $('#remote_token').val(parsed.token);
                    $('#handshake-result-status').html('<span class="skaaai-inline-result success">Auto-filled: ' + (parsed.site_name || parsed.url) + '</span>');
                } else {
                    alert('Pairing key is missing URL or security token.');
                }
            } catch (e) {
                alert('Failed to parse Pairing Key: ' + e.message);
            }
        });

        // 6. Test Handshake Connection
        $('#btn-test-handshake').on('click', function() {
            var remoteUrl = $('#remote_url').val().trim();
            var token = $('#remote_token').val().trim();
            var $status = $('#handshake-result-status');
            var $btn = $(this);

            if (!remoteUrl || !token) {
                $status.html('<span class="skaaai-inline-result error">Please provide both Remote URL and Token.</span>');
                return;
            }

            $btn.prop('disabled', true);
            $status.html('<span class="skaaai-inline-result">' + skaaaiAdmin.i18n.testing + '</span>');

            $.post(skaaaiAdmin.ajax_url, {
                action: 'skaaai_test_handshake',
                nonce: skaaaiAdmin.nonce,
                remote_url: remoteUrl,
                token: token
            }, function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    var details = response.data.site_name ? ' (' + response.data.site_name + ')' : '';
                    var codeBadge = response.data.can_code ? ' | ⚡ Code Push: Enabled' : ' | 🔒 Code Push: Disabled';
                    $status.html('<span class="skaaai-inline-result success">✔ ' + response.data.message + details + codeBadge + '</span>');
                } else {
                    $status.html('<span class="skaaai-inline-result error">✖ ' + response.data.message + '</span>');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $status.html('<span class="skaaai-inline-result error">✖ Network error connecting to remote site.</span>');
            });
        });

        // 7. Lưu cấu hình Form
        $('#skaaai-pairing-form').on('submit', function(e) {
            e.preventDefault();

            var $btn = $('#btn-save-settings');
            var $status = $('#save-settings-status');
            var role = $('input[name="role"]:checked').val();
            var remoteUrl = $('#remote_url').val();
            var remoteToken = $('#remote_token').val();
            var allowCodeDeploy = $('#allow_code_deploy').is(':checked') ? 1 : 0;

            $btn.prop('disabled', true);
            $status.html('<span class="skaaai-inline-result">' + skaaaiAdmin.i18n.saving + '</span>');

            $.post(skaaaiAdmin.ajax_url, {
                action: 'skaaai_save_settings',
                nonce: skaaaiAdmin.nonce,
                role: role,
                remote_url: remoteUrl,
                remote_token: remoteToken,
                allow_code_deploy: allowCodeDeploy
            }, function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    $status.html('<span class="skaaai-inline-result success">✔ ' + skaaaiAdmin.i18n.saved + '</span>');
                    setTimeout(function() {
                        $status.empty();
                    }, 3000);
                } else {
                    $status.html('<span class="skaaai-inline-result error">✖ ' + response.data.message + '</span>');
                }
            });
        });

        // 7b. Tự động lưu khi toggle checkbox Cho phép nhận Code
        $('#allow_code_deploy').on('change', function() {
            var isChecked = $(this).is(':checked') ? 1 : 0;
            var $status = $('#allow-code-deploy-status');
            $status.html('<span class="skaaai-inline-result">' + skaaaiAdmin.i18n.saving + '</span>');

            $.post(skaaaiAdmin.ajax_url, {
                action: 'skaaai_save_settings',
                nonce: skaaaiAdmin.nonce,
                role: $('input[name="role"]:checked').val(),
                remote_url: $('#remote_url').val(),
                remote_token: $('#remote_token').val(),
                allow_code_deploy: isChecked
            }, function(response) {
                if (response.success) {
                    $status.html('<span class="skaaai-inline-result success">✔ ' + skaaaiAdmin.i18n.saved + '</span>');
                    setTimeout(function() {
                        $status.empty();
                    }, 3000);
                } else {
                    $status.html('<span class="skaaai-inline-result error">✖ ' + response.data.message + '</span>');
                }
            });
        });

        // Hàm helper cập nhật bảng danh sách node
        function updateCustomNodeTableRow(meta) {
            if (!meta || typeof wp === 'undefined' || !wp.template) return;
            var $tbody = $('#custom-nodes-tbody');
            $('#no-custom-files-row').remove();

            var $existingRow = $tbody.find('tr[data-file="' + meta.filename + '"]');
            if ($existingRow.length > 0) {
                $existingRow.find('.file-size').text(meta.size);
                $existingRow.find('.file-modified').text(meta.modified);
                if (meta.has_bak && $existingRow.find('.skaaai-bak-badge').length === 0) {
                    $existingRow.find('td:first-child code').after(' <span class="skaaai-bak-badge" title="Backup file exists (.bak)">📦 .bak</span>');
                }
            } else {
                var template = wp.template('skaaai-custom-node-row');
                var rowHtml = template({
                    filename: meta.filename,
                    size: meta.size,
                    modified: meta.modified,
                    has_bak: meta.has_bak || false,
                    is_sender: $('input[name="role"]:checked').val() === 'sender'
                });
                $tbody.prepend(rowHtml);
            }
        }

        // 8. Deploy Code từ xa (Đồng thời lưu bản sao Local)
        $('#btn-deploy-code').on('click', function() {
            var filename = $('#deploy_filename').val().trim();
            var code = $('#deploy_code').val();
            var overwrite = $('#deploy_overwrite').is(':checked') ? 1 : 0;
            var $btn = $(this);
            var $console = $('#deploy-output-status');

            if (!filename) {
                alert('Please enter a valid PHP filename (e.g., class-my-node.php)');
                return;
            }

            if (!code.trim()) {
                alert('Code content cannot be empty.');
                return;
            }

            $btn.prop('disabled', true);
            $console.removeClass('hidden').html('⏳ ' + skaaaiAdmin.i18n.deploying + '\nChecking local syntax & saving local copy...\n');

            $.post(skaaaiAdmin.ajax_url, {
                action: 'skaaai_deploy_code_ajax',
                nonce: skaaaiAdmin.nonce,
                filename: filename,
                code: code,
                overwrite: overwrite
            }, function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    var out = '✔ DEPLOY SUCCESS!\n';
                    out += 'Target File: ' + response.data.filename + '\n';
                    if (response.data.backup_made) {
                        out += 'Backup Created: ' + response.data.filename + '.bak\n';
                    }
                    if (response.data.local_saved) {
                        out += 'Local Copy: ✔ wp-content/skaaa-custom-nodes/' + response.data.filename + '\n';
                    }
                    out += 'Message: ' + response.data.message;
                    $console.html(out);

                    // Cập nhật bảng Active Custom Nodes trên UI Local
                    if (response.data.file_meta) {
                        updateCustomNodeTableRow(response.data.file_meta);
                    }
                } else {
                    var failMsg = response.data.message || 'Unknown deployment error.';
                    if (response.data.local_saved && response.data.file_meta) {
                        updateCustomNodeTableRow(response.data.file_meta);
                    }
                    $console.html('✖ DEPLOY FAILED:\n' + failMsg);
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $console.html('✖ HTTP Network Error while contacting server.');
            });
        });

        // 9. Nạp file vào Editor để xem/sửa
        $(document).on('click', '.btn-load-file', function() {
            var filename = $(this).data('file');
            var $btn = $(this);
            $btn.prop('disabled', true);

            $.post(skaaaiAdmin.ajax_url, {
                action: 'skaaai_load_file_content',
                nonce: skaaaiAdmin.nonce,
                filename: filename
            }, function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    $('#deploy_filename').val(response.data.filename);
                    $('#deploy_code').val(response.data.code);
                    $('html, body').animate({
                        scrollTop: $('#deploy_filename').offset().top - 80
                    }, 300);
                    $('#deploy_code').focus();
                } else {
                    alert(response.data.message || 'Error loading file content.');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                alert('Network error loading file content.');
            });
        });

        // 10. Đẩy file có sẵn từ Local lên Live Webhost
        $(document).on('click', '.btn-push-file', function() {
            var filename = $(this).data('file');
            var $btn = $(this);
            var originalHtml = $btn.html();

            $btn.prop('disabled', true).text(skaaaiAdmin.i18n.pushing);

            $.post(skaaaiAdmin.ajax_url, {
                action: 'skaaai_push_existing_file',
                nonce: skaaaiAdmin.nonce,
                filename: filename
            }, function(response) {
                $btn.prop('disabled', false).html(originalHtml);
                if (response.success) {
                    alert(skaaaiAdmin.i18n.push_success + '\n(' + filename + ')');
                } else {
                    alert('Push failed: ' + (response.data.message || 'Unknown error.'));
                }
            }).fail(function() {
                $btn.prop('disabled', false).html(originalHtml);
                alert('Network error pushing file to remote.');
            });
        });

        // 11. Xóa file Custom Node
        $(document).on('click', '.btn-delete-file', function() {
            var filename = $(this).data('file');
            var $row = $(this).closest('tr');

            if (!confirm(skaaaiAdmin.i18n.confirm_delete + '\n(' + filename + ')')) {
                return;
            }

            $.post(skaaaiAdmin.ajax_url, {
                action: 'skaaai_delete_custom_file',
                nonce: skaaaiAdmin.nonce,
                filename: filename
            }, function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || 'Failed to delete file.');
                }
            });
        });

    });
})(jQuery);
