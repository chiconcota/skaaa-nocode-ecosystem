/**
 * SkaaaWind JS Compiler - Client-side Tailwind JIT Compiler
 * Ported 1:1 from PHP Tailwind_Compiler and Tailwind_Config.
 * 
 * Works 100% offline inside Gutenberg Editor.
 */

class SkaaaWindCompiler {
    static mediaQueries = {
        'sm': '@media (min-width: 640px) { ',
        'md': '@media (min-width: 768px) { ',
        'lg': '@media (min-width: 1024px) { ',
        'xl': '@media (min-width: 1280px) { ',
        '2xl': '@media (min-width: 1536px) { ',
        'max-sm': '@media (max-width: 639px) { ',
        'max-md': '@media (max-width: 767px) { ',
        'max-lg': '@media (max-width: 1023px) { ',
        'max-xl': '@media (max-width: 1279px) { ',
        'max-2xl': '@media (max-width: 1535px) { ',
    };

    static basicColors = {
        'text-white': 'color: #ffffff;',
        'text-black': 'color: #000000;',
        'bg-white': 'background-color: #ffffff;',
        'bg-black': 'background-color: #000000;',
        'bg-transparent': 'background-color: transparent;',
    };

    static fontFamily = {
        'font-sans': 'font-family: var(--font-primary, ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji");',
        'font-serif': 'font-family: var(--font-secondary, ui-serif, Georgia, Cambria, "Times New Roman", Times, serif);',
        'font-mono': 'font-family: var(--font-mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);',
    };

    static weights = {
        'font-thin': 'font-weight: 100;',
        'font-light': 'font-weight: 300;',
        'font-normal': 'font-weight: 400;',
        'font-medium': 'font-weight: 500;',
        'font-semibold': 'font-weight: 600;',
        'font-bold': 'font-weight: 700;',
        'font-extrabold': 'font-weight: 800;',
        'font-black': 'font-weight: 900;',
    };

    static shadowMap = {
        'shadow-sm': 'box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);',
        'shadow': 'box-shadow: var(--skaaa-box-shadow, 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06));',
        'shadow-md': 'box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);',
        'shadow-lg': 'box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);',
        'shadow-xl': 'box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);',
        'shadow-2xl': 'box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);',
        'shadow-none': 'box-shadow: none;',
    };

    static maxWMap = {
        'xs': '20rem', 'sm': '24rem', 'md': '28rem', 'lg': '32rem', 'xl': '36rem',
        '2xl': '42rem', '3xl': '48rem', '4xl': '56rem', '5xl': '64rem', '6xl': '72rem',
        '7xl': '80rem', 'full': '100%', 'none': 'none',
    };

    static marginAutoMap = {
        'mx-auto': 'margin-left: auto; margin-right: auto;',
        'my-auto': 'margin-top: auto; margin-bottom: auto;',
        'ml-auto': 'margin-left: auto;',
        'mr-auto': 'margin-right: auto;',
        'mt-auto': 'margin-top: auto;',
        'mb-auto': 'margin-bottom: auto;',
    };

    static layoutMap = {
        'flex': 'display: flex;',
        'inline-flex': 'display: inline-flex;',
        'grid': 'display: grid;',
        'inline-grid': 'display: inline-grid;',
        'flex-col': 'flex-direction: column;',
        'flex-col-reverse': 'flex-direction: column-reverse;',
        'flex-row': 'flex-direction: row;',
        'flex-row-reverse': 'flex-direction: row-reverse;',
        'flex-wrap': 'flex-wrap: wrap;',
        'flex-wrap-reverse': 'flex-wrap: wrap-reverse;',
        'flex-nowrap': 'flex-wrap: nowrap;',
        'items-start': 'align-items: flex-start;',
        'items-center': 'align-items: center;',
        'items-end': 'align-items: flex-end;',
        'items-stretch': 'align-items: stretch;',
        'justify-start': 'justify-content: flex-start;',
        'justify-center': 'justify-content: center;',
        'justify-end': 'justify-content: flex-end;',
        'justify-between': 'justify-content: space-between;',
        'justify-around': 'justify-content: space-around;',
    };

    static sizeMap = {
        'xs': 'font-size: 0.75rem; line-height: 1rem;',
        'sm': 'font-size: 0.875rem; line-height: 1.25rem;',
        'base': 'font-size: 1rem; line-height: 1.5rem;',
        'lg': 'font-size: 1.125rem; line-height: 1.75rem;',
        'xl': 'font-size: 1.25rem; line-height: 1.75rem;',
        '2xl': 'font-size: 1.5rem; line-height: 2rem;',
        '3xl': 'font-size: 1.875rem; line-height: 2.25rem;',
        '4xl': 'font-size: 2.25rem; line-height: 2.5rem;',
        '5xl': 'font-size: 3rem; line-height: 1;',
        '6xl': 'font-size: 3.75rem; line-height: 1;',
        '7xl': 'font-size: 4.5rem; line-height: 1;',
        '8xl': 'font-size: 6rem; line-height: 1;',
        '9xl': 'font-size: 8rem; line-height: 1;',
    };

    static textAlignMap = {
        'text-left': 'text-align: left;',
        'text-center': 'text-align: center;',
        'text-right': 'text-align: right;',
        'text-justify': 'text-align: justify;',
    };

    static textDecoMap = {
        'underline': 'text-decoration-line: underline;',
        'overline': 'text-decoration-line: overline;',
        'line-through': 'text-decoration-line: line-through;',
        'no-underline': 'text-decoration-line: none;',
    };

    static textMiscMap = {
        'uppercase': 'text-transform: uppercase;',
        'lowercase': 'text-transform: lowercase;',
        'capitalize': 'text-transform: capitalize;',
        'normal-case': 'text-transform: none;',
        'italic': 'font-style: italic;',
        'not-italic': 'font-style: normal;',
        'truncate': 'overflow: hidden; text-overflow: ellipsis; white-space: nowrap;',
        'antialiased': '-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;',
        'subpixel-antialiased': '-webkit-font-smoothing: auto; -moz-osx-font-smoothing: auto;',
    };

    static whitespaceMap = {
        'whitespace-normal': 'white-space: normal;',
        'whitespace-nowrap': 'white-space: nowrap;',
        'whitespace-pre': 'white-space: pre;',
        'whitespace-pre-line': 'white-space: pre-line;',
        'whitespace-pre-wrap': 'white-space: pre-wrap;',
        'whitespace-break-spaces': 'white-space: break-spaces;',
    };

    static leadingMap = {
        'none': '1',
        'tight': '1.25',
        'snug': '1.375',
        'normal': '1.5',
        'relaxed': '1.625',
        'loose': '2',
    };

    static trackingMap = {
        'tighter': '-0.05em',
        'tight': '-0.025em',
        'normal': '0em',
        'wide': '0.025em',
        'wider': '0.05em',
        'widest': '0.1em',
    };

    static borderStyleMap = {
        'border-solid': 'border-style: solid;',
        'border-dashed': 'border-style: dashed;',
        'border-dotted': 'border-style: dotted;',
        'border-double': 'border-style: double;',
        'border-hidden': 'border-style: hidden;',
        'border-none': 'border-style: none;',
    };

    static borderBasicMap = {
        'border-white': 'border-color: #ffffff;',
        'border-black': 'border-color: #000000;',
        'border-transparent': 'border-color: transparent;',
    };

    static ringBasic = {
        'ring-white': '--tw-ring-color: #ffffff;',
        'ring-black': '--tw-ring-color: #000000;',
        'ring-transparent': '--tw-ring-color: transparent;',
    };

    static ringOffsetBasic = {
        'ring-offset-white': '--tw-ring-offset-color: #ffffff;',
        'ring-offset-black': '--tw-ring-offset-color: #000000;',
    };

    static bgUtilMap = {
        'bg-cover': 'background-size: cover;',
        'bg-contain': 'background-size: contain;',
        'bg-auto': 'background-size: auto;',
        'bg-center': 'background-position: center;',
        'bg-top': 'background-position: top;',
        'bg-bottom': 'background-position: bottom;',
        'bg-left': 'background-position: left;',
        'bg-right': 'background-position: right;',
        'bg-left-top': 'background-position: left top;',
        'bg-right-top': 'background-position: right top;',
        'bg-left-bottom': 'background-position: left bottom;',
        'bg-right-bottom': 'background-position: right bottom;',
        'bg-repeat': 'background-repeat: repeat;',
        'bg-no-repeat': 'background-repeat: no-repeat;',
        'bg-repeat-x': 'background-repeat: repeat-x;',
        'bg-repeat-y': 'background-repeat: repeat-y;',
        'bg-repeat-round': 'background-repeat: round;',
        'bg-repeat-space': 'background-repeat: space;',
        'bg-fixed': 'background-attachment: fixed;',
        'bg-local': 'background-attachment: local;',
        'bg-scroll': 'background-attachment: scroll;',
        'bg-clip-border': 'background-clip: border-box;',
        'bg-clip-padding': 'background-clip: padding-box;',
        'bg-clip-content': 'background-clip: content-box;',
        'bg-clip-text': '-webkit-background-clip: text; background-clip: text;',
        'bg-origin-border': 'background-origin: border-box;',
        'bg-origin-padding': 'background-origin: padding-box;',
        'bg-origin-content': 'background-origin: content-box;',
    };

    static gradBasic = {
        'from-white': '--tw-gradient-from: #ffffff; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);',
        'from-black': '--tw-gradient-from: #000000; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);',
        'from-transparent': '--tw-gradient-from: transparent; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);',
        'to-white': '--tw-gradient-to: #ffffff;',
        'to-black': '--tw-gradient-to: #000000;',
        'to-transparent': '--tw-gradient-to: transparent;',
        'via-white': '--tw-gradient-stops: var(--tw-gradient-from), #ffffff, var(--tw-gradient-to, transparent);',
        'via-black': '--tw-gradient-stops: var(--tw-gradient-from), #000000, var(--tw-gradient-to, transparent);',
        'via-transparent': '--tw-gradient-stops: var(--tw-gradient-from), transparent, var(--tw-gradient-to, transparent);',
    };

    static radiusMap = {
        '': 'var(--skaaa-border-radius, 0.25rem)',
        '-sm': '0.125rem',
        '-md': '0.375rem',
        '-lg': '0.5rem',
        '-xl': '0.75rem',
        '-2xl': '1rem',
        '-3xl': '1.5rem',
        '-full': '9999px',
        '-none': '0px',
    };

    static flexExtra = {
        'flex-1': 'flex: 1 1 0%;', 'flex-auto': 'flex: 1 1 auto;',
        'flex-initial': 'flex: 0 1 auto;', 'flex-none': 'flex: none;',
        'flex-shrink': 'flex-shrink: 1;', 'flex-shrink-0': 'flex-shrink: 0;',
        'shrink': 'flex-shrink: 1;', 'shrink-0': 'flex-shrink: 0;',
        'flex-grow': 'flex-grow: 1;', 'flex-grow-0': 'flex-grow: 0;',
        'grow': 'flex-grow: 1;', 'grow-0': 'flex-grow: 0;',
        'self-auto': 'align-self: auto;', 'self-start': 'align-self: flex-start;',
        'self-end': 'align-self: flex-end;', 'self-center': 'align-self: center;',
        'self-stretch': 'align-self: stretch;',
        'order-first': 'order: -9999;', 'order-last': 'order: 9999;', 'order-none': 'order: 0;',
    };

    static transitionMap = {
        '-all': 'transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: var(--skaaa-transition-duration, 150ms);',
        '-colors': 'transition-property: color, background-color, border-color, text-decoration-color, fill, stroke; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: var(--skaaa-transition-duration, 150ms);',
        '-opacity': 'transition-property: opacity; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: var(--skaaa-transition-duration, 150ms);',
        '-shadow': 'transition-property: box-shadow; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: var(--skaaa-transition-duration, 150ms);',
        '-transform': 'transition-property: transform; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: var(--skaaa-transition-duration, 150ms);',
    };

    static easeMap = {
        'linear': 'linear',
        'in': 'cubic-bezier(0.4, 0, 1, 1)',
        'out': 'cubic-bezier(0, 0, 0.2, 1)',
        'in-out': 'cubic-bezier(0.4, 0, 0.2, 1)',
    };

    static blurMap = {
        '': '8px', '-none': '0', '-sm': '4px', '-md': '12px',
        '-lg': '16px', '-xl': '24px', '-2xl': '40px', '-3xl': '64px',
    };

    static palette = {
        'slate': { '50': '#f8fafc', '100': '#f1f5f9', '200': '#e2e8f0', '300': '#cbd5e1', '400': '#94a3b8', '500': '#64748b', '600': '#475569', '700': '#334155', '800': '#1e293b', '900': '#0f172a', '950': '#020617' },
        'gray': { '50': '#f9fafb', '100': '#f3f4f6', '200': '#e5e7eb', '300': '#d1d5db', '400': '#9ca3af', '500': '#6b7280', '600': '#4b5563', '700': '#374151', '800': '#1f2937', '900': '#111827', '950': '#030712' },
        'zinc': { '50': '#fafafa', '100': '#f4f4f5', '200': '#e4e4e7', '300': '#d4d4d8', '400': '#a1a1aa', '500': '#71717a', '600': '#52525b', '700': '#3f3f46', '800': '#27272a', '900': '#18181b', '950': '#09090b' },
        'neutral': { '50': '#fafafa', '100': '#f5f5f5', '200': '#e5e5e5', '300': '#d4d4d4', '400': '#a3a3a3', '500': '#737373', '600': '#525252', '700': '#404040', '800': '#262626', '900': '#171717', '950': '#0a0a0a' },
        'stone': { '50': '#fafaf9', '100': '#f5f5f4', '200': '#e7e5e4', '300': '#d6d3d1', '400': '#a8a29e', '500': '#78716c', '600': '#57534e', '700': '#44403c', '800': '#292524', '900': '#1c1917', '950': '#0c0a09' },
        'red': { '50': '#fef2f2', '100': '#fee2e2', '200': '#fecaca', '300': '#fca5a5', '400': '#f87171', '500': '#ef4444', '600': '#dc2626', '700': '#b91c1c', '800': '#991b1b', '900': '#7f1d1d', '950': '#450a0a' },
        'orange': { '50': '#fff7ed', '100': '#ffedd5', '200': '#fed7aa', '300': '#fdba74', '400': '#fb923c', '500': '#f97316', '600': '#ea580c', '700': '#c2410c', '800': '#9a3412', '900': '#7c2d12', '950': '#431407' },
        'amber': { '50': '#fffbeb', '100': '#fef3c7', '200': '#fde68a', '300': '#fcd34d', '400': '#fbbf24', '500': '#f59e0b', '600': '#d97706', '700': '#b45309', '800': '#92400e', '900': '#78350f', '950': '#451a03' },
        'yellow': { '50': '#fefce8', '100': '#fef9c3', '200': '#fef08a', '300': '#fde047', '400': '#facc15', '500': '#eab308', '600': '#ca8a04', '700': '#a16207', '800': '#854d0e', '900': '#713f12', '950': '#422006' },
        'lime': { '50': '#f7fee7', '100': '#ecfccb', '200': '#d9f99d', '300': '#bef264', '400': '#a3e635', '500': '#84cc16', '600': '#65a30d', '700': '#4d7c0f', '800': '#3f6212', '900': '#365314', '950': '#1a2e05' },
        'green': { '50': '#f0fdf4', '100': '#dcfce7', '200': '#bbf7d0', '300': '#86efac', '400': '#4ade80', '500': '#22c55e', '600': '#16a34a', '700': '#15803d', '800': '#166534', '900': '#14532d', '950': '#052e16' },
        'emerald': { '50': '#ecfdf5', '100': '#d1fae5', '200': '#a7f3d0', '300': '#6ee7b7', '400': '#34d399', '500': '#10b981', '600': '#059669', '700': '#047857', '800': '#065f46', '900': '#064e3b', '950': '#022c22' },
        'teal': { '50': '#f0fdfa', '100': '#ccfbf1', '200': '#99f6e4', '300': '#5eead4', '400': '#2dd4bf', '500': '#14b8a6', '600': '#0d9488', '700': '#0f766e', '800': '#115e59', '900': '#134e4a', '950': '#042f2e' },
        'cyan': { '50': '#ecfeff', '100': '#cffafe', '200': '#a5f3fc', '300': '#67e8f9', '400': '#22d3ee', '500': '#06b6d4', '600': '#0891b2', '700': '#0e7490', '800': '#155e75', '900': '#164e63', '950': '#083344' },
        'sky': { '50': '#f0f9ff', '100': '#e0f2fe', '200': '#bae6fd', '300': '#7dd3fc', '400': '#38bdf8', '500': '#0ea5e9', '600': '#0284c7', '700': '#0369a1', '800': '#075985', '900': '#0c4a6e', '950': '#082f49' },
        'blue': { '50': '#eff6ff', '100': '#dbeafe', '200': '#bfdbfe', '300': '#93c5fd', '400': '#60a5fa', '500': '#3b82f6', '600': '#2563eb', '700': '#1d4ed8', '800': '#1e40af', '900': '#1e3a8a', '950': '#172554' },
        'indigo': { '50': '#eef2ff', '100': '#e0e7ff', '200': '#c7d2fe', '300': '#a5b4fc', '400': '#818cf8', '500': '#6366f1', '600': '#4f46e5', '700': '#4338ca', '800': '#3730a3', '900': '#312e81', '950': '#1e1b4b' },
        'violet': { '50': '#f5f3ff', '100': '#ede9fe', '200': '#ddd6fe', '300': '#c4b5fd', '400': '#a78bfa', '500': '#8b5cf6', '600': '#7c3aed', '700': '#6d28d9', '800': '#5b21b6', '900': '#4c1d95', '950': '#2e1065' },
        'purple': { '50': '#faf5ff', '100': '#f3e8ff', '200': '#e9d5ff', '300': '#d8b4fe', '400': '#c084fc', '500': '#a855f7', '600': '#9333ea', '700': '#7e22ce', '800': '#6b21a8', '900': '#581c87', '950': '#3b0764' },
        'fuchsia': { '50': '#fdf4ff', '100': '#fae8ff', '200': '#f5d0fe', '300': '#f0abfc', '400': '#e879f9', '500': '#d946ef', '600': '#c026d3', '700': '#a21caf', '800': '#86198f', '900': '#701a75', '950': '#4a044e' },
        'pink': { '50': '#fdf2f8', '100': '#fce7f3', '200': '#fbcfe8', '300': '#f9a8d4', '400': '#f472b6', '500': '#ec4899', '600': '#db2777', '700': '#be185d', '800': '#9d174d', '900': '#831843', '950': '#500724' },
        'rose': { '50': '#fff1f2', '100': '#ffe4e6', '200': '#fecdd3', '300': '#fda4af', '400': '#fb7185', '500': '#f43f5e', '600': '#e11d48', '700': '#be123c', '800': '#9f1239', '900': '#881337', '950': '#4c0519' },
        'black': { 'default': '#000000' },
        'white': { 'default': '#ffffff' }
    };

    static cssPropMap = {
        'bg': 'background-color',
        'text': 'color',
        'border': 'border-color',
        'ring': '--tw-ring-color',
        'shadow': '--tw-shadow-color',
        'from': '--tw-gradient-from',
        'to': '--tw-gradient-to',
    };

    static initRules() {
        if (window.skaaaEditorConfig && window.skaaaEditorConfig.tailwindRules) {
            const rules = window.skaaaEditorConfig.tailwindRules;
            if (rules.mediaQueries) this.mediaQueries = rules.mediaQueries;
            if (rules.basicColors) this.basicColors = rules.basicColors;
            if (rules.fontFamily) this.fontFamily = rules.fontFamily;
            if (rules.weights) this.weights = rules.weights;
            if (rules.shadowMap) this.shadowMap = rules.shadowMap;
            if (rules.maxWMap) this.maxWMap = rules.maxWMap;
            if (rules.marginAutoMap) this.marginAutoMap = rules.marginAutoMap;
            if (rules.layoutMap) this.layoutMap = rules.layoutMap;
            if (rules.sizeMap) this.sizeMap = rules.sizeMap;
            if (rules.textAlignMap) this.textAlignMap = rules.textAlignMap;
            if (rules.textDecoMap) this.textDecoMap = rules.textDecoMap;
            if (rules.textMiscMap) this.textMiscMap = rules.textMiscMap;
            if (rules.whitespaceMap) this.whitespaceMap = rules.whitespaceMap;
            if (rules.leadingMap) this.leadingMap = rules.leadingMap;
            if (rules.trackingMap) this.trackingMap = rules.trackingMap;
            if (rules.borderStyleMap) this.borderStyleMap = rules.borderStyleMap;
            if (rules.borderBasicMap) this.borderBasicMap = rules.borderBasicMap;
            if (rules.ringBasic) this.ringBasic = rules.ringBasic;
            if (rules.ringOffsetBasic) this.ringOffsetBasic = rules.ringOffsetBasic;
            if (rules.bgUtilMap) this.bgUtilMap = rules.bgUtilMap;
            if (rules.gradBasic) this.gradBasic = rules.gradBasic;
            if (rules.radiusMap) this.radiusMap = rules.radiusMap;
            if (rules.flexExtra) this.flexExtra = rules.flexExtra;
            if (rules.transitionMap) this.transitionMap = rules.transitionMap;
            if (rules.easeMap) this.easeMap = rules.easeMap;
            if (rules.blurMap) this.blurMap = rules.blurMap;
            if (rules.palette) this.palette = rules.palette;
        }
    }

    constructor(brandColors = null, darkBrandColors = null) {
        SkaaaWindCompiler.initRules();
        // Load brand colors from parameter or fallback to localized config
        this.brandColors = brandColors || (window.skaaaEditorConfig && window.skaaaEditorConfig.brandColorsJson) || (window.skaaaDesignTokens && window.skaaaDesignTokens.colors) || {};
        this.darkBrandColors = darkBrandColors || (window.skaaaEditorConfig && window.skaaaEditorConfig.darkBrandColorsJson) || (window.skaaaDesignTokens && window.skaaaDesignTokens.darkColors) || {};
    }

    hexToRgb(hex) {
        hex = hex.replace(/^#/, '');
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        return `${r}, ${g}, ${b}`;
    }

    getColorHex(color, shade, isDark = false) {
        const customColors = isDark 
            ? Object.assign({}, this.brandColors, this.darkBrandColors)
            : this.brandColors;
        if (customColors && customColors[color]) {
            return customColors[color];
        }
        const c = SkaaaWindCompiler.palette[color];
        if (c) {
            return c[shade] || c['default'] || null;
        }
        return null;
    }

    resolveCustomColor(className, colors = null) {
        const customColors = colors || this.brandColors;
        if (!customColors || Object.keys(customColors).length === 0) {
            return null;
        }

        const matches = className.match(/^(bg|text|border|ring|shadow|from|via|to)-([a-z0-9-]+?)(?:\/([0-9]+))?$/);
        if (!matches) {
            return null;
        }

        const prefix = matches[1];
        const colorName = matches[2];
        const opacity = matches[3] ? parseInt(matches[3]) : null;

        if (!customColors[colorName]) {
            return null;
        }

        const hex = customColors[colorName];
        let color;
        if (opacity !== null) {
            const rgb = this.hexToRgb(hex);
            const alpha = (opacity / 100).toFixed(2);
            color = `rgba(${rgb}, ${alpha})`;
        } else {
            color = hex;
        }

        if (prefix === 'from') {
            return `--tw-gradient-from: ${color}; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);`;
        }
        if (prefix === 'via') {
            return `--tw-gradient-stops: var(--tw-gradient-from), ${color}, var(--tw-gradient-to, transparent);`;
        }
        if (prefix === 'to') {
            return `--tw-gradient-to: ${color};`;
        }

        const cssPropMap = {
            'bg': 'background-color',
            'text': 'color',
            'border': 'border-color',
            'ring': '--tw-ring-color',
            'shadow': '--tw-shadow-color',
        };

        if (cssPropMap[prefix]) {
            return `${cssPropMap[prefix]}: ${color};`;
        }
        return null;
    }

    resolveSpacing(prefix, value) {
        const type = prefix[0] === 'p' ? 'padding' : 'margin';
        const remStr = (parseFloat(value) * 0.25).toFixed(3);
        const dir = prefix.substring(1);

        const map = {
            '': `${type}: ${remStr}rem;`,
            'x': `${type}-left: ${remStr}rem; ${type}-right: ${remStr}rem;`,
            'y': `${type}-top: ${remStr}rem; ${type}-bottom: ${remStr}rem;`,
            't': `${type}-top: ${remStr}rem;`,
            'b': `${type}-bottom: ${remStr}rem;`,
            'l': `${type}-left: ${remStr}rem;`,
            'r': `${type}-right: ${remStr}rem;`,
            's': `${type}-inline-start: ${remStr}rem;`,
            'e': `${type}-inline-end: ${remStr}rem;`,
        };

        return map[dir] || '';
    }

    resolveDimension(value) {
        if (value === 'full') return '100%';
        if (value === 'screen') return '100vh';
        if (value === 'auto') return 'auto';
        if (!isNaN(value) && !isNaN(parseFloat(value))) {
            return (parseFloat(value) * 0.25).toFixed(3) + 'rem';
        }
        
        let matches = value.match(/^\[(.+)\]$/);
        if (matches) return matches[1].replace(/_/g, ' ');

        matches = value.match(/^(\d+)\/(\d+)$/);
        if (matches) {
            const num = parseFloat(matches[1]);
            const den = parseFloat(matches[2]);
            if (den > 0) {
                return ((num / den) * 100).toFixed(6) + '%';
            }
        }
        return null;
    }

    resolveClass(className, isDark = false) {
        // 0. Custom Brand Colors
        let colors = isDark 
            ? Object.assign({}, this.brandColors, this.darkBrandColors)
            : this.brandColors;
        let customResult = this.resolveCustomColor(className, colors);
        if (customResult) {
            return customResult;
        }

        // 0.5 Support Arbitrary Colors: bg-[#1da1f2] or text-[#ff0000]
        let arbitraryColorMatches = className.match(/^(bg|text|border|ring|from|via|to)-\[#([a-fA-F0-9]{3,8})\](?:\/([0-9]+))?$/);
        if (arbitraryColorMatches) {
            const prefix = arbitraryColorMatches[1];
            const hex = '#' + arbitraryColorMatches[2];
            const opacity = arbitraryColorMatches[3] ? parseInt(arbitraryColorMatches[3]) : null;
            let color;
            if (opacity !== null) {
                const rgb = this.hexToRgb(hex);
                const alpha = (opacity / 100).toFixed(2);
                color = `rgba(${rgb}, ${alpha})`;
            } else {
                color = hex;
            }

            if (prefix === 'from') {
                return `--tw-gradient-from: ${color}; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);`;
            }
            if (prefix === 'via') {
                return `--tw-gradient-stops: var(--tw-gradient-from), ${color}, var(--tw-gradient-to, transparent);`;
            }
            if (prefix === 'to') {
                return `--tw-gradient-to: ${color};`;
            }

            const propMap = {
                'bg': 'background-color',
                'text': 'color',
                'border': 'border-color',
                'ring': '--tw-ring-color',
            };

            if (propMap[prefix] !== undefined) {
                return `${propMap[prefix]}: ${color};`;
            }
        }

        // 1. Colors (text-*, bg-*)
        let matches = className.match(/^(text|bg)-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/);
        if (matches) {
            const type = matches[1] === 'text' ? 'color' : 'background-color';
            const hex = this.getColorHex(matches[2], matches[3], isDark);
            if (!hex) return null;
            if (matches[4] !== undefined) {
                const rgb = this.hexToRgb(hex);
                const alpha = (parseInt(matches[4]) / 100).toFixed(2);
                return `${type}: rgba(${rgb}, ${alpha});`;
            }
            return `${type}: ${hex};`;
        }

        // 1.2 Basic colors
        if (SkaaaWindCompiler.basicColors[className] !== undefined) {
            return SkaaaWindCompiler.basicColors[className];
        }

        // 1.3 Basic colors with opacity
        matches = className.match(/^(text|bg)-(white|black)\/([0-9]+)$/);
        if (matches) {
            const type = matches[1] === 'text' ? 'color' : 'background-color';
            const hex = matches[2] === 'white' ? '#ffffff' : '#000000';
            const rgb = this.hexToRgb(hex);
            const alpha = (parseInt(matches[3]) / 100).toFixed(2);
            return `${type}: rgba(${rgb}, ${alpha});`;
        }

        // 2. Font Weights & Font Families
        if (SkaaaWindCompiler.fontFamily && SkaaaWindCompiler.fontFamily[className] !== undefined) {
            return SkaaaWindCompiler.fontFamily[className];
        }
        if (SkaaaWindCompiler.weights[className] !== undefined) {
            return SkaaaWindCompiler.weights[className];
        }

        // 3. Spacing
        matches = className.match(/^([pm][trblxyse]?)-([0-9.]+)$/);
        if (matches) {
            return this.resolveSpacing(matches[1], matches[2]);
        }
        matches = className.match(/^([pm][trblxyse]?)-\[(.+)\]$/);
        if (matches) {
            const type = matches[1][0] === 'p' ? 'padding' : 'margin';
            const val = matches[2].replace(/_/g, ' ');
            const dir = matches[1].substring(1);
            const dirMap = {
                '': `${type}: ${val};`,
                'x': `${type}-left: ${val}; ${type}-right: ${val};`,
                'y': `${type}-top: ${val}; ${type}-bottom: ${val};`,
                't': `${type}-top: ${val};`,
                'b': `${type}-bottom: ${val};`,
                'l': `${type}-left: ${val};`,
                'r': `${type}-right: ${val};`,
                's': `${type}-inline-start: ${val};`,
                'e': `${type}-inline-end: ${val};`,
            };
            return dirMap[dir] || null;
        }

        // 4. Dimensions
        matches = className.match(/^(w|h)-(.+)$/);
        if (matches) {
            const prop = matches[1] === 'w' ? 'width' : 'height';
            const mapped = this.resolveDimension(matches[2]);
            return mapped ? `${prop}: ${mapped};` : null;
        }
        matches = className.match(/^size-(.+)$/);
        if (matches) {
            const mapped = this.resolveDimension(matches[1]);
            return mapped ? `width: ${mapped}; height: ${mapped};` : null;
        }
        matches = className.match(/^(min|max)-(w|h)-(.+)$/);
        if (matches) {
            const prop = matches[2] === 'w' ? 'width' : 'height';
            const value = matches[3];
            let mapped = this.resolveDimension(value);
            if (value === 'none') mapped = 'none';
            else if (value === 'full') mapped = '100%';

            if (mapped) {
                return `${matches[1]}-${prop}: ${mapped};`;
            }
        }

        // 4.1 Object Fit & Aspect Ratio
        matches = className.match(/^object-(cover|contain|fill|none|scale-down)$/);
        if (matches) {
            return `object-fit: ${matches[1]};`;
        }
        matches = className.match(/^aspect-(video|square|auto|(\d+)\/(\d+))$/);
        if (matches) {
            let val = matches[1];
            if (val === 'video') val = '16 / 9';
            else if (val === 'square') val = '1 / 1';
            else if (val.includes('/')) val = val.replace('/', ' / ');
            return `aspect-ratio: ${val};`;
        }
        matches = className.match(/^aspect-\[(.+)\]$/);
        if (matches) {
            const val = matches[1].replace('/', ' / ');
            return `aspect-ratio: ${val};`;
        }

        // 4.2 Shadows & Z-Index & Container & Max Width & Auto Margin
        if (SkaaaWindCompiler.shadowMap[className] !== undefined) return SkaaaWindCompiler.shadowMap[className];
        matches = className.match(/^shadow-\[(.+)\]$/);
        if (matches) {
            const val = matches[1].replace(/_/g, ' ');
            return `box-shadow: ${val};`;
        }
        matches = className.match(/^shadow-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/);
        if (matches) {
            const hex = this.getColorHex(matches[1], matches[2]);
            if (hex) {
                if (matches[3] !== undefined) {
                    const rgb = this.hexToRgb(hex);
                    const alpha = (parseInt(matches[3]) / 100).toFixed(2);
                    return `--tw-shadow-color: rgba(${rgb}, ${alpha}); box-shadow: 0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -2px var(--tw-shadow-color);`;
                }
                return `--tw-shadow-color: ${hex}; box-shadow: 0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -2px var(--tw-shadow-color);`;
            }
        }
        matches = className.match(/^z-(\d+)$/);
        if (matches) return `z-index: ${matches[1]};`;
        if (className === 'z-auto') return 'z-index: auto;';
        if (className === 'container' || className === 'skaaa-container') {
            const tokens = (window.skaaaEditorConfig && window.skaaaEditorConfig.tokens) || {};
            const containerWidth = tokens.containerWidth || '1280px';
            return `width: 100%; max-width: ${containerWidth}; margin-right: auto; margin-left: auto;`;
        }
        matches = className.match(/^max-w-([a-z0-9]+)$/);
        if (matches) {
            if (matches[1] === '7xl') {
                const tokens = (window.skaaaEditorConfig && window.skaaaEditorConfig.tokens) || {};
                const containerWidth = tokens.containerWidth || '1280px';
                return `max-width: ${containerWidth};`;
            }
            if (SkaaaWindCompiler.maxWMap[matches[1]] !== undefined) {
                return `max-width: ${SkaaaWindCompiler.maxWMap[matches[1]]};`;
            }
        }
        if (SkaaaWindCompiler.marginAutoMap[className] !== undefined) return SkaaaWindCompiler.marginAutoMap[className];

        // 5. Flexbox & Grid
        if (SkaaaWindCompiler.layoutMap[className] !== undefined) return SkaaaWindCompiler.layoutMap[className];
        matches = className.match(/^gap-([0-9.]+)$/);
        if (matches) {
            const valStr = (parseFloat(matches[1]) * 0.25).toFixed(3);
            return `gap: ${valStr}rem; row-gap: ${valStr}rem; column-gap: ${valStr}rem; --wp--style--block-gap: ${valStr}rem;`;
        }
        matches = className.match(/^gap-(x|y)-([0-9.]+)$/);
        if (matches) {
            const prop = matches[1] === 'x' ? 'column-gap' : 'row-gap';
            const valStr = (parseFloat(matches[2]) * 0.25).toFixed(3);
            return `${prop}: ${valStr}rem;`;
        }
        matches = className.match(/^grid-cols-([1-9]|1[0-2])$/);
        if (matches) return `grid-template-columns: repeat(${matches[1]}, minmax(0, 1fr));`;
        matches = className.match(/^grid-cols-\[(.+)\]$/);
        if (matches) {
            const val = matches[1].replace(/_/g, ' ');
            return `grid-template-columns: ${val};`;
        }
        matches = className.match(/^col-span-([1-9]|1[0-2])$/);
        if (matches) return `grid-column: span ${matches[1]} / span ${matches[1]};`;
        if (className === 'col-span-full') return 'grid-column: 1 / -1;';
        matches = className.match(/^grid-rows-([1-9]|1[0-2])$/);
        if (matches) return `grid-template-rows: repeat(${matches[1]}, minmax(0, 1fr));`;

        // 5.5 Typography Maps
        matches = className.match(/^text-([a-z0-9]+)$/);
        if (matches && SkaaaWindCompiler.sizeMap[matches[1]] !== undefined) {
            return SkaaaWindCompiler.sizeMap[matches[1]];
        }
        matches = className.match(/^text-\[(.+?)\](?:\/(.+))?$/);
        if (matches) {
            const val = matches[1].replace(/_/g, ' ');
            if (val.startsWith('rgb(') || val.startsWith('rgba(') || val.startsWith('hsl(') || val.startsWith('hsla(') || val.startsWith('#')) {
                return `color: ${val};`;
            }
            if (matches[2]) {
                let lh = matches[2].replace(/_/g, ' ');
                if (lh.startsWith('[') && lh.endsWith(']')) {
                    lh = lh.slice(1, -1);
                }
                return `font-size: ${val}; line-height: ${lh};`;
            }
            return `font-size: ${val};`;
        }
        if (SkaaaWindCompiler.textAlignMap[className] !== undefined) return SkaaaWindCompiler.textAlignMap[className];
        if (SkaaaWindCompiler.textDecoMap[className] !== undefined) return SkaaaWindCompiler.textDecoMap[className];
        if (SkaaaWindCompiler.textMiscMap[className] !== undefined) return SkaaaWindCompiler.textMiscMap[className];
        if (SkaaaWindCompiler.whitespaceMap[className] !== undefined) return SkaaaWindCompiler.whitespaceMap[className];

        matches = className.match(/^leading-([a-z0-9.]+)$/);
        if (matches) {
            if (SkaaaWindCompiler.leadingMap[matches[1]] !== undefined) {
                return `line-height: ${SkaaaWindCompiler.leadingMap[matches[1]]};`;
            }
            if (!isNaN(matches[1])) {
                return `line-height: ${(parseFloat(matches[1]) * 0.25).toFixed(3)}rem;`;
            }
        }
        matches = className.match(/^tracking-([a-z]+)$/);
        if (matches && SkaaaWindCompiler.trackingMap[matches[1]] !== undefined) {
            return `letter-spacing: ${SkaaaWindCompiler.trackingMap[matches[1]]};`;
        }
        matches = className.match(/^line-clamp-([0-9]+)$/);
        if (matches) {
            return `overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: ${matches[1]};`;
        }
        if (className === 'line-clamp-none') return 'overflow: visible; display: block; -webkit-box-orient: horizontal; -webkit-line-clamp: none;';

        matches = className.match(/^space-(x|y)-([0-9.]+)$/);
        if (matches) {
            const val = parseFloat(matches[2]) * 0.25;
            const prop = matches[1] === 'x' ? 'margin-left' : 'margin-top';
            const opp = matches[1] === 'x' ? 'margin-right' : 'margin-bottom';
            return `& > :not([hidden]) ~ :not([hidden]) { ${prop}: ${val}rem; ${opp}: 0; }`;
        }

        // 6. Borders
        if (className === 'border') return 'border-width: 1px; border-style: solid;';
        if (SkaaaWindCompiler.borderStyleMap[className] !== undefined) return SkaaaWindCompiler.borderStyleMap[className];
        matches = className.match(/^border-([0-9]+)$/);
        if (matches) return `border-width: ${matches[1]}px; border-style: solid;`;
        matches = className.match(/^border-\[(.+)\]$/);
        if (matches) {
            const val = matches[1].replace(/_/g, ' ');
            if (!/^(#|rgb|hsl)/.test(val)) {
                return `border-width: ${val}; border-style: solid;`;
            }
        }
        matches = className.match(/^border-([trblxyse])(?:-([0-9]+))?$/);
        if (matches) {
            const width = matches[2] !== undefined ? matches[2] + 'px' : '1px';
            if (matches[1] === 'x') {
                return `border-left-width: ${width}; border-right-width: ${width}; border-style: solid;`;
            }
            if (matches[1] === 'y') {
                return `border-top-width: ${width}; border-bottom-width: ${width}; border-style: solid;`;
            }
            if (matches[1] === 's') {
                return `border-inline-start-width: ${width}; border-style: solid;`;
            }
            if (matches[1] === 'e') {
                return `border-inline-end-width: ${width}; border-style: solid;`;
            }
            const sideMap = { 't': 'top', 'r': 'right', 'b': 'bottom', 'l': 'left' };
            return `border-${sideMap[matches[1]]}-width: ${width}; border-style: solid;`;
        }
        matches = className.match(/^border-([trblxyse])-\[(.+)\]$/);
        if (matches) {
            const val = matches[2].replace(/_/g, ' ');
            if (matches[1] === 'x') {
                return `border-left-width: ${val}; border-right-width: ${val}; border-style: solid;`;
            }
            if (matches[1] === 'y') {
                return `border-top-width: ${val}; border-bottom-width: ${val}; border-style: solid;`;
            }
            if (matches[1] === 's') {
                return `border-inline-start-width: ${val}; border-style: solid;`;
            }
            if (matches[1] === 'e') {
                return `border-inline-end-width: ${val}; border-style: solid;`;
            }
            const sideMap = { 't': 'top', 'r': 'right', 'b': 'bottom', 'l': 'left' };
            return `border-${sideMap[matches[1]]}-width: ${val}; border-style: solid;`;
        }
        matches = className.match(/^border-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/);
        if (matches) {
            const hex = this.getColorHex(matches[1], matches[2]);
            if (!hex) return null;
            if (matches[3] !== undefined) {
                const rgb = this.hexToRgb(hex);
                const alpha = (parseInt(matches[3]) / 100).toFixed(2);
                return `border-color: rgba(${rgb}, ${alpha});`;
            }
            return `border-color: ${hex};`;
        }
        matches = className.match(/^border-(white|black)\/([0-9]+)$/);
        if (matches) {
            const hex = matches[1] === 'white' ? '#ffffff' : '#000000';
            const rgb = this.hexToRgb(hex);
            const alpha = (parseInt(matches[2]) / 100).toFixed(2);
            return `border-color: rgba(${rgb}, ${alpha});`;
        }
        if (SkaaaWindCompiler.borderBasicMap[className] !== undefined) return SkaaaWindCompiler.borderBasicMap[className];

        // 6.6 Ring Width & Color
        if (className === 'ring') return 'box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);';
        matches = className.match(/^ring-([0-8])$/);
        if (matches) {
            const width = matches[1];
            if (width === '0') return 'box-shadow: 0 0 0 0px transparent;';
            return `box-shadow: 0 0 0 ${width}px var(--tw-ring-color, rgba(59, 130, 246, 0.5));`;
        }
        if (className === 'ring-inset') return '--tw-ring-inset: inset;';
        matches = className.match(/^ring-offset-([0-9]+)$/);
        if (matches) return `--tw-ring-offset-width: ${matches[1]}px;`;
        matches = className.match(/^ring-([a-z]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/);
        if (matches) {
            const hex = this.getColorHex(matches[1], matches[2]);
            if (matches[3] !== undefined) {
                const rgb = this.hexToRgb(hex);
                const alpha = (parseInt(matches[3]) / 100).toFixed(2);
                return `--tw-ring-color: rgba(${rgb}, ${alpha});`;
            }
            return `--tw-ring-color: ${hex};`;
        }
        if (SkaaaWindCompiler.ringBasic[className] !== undefined) return SkaaaWindCompiler.ringBasic[className];

        matches = className.match(/^ring-offset-([a-z]+)-([1-9]00|950|50)$/);
        if (matches) {
            return `--tw-ring-offset-color: ${this.getColorHex(matches[1], matches[2])};`;
        }
        if (SkaaaWindCompiler.ringOffsetBasic[className] !== undefined) return SkaaaWindCompiler.ringOffsetBasic[className];

        // 6.7 Outline
        if (className === 'outline-none') return 'outline: 2px solid transparent !important; outline-offset: 2px !important;';
        if (className === 'outline') return 'outline-style: solid !important;';
        matches = className.match(/^outline-(dashed|dotted|double)$/);
        if (matches) return `outline-style: ${matches[1]} !important;`;
        matches = className.match(/^outline-([0-9]+)$/);
        if (matches) return `outline-width: ${matches[1]}px !important; outline-style: solid !important;`;
        matches = className.match(/^-?outline-offset-([0-9]+)$/);
        if (matches) {
            const val = className.startsWith('-') ? '-' + matches[1] : matches[1];
            return `outline-offset: ${val}px !important;`;
        }
        matches = className.match(/^outline-([a-z]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/);
        if (matches) {
            const hex = this.getColorHex(matches[1], matches[2]);
            if (matches[3] !== undefined) {
                const rgb = this.hexToRgb(hex);
                const alpha = (parseInt(matches[3]) / 100).toFixed(2);
                return `outline-color: rgba(${rgb}, ${alpha}) !important;`;
            }
            return `outline-color: ${hex} !important;`;
        }
        if (className === 'outline-white') return 'outline-color: #ffffff !important;';
        if (className === 'outline-black') return 'outline-color: #000000 !important;';
        if (className === 'outline-transparent') return 'outline-color: transparent !important;';

        // 6.9 Background & Gradients
        if (SkaaaWindCompiler.bgUtilMap[className] !== undefined) return SkaaaWindCompiler.bgUtilMap[className];
        matches = className.match(/^bg-gradient-to-(t|tr|r|br|b|bl|l|tl)$/);
        if (matches) {
            const dirMap = {
                't': 'to top', 'tr': 'to top right', 'r': 'to right', 'br': 'to bottom right',
                'b': 'to bottom', 'bl': 'to bottom left', 'l': 'to left', 'tl': 'to top left',
            };
            return `background-image: linear-gradient(${dirMap[matches[1]]}, var(--tw-gradient-stops));`;
        }
        matches = className.match(/^(from|via|to)-([a-z0-9-]+)-([1-9]00|950|50)(?:\/([0-9]+))?$/);
        if (matches) {
            const hex = this.getColorHex(matches[2], matches[3]);
            if (!hex) return null;
            let color;
            if (matches[4] !== undefined) {
                const rgb = this.hexToRgb(hex);
                const alpha = (parseInt(matches[4]) / 100).toFixed(2);
                color = `rgba(${rgb}, ${alpha})`;
            } else {
                color = hex;
            }
            const gradMap = {
                'from': `--tw-gradient-from: ${color}; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);`,
                'via': `--tw-gradient-stops: var(--tw-gradient-from), ${color}, var(--tw-gradient-to, transparent);`,
                'to': `--tw-gradient-to: ${color};`,
            };
            return gradMap[matches[1]];
        }
        if (SkaaaWindCompiler.gradBasic[className] !== undefined) return SkaaaWindCompiler.gradBasic[className];

        // 6.12 Rounded
        matches = className.match(/^rounded(-[a-z0-9\-]+)?$/);
        if (matches) {
            const type = matches[1] || '';
            return SkaaaWindCompiler.radiusMap[type] !== undefined ? `border-radius: ${SkaaaWindCompiler.radiusMap[type]};` : null;
        }

        // 7. Display & Position
        if (['hidden', 'block', 'inline-block', 'flex', 'grid', 'inline-flex'].includes(className)) {
            return `display: ${className === 'hidden' ? 'none' : className};`;
        }
        if (className === 'group') return '/* group marker */';
        matches = className.match(/^overflow-(auto|hidden|visible|scroll|x-auto|x-hidden|y-auto|y-hidden)$/);
        if (matches) {
            let prop = 'overflow';
            if (matches[1].startsWith('x-')) prop = 'overflow-x';
            if (matches[1].startsWith('y-')) prop = 'overflow-y';
            return `${prop}: ${matches[1].replace(/^(x-|y-)/, '')};`;
        }
        if (['relative', 'absolute', 'fixed', 'sticky', 'static'].includes(className)) return `position: ${className};`;

        matches = className.match(/^(inset|inset-x|inset-y|top|right|bottom|left|start|end)-(\d+\/\d+|\d+\.?\d*|full|auto|px)$/);
        if (matches) {
            let cssVal;
            const val = matches[2];
            if (val.includes('/')) {
                const [n, d] = val.split('/');
                cssVal = (parseFloat(n) / parseFloat(d) * 100) + '%';
            } else if (val === 'full') cssVal = '100%';
            else if (val === 'auto') cssVal = 'auto';
            else if (val === 'px') cssVal = '1px';
            else if (val === '0') cssVal = '0px';
            else cssVal = (parseFloat(val) * 0.25).toFixed(3) + 'rem';

            const propMap = {
                'inset': `inset: ${cssVal};`,
                'inset-x': `left: ${cssVal}; right: ${cssVal};`,
                'inset-y': `top: ${cssVal}; bottom: ${cssVal};`,
                'top': `top: ${cssVal};`,
                'right': `right: ${cssVal};`,
                'bottom': `bottom: ${cssVal};`,
                'left': `left: ${cssVal};`,
                'start': `inset-inline-start: ${cssVal};`,
                'end': `inset-inline-end: ${cssVal};`,
            };
            return propMap[matches[1]] || null;
        }
        matches = className.match(/^(inset|inset-x|inset-y|top|right|bottom|left|start|end)-\[(.+)\]$/);
        if (matches) {
            const val = matches[2];
            const propMap = {
                'inset': `inset: ${val};`,
                'inset-x': `left: ${val}; right: ${val};`,
                'inset-y': `top: ${val}; bottom: ${val};`,
                'top': `top: ${val};`,
                'right': `right: ${val};`,
                'bottom': `bottom: ${val};`,
                'left': `left: ${val};`,
                'start': `inset-inline-start: ${val};`,
                'end': `inset-inline-end: ${val};`,
            };
            return propMap[matches[1]] || null;
        }

        // 8. Transitions & Transforms
        if (className === 'transform') return '/* transform placeholder */';
        matches = className.match(/^transition(-[a-z]+)?$/);
        if (matches) {
            const type = matches[1] || '-all';
            return SkaaaWindCompiler.transitionMap[type] || null;
        }
        matches = className.match(/^duration-([0-9]+)$/);
        if (matches) return `transition-duration: ${matches[1]}ms;`;
        matches = className.match(/^ease-(linear|in|out|in-out)$/);
        if (matches) {
            return SkaaaWindCompiler.easeMap[matches[1]] !== undefined ? `transition-timing-function: ${SkaaaWindCompiler.easeMap[matches[1]]};` : null;
        }
        matches = className.match(/^translate-([xy])-(\d+\.?\d*|full|px|1\/2|1\/3|2\/3|1\/4|2\/4|3\/4)$/);
        if (matches) {
            const axis = matches[1] === 'x' ? 'X' : 'Y';
            const fracMap = { '1/2': '50%', '1/3': '33.333333%', '2/3': '66.666667%', '1/4': '25%', '2/4': '50%', '3/4': '75%' };
            let cssVal;
            if (fracMap[matches[2]] !== undefined) cssVal = fracMap[matches[2]];
            else if (matches[2] === 'full') cssVal = '100%';
            else if (matches[2] === 'px') cssVal = '1px';
            else if (matches[2] === '0') cssVal = '0px';
            else cssVal = (parseFloat(matches[2]) * 0.25).toFixed(3) + 'rem';
            return `transform: translate${axis}(${cssVal});`;
        }
        matches = className.match(/^translate-([xy])-\[(.+)\]$/);
        if (matches) {
            return `transform: translate${matches[1].toUpperCase()}(${matches[2].replace(/_/g, ' ')});`;
        }
        matches = className.match(/^rotate-([0-9]+)$/);
        if (matches) return `transform: rotate(${matches[1]}deg);`;
        matches = className.match(/^rotate-\[(.+)\]$/);
        if (matches) return `transform: rotate(${matches[1]});`;
        matches = className.match(/^scale-\[([0-9.]+)\]$/);
        if (matches) return `transform: scale(${matches[1]});`;
        matches = className.match(/^scale-([0-9]+)$/);
        if (matches) return `transform: scale(${(parseInt(matches[1]) / 100)});`;

        // 9. Filters
        matches = className.match(/^backdrop-blur(-[a-z0-9]+)?$/);
        if (matches) {
            const size = matches[1] || '';
            const val = SkaaaWindCompiler.blurMap[size];
            return val ? `-webkit-backdrop-filter: blur(${val}); backdrop-filter: blur(${val});` : null;
        }
        matches = className.match(/^blur(-[a-z0-9]+)?$/);
        if (matches) {
            const size = matches[1] || '';
            const val = SkaaaWindCompiler.blurMap[size];
            return val ? `filter: blur(${val});` : null;
        }
        matches = className.match(/^brightness-([0-9]+)$/);
        if (matches) return `filter: brightness(${(parseInt(matches[1]) / 100)});`;
        matches = className.match(/^contrast-([0-9]+)$/);
        if (matches) return `filter: contrast(${(parseInt(matches[1]) / 100)});`;
        matches = className.match(/^opacity-([0-9]+)$/);
        if (matches) return `opacity: ${(parseInt(matches[1]) / 100)};`;
        if (className === 'isolate') return 'isolation: isolate;';

        // 10. Extras & Accessibility
        if (SkaaaWindCompiler.flexExtra[className] !== undefined) return SkaaaWindCompiler.flexExtra[className];
        matches = className.match(/^(shrink|grow)-\[(.+)\]$/);
        if (matches) {
            const prop = matches[1] === 'shrink' ? 'flex-shrink' : 'flex-grow';
            return `${prop}: ${matches[2].replace(/_/g, ' ')};`;
        }
        matches = className.match(/^order-([0-9]+)$/);
        if (matches) return `order: ${matches[1]};`;
        matches = className.match(/^cursor-(pointer|default|wait|text|move|not-allowed|grab|grabbing|auto)$/);
        if (matches) return `cursor: ${matches[1]};`;
        if (className === 'pointer-events-none') return 'pointer-events: none;';
        if (className === 'pointer-events-auto') return 'pointer-events: auto;';
        matches = className.match(/^select-(none|text|all|auto)$/);
        if (matches) return `user-select: ${matches[1]}; -webkit-user-select: ${matches[1]};`;

        if (className === 'sr-only') return 'position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0;';
        if (className === 'not-sr-only') return 'position: static; width: auto; height: auto; padding: 0; margin: 0; overflow: visible; clip: auto; white-space: normal;';

        // 11. Animations & Delays
        if (className === 'animate-blob') return 'animation: blob 7s infinite;';
        if (className === 'animate-spin') return 'animation: spin 1s linear infinite;';
        if (className === 'animate-ping') return 'animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;';
        if (className === 'animate-pulse') return 'animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;';
        if (className === 'animate-bounce') return 'animation: bounce 1s infinite;';
        matches = className.match(/^(animation-delay|delay)-([0-9]+)$/);
        if (matches) return `animation-delay: ${matches[2]}ms; transition-delay: ${matches[2]}ms;`;

        matches = className.match(/^content-\[(.+)\]$/);
        if (matches) {
            const val = matches[1].replace(/_/g, ' ');
            return `content: ${val};`;
        }
        if (className === 'content-none') return 'content: none;';

        return null;
    }

    compile(classesString) {
        if (!classesString || !classesString.trim()) {
            return { css: '', unresolved: [] };
        }

        const classList = [...new Set(classesString.split(/\s+/).filter(Boolean))];
        let compiledCss = (window.skaaaEditorConfig && window.skaaaEditorConfig.editorResetCss) || '';
        const unresolved = [];

        const mediaQueries = SkaaaWindCompiler.mediaQueries;
        const responsiveCss = {};

        for (const className of classList) {
            // Skip WordPress and Skaaa internal classes
            if (/^(wp-|skaaa-|is-|components-|editor-)/.test(className)) {
                continue;
            }

            // Extract modifiers
            let prefix = '';
            let pseudo = '';
            let groupPrefix = '';
            let customMedia = '';
            let isDark = false;
            let baseClass = className;

            if (className.includes(':')) {
                const modifiers = className.split(':');
                baseClass = modifiers.pop();
                let isValidModifiers = true;

                for (const mod of modifiers) {
                    if (mediaQueries[mod]) {
                        prefix = mod;
                    } else if (mod === 'dark') {
                        isDark = true;
                    } else if (['hover', 'focus', 'focus-within', 'focus-visible', 'active', 'disabled', 'checked', 'target', 'indeterminate', 'required', 'valid', 'invalid', 'read-only', 'empty', 'default', 'in-range', 'out-of-range', 'placeholder-shown', 'autofill'].includes(mod)) {
                        pseudo += ':' + mod;
                    } else if (['before', 'after', 'placeholder', 'first-letter', 'first-line', 'marker', 'selection', 'file', 'backdrop'].includes(mod)) {
                        pseudo += '::' + mod;
                    } else if (mod === 'not-checked') {
                        pseudo += ':not(:checked)';
                    } else if (mod.indexOf('group-has-') === 0) {
                        const state = mod.replace('group-has-', '');
                        groupPrefix += `.group:has(:${state}) `;
                    } else if (mod.indexOf('group-') === 0) {
                        const state = mod.replace('group-', '');
                        groupPrefix += `.group:${state} `;
                    } else if (mod.indexOf('peer-has-') === 0) {
                        const state = mod.replace('peer-has-', '');
                        groupPrefix += `.peer:has(:${state}) ~ `;
                    } else if (mod.indexOf('peer-') === 0) {
                        const state = mod.replace('peer-', '');
                        groupPrefix += `.peer:${state} ~ `;
                    } else if (mod.indexOf('has-') === 0) {
                        const state = mod.replace('has-', '');
                        pseudo += `:has(:${state})`;
                    } else if (mod === 'forced-colors') {
                        customMedia = '@media (forced-colors: active) { ';
                    } else {
                        isValidModifiers = false;
                        break;
                    }
                }

                if (!isValidModifiers) {
                    unresolved.push(className);
                    continue;
                }
            }

            // Handle negative prefix
            let isNegative = false;
            if (baseClass.startsWith('-') && baseClass.length > 1) {
                isNegative = true;
                baseClass = baseClass.substring(1);
            }

            let cssRule = this.resolveClass(baseClass, isDark);

            // Apply negation
            if (isNegative && cssRule) {
                cssRule = cssRule.replace(/:\s*([0-9.]+)/, ': -$1');
                cssRule = cssRule.replace(/:\s*calc/, ': calc(-1 * ');
            }

            if (cssRule) {
                // Escape special characters in class name for CSS selector
                const escapedClass = className.replace(/[:[\]/.(),#%!@=]/g, '\\$&');
                let selectorSuffix = pseudo;

                if ((pseudo.includes('::before') || pseudo.includes('::after')) && !cssRule.includes('content:')) {
                    cssRule = 'content: ""; ' + cssRule;
                }

                let baseSelector = 'html body.skaaaaa-builder';
                let editorBaseSelector = '.editor-styles-wrapper';

                if (isDark) {
                    baseSelector = 'html.dark body.skaaaaa-builder';
                    editorBaseSelector = ':is(.editor-styles-wrapper.dark, .editor-styles-wrapper .dark, html.dark .editor-styles-wrapper, body.dark .editor-styles-wrapper)';
                }

                let fullRule = '';
                if (cssRule.startsWith('&')) {
                    fullRule = cssRule.replace(/&/g, `${baseSelector} ${groupPrefix}.${escapedClass}.${escapedClass}${selectorSuffix}`);
                } else {
                    fullRule = `${baseSelector} ${groupPrefix}.${escapedClass}.${escapedClass}${selectorSuffix} { ${cssRule} }\n${editorBaseSelector} ${groupPrefix}.${escapedClass}.${escapedClass}${selectorSuffix} { ${cssRule} }`;
                }

                if (customMedia) {
                    compiledCss += `\n${customMedia}\n/* Source: ${className} */\n${fullRule}\n}\n`;
                } else if (prefix) {
                    if (!responsiveCss[prefix]) {
                        responsiveCss[prefix] = '';
                    }
                    responsiveCss[prefix] += `/* Source: ${className} */\n${fullRule}\n`;
                } else {
                    compiledCss += `/* Source: ${className} */\n${fullRule}\n`;
                }
            } else {
                unresolved.push(className);
            }
        }

        // Add responsive rules
        for (const prefix in mediaQueries) {
            if (responsiveCss[prefix]) {
                compiledCss += `\n${mediaQueries[prefix]}\n${responsiveCss[prefix]}}\n`;
            }
        }

        return {
            css: compiledCss,
            unresolved: unresolved
        };
    }
}

// Expose globally
window.SkaaaWindCompiler = SkaaaWindCompiler;
