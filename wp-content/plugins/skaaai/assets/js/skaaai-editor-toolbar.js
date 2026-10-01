/**
 * Skaaai 1-Click Push to Live Editor Integration
 *
 * Tích hợp nút bấm trực tiếp trên Header Toolbar của Gutenberg
 * và bảng điều khiển Document Settings (Status & Visibility Panel).
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
        const [ syncStatus, setSyncStatus ] = useState( config.sync_status || 'not_synced' );
        const [ syncLabel, setSyncLabel ] = useState( config.sync_label || 'Not Synced' );
        const [ badgeIcon, setBadgeIcon ] = useState( config.badge_icon || '⚪' );
        const [ lastSynced, setLastSynced ] = useState( config.last_synced );
        const [ permalink, setPermalink ] = useState( config.remote_permalink );

        /**
         * Xử lý gửi bài viết lên Live Webhost
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

        // Gắn hàm push vào window để Header Button gọi
        window.skaaaiExecutePush = executePush;

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
                            disabled: isPushing,
                            className: 'skaaai-sidebar-push-btn',
                            onClick: () => executePush( false ),
                        },
                        isPushing ? config.i18n.pushing : ( syncStatus === 'synced' ? config.i18n.re_sync : config.i18n.push_to_live )
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
     * Cập nhật trạng thái hiển thị của Header Button
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
     * Tự động nhúng nút "🚀 Push to Live" lên Header Toolbar của Gutenberg
     */
    function injectHeaderToolbarButton() {
        const targetContainer = document.querySelector( '.edit-post-header__settings' ) ||
                                document.querySelector( '.editor-header__settings' );

        if ( ! targetContainer || document.getElementById( 'skaaai-header-push-btn' ) ) {
            return;
        }

        const button = document.createElement( 'button' );
        button.id = 'skaaai-header-push-btn';
        button.type = 'button';
        button.className = 'components-button skaaai-header-push-btn';
        button.title = config.i18n.push_to_live;
        button.innerHTML = `<span>🚀</span> <span>${config.i18n.push_to_live}</span>`;

        button.addEventListener( 'click', function( e ) {
            e.preventDefault();
            if ( typeof window.skaaaiExecutePush === 'function' ) {
                window.skaaaiExecutePush( false );
            }
        } );

        // Chèn vào vị trí đầu tiên của header settings (bên trái nút Lưu nháp / Đăng bài)
        targetContainer.prepend( button );
    }

    // Đăng ký Plugin trong Gutenberg
    registerPlugin( 'skaaai-sync-bridge', {
        render: SkaaaiSidebarSyncPanel,
    } );

    // Khởi chạy vòng lặp inject button an toàn khi Gutenberg sẵn sàng
    document.addEventListener( 'DOMContentLoaded', function() {
        injectHeaderToolbarButton();
        const intervalId = setInterval( function() {
            injectHeaderToolbarButton();
            if ( document.getElementById( 'skaaai-header-push-btn' ) ) {
                clearInterval( intervalId );
            }
        }, 500 );
    } );

} )( window.wp, window.skaaaiEditorSync );
