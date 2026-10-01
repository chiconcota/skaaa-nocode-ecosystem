/**
 * Skaaai Post List Quick Sync (edit.php)
 *
 * Xử lý sự kiện click nút Push nhanh và cập nhật trạng thái
 * đồng bộ trực tiếp trên bảng danh sách bài viết.
 */
( function( $ ) {
    'use strict';

    if ( typeof skaaaiPostList === 'undefined' ) {
        return;
    }

    $( document ).on( 'click', '.skaaai-push-row-btn', function( e ) {
        e.preventDefault();

        const $btn = $( this );
        const postId = $btn.data( 'post-id' );
        if ( ! postId || $btn.prop( 'disabled' ) ) {
            return;
        }

        executePushRow( postId, $btn, false );
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
                        .removeClass( 'skaaai-badge-not_synced skaaai-badge-ahead' )
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

} )( jQuery );
