/**
 * Skaaai 1-Click Full Ecosystem Sync Client Controller (Bidirectional Push & Pull)
 * Version: 1.5.0
 */

(function($) {
    'use strict';

    var currentScopes = ['presets', 'organisms', 'theme_templates', 'workflows', 'custom_tables', 'pages', 'settings'];
    var currentMode   = 'push'; // 'push' hoặc 'pull'

    function getSelectedScopes() {
        var scopes = [];
        var $checkboxes = $('input[name="ecosystem_scope[]"]:checked');
        if ($checkboxes.length > 0) {
            $checkboxes.each(function() {
                scopes.push($(this).val());
            });
        }
        return scopes.length > 0 ? scopes : currentScopes;
    }

    function switchStep(stepName) {
        $('.skaaai-modal-step').addClass('hidden').removeClass('active');
        $('#skaaai-step-' + stepName).removeClass('hidden').addClass('active');

        if (stepName === 'review') {
            $('#btn-approve-sync').removeClass('hidden');
            $('#btn-modal-cancel').text('Cancel').removeClass('hidden');
        } else if (stepName === 'progress') {
            $('#btn-approve-sync').addClass('hidden');
            $('#btn-modal-cancel').addClass('hidden');
        } else if (stepName === 'completed') {
            $('#btn-approve-sync').addClass('hidden');
            $('#btn-modal-cancel').text('Close').removeClass('hidden');
        } else if (stepName === 'error') {
            $('#btn-approve-sync').addClass('hidden');
            $('#btn-modal-cancel').text('Close').removeClass('hidden');
        } else {
            $('#btn-approve-sync').addClass('hidden');
            $('#btn-modal-cancel').text('Cancel').removeClass('hidden');
        }
    }

    function openModal(mode) {
        currentMode = mode || 'push';

        if (!skaaaiEcosystem.is_paired) {
            alert(skaaaiEcosystem.i18n.not_paired_error);
            return;
        }

        // Tùy biến giao diện theo hướng Push hay Pull
        if (currentMode === 'pull') {
            $('#skaaai-modal-title-text').text('📥 1-Click Full Ecosystem Pull from Live');
            $('#skaaai-analyzing-text').text(skaaaiEcosystem.i18n.analyzing_pull || 'Fetching and analyzing Ecosystem from Live Webhost...');
            $('#skaaai-review-banner-title').text('Remote Ecosystem Pre-Flight Analysis Completed');
            $('#skaaai-review-banner-sub').text('Review remote changes below before overwriting your Localhost database.');
            $('#btn-approve-sync-text').text(skaaaiEcosystem.i18n.approve_btn_pull || 'Approve & Pull to Localhost');
            $('#btn-approve-sync').removeClass('skaaai-btn-gradient').addClass('skaaai-btn-pull-gradient');
            $('#btn-approve-sync .dashicons').removeClass('dashicons-yes').addClass('dashicons-cloud-download');
        } else {
            $('#skaaai-modal-title-text').text('🚀 1-Click Full Ecosystem Push to Live');
            $('#skaaai-analyzing-text').text(skaaaiEcosystem.i18n.analyzing_push || 'Analyzing Local Ecosystem vs Live Webhost...');
            $('#skaaai-review-banner-title').text('Pre-Flight Analysis Completed');
            $('#skaaai-review-banner-sub').text('Review changes below before committing updates to the Live host.');
            $('#btn-approve-sync-text').text(skaaaiEcosystem.i18n.approve_btn_push || 'Approve & Execute Push');
            $('#btn-approve-sync').removeClass('skaaai-btn-pull-gradient').addClass('skaaai-btn-gradient');
            $('#btn-approve-sync .dashicons').removeClass('dashicons-cloud-download').addClass('dashicons-yes');
        }

        $('#skaaai-ecosystem-modal').removeClass('hidden');
        $('body').addClass('skaaai-modal-open');

        // Reset và chạy bước Analyzing
        switchStep('analyzing');
        runPreflightDiff();
    }

    function closeModal() {
        $('#skaaai-ecosystem-modal').addClass('hidden');
        $('body').removeClass('skaaai-modal-open');
    }

    function runPreflightDiff() {
        var scopes = getSelectedScopes();
        var actionName = (currentMode === 'pull') ? 'skaaai_ecosystem_pull_diff' : 'skaaai_ecosystem_diff';

        $.ajax({
            url: skaaaiEcosystem.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: actionName,
                nonce: skaaaiEcosystem.nonce,
                scopes: scopes
            },
            success: function(response) {
                if (response.success && response.data && response.data.summary) {
                    renderDiffSummary(response.data.summary);
                    switchStep('review');
                } else {
                    var defaultErr = (currentMode === 'pull') ? skaaaiEcosystem.i18n.diff_error_pull : skaaaiEcosystem.i18n.diff_error_push;
                    var msg = response.data && response.data.message ? response.data.message : defaultErr;
                    showError(msg);
                }
            },
            error: function(xhr, status, error) {
                var defaultErr = (currentMode === 'pull') ? skaaaiEcosystem.i18n.diff_error_pull : skaaaiEcosystem.i18n.diff_error_push;
                showError(defaultErr + ' (' + error + ')');
            }
        });
    }

    function renderDiffSummary(summary) {
        var $container = $('#skaaai-diff-summary-container');
        $container.empty();

        var directionLabel = (currentMode === 'pull') ? 'from Live' : 'to Live';

        // 1. Presets
        if (summary.presets) {
            $container.append(createDiffCard('🎨 Design Tokens', summary.presets.count, summary.presets.action));
        }

        // 2. Organisms
        if (summary.organisms) {
            var orgText = summary.organisms.map(function(o) { return o.name + (o.action ? ' (' + o.action + ')' : ''); }).join(', ');
            $container.append(createDiffCard('🧩 Organisms', summary.organisms.length, orgText || 'Header & Footer'));
        }

        // 3. Theme Templates
        if (summary.theme_templates) {
            var tplText = summary.theme_templates.map(function(t) { return t.name + ' (' + t.location + ')'; }).join(', ');
            $container.append(createDiffCard('📐 Theme Templates', summary.theme_templates.length, tplText || 'Site layout rules'));
        }

        // 4. Workflows
        if (summary.workflows) {
            var wfText = summary.workflows.map(function(w) { return w.name; }).join(', ');
            $container.append(createDiffCard('⚡ Logic Workflows', summary.workflows.length, wfText || 'DAG Automations'));
        }

        // 5. Custom Tables
        if (summary.custom_tables) {
            var totalRows = 0;
            summary.custom_tables.forEach(function(t) { totalRows += (t.row_count || 0); });
            var tblText = summary.custom_tables.length + ' tables, ' + totalRows + ' content rows ' + directionLabel;
            $container.append(createDiffCard('🗄️ Smart Object Tables', summary.custom_tables.length, tblText));
        }

        // 6. Pages & Posts Chuyên Sâu
        if (summary.content_items || summary.pages || summary.posts) {
            $container.append(createContentDiffCard(summary, directionLabel));
        }

        // 7. Settings
        if (summary.settings) {
            var settingsText = 'Front page: ' + (summary.settings.front_page_slug || 'static') + ' | ' + (summary.settings.permalink_structure || 'standard');
            $container.append(createDiffCard('⚙️ Site Setup', 'Ready', settingsText));
        }
    }

    function createContentDiffCard(summary, directionLabel) {
        var items = summary.content_items || summary.pages || [];
        var counts = summary.content_counts || {
            total: items.length,
            modified: 0,
            new: 0,
            deleted: 0,
            unchanged: items.length
        };

        if (!summary.content_counts && Array.isArray(items)) {
            var mod = 0, nw = 0, del = 0, unc = 0;
            items.forEach(function(it) {
                if (it.status === 'modified') mod++;
                else if (it.status === 'new') nw++;
                else if (it.status === 'deleted_on_remote') del++;
                else unc++;
            });
            counts = { total: items.length, modified: mod, new: nw, deleted: del, unchanged: unc };
        }

        var $card = $('<div class="skaaai-diff-card skaaai-diff-card-wide"></div>');

        // Header
        var $header = $('<div class="skaaai-diff-card-header"></div>');
        var $titleLeft = $('<div></div>');
        var remoteLiveCount = counts.total - counts.deleted;
        var titleText = '📄 Pages & Blog Posts (' + remoteLiveCount + ' on Live' + (counts.deleted > 0 ? ', ' + counts.deleted + ' deleted remotely' : '') + ')';
        $titleLeft.append($('<div class="skaaai-diff-card-title"></div>').text(titleText));

        // Badges bar
        var $badgesBar = $('<div class="skaaai-diff-badges-bar"></div>');
        if (counts.modified > 0) {
            $badgesBar.append($('<span class="skaaai-badge-stat skaaai-badge-stat-mod"></span>').text('🟡 ' + counts.modified + ' Modified'));
        }
        if (counts.new > 0) {
            $badgesBar.append($('<span class="skaaai-badge-stat skaaai-badge-stat-new"></span>').text('🔵 ' + counts.new + ' New'));
        }
        if (counts.deleted > 0) {
            $badgesBar.append($('<span class="skaaai-badge-stat skaaai-badge-stat-del"></span>').text('🗑️ ' + counts.deleted + ' Deleted on Live'));
        }
        if (counts.unchanged > 0) {
            $badgesBar.append($('<span class="skaaai-badge-stat skaaai-badge-stat-sync"></span>').text('⚪ ' + counts.unchanged + ' Synced'));
        }
        $titleLeft.append($badgesBar);
        $header.append($titleLeft);

        // Nút toggle accordion
        var hasChanges = (counts.modified > 0 || counts.new > 0 || counts.deleted > 0);
        var toggleLabel = hasChanges ? 'Hide Details ▲' : 'View Items ▼';
        var $toggleBtn = $('<button type="button" class="skaaai-diff-list-toggle"></button>').text(toggleLabel);
        $header.append($toggleBtn);
        $card.append($header);

        // Container danh sách các bài viết
        var $itemsContainer = $('<div class="skaaai-diff-items-container"></div>');
        if (!hasChanges) {
            $itemsContainer.hide();
        }

        if (items.length === 0) {
            var $emptyRow = $('<div class="skaaai-diff-item-row"></div>');
            $emptyRow.append($('<span class="skaaai-diff-action-desc"></span>').text('No pages or posts found.'));
            $itemsContainer.append($emptyRow);
        } else {
            items.forEach(function(item) {
                var $row = $('<div class="skaaai-diff-item-row"></div>');
                if (item.status === 'unchanged') {
                    $row.addClass('item-unchanged');
                }

                // Cột trái: Tên bài, type, slug, ngày sửa
                var $left = $('<div class="skaaai-diff-item-left"></div>');
                var $titleWrap = $('<div class="skaaai-diff-item-title-wrap"></div>');

                var typeBadgeClass = (item.post_type === 'post') ? 'skaaai-badge-type skaaai-badge-type-post' : 'skaaai-badge-type';
                var typeLabel = (item.post_type === 'post') ? 'Post' : 'Page';
                $titleWrap.append($('<span></span>').addClass(typeBadgeClass).text(typeLabel));

                var displayTitle = item.title || '(No Title)';
                if (item.is_front_page) {
                    displayTitle += ' [Homepage]';
                }
                $titleWrap.append($('<span class="skaaai-diff-item-title"></span>').text(displayTitle));
                $left.append($titleWrap);

                var $meta = $('<div class="skaaai-diff-item-meta"></div>');
                if (item.slug) {
                    $meta.append($('<code></code>').text('/' + item.slug));
                }
                if (item.remote_modified && item.remote_modified !== '-') {
                    $meta.append($('<span></span>').text('Live: ' + item.remote_modified));
                }
                if (item.local_modified && item.local_modified !== '-') {
                    $meta.append($('<span></span>').text('Local: ' + item.local_modified));
                }
                $left.append($meta);
                $row.append($left);

                // Cột phải: Badge trạng thái và action
                var $right = $('<div class="skaaai-diff-item-right"></div>');
                var statusClass = 'skaaai-badge-status ' + (item.status || 'unchanged');
                var statusText = (item.status === 'modified') ? 'Modified' :
                                 (item.status === 'new') ? 'New' :
                                 (item.status === 'deleted_on_remote') ? 'Deleted on Live' : 'Synced';

                $right.append($('<span></span>').addClass(statusClass).text(statusText));
                if (item.action) {
                    $right.append($('<span class="skaaai-diff-action-desc"></span>').text(item.action));
                }
                $row.append($right);

                $itemsContainer.append($row);
            });
        }

        $card.append($itemsContainer);

        // Sự kiện toggle
        $toggleBtn.on('click', function(e) {
            e.preventDefault();
            $itemsContainer.slideToggle(150, function() {
                var isVisible = $itemsContainer.is(':visible');
                $toggleBtn.text(isVisible ? 'Hide Details ▲' : 'View Items ▼');
            });
        });

        return $card;
    }

    function createDiffCard(title, count, desc) {
        var $card = $('<div class="skaaai-diff-card"></div>');
        $card.append($('<div class="skaaai-diff-card-title"></div>').text(title));
        $card.append($('<div class="skaaai-diff-card-count"></div>').text(count));
        $card.append($('<div class="skaaai-diff-card-desc"></div>').text(desc));
        return $card;
    }

    function executeSync() {
        var scopes = getSelectedScopes();
        switchStep('progress');

        // Reset progress items
        $('.skaaai-progress-item').removeClass('done running');
        var $items = $('.skaaai-progress-item');
        var currentIdx = 0;

        function advanceProgress() {
            if (currentIdx < $items.length) {
                $items.eq(currentIdx).addClass('running');
                $items.eq(currentIdx).find('.dashicons').removeClass('dashicons-ellipsis').addClass('dashicons-update');
            }
        }

        advanceProgress();

        var stageTimer = setInterval(function() {
            if (currentIdx < $items.length - 1) {
                $items.eq(currentIdx).removeClass('running').addClass('done');
                $items.eq(currentIdx).find('.dashicons').removeClass('dashicons-update').addClass('dashicons-yes');
                currentIdx++;
                advanceProgress();
            }
        }, 1200);

        var actionExecute = (currentMode === 'pull') ? 'skaaai_ecosystem_pull_execute' : 'skaaai_ecosystem_execute';

        $.ajax({
            url: skaaaiEcosystem.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: actionExecute,
                nonce: skaaaiEcosystem.nonce,
                scopes: scopes
            },
            success: function(response) {
                clearInterval(stageTimer);

                // Mark all done
                $items.removeClass('running').addClass('done');
                $items.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-yes');

                setTimeout(function() {
                    if (response.success) {
                        if (currentMode === 'pull') {
                            $('#skaaai-completed-title').text(skaaaiEcosystem.i18n.sync_success_pull || 'Full Ecosystem Pulled Successfully!');
                            $('#skaaai-completed-details').text('All selected components, smart object tables, workflows, pages, and configurations have been pulled from Live and applied to Localhost.');
                            $('#btn-view-live-site').attr('href', '/').html('<span class="dashicons dashicons-admin-home"></span> View Localhost Site');
                        } else {
                            $('#skaaai-completed-title').text(skaaaiEcosystem.i18n.sync_success_push || 'Full Ecosystem Pushed to Live Successfully!');
                            $('#skaaai-completed-details').text('All selected components, smart object tables, workflows, pages, and configurations have been deployed to your Live Webhost.');
                            $('#btn-view-live-site').attr('href', skaaaiEcosystem.remote_url).html('<span class="dashicons dashicons-external"></span> View Live Website');
                        }
                        switchStep('completed');
                    } else {
                        var defaultErr = (currentMode === 'pull') ? skaaaiEcosystem.i18n.sync_error_pull : skaaaiEcosystem.i18n.sync_error_push;
                        var msg = response.data && response.data.message ? response.data.message : defaultErr;
                        showError(msg);
                    }
                }, 600);
            },
            error: function(xhr, status, error) {
                clearInterval(stageTimer);
                var defaultErr = (currentMode === 'pull') ? skaaaiEcosystem.i18n.sync_error_pull : skaaaiEcosystem.i18n.sync_error_push;
                showError(defaultErr + ' (' + error + ')');
            }
        });
    }

    function showError(message) {
        $('#skaaai-error-message').text(message);
        switchStep('error');
    }

    // Event Bindings
    $(document).ready(function() {
        // Nút kích hoạt Push (Đẩy lên Live)
        $(document).on('click', '.skaaai-trigger-full-sync, #wp-admin-bar-skaaai-bar-full-sync', function(e) {
            e.preventDefault();
            openModal('push');
        });

        // Nút kích hoạt Pull (Kéo từ Live về)
        $(document).on('click', '.skaaai-trigger-full-pull, #wp-admin-bar-skaaai-bar-full-pull', function(e) {
            e.preventDefault();
            openModal('pull');
        });

        // Phê duyệt đồng bộ
        $('#btn-approve-sync').on('click', function(e) {
            e.preventDefault();
            executeSync();
        });

        // Đóng modal
        $('.skaaai-modal-close, #btn-modal-cancel').on('click', function(e) {
            e.preventDefault();
            closeModal();
        });

        // Đóng bằng phím Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && !$('#skaaai-ecosystem-modal').hasClass('hidden')) {
                closeModal();
            }
        });
    });

})(jQuery);
