/**
 * Skaaai Post List Quick Sync (edit.php)
 *
 * Xử lý sự kiện click nút Push nhanh, Pull nhanh và tự động
 * đối soát trạng thái (Diff Check) trực tiếp trên bảng danh sách bài viết.
 */
( function( $ ) {
    'use strict';

    if ( typeof skaaaiPostList === 'undefined' ) {
        return;
    }

    // 1. Sự kiện click nút Push
    $( document ).on( 'click', '.skaaai-push-row-btn', function( e ) {
        e.preventDefault();

        const $btn = $( this );
        const postId = $btn.data( 'post-id' );
        if ( ! postId || $btn.prop( 'disabled' ) ) {
            return;
        }

        executePushRow( postId, $btn, false );
    } );

    // 2. Sự kiện click nút Pull (Kèm Diff Preview)
    $( document ).on( 'click', '.skaaai-pull-row-btn', function( e ) {
        e.preventDefault();

        const $btn = $( this );
        const postId = $btn.data( 'post-id' );
        if ( ! postId || $btn.prop( 'disabled' ) ) {
            return;
        }

        const originalText = $btn.find( '.skaaai-btn-text' ).text();
        $btn.prop( 'disabled', true ).addClass( 'is-loading' );
        $btn.find( '.skaaai-btn-text' ).text( skaaaiPostList.i18n.diff_loading || 'Checking...' );

        // Lấy dữ liệu Diff từ Live trước khi mở Modal
        $.ajax( {
            url: skaaaiPostList.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'skaaai_get_post_diff',
                post_id: postId,
                nonce: skaaaiPostList.nonce,
            },
            success: function( response ) {
                if ( response.success && response.data ) {
                    showPostDiffModal( response.data, postId, $btn, originalText );
                } else {
                    $btn.prop( 'disabled', false ).removeClass( 'is-loading' );
                    $btn.find( '.skaaai-btn-text' ).text( originalText );
                    alert( response.data?.message || skaaaiPostList.i18n.diff_error );
                }
            },
            error: function( xhr ) {
                $btn.prop( 'disabled', false ).removeClass( 'is-loading' );
                $btn.find( '.skaaai-btn-text' ).text( originalText );
                const res = xhr.responseJSON;
                alert( res?.data?.message || skaaaiPostList.i18n.diff_error );
            }
        } );
    } );

    function executePushRow( postId, $btn, force ) {
        const $cell = $btn.closest( '.skaaai-sync-cell' );
        const originalText = $btn.find( '.skaaai-btn-text' ).text();

        $btn.prop( 'disabled', true ).addClass( 'is-loading' );
        $btn.find( '.skaaai-btn-text' ).text( skaaaiPostList.i18n.pushing );

        $.ajax( {
            url: skaaaiPostList.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'skaaai_push_post',
                post_id: postId,
                nonce: skaaaiPostList.nonce,
                force: force ? 1 : 0,
            },
            success: function( response ) {
                if ( response.success ) {
                    const data = response.data || {};

                    // Cập nhật huy hiệu
                    const $badge = $cell.find( '.skaaai-sync-badge' );
                    $badge
                        .removeClass( 'skaaai-badge-not_synced skaaai-badge-ahead skaaai-badge-remote_ahead' )
                        .addClass( 'skaaai-badge-synced' )
                        .text( '🟢 ' + skaaaiPostList.i18n.pushed )
                        .attr( 'title', 'Last synced: ' + ( data.last_synced || '' ) );

                    // Thêm link xem live nếu có
                    if ( data.permalink && $cell.find( '.skaaai-view-live-link' ).length === 0 ) {
                        const $link = $( '<a>', {
                            href: data.permalink,
                            target: '_blank',
                            rel: 'noopener noreferrer',
                            class: 'skaaai-view-live-link',
                            title: 'View published page on Live host',
                            html: '<span class="dashicons dashicons-external"></span>',
                        } );
                        $cell.find( '.skaaai-sync-actions' ).append( $link );
                    }
                } else {
                    alert( response.data?.message || skaaaiPostList.i18n.push_failed );
                }
            },
            error: function( xhr ) {
                if ( xhr.status === 409 ) {
                    if ( confirm( skaaaiPostList.i18n.confirm_force ) ) {
                        executePushRow( postId, $btn, true );
                        return;
                    }
                } else {
                    const res = xhr.responseJSON;
                    alert( res?.data?.message || skaaaiPostList.i18n.push_failed );
                }
            },
            complete: function() {
                $btn.prop( 'disabled', false ).removeClass( 'is-loading' );
                $btn.find( '.skaaai-btn-text' ).text( originalText );
            },
        } );
    }

    function executePullRow( postId, $btn ) {
        const $cell = $btn.closest( '.skaaai-sync-cell' );
        const originalText = $btn.find( '.skaaai-btn-text' ).text();

        $btn.prop( 'disabled', true ).addClass( 'is-loading' );
        $btn.find( '.skaaai-btn-text' ).text( skaaaiPostList.i18n.pulling );

        $.ajax( {
            url: skaaaiPostList.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'skaaai_pull_post',
                post_id: postId,
                nonce: skaaaiPostList.nonce,
            },
            success: function( response ) {
                if ( response.success ) {
                    const data = response.data || {};

                    // Cập nhật huy hiệu về Synced
                    const $badge = $cell.find( '.skaaai-sync-badge' );
                    $badge
                        .removeClass( 'skaaai-badge-not_synced skaaai-badge-ahead skaaai-badge-remote_ahead' )
                        .addClass( 'skaaai-badge-synced' )
                        .text( '🟢 ' + skaaaiPostList.i18n.pulled )
                        .attr( 'title', 'Last synced: ' + ( data.last_synced || '' ) );

                    // Thêm link xem live nếu có
                    if ( data.remote_permalink && $cell.find( '.skaaai-view-live-link' ).length === 0 ) {
                        const $link = $( '<a>', {
                            href: data.remote_permalink,
                            target: '_blank',
                            rel: 'noopener noreferrer',
                            class: 'skaaai-view-live-link',
                            title: 'View published page on Live host',
                            html: '<span class="dashicons dashicons-external"></span>',
                        } );
                        $cell.find( '.skaaai-sync-actions' ).append( $link );
                    }
                } else {
                    alert( response.data?.message || skaaaiPostList.i18n.pull_failed );
                }
            },
            error: function( xhr ) {
                const res = xhr.responseJSON;
                alert( res?.data?.message || skaaaiPostList.i18n.pull_failed );
            },
            complete: function() {
                $btn.prop( 'disabled', false ).removeClass( 'is-loading' );
                $btn.find( '.skaaai-btn-text' ).text( originalText );
            },
        } );
    }

    // 3. Tự động kiểm tra đối soát trạng thái chênh lệch với Live (Diff Checker)
    function checkRemoteStatuses() {
        const postIds = [];
        $( '.skaaai-sync-cell' ).each( function() {
            const pid = $( this ).data( 'post-id' );
            if ( pid ) {
                postIds.push( pid );
            }
        } );

        if ( postIds.length === 0 ) {
            return;
        }

        $.ajax( {
            url: skaaaiPostList.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'skaaai_check_remote_status',
                post_ids: postIds,
                nonce: skaaaiPostList.nonce,
            },
            success: function( response ) {
                if ( response.success && response.data?.statuses ) {
                    const statuses = response.data.statuses;

                    $( '.skaaai-sync-cell' ).each( function() {
                        const $cell = $( this );
                        const pid = $cell.data( 'post-id' );
                        const $badge = $cell.find( '.skaaai-sync-badge' );

                        // Tìm status theo local_post_id hoặc uuid
                        for ( const key in statuses ) {
                            const item = statuses[ key ];
                            // Kiểm tra nếu status là remote_ahead
                            if ( item.status === 'remote_ahead' ) {
                                // Tìm đúng row
                                const rowPid = $cell.find( '.skaaai-push-row-btn' ).data( 'post-id' );
                                if ( rowPid === pid ) {
                                    $badge
                                        .removeClass( 'skaaai-badge-synced skaaai-badge-ahead skaaai-badge-not_synced' )
                                        .addClass( 'skaaai-badge-remote_ahead' )
                                        .text( '⬇️ ' + skaaaiPostList.i18n.remote_ahead )
                                        .attr( 'title', 'Remote has newer revisions than Localhost' );
                                }
                            }
                        }
                    } );
                }
            },
        } );
    }

    function escHtml( str ) {
        return $( '<div>' ).text( str || '' ).html();
    }

    function showPostDiffModal( diff, postId, $btn, originalText ) {
        const $modal = $( '#skaaai-post-diff-modal' );
        const $body  = $( '#skaaai-post-diff-body' );
        $body.empty();

        const local  = diff.local || {};
        const remote = diff.remote || {};
        const comp   = diff.comparison || {};

        let html = '<div class="skaaai-post-diff-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">';

        // Cột 1: Cục bộ (Localhost)
        html += '<div class="skaaai-diff-col" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;">';
        html += '<h4 style="margin:0 0 10px;color:#475569;display:flex;align-items:center;gap:6px;"><span class="dashicons dashicons-admin-home"></span> Localhost (Current)</h4>';
        html += '<p style="margin:6px 0;font-size:13px;"><strong>Title:</strong> ' + escHtml( local.title ) + '</p>';
        html += '<p style="margin:6px 0;font-size:13px;"><strong>Modified:</strong> <span style="font-family:monospace;font-size:12px;">' + escHtml( local.modified ) + '</span></p>';
        html += '<p style="margin:6px 0;font-size:13px;"><strong>Blocks:</strong> ' + ( local.block_count || 0 ) + ' blocks (' + ( local.content_length || 0 ) + ' chars)</p>';
        if ( local.featured_image ) {
            html += '<div style="margin-top:10px;"><strong>Featured Image:</strong><br><img src="' + escHtml( local.featured_image ) + '" style="max-width:100%;height:70px;object-fit:cover;border-radius:4px;margin-top:4px;border:1px solid #e2e8f0;" /></div>';
        } else {
            html += '<p style="margin:6px 0;font-size:12px;color:#94a3b8;"><em>No featured image</em></p>';
        }
        html += '</div>';

        // Cột 2: Máy chủ Live (Remote)
        html += '<div class="skaaai-diff-col" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;">';
        html += '<h4 style="margin:0 0 10px;color:#166534;display:flex;align-items:center;gap:6px;"><span class="dashicons dashicons-cloud"></span> Live Webhost (Remote)</h4>';
        html += '<p style="margin:6px 0;font-size:13px;"><strong>Title:</strong> ' + escHtml( remote.title ) + ( comp.title_diff ? ' <span style="background:#fef08a;color:#854d0e;font-size:11px;padding:1px 6px;border-radius:4px;font-weight:600;">Changed</span>' : '' ) + '</p>';
        html += '<p style="margin:6px 0;font-size:13px;"><strong>Modified:</strong> <span style="font-family:monospace;font-size:12px;">' + escHtml( remote.modified ) + '</span>' + ( comp.is_remote_newer ? ' <span style="background:#dbeafe;color:#1e40af;font-size:11px;padding:1px 6px;border-radius:4px;font-weight:600;">Newer</span>' : '' ) + '</p>';
        html += '<p style="margin:6px 0;font-size:13px;"><strong>Blocks:</strong> ' + ( remote.block_count || 0 ) + ' blocks (' + ( remote.content_length || 0 ) + ' chars)' + ( comp.blocks_diff ? ' <span style="background:#fef08a;color:#854d0e;font-size:11px;padding:1px 6px;border-radius:4px;font-weight:600;">Changed</span>' : '' ) + '</p>';
        if ( remote.featured_image ) {
            html += '<div style="margin-top:10px;"><strong>Featured Image:</strong>' + ( comp.image_diff ? ' <span style="background:#fef08a;color:#854d0e;font-size:11px;padding:1px 6px;border-radius:4px;font-weight:600;">Changed</span>' : '' ) + '<br><img src="' + escHtml( remote.featured_image ) + '" style="max-width:100%;height:70px;object-fit:cover;border-radius:4px;margin-top:4px;border:1px solid #bbf7d0;" /></div>';
        } else {
            html += '<p style="margin:6px 0;font-size:12px;color:#94a3b8;"><em>No remote featured image</em></p>';
        }
        html += '</div>';

        html += '</div>';

        // Ghi chú an toàn Revision
        html += '<div style="margin-top:14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:8px 12px;font-size:12px;color:#1e40af;display:flex;align-items:center;gap:8px;">';
        html += '<span class="dashicons dashicons-shield" style="font-size:18px;"></span>';
        html += '<span><strong>Safety First:</strong> A WordPress Revision will be saved before overwriting. You can always rollback in Post Revisions.</span>';
        html += '</div>';

        $body.html( html );
        $modal.removeClass( 'hidden' );

        $( '#btn-post-diff-approve' ).off( 'click' ).on( 'click', function() {
            $modal.addClass( 'hidden' );
            executePullRow( postId, $btn );
        } );

        $( '#btn-post-diff-cancel, #btn-post-diff-close' ).off( 'click' ).on( 'click', function() {
            $modal.addClass( 'hidden' );
            $btn.prop( 'disabled', false ).removeClass( 'is-loading' );
            $btn.find( '.skaaai-btn-text' ).text( originalText );
        } );
    }

    // Tự động kích hoạt đối soát ngầm sau khi trang nạp xong 1 giây
    $( function() {
        setTimeout( checkRemoteStatuses, 1000 );
    } );

} )( jQuery );
