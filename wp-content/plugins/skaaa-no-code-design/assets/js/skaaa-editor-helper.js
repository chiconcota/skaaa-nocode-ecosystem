(function () {
    let compilerInstance = null;
    let previousHash = '';
    let lastUnresolvedString = '';
    let activeIframeDoc = null; // Lưu trữ document hiện tại của iframe để tránh gán trùng lặp
    const stylesheetsMap = new Map(); // doc -> CSSStyleSheet
    const FONT_STYLESHEET_URL = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap';

    /**
     * Ensure Google Fonts Material Symbols stylesheet is loaded via <link> in document head.
     */
    function ensureFontLink(doc) {
        try {
            if (!doc) return;
            let link = doc.getElementById('skaaa-material-symbols-font');
            if (!link) {
                link = doc.createElement('link');
                link.id = 'skaaa-material-symbols-font';
                link.rel = 'stylesheet';
                link.href = FONT_STYLESHEET_URL;
                const container = doc.head || doc.body;
                if (container) {
                    container.appendChild(link);
                }
            }
        } catch (e) {
            console.error('Skaaa font link injection error:', e);
        }
    }

    // Gutenberg Editor CSS Fixes (will be bundled with compiled JIT CSS)
    const editorFixesCss = `
        /* Nuke Gutenberg Input Overrides with structural specificity (no !important) so Tailwind's !important wins */
        .editor-styles-wrapper .block-editor-block-list__block.wp-block-skaaaaa-builder-input:not([type="checkbox"]):not([type="radio"]),
        .editor-styles-wrapper .block-editor-block-list__block.wp-block-skaaaaa-builder-select select {
            appearance: none;
            background-color: transparent;
            border-width: 0;
            border-style: solid;
            border-color: #e5e7eb; /* Parity with frontend base reset */
            border-radius: 0;
            padding: 0;
            outline-width: 0;
            outline-style: solid;
            outline-color: transparent;
            margin: 0;
            box-shadow: none;
        }

        /* Tailwind V4 Layout Parity Shims for Editor */
        .editor-styles-wrapper .\\-outline-offset-1 { outline-offset: -1px; }
        .editor-styles-wrapper .\\-outline-offset-2 { outline-offset: -2px; }
        .editor-styles-wrapper .focus\\:\\-outline-offset-1:focus, 
        .editor-styles-wrapper .focus\\:\\-outline-offset-1:focus-within { outline-offset: -1px; }
        .editor-styles-wrapper .focus\\:\\-outline-offset-2:focus, 
        .editor-styles-wrapper .focus\\:\\-outline-offset-2:focus-within { outline-offset: -2px; }

        /* V4 Polyfill: Indeterminate & Group-Has State for SVG Checkboxes (JIT CSS handles dynamically, this is helper) */
        .editor-styles-wrapper .group:has(:checked) .group-has-checked\\:opacity-100 { opacity: 1; }
        .editor-styles-wrapper .group:has(:disabled) .group-has-disabled\\:stroke-gray-950\\/25 { stroke: rgba(3, 7, 18, 0.25); }
        .editor-styles-wrapper :indeterminate.indeterminate\\:bg-indigo-600 { background-color: #4f46e5; }
        .editor-styles-wrapper :indeterminate.indeterminate\\:border-indigo-600 { border-color: #4f46e5; }
        .editor-styles-wrapper :disabled:checked.disabled\\:checked\\:bg-gray-100 { background-color: #f3f4f6; }

        /* Ensure outline width utilities force solid style if Preflight is disabled */
        .editor-styles-wrapper [class*="outline-1"], 
        .editor-styles-wrapper [class*="outline-2"], 
        .editor-styles-wrapper [class*="outline-4"], 
        .editor-styles-wrapper [class*="outline-8"] {
            outline-style: solid;
        }

        /* Force root container to not be a flexbox */
        .editor-styles-wrapper.editor-styles-wrapper .is-root-container.block-editor-block-list__layout {
            display: block;
        }

        /* Remove unwanted 1px border on Skaaa Button blocks in editor */
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-button:not(.border) {
            border: none;
        }

        /* Ensure border class always works (preflight: false may not set border-style: solid) */
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container.border {
            border-style: solid;
        }

        /* Container, video block and nested inner blocks layout preservation */
        .editor-styles-wrapper .wp-block-skaaaaa-builder-container > .block-editor-inner-blocks,
        .editor-styles-wrapper .wp-block-skaaaaa-builder-container > .block-editor-inner-blocks > .block-editor-block-list__layout,
        .editor-styles-wrapper .wp-block-skaaaaa-builder-container:not(.block-editor-block-list__layout) > .block-editor-block-list__layout,
        .editor-styles-wrapper .wp-block-skaaaaa-builder-video .block-editor-inner-blocks,
        .editor-styles-wrapper .wp-block-skaaaaa-builder-video .block-editor-block-list__layout,
        .editor-styles-wrapper [class*='wp-block-skaaaaa-builder'] > .wp-block-html,
        .editor-styles-wrapper .skaaapine-wrapper,
        .editor-styles-wrapper .skaaa-dynamic-binding-wrapper {
            display: contents;
        }

        /* Video block specific editor fixes */
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-video {
            isolation: isolate;
        }

        .wp-block-skaaaaa-builder-video .skaaa-video-wrapper {
            min-width: 100%;
        }

        .wp-block-skaaaaa-builder-container > .wp-block {
            margin-left: 0;
            margin-right: 0;
        }

        /* Flex row & Grid children resets */
        .wp-block-skaaaaa-builder-container[class*="flex"]:not([class*="flex-col"]) > .wp-block:not([class*="w-"]):not([class*="size-"]):not(.container),
        .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:not([class*="w-"]):not([class*="size-"]):not(.container) {
            width: auto;
            max-width: none;
        }

        .wp-block-skaaaaa-builder-container:not([class*="flex"]):not([class*="grid"]) > .wp-block:not([class*="max-w-"]):not(.container):not([class*="size-"]):not([class*="w-"]),
        .wp-block-skaaaaa-builder-container[class*="flex-col"] > .wp-block:not([class*="max-w-"]):not(.container):not([class*="size-"]):not([class*="w-"]) {
            width: auto;
            max-width: none;
        }

        /* Ensure .container works by allowing margin: auto and limits */
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.container {
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        /* Flex item utility pass-through to Gutenberg wrapper */
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.flex-1 { flex: 1 1 0%; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.flex-auto { flex: 1 1 auto; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.flex-initial { flex: 0 1 auto; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.flex-none { flex: none; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.self-auto { align-self: auto; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.self-start { align-self: flex-start; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.self-end { align-self: flex-end; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.self-center { align-self: center; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.self-stretch { align-self: stretch; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.justify-self-auto { justify-self: auto; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.justify-self-start { justify-self: start; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.justify-self-end { justify-self: end; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.justify-self-center { justify-self: center; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.justify-self-stretch { justify-self: stretch; }

        /* Grid item col-span pass-through to Gutenberg wrapper (Unprefixed / Mobile-first) */
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-12"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-12"] { grid-column: span 12 / span 12; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-11"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-11"] { grid-column: span 11 / span 11; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-10"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-10"] { grid-column: span 10 / span 10; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-9"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-9"] { grid-column: span 9 / span 9; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-8"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-8"] { grid-column: span 8 / span 8; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-7"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-7"] { grid-column: span 7 / span 7; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-6"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-6"] { grid-column: span 6 / span 6; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-5"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-5"] { grid-column: span 5 / span 5; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-4"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-4"] { grid-column: span 4 / span 4; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-3"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-3"] { grid-column: span 3 / span 3; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-2"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-2"] { grid-column: span 2 / span 2; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-1"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-1"] { grid-column: span 1 / span 1; }
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="col-span-full"]),
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="col-span-full"] { grid-column: 1 / -1; }

        /* Responsive Grid item col-span pass-through */
        @media (min-width: 640px) {
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-12"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-12"] { grid-column: span 12 / span 12; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-11"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-11"] { grid-column: span 11 / span 11; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-10"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-10"] { grid-column: span 10 / span 10; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-9"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-9"] { grid-column: span 9 / span 9; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-8"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-8"] { grid-column: span 8 / span 8; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-7"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-7"] { grid-column: span 7 / span 7; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-6"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-6"] { grid-column: span 6 / span 6; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-5"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-5"] { grid-column: span 5 / span 5; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-4"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-4"] { grid-column: span 4 / span 4; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-3"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-3"] { grid-column: span 3 / span 3; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-2"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-2"] { grid-column: span 2 / span 2; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-1"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-1"] { grid-column: span 1 / span 1; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="sm:col-span-full"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="sm:col-span-full"] { grid-column: 1 / -1; }
        }

        @media (min-width: 768px) {
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-12"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-12"] { grid-column: span 12 / span 12; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-11"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-11"] { grid-column: span 11 / span 11; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-10"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-10"] { grid-column: span 10 / span 10; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-9"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-9"] { grid-column: span 9 / span 9; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-8"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-8"] { grid-column: span 8 / span 8; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-7"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-7"] { grid-column: span 7 / span 7; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-6"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-6"] { grid-column: span 6 / span 6; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-5"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-5"] { grid-column: span 5 / span 5; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-4"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-4"] { grid-column: span 4 / span 4; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-3"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-3"] { grid-column: span 3 / span 3; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-2"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-2"] { grid-column: span 2 / span 2; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-1"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-1"] { grid-column: span 1 / span 1; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="md:col-span-full"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="md:col-span-full"] { grid-column: 1 / -1; }
        }

        @media (min-width: 1024px) {
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-12"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-12"] { grid-column: span 12 / span 12; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-11"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-11"] { grid-column: span 11 / span 11; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-10"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-10"] { grid-column: span 10 / span 10; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-9"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-9"] { grid-column: span 9 / span 9; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-8"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-8"] { grid-column: span 8 / span 8; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-7"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-7"] { grid-column: span 7 / span 7; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-6"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-6"] { grid-column: span 6 / span 6; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-5"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-5"] { grid-column: span 5 / span 5; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-4"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-4"] { grid-column: span 4 / span 4; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-3"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-3"] { grid-column: span 3 / span 3; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-2"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-2"] { grid-column: span 2 / span 2; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-1"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-1"] { grid-column: span 1 / span 1; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="lg:col-span-full"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="lg:col-span-full"] { grid-column: 1 / -1; }
        }

        @media (min-width: 1280px) {
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-12"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-12"] { grid-column: span 12 / span 12; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-11"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-11"] { grid-column: span 11 / span 11; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-10"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-10"] { grid-column: span 10 / span 10; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-9"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-9"] { grid-column: span 9 / span 9; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-8"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-8"] { grid-column: span 8 / span 8; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-7"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-7"] { grid-column: span 7 / span 7; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-6"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-6"] { grid-column: span 6 / span 6; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-5"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-5"] { grid-column: span 5 / span 5; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-4"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-4"] { grid-column: span 4 / span 4; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-3"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-3"] { grid-column: span 3 / span 3; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-2"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-2"] { grid-column: span 2 / span 2; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-1"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-1"] { grid-column: span 1 / span 1; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="xl:col-span-full"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="xl:col-span-full"] { grid-column: 1 / -1; }
        }

        @media (min-width: 1536px) {
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-12"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-12"] { grid-column: span 12 / span 12; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-11"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-11"] { grid-column: span 11 / span 11; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-10"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-10"] { grid-column: span 10 / span 10; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-9"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-9"] { grid-column: span 9 / span 9; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-8"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-8"] { grid-column: span 8 / span 8; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-7"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-7"] { grid-column: span 7 / span 7; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-6"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-6"] { grid-column: span 6 / span 6; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-5"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-5"] { grid-column: span 5 / span 5; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-4"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-4"] { grid-column: span 4 / span 4; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-3"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-3"] { grid-column: span 3 / span 3; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-2"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-2"] { grid-column: span 2 / span 2; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-1"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-1"] { grid-column: span 1 / span 1; }
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block:has(> [class~="2xl:col-span-full"]),
            .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="grid"] > .wp-block[class~="2xl:col-span-full"] { grid-column: 1 / -1; }
        }

        /* POSITIONING OVERRIDES (CRITICAL) */
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container.absolute,
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container.absolute.block-editor-block-list__layout {
            position: absolute;
            width: auto;
            height: auto;
            margin: 0;
        }

        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.absolute {
            position: absolute;
            width: auto;
            height: auto;
            margin: 0;
        }

        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container.sticky,
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container.sticky.block-editor-block-list__layout,
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.sticky {
            position: sticky;
            width: auto;
            height: auto;
        }

        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container.relative,
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container.relative.block-editor-block-list__layout,
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block.relative {
            position: relative;
        }

        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container[class*="inset-"],
        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container > .wp-block[class*="inset-"] {
            width: auto;
            height: auto;
        }

        .editor-styles-wrapper.editor-styles-wrapper .wp-block-skaaaaa-builder-container.flex-col:not([class*="items-"]) > .wp-block {
            align-self: stretch;
        }
    `;

    function getCompiler() {
        if (!compilerInstance && window.SkaaaWindCompiler) {
            compilerInstance = new window.SkaaaWindCompiler();
        }
        return compilerInstance;
    }

    /**
     * Extract classes from raw HTML string (e.g. from organisms cache or templates)
     */
    function extractClassesFromHtml(html, classArray) {
        if (!html || typeof html !== 'string') return;
        const classRegex = /class=["']([^"']+)["']/g;
        let match;
        while ((match = classRegex.exec(html)) !== null) {
            if (match[1]) {
                const parts = match[1].split(/\s+/).filter(Boolean);
                parts.forEach(cls => classArray.push(cls));
            }
        }
    }

    /**
     * Run JIT compilation on all classes collected from Gutenberg block tree,
     * referenced Organisms, and the live editor canvas DOM elements.
     */
    function runJITCompilation() {
        const compiler = getCompiler();
        if (!compiler) return;

        const classes = [];

        // 1. Scan Gutenberg Block Tree
        if (window.wp && window.wp.data && window.wp.data.select) {
            const select = window.wp.data.select;
            if (select('core/block-editor')) {
                const blocks = select('core/block-editor').getBlocks();

                function scanBlocks(blockList) {
                    if (!blockList) return;
                    blockList.forEach(block => {
                        if (block.attributes) {
                            if (block.attributes.tailwindClasses) {
                                classes.push(block.attributes.tailwindClasses);
                            }
                            if (block.attributes.className) {
                                classes.push(block.attributes.className);
                            }

                            // Support for Organism References (Skaaa Symbol)
                            if (block.name === 'skaaaaa-builder/organism-ref' && block.attributes.organismId) {
                                const orgId = String(block.attributes.organismId);
                                const cache = window.skaaaOrganismsCache || {};
                                const org = cache[orgId] || Object.values(cache).find(o => String(o.id) === orgId);
                                if (org && org.html_content) {
                                    extractClassesFromHtml(org.html_content, classes);
                                }
                            }

                            // Support for Skaaa Loop Slots referencing Organisms
                            if (block.name === 'skaaaaa-builder/loop' && Array.isArray(block.attributes.slots)) {
                                const cache = window.skaaaOrganismsCache || {};
                                block.attributes.slots.forEach(slot => {
                                    if (slot && slot.organismId) {
                                        const orgId = String(slot.organismId);
                                        const org = cache[orgId] || Object.values(cache).find(o => String(o.id) === orgId);
                                        if (org && org.html_content) {
                                            extractClassesFromHtml(org.html_content, classes);
                                        }
                                    }
                                });
                            }

                            // Support for htmlAttributes (e.g. Alpine x-transition)
                            if (Array.isArray(block.attributes.htmlAttributes)) {
                                block.attributes.htmlAttributes.forEach(attr => {
                                    if (attr && typeof attr.value === 'string') {
                                        const attrVals = attr.value.split(/\s+/).filter(Boolean);
                                        attrVals.forEach(v => classes.push(v));
                                    }
                                });
                            }
                        }
                        if (block.innerBlocks && block.innerBlocks.length > 0) {
                            scanBlocks(block.innerBlocks);
                        }
                    });
                }

                scanBlocks(blocks);
            }
        }

        // 2. Scan Live Editor Canvas DOM (Catches ServerSideRendered elements like Organisms, widgets, dynamic blocks)
        const targetDoc = activeIframeDoc || (document.querySelector('.block-editor-block-list__layout') ? document : null);
        if (targetDoc) {
            try {
                const elements = targetDoc.querySelectorAll('[class]');
                elements.forEach(el => {
                    const classAttr = el.getAttribute('class');
                    if (classAttr && typeof classAttr === 'string') {
                        // Skip Gutenberg internal UI controls to keep compilation lean
                        const parts = classAttr.split(/\s+/).filter(Boolean);
                        parts.forEach(cls => {
                            if (!cls.startsWith('components-') && !cls.startsWith('block-editor-') && !cls.startsWith('editor-')) {
                                classes.push(cls);
                            }
                        });
                    }
                });
            } catch (e) {}
        }

        // Deduplicate classes
        const uniqueClasses = [...new Set(classes.join(' ').split(/\s+/).filter(Boolean))];
        const allClassStr = uniqueClasses.join(' ');

        if (allClassStr !== previousHash) {
            previousHash = allClassStr;

            const compiled = compiler.compile(allClassStr);
            
            // Build absolute unified stylesheet (Resets + Brand colors + Editor Fixes + Compiled classes)
            const brandColorsCss = (window.skaaaEditorConfig && window.skaaaEditorConfig.brandColorsCss) || '';
            const fontImport = `@import url('${FONT_STYLESHEET_URL}');`;
            const unifiedCss = `${fontImport}\n${brandColorsCss}\n${editorFixesCss}\n${compiled.css}`;

            updateEditorStylesheets(unifiedCss);

            // Log unresolved classes to console
            if (compiled.unresolved && compiled.unresolved.length > 0) {
                const unresolvedStr = compiled.unresolved.join(', ');
                if (unresolvedStr !== lastUnresolvedString) {
                    lastUnresolvedString = unresolvedStr;
                    console.warn(`[SkaaaWind Warning] The following classes are not supported by the local JIT compiler: [${unresolvedStr}]`);
                }
            } else {
                lastUnresolvedString = '';
            }
        }
    }

    /**
     * Inject stylesheet into the target document using DOM style tags.
     */
    function updateDocStyle(doc, css) {
        try {
            ensureFontLink(doc);

            let style = doc.getElementById('skaaawind-compiled-css');
            
            if (!style) {
                style = doc.createElement('style');
                style.id = 'skaaawind-compiled-css';
                
                const container = doc.head || doc.body;
                if (container) {
                    container.appendChild(style);
                }
            }
            
            if (style) {
                style.innerHTML = css;
            }
        } catch (e) {
            console.error('SkaaaWind stylesheet injection error:', e);
        }
    }

    /**
     * Inject style blocks into all active iframe contexts.
     * Prevents main document style injection to completely eliminate Gutenberg warning.
     */
    function updateEditorStylesheets(css) {
        const iframeSelectors = [
            'iframe[name="editor-canvas"]',
            '.edit-site-visual-editor__editor-canvas iframe',
            '.block-editor-iframe__container iframe'
        ];

        iframeSelectors.forEach(selector => {
            const iframes = document.querySelectorAll(selector);
            iframes.forEach(iframe => {
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    if (doc) {
                        updateDocStyle(doc, css);
                    }
                } catch (e) {}
            });
        });
    }

    // Subscribe to Gutenberg store changes
    if (window.wp && window.wp.data) {
        window.wp.data.subscribe(() => {
            try {
                runJITCompilation();
            } catch (e) {
                console.error('Skaaa JIT compilation error in subscriber:', e);
            }
        });
    }

    /**
     * Inject SkaaaWind offline stylesheet context.
     */
    function injectTailwind(doc) {
        if (!doc || doc === window.document) return;

        // 1. Tạo đồng bộ thẻ style trong iframe ngay lập tức để bypass Gutenberg warning check
        ensureFontLink(doc);

        let style = doc.getElementById('skaaawind-compiled-css');
        if (!style) {
            style = doc.createElement('style');
            style.id = 'skaaawind-compiled-css';
            const container = doc.body || doc.head;
            if (container) {
                container.appendChild(style);
            }
        }

        // 2. Chạy biên dịch đồng bộ lần đầu
        runJITCompilation();

        // 3. Khôi phục trạng thái Dark Mode nếu đang được kích hoạt
        try {
            if (localStorage.getItem('skaaa_editor_dark_mode') === 'dark') {
                if (doc.documentElement) doc.documentElement.classList.add('dark');
                if (doc.body) doc.body.classList.add('dark');
                const wrapper = doc.querySelector('.editor-styles-wrapper');
                if (wrapper) wrapper.classList.add('dark');
            }
        } catch (e) {}

        // MutationObserver for Dynamic Elements (e.g. video, ServerSideRendered Organisms)
        const observer = new MutationObserver((mutations) => {
            let shouldRecompile = false;
            mutations.forEach((mutation) => {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    shouldRecompile = true;
                } else if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    shouldRecompile = true;
                }
            });
            if (shouldRecompile) {
                runJITCompilation();
            }
        });

        observer.observe(doc.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
        console.log('Skaaa Builder: SkaaaWind offline JIT compiler active.');
    }

    /**
     * Scan the main document for the editor canvas iframe.
     */
    function scanForIframes() {
        const iframeSelectors = [
            'iframe[name="editor-canvas"]',
            '.edit-site-visual-editor__editor-canvas iframe',
            '.block-editor-iframe__container iframe'
        ];

        let found = false;
        iframeSelectors.forEach(selector => {
            const iframes = document.querySelectorAll(selector);
            iframes.forEach(iframe => {
                found = true;
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    if (doc) {
                        // Chỉ chạy inject khi phát hiện iframe mới (document context thay đổi)
                        if (doc !== activeIframeDoc) {
                            activeIframeDoc = doc;
                            previousHash = ''; // Reset một lần duy nhất để force compile cho context mới
                            
                            if (doc.readyState === 'complete' || doc.readyState === 'interactive') {
                                injectTailwind(doc);
                            } else {
                                iframe.addEventListener('load', () => {
                                    activeIframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                    previousHash = '';
                                    injectTailwind(activeIframeDoc);
                                }, { once: true });
                            }
                        }
                    }
                } catch (e) { }
            });
        });

        if (!found) {
            // Adopt style in main doc if block editor layout is present directly (non-iframe mode)
            if (document.querySelector('.block-editor-block-list__layout')) {
                if (activeIframeDoc !== document) {
                    activeIframeDoc = document;
                    previousHash = '';
                    injectTailwind(document);
                }
            }
        }
    }

    /**
     * Set up a MutationObserver to watch for the iframe being added.
     */
    function observeEditor() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    scanForIframes();
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Inject UI refinements into the MAIN document.
     */
    function injectMainDocStyles() {
        ensureFontLink(document);

        if (document.getElementById('skaaa-main-ui-refinements')) return;

        const style = document.createElement('style');
        style.id = 'skaaa-main-ui-refinements';
        style.innerHTML = `
            body.wp-admin.wp-admin .interface-complementary-area .components-panel__body,
            body.wp-admin.wp-admin .interface-complementary-area .components-panel__row,
            body.wp-admin.wp-admin .interface-complementary-area {
                border-left: none;
            }

            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area input[type="text"],
            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area input[type="number"],
            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area input[type="url"],
            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area input[type="email"],
            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area input[type="search"],
            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area input.components-text-control__input,
            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area textarea.components-textarea-control__input,
            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area select.components-select-control__input,
            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area .components-base-control__help {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                font-size: 14px;
                overflow: visible;
            }

            body.wp-admin.wp-admin.skaaaaa-builder .interface-complementary-area {
                --wp-admin-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }

            .skaaa-editor-active-indicator {
                position: fixed; 
                bottom: 12px; 
                right: 12px; 
                color: #10b981; 
                font-family: 'Material Symbols Outlined';
                font-size: 18px;
                z-index: 999999; 
                pointer-events: none;
                text-shadow: 0 0 5px rgba(16, 185, 129, 0.4);
            }
        `;
        if (document.body) {
            document.body.appendChild(style);
        } else {
            document.head.appendChild(style);
        }
    }

    // Initial scan
    if (document.readyState === 'complete') {
        injectMainDocStyles();
        scanForIframes();
        observeEditor();
    } else {
        window.addEventListener('load', () => {
            injectMainDocStyles();
            scanForIframes();
            observeEditor();
        });
    }

    // Fail-safe poll
    setInterval(() => {
        injectMainDocStyles();
        scanForIframes();
    }, 5000);
})();
