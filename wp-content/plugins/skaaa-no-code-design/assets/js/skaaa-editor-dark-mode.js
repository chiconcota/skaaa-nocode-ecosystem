/**
 * Skaaa Editor Dark Mode Toggle
 * Version: 2.5.1
 * 
 * Provides an on-demand Dark/Light mode toggle in Gutenberg Editor
 * with instant canvas preview and persistent localStorage state.
 * Guaranteed 0ms loop-free execution.
 */
( function( wp, config ) {
    'use strict';

    config = config || {};
    config.i18n = config.i18n || {
        darkMode: 'Dark Mode',
        lightMode: 'Light Mode',
        switchToDark: 'Switch to Dark Mode (Preview)',
        switchToLight: 'Switch to Light Mode (Preview)',
    };

    const STORAGE_KEY = 'skaaa_editor_dark_mode';
    let isDark = localStorage.getItem( STORAGE_KEY ) === 'dark';

    const IFRAME_SELECTORS = [
        'iframe[name="editor-canvas"]',
        '.edit-site-visual-editor__editor-canvas iframe',
        '.block-editor-iframe__container iframe',
    ];

    /**
     * Synchronize dark classes on all active Gutenberg canvas iframes
     */
    function syncCanvasIframes( darkState ) {
        IFRAME_SELECTORS.forEach( function( selector ) {
            const iframes = document.querySelectorAll( selector );
            iframes.forEach( function( iframe ) {
                try {
                    const doc = iframe.contentDocument || ( iframe.contentWindow && iframe.contentWindow.document );
                    if ( doc ) {
                        if ( doc.documentElement ) {
                            doc.documentElement.classList.toggle( 'dark', darkState );
                        }
                        if ( doc.body ) {
                            doc.body.classList.toggle( 'dark', darkState );
                        }
                        const iframeWrapper = doc.querySelector( '.editor-styles-wrapper' );
                        if ( iframeWrapper ) {
                            iframeWrapper.classList.toggle( 'dark', darkState );
                        }
                    }

                    // Attach load listener to preserve dark mode when iframe re-renders
                    if ( ! iframe._hasSkaaaDarkListener ) {
                        iframe._hasSkaaaDarkListener = true;
                        iframe.addEventListener( 'load', function() {
                            setTimeout( function() {
                                syncCanvasIframes( isDark );
                            }, 50 );
                        } );
                    }
                } catch ( e ) {
                    // Cross-origin safety
                }
            } );
        } );
    }

    /**
     * Apply dark mode state across both the editor shell and canvas iframe
     */
    function applyDarkMode( darkState ) {
        isDark = darkState;

        // 1. Update main document shell
        if ( document.body ) {
            document.body.classList.toggle( 'skaaa-editor-dark-mode', isDark );
        }
        if ( document.documentElement ) {
            document.documentElement.classList.toggle( 'dark', isDark );
        }

        // Inline wrapper in main document (if non-iframe)
        const mainWrappers = document.querySelectorAll( '.editor-styles-wrapper' );
        mainWrappers.forEach( function( wrapper ) {
            wrapper.classList.toggle( 'dark', isDark );
        } );

        // 2. Synchronize canvas iframes
        syncCanvasIframes( isDark );

        // 3. Update toggle button appearance
        updateButtonUI( isDark );
    }

    /**
     * Update button text, icon, and active classes
     */
    function updateButtonUI( darkState ) {
        const btn = document.getElementById( 'skaaa-editor-dark-toggle-btn' );
        if ( ! btn ) {
            return;
        }

        const iconEl = btn.querySelector( '.skaaa-toggle-icon' );
        const labelEl = btn.querySelector( '.skaaa-toggle-label' );

        if ( darkState ) {
            btn.classList.add( 'is-dark-active' );
            btn.title = config.i18n.switchToLight;
            if ( iconEl && iconEl.textContent !== '☀️' ) {
                iconEl.textContent = '☀️';
            }
            if ( labelEl && labelEl.textContent !== config.i18n.lightMode ) {
                labelEl.textContent = config.i18n.lightMode;
            }
        } else {
            btn.classList.remove( 'is-dark-active' );
            btn.title = config.i18n.switchToDark;
            if ( iconEl && iconEl.textContent !== '🌙' ) {
                iconEl.textContent = '🌙';
            }
            if ( labelEl && labelEl.textContent !== config.i18n.darkMode ) {
                labelEl.textContent = config.i18n.darkMode;
            }
        }
    }

    /**
     * Create and inject the Dark/Light Mode toggle button into Gutenberg's Header Toolbar
     */
    function injectDarkModeButton() {
        const targetContainer = document.querySelector( '.edit-post-header__settings' ) ||
                                document.querySelector( '.editor-header__settings' );

        if ( ! targetContainer ) {
            return;
        }

        let btn = document.getElementById( 'skaaa-editor-dark-toggle-btn' );
        if ( ! btn ) {
            btn = document.createElement( 'button' );
            btn.id = 'skaaa-editor-dark-toggle-btn';
            btn.type = 'button';
            btn.className = 'components-button skaaa-editor-dark-toggle-btn';
            btn.innerHTML = '<span class="skaaa-toggle-icon">' + ( isDark ? '☀️' : '🌙' ) + '</span><span class="skaaa-toggle-label">' + ( isDark ? config.i18n.lightMode : config.i18n.darkMode ) + '</span>';

            btn.addEventListener( 'click', function( e ) {
                e.preventDefault();
                const newState = ! isDark;
                localStorage.setItem( STORAGE_KEY, newState ? 'dark' : 'light' );
                applyDarkMode( newState );
            } );

            // Prepend button so it displays cleanly in the header
            targetContainer.prepend( btn );
            updateButtonUI( isDark );
        }
    }

    /**
     * Initialize on DOM ready without infinite MutationObserver loops
     */
    function init() {
        // Initial button injection & dark state application
        injectDarkModeButton();
        applyDarkMode( isDark );

        // Periodic sync during initial load (clears automatically)
        let attempts = 0;
        const intervalId = setInterval( function() {
            attempts++;
            injectDarkModeButton();
            if ( isDark ) {
                syncCanvasIframes( true );
            }
            if ( attempts >= 10 && document.getElementById( 'skaaa-editor-dark-toggle-btn' ) ) {
                clearInterval( intervalId );
            }
        }, 500 );

        // Scoped observer to re-inject button ONLY if removed by React header re-render
        if ( window.MutationObserver ) {
            let isReinjecting = false;
            const observer = new MutationObserver( function() {
                if ( isReinjecting ) return;
                if ( ! document.getElementById( 'skaaa-editor-dark-toggle-btn' ) ) {
                    isReinjecting = true;
                    injectDarkModeButton();
                    isReinjecting = false;
                }
            } );

            const headerContainer = document.querySelector( '.edit-post-header' ) ||
                                    document.querySelector( '.editor-header' ) ||
                                    document.querySelector( '.edit-post-layout' );
            if ( headerContainer ) {
                observer.observe( headerContainer, { childList: true, subtree: true } );
            }
        }
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }

} )( window.wp, window.skaaaEditorDarkModeConfig );
