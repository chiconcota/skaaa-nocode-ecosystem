/**
 * Skaaai 1-Click Full Ecosystem Sync Client Controller
 * Version: 1.4.0
 */

(function($) {
    'use strict';

    var currentScopes = ['presets', 'organisms', 'theme_templates', 'workflows', 'custom_tables', 'pages', 'settings'];

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

    function openModal() {
        if (!skaaaiEcosystem.is_paired) {
            alert(skaaaiEcosystem.i18n.not_paired_error);
            return;
        }

        $('#skaaai-ecosystem-modal').removeClass('hidden');
        $('body').addClass('skaaai-modal-open');

        // Reset and start Analyzing step
        switchStep('analyzing');
        runPreflightDiff();
    }

    function closeModal() {
        $('#skaaai-ecosystem-modal').addClass('hidden');
        $('body').removeClass('skaaai-modal-open');
    }

    function runPreflightDiff() {
        var scopes = getSelectedScopes();

        $.ajax({
            url: skaaaiEcosystem.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'skaaai_ecosystem_diff',
                nonce: skaaaiEcosystem.nonce,
                scopes: scopes
            },
            success: function(response) {
                if (response.success && response.data && response.data.summary) {
                    renderDiffSummary(response.data.summary);
                    switchStep('review');
                } else {
                    var msg = response.data && response.data.message ? response.data.message : skaaaiEcosystem.i18n.diff_error;
                    showError(msg);
                }
            },
            error: function(xhr, status, error) {
                showError(skaaaiEcosystem.i18n.diff_error + ' (' + error + ')');
            }
        });
    }

    function renderDiffSummary(summary) {
        var $container = $('#skaaai-diff-summary-container');
        $container.empty();

        // 1. Presets
        if (summary.presets) {
            $container.append(createDiffCard('🎨 Design Tokens', summary.presets.count, summary.presets.action));
        }

        // 2. Organisms
        if (summary.organisms) {
            var orgText = summary.organisms.map(function(o) { return o.name; }).join(', ');
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
            var tblText = summary.custom_tables.length + ' tables, ' + totalRows + ' content rows';
            $container.append(createDiffCard('🗄️ Custom Tables', summary.custom_tables.length, tblText));
        }

        // 6. Pages
        if (summary.pages) {
            var pagesText = summary.pages.length + ' published pages with media';
            $container.append(createDiffCard('📄 Pages & Media', summary.pages.length, pagesText));
        }

        // 7. Settings
        if (summary.settings) {
            var settingsText = 'Front page: ' + (summary.settings.front_page_slug || 'static') + ' | ' + (summary.settings.permalink_structure || 'standard');
            $container.append(createDiffCard('⚙️ Site Setup', 'Ready', settingsText));
        }
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

        $.ajax({
            url: skaaaiEcosystem.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'skaaai_ecosystem_execute',
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
                        $('#btn-view-live-site').attr('href', skaaaiEcosystem.remote_url);
                        switchStep('completed');
                    } else {
                        var msg = response.data && response.data.message ? response.data.message : skaaaiEcosystem.i18n.sync_error;
                        showError(msg);
                    }
                }, 600);
            },
            error: function(xhr, status, error) {
                clearInterval(stageTimer);
                showError(skaaaiEcosystem.i18n.sync_error + ' (' + error + ')');
            }
        });
    }

    function showError(message) {
        $('#skaaai-error-message').text(message);
        switchStep('error');
    }

    // Event Bindings
    $(document).ready(function() {
        $(document).on('click', '.skaaai-trigger-full-sync, #wp-admin-bar-skaaai-bar-full-sync, #wp-admin-bar-skaaai-ecosystem-sync-bar > .ab-item', function(e) {
            e.preventDefault();
            openModal();
        });

        $('#btn-approve-sync').on('click', function(e) {
            e.preventDefault();
            executeSync();
        });

        $('.skaaai-modal-close, #btn-modal-cancel').on('click', function(e) {
            e.preventDefault();
            closeModal();
        });

        // Close on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && !$('#skaaai-ecosystem-modal').hasClass('hidden')) {
                closeModal();
            }
        });
    });

})(jQuery);
