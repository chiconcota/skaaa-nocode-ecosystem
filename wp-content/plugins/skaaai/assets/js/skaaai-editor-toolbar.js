/**
 * Skaaai 1-Click Push & Pull Editor Integration
 *
 * Tích hợp nút bấm trực tiếp "🚀 Push to Live" và "📥 Pull from Live"
 * trên Header Toolbar của Gutenberg và bảng điều khiển Document Settings.
 */
( function( wp, config ) {
    'use strict';

    if ( ! wp || ! wp.plugins || ! wp.editPost || ! config ) {
        return;
    }

    const { registerPlugin } = wp.plugins;
    const { PluginPostStatusInfo } = wp.editPost;
    const { createElement: el, useState, useEffect } = wp.element;
    const { Button } = wp.components;
    const { dispatch, select } = wp.data;

    /**
     * Component Giao Diện Sidebar Document
     */
    const SkaaaiSidebarSyncPanel = () => {
        const [ isPushing, setIsPushing ] = useState( false );
        const [ isPulling, setIsPulling ] = useState( false );
        const [ syncStatus, setSyncStatus ] = useState( config.sync_status || 'not_synced' );
        const [ syncLabel, setSyncLabel ] = useState( config.sync_label || 'Not Synced' );
        const [ badgeIcon, setBadgeIcon ] = useState( config.badge_icon || '⚪' );
        const [ lastSynced, setLastSynced ] = useState( config.last_synced );
        const [ permalink, setPermalink ] = useState( config.remote_permalink );

        /**
         * Xử lý gửi bài viết lên Live Webhost (Push)
         */
        const executePush = async ( force = false ) => {
            if ( ! config.is_paired ) {
                dispatch( 'core/notices' ).createWarningNotice(
                    config.i18n.not_paired_warning,
                    {
                        id: 'skaaai-not-paired',
                        isDismissible: true,
                        actions: [
                            {
                                label: config.i18n.skaaa_sync,
                                url: config.i18n.settings_link,
                            },
                        ],
                    }
                );
                return;
            }

            // 1. Kiểm tra và lưu bài viết trước nếu có thay đổi chưa lưu
            const isDirty = select( 'core/editor' ).isEditedPostDirty();
            if ( isDirty ) {
                dispatch( 'core/notices' ).createInfoNotice(
                    config.i18n.saving_post_first,
                    { id: 'skaaai-saving-info', isDismissible: true }
                );
                await dispatch( 'core/editor' ).savePost();
            }

            setIsPushing( true );
            updateHeaderButtonState( true );

            const formData = new FormData();
            formData.append( 'action', 'skaaai_push_post' );
            formData.append( 'post_id', config.post_id );
            formData.append( 'nonce', config.nonce );
            if ( force ) {
                formData.append( 'force', '1' );
            }

            try {
                const response = await fetch( config.ajax_url, {
                    method: 'POST',
                    body: formData,
                } );

                const result = await response.json();

                if ( result.success ) {
                    const data = result.data || {};
                    setSyncStatus( 'synced' );
                    setSyncLabel( 'Synced' );
                    setBadgeIcon( '🟢' );
                    setLastSynced( data.last_synced || new Date().toISOString() );
                    if ( data.permalink ) {
                        setPermalink( data.permalink );
                    }

                    // Bắn Toast Notice thành công
                    dispatch( 'core/notices' ).createSuccessNotice(
                        data.message || config.i18n.push_success,
                        {
                            id: 'skaaai-push-success',
                            isDismissible: true,
                            actions: data.permalink ? [
                                {
                                    label: config.i18n.view_live,
                                    url: data.permalink,
                                },
                            ] : [],
                        }
                    );
                } else if ( response.status === 409 || result.data?.conflict ) {
                    // Xung đột phiên bản
                    dispatch( 'core/notices' ).createWarningNotice(
                        result.data?.message || config.i18n.conflict_detected,
                        {
                            id: 'skaaai-push-conflict',
                            isDismissible: true,
                            actions: [
                                {
                                    label: config.i18n.force_push,
                                    onClick: () => executePush( true ),
                                },
                            ],
                        }
                    );
                } else {
                    const errorMsg = result.data?.message || 'Push failed.';
                    dispatch( 'core/notices' ).createErrorNotice(
                        errorMsg,
                        { id: 'skaaai-push-error', isDismissible: true }
                    );
                }
            } catch ( err ) {
                dispatch( 'core/notices' ).createErrorNotice(
                    err.message || 'Network request failed.',
                    { id: 'skaaai-push-network-error', isDismissible: true }
                );
            } finally {
                setIsPushing( false );
                updateHeaderButtonState( false );
            }
        };

        /**
         * Xử lý kéo bài viết từ Live Webhost về Localhost (Pull)
         */
        const executePull = async () => {
            if ( ! config.is_paired ) {
                dispatch( 'core/notices' ).createWarningNotice(
                    config.i18n.not_paired_warning,
                    {
                        id: 'skaaai-not-paired',
                        isDismissible: true,
                        actions: [
                            {
                                label: config.i18n.skaaa_sync,
                                url: config.i18n.settings_link,
                            },
                        ],
                    }
                );
                return;
            }

            // Lấy dữ liệu Diff từ Live trước khi yêu cầu xác nhận
            setIsPulling( true );
            updateHeaderPullButtonState( true );

            let diffMsg = config.i18n.confirm_pull_editor;
            try {
                const diffFormData = new FormData();
                diffFormData.append( 'action', 'skaaai_get_post_diff' );
                diffFormData.append( 'post_id', config.post_id );
                diffFormData.append( 'nonce', config.nonce );

                const diffRes = await fetch( config.ajax_url, {
                    method: 'POST',
                    body: diffFormData,
                } );
                const diffJson = await diffRes.json();
                if ( diffJson.success && diffJson.data ) {
                    const local  = diffJson.data.local || {};
                    const remote = diffJson.data.remote || {};
                    const comp   = diffJson.data.comparison || {};
                    diffMsg = `[DIFF PREVIEW: REMOTE vs LOCAL]\n\n` +
                              `• Title: "${local.title}" ➔ "${remote.title}"` + ( comp.title_diff ? ' (CHANGED)' : '' ) + `\n` +
                              `• Remote Modified: ${remote.modified}` + ( comp.is_remote_newer ? ' (NEWER)' : '' ) + `\n` +
                              `• Local Modified: ${local.modified}\n` +
                              `• Content Blocks: ${local.block_count} local vs ${remote.block_count} remote\n\n` +
                              `A WordPress Revision will be saved before overwriting. Do you want to pull and overwrite now?`;
                }
            } catch ( err ) {
                // Fallback to default message
            } finally {
                setIsPulling( false );
                updateHeaderPullButtonState( false );
            }

            if ( ! confirm( diffMsg ) ) {
                return;
            }

            setIsPulling( true );
            updateHeaderPullButtonState( true );

            const formData = new FormData();
            formData.append( 'action', 'skaaai_pull_post' );
            formData.append( 'post_id', config.post_id );
            formData.append( 'nonce', config.nonce );

            try {
                const response = await fetch( config.ajax_url, {
                    method: 'POST',
                    body: formData,
                } );

                const result = await response.json();

                if ( result.success ) {
                    dispatch( 'core/notices' ).createSuccessNotice(
                        config.i18n.pull_success,
                        { id: 'skaaai-pull-success', isDismissible: false }
                    );

                    // Tự động tải lại trang Editor sau 1 giây để nạp trọn vẹn nội dung vừa kéo về
                    setTimeout( () => {
                        window.location.reload();
                    }, 1000 );
                } else {
                    const errorMsg = result.data?.message || 'Pull failed.';
                    dispatch( 'core/notices' ).createErrorNotice(
                        errorMsg,
                        { id: 'skaaai-pull-error', isDismissible: true }
                    );
                }
            } catch ( err ) {
                dispatch( 'core/notices' ).createErrorNotice(
                    err.message || 'Network request failed.',
                    { id: 'skaaai-pull-network-error', isDismissible: true }
                );
            } finally {
                setIsPulling( false );
                updateHeaderPullButtonState( false );
            }
        };

        // Gắn hàm push và pull vào window để Header Button gọi
        window.skaaaiExecutePush = executePush;
        window.skaaaiExecutePull = executePull;

        return el(
            PluginPostStatusInfo,
            { className: 'skaaai-plugin-post-status-info' },
            el(
                'div',
                { className: 'skaaai-sync-sidebar-box' },
                el(
                    'div',
                    { className: 'skaaai-sidebar-status-row' },
                    el( 'strong', null, config.i18n.skaaa_sync ),
                    el(
                        'span',
                        { className: `skaaai-sync-badge skaaai-badge-${syncStatus}` },
                        `${badgeIcon} ${syncLabel}`
                    )
                ),
                el(
                    'div',
                    { className: 'skaaai-sidebar-time-row' },
                    `${config.i18n.last_synced_label} ${lastSynced || config.i18n.never}`
                ),
                el(
                    'div',
                    { className: 'skaaai-sidebar-actions-row' },
                    el(
                        Button,
                        {
                            isPrimary: true,
                            isSmall: true,
                            isBusy: isPushing,
                            disabled: isPushing || isPulling,
                            className: 'skaaai-sidebar-push-btn',
                            onClick: () => executePush( false ),
                        },
                        isPushing ? config.i18n.pushing : ( syncStatus === 'synced' ? config.i18n.re_sync : config.i18n.push_to_live )
                    ),
                    el(
                        Button,
                        {
                            isSecondary: true,
                            isSmall: true,
                            isBusy: isPulling,
                            disabled: isPushing || isPulling,
                            className: 'skaaai-sidebar-pull-btn',
                            onClick: () => executePull(),
                            title: config.i18n.pull_from_live,
                        },
                        isPulling ? config.i18n.pulling_from_live : config.i18n.pull_from_live
                    ),
                    permalink && el(
                        Button,
                        {
                            isSecondary: true,
                            isSmall: true,
                            href: permalink,
                            target: '_blank',
                            rel: 'noopener noreferrer',
                            title: config.i18n.view_live,
                        },
                        '↗'
                    )
                )
            )
        );
    };

    /**
     * Cập nhật trạng thái hiển thị của Header Push Button
     */
    function updateHeaderButtonState( isBusy ) {
        const btn = document.getElementById( 'skaaai-header-push-btn' );
        if ( ! btn ) {
            return;
        }

        if ( isBusy ) {
            btn.disabled = true;
            btn.innerHTML = `<span class="skaaai-spinner-icon">⚡</span> <span>${config.i18n.pushing}</span>`;
        } else {
            btn.disabled = false;
            btn.innerHTML = `<span>🚀</span> <span>${config.i18n.push_to_live}</span>`;
        }
    }

    /**
     * Cập nhật trạng thái hiển thị của Header Pull Button
     */
    function updateHeaderPullButtonState( isBusy ) {
        const btn = document.getElementById( 'skaaai-header-pull-btn' );
        if ( ! btn ) {
            return;
        }

        if ( isBusy ) {
            btn.disabled = true;
            btn.innerHTML = `<span class="skaaai-spinner-icon">⚡</span> <span>${config.i18n.pulling_from_live}</span>`;
        } else {
            btn.disabled = false;
            btn.innerHTML = `<span>📥</span> <span>${config.i18n.pull_from_live}</span>`;
        }
    }

    /**
     * Tự động nhúng nút "🚀 Push to Live" và "📥 Pull from Live" lên Header Toolbar của Gutenberg
     */
    function injectHeaderToolbarButtons() {
        const targetContainer = document.querySelector( '.edit-post-header__settings' ) ||
                                document.querySelector( '.editor-header__settings' );

        if ( ! targetContainer ) {
            return;
        }

        // 1. Nhúng nút Pull from Live
        if ( ! document.getElementById( 'skaaai-header-pull-btn' ) ) {
            const pullBtn = document.createElement( 'button' );
            pullBtn.id = 'skaaai-header-pull-btn';
            pullBtn.type = 'button';
            pullBtn.className = 'components-button skaaai-header-pull-btn';
            pullBtn.title = config.i18n.pull_from_live;
            pullBtn.innerHTML = `<span>📥</span> <span>${config.i18n.pull_from_live}</span>`;

            pullBtn.addEventListener( 'click', function( e ) {
                e.preventDefault();
                if ( typeof window.skaaaiExecutePull === 'function' ) {
                    window.skaaaiExecutePull();
                }
            } );

            targetContainer.prepend( pullBtn );
        }

        // 2. Nhúng nút Push to Live
        if ( ! document.getElementById( 'skaaai-header-push-btn' ) ) {
            const pushBtn = document.createElement( 'button' );
            pushBtn.id = 'skaaai-header-push-btn';
            pushBtn.type = 'button';
            pushBtn.className = 'components-button skaaai-header-push-btn';
            pushBtn.title = config.i18n.push_to_live;
            pushBtn.innerHTML = `<span>🚀</span> <span>${config.i18n.push_to_live}</span>`;

            pushBtn.addEventListener( 'click', function( e ) {
                e.preventDefault();
                if ( typeof window.skaaaiExecutePush === 'function' ) {
                    window.skaaaiExecutePush( false );
                }
            } );

            targetContainer.prepend( pushBtn );
        }
    }

    // Đăng ký Plugin trong Gutenberg
    registerPlugin( 'skaaai-sync-bridge', {
        render: SkaaaiSidebarSyncPanel,
    } );

    // Khởi chạy vòng lặp inject buttons an toàn khi Gutenberg sẵn sàng
    document.addEventListener( 'DOMContentLoaded', function() {
        injectHeaderToolbarButtons();
        const intervalId = setInterval( function() {
            injectHeaderToolbarButtons();
            if ( document.getElementById( 'skaaai-header-push-btn' ) && document.getElementById( 'skaaai-header-pull-btn' ) ) {
                clearInterval( intervalId );
            }
        }, 500 );
    } );

} )( window.wp, window.skaaaiEditorSync );
