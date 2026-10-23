<?php
defined( 'ABSPATH' ) || exit;
?>
<script src="https://cdn.tailwindcss.com"></script>
<!-- Alpine Plugins -->
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
/* Đè Reset WP padding */
#wpcontent { padding-left: 0; padding-bottom: 0; }
.skaaa-token-wrap {
    min-height: calc(100vh - 32px);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}
</style>

<div class="skaaa-token-wrap bg-slate-50 relative flex text-slate-800" x-data="skaaaDesignTokensApp()" x-init="initApp()">
    
    <!-- Sidebar / Tabs Menu -->
    <div class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 min-h-full shadow-sm z-10">
        <div class="h-16 flex items-center px-6 border-b border-slate-200 shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">token</span>
                </div>
                <h1 class="font-bold text-slate-900 leading-tight m-0 border-0 p-0 text-base">Design Tokens</h1>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 space-y-1">
            <p class="text-xs font-bold tracking-wider text-slate-400 uppercase mb-3 ml-2 mt-2"><?php esc_html_e( 'Global configuration', 'skaaa-no-code-design' ); ?></p>
            
            <template x-for="tab in tabs" :key="tab.id">
                <button 
                    @click="scrollTo(tab.id)"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors border-0 cursor-pointer"
                    :class="activeTab === tab.id ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 bg-transparent'">
                    <span class="material-symbols-outlined text-[20px]" x-text="tab.icon"></span>
                    <span x-text="tab.name"></span>
                </button>
            </template>
        </div>

        <div class="p-4 border-t border-slate-200">
            <button @click="saveTokens" :disabled="isSaving" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium border-0 cursor-pointer disabled:opacity-50">
                <span class="material-symbols-outlined text-[18px]" x-text="isSaving ? 'sync' : 'save'" :class="isSaving ? 'animate-spin' : ''"></span>
                <span x-text="isSaving ? '<?php echo esc_js( __( 'Saving...', 'skaaa-no-code-design' ) ); ?>' : '<?php echo esc_js( __( 'Save Changes', 'skaaa-no-code-design' ) ); ?>'"></span>
            </button>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="flex-1 overflow-y-auto relative h-[calc(100vh-32px)]" @scroll.passive="onScroll">
        
        <!-- Tab 1: Brand & Colors -->
        <div id="colors" class="p-8 max-w-4xl mx-auto space-y-8">
            <!-- Brand Logo -->
            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-2xl font-bold text-slate-900 m-0 border-0 p-0">Brand Identity</h2>
                    <p class="text-slate-500 mt-2"><?php esc_html_e( 'Definition of Logo and official brand identity of the system.', 'skaaa-no-code-design' ); ?></p>
                </div>
                
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Site Logo (Brand)', 'skaaa-no-code-design' ); ?></label>
                    <div class="flex gap-4 items-start">
                        <div class="w-32 h-32 border-2 border-dashed border-slate-300 rounded-lg flex items-center justify-center bg-slate-50 overflow-hidden relative group">
                            <img x-show="formData.brand.logoUrl" :src="formData.brand.logoUrl" class="max-w-full max-h-full object-contain" />
                            <span x-show="!formData.brand.logoUrl" class="material-symbols-outlined text-[40px] text-slate-300">image</span>
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                                <button @click.prevent="openLogoUploader" class="w-8 h-8 rounded-full bg-white text-slate-800 flex items-center justify-center border-0 cursor-pointer shadow-sm hover:scale-110 transition" title="<?php echo esc_attr( __( 'Upload', 'skaaa-no-code-design' ) ); ?>">
                                    <span class="material-symbols-outlined text-[18px]">upload</span>
                                </button>
                                <button x-show="formData.brand.logoUrl" @click.prevent="formData.brand.logoUrl = ''" class="w-8 h-8 rounded-full bg-rose-500 text-white flex items-center justify-center border-0 cursor-pointer shadow-sm hover:scale-110 transition" title="<?php echo esc_attr( __( 'Remove', 'skaaa-no-code-design' ) ); ?>">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </div>
                        <div class="flex-1 space-y-3">
                            <input type="text" x-model="formData.brand.logoUrl" placeholder="<?php echo esc_attr( __( 'Logo URL', 'skaaa-no-code-design' ) ); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none bg-slate-50" readonly>
                            <p class="text-xs text-slate-500 leading-relaxed"><?php echo sprintf( esc_html__( 'Upload the main logo of the project. This logo will be exported to %s for Frontend to easily display automatically without querying WordPress Database.', 'skaaa-no-code-design' ), '<code class="bg-slate-100 px-1 rounded text-slate-700">tokens.json</code>' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colors -->
            <div class="space-y-6 pt-6 border-t border-slate-200">
                <div class="pb-2 flex justify-between items-end">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 m-0 border-0 p-0"><?php esc_html_e( 'Colors', 'skaaa-no-code-design' ); ?></h2>
                        <p class="text-slate-500 mt-2"><?php esc_html_e( 'System color palette defines Brand Identity, applied automatically via Tailwind.', 'skaaa-no-code-design' ); ?></p>
                    </div>
                    <button @click="addColor()" class="px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-sm font-semibold transition border-0 cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">add</span> <?php esc_html_e( 'Add Color', 'skaaa-no-code-design' ); ?>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <template x-for="(color, index) in colorsList" :key="index">
                        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4 relative group">
                            <!-- Xóa Màu -->
                            <button @click="colorsList.splice(index, 1)" class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center opacity-0 group-hover:opacity-100 transition border-0 cursor-pointer shadow-sm">
                                <span class="material-symbols-outlined text-[14px]">close</span>
                            </button>
                            
                            <div class="w-16 h-16 rounded-lg border border-slate-200 shadow-inner flex-shrink-0 relative overflow-hidden" :style="'background-color: ' + color.value">
                                <input type="color" x-model="color.value" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            </div>
                            <div class="flex-1">
                                <input type="text" x-model="color.key" placeholder="e.g. primary" class="block w-full text-sm font-bold text-slate-700 mb-1 bg-transparent border-0 border-b border-transparent hover:border-slate-300 focus:border-indigo-500 outline-none p-0">
                                <input type="text" x-model="color.value" class="w-full px-3 py-1.5 border border-slate-300 rounded text-sm text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none uppercase font-mono">
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Dark Mode Colors -->
            <div class="space-y-6 pt-6 border-t border-slate-200">
                <div class="pb-2 flex justify-between items-end">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 m-0 border-0 p-0"><?php esc_html_e( 'Dark Mode colors', 'skaaa-no-code-design' ); ?></h2>
                        <p class="text-slate-500 mt-2"><?php esc_html_e( 'Separate color values ​​for Dark Mode. ', 'skaaa-no-code-design' ); ?></p>
                    </div>
                    <button @click="addDarkColor()" class="px-4 py-2 bg-slate-800 text-white hover:bg-slate-900 rounded-lg text-sm font-semibold transition border-0 cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">add</span> <?php esc_html_e( 'Add Dark Color', 'skaaa-no-code-design' ); ?>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <template x-for="(color, index) in darkColorsList" :key="index">
                        <div class="bg-slate-800 p-5 rounded-xl border border-slate-700 shadow-sm flex items-center gap-4 relative group">
                            <!-- Xóa Màu -->
                            <button @click="darkColorsList.splice(index, 1)" class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-rose-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition border-0 cursor-pointer shadow-sm">
                                <span class="material-symbols-outlined text-[14px]">close</span>
                            </button>
                            
                            <div class="w-16 h-16 rounded-lg border border-slate-600 shadow-inner flex-shrink-0 relative overflow-hidden" :style="'background-color: ' + color.value">
                                <input type="color" x-model="color.value" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            </div>
                            <div class="flex-1">
                                <input type="text" x-model="color.key" placeholder="e.g. primary" class="block w-full text-sm font-bold text-slate-200 mb-1 bg-transparent border-0 border-b border-transparent hover:border-slate-600 focus:border-indigo-400 outline-none p-0">
                                <input type="text" x-model="color.value" class="w-full px-3 py-1.5 border border-slate-600 bg-slate-900 text-slate-300 rounded text-sm focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none uppercase font-mono">
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Tab 2: Typography -->
        <div id="typography" class="p-8 max-w-4xl mx-auto space-y-8">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-2xl font-bold text-slate-900 m-0 border-0 p-0"><?php esc_html_e( 'Typography', 'skaaa-no-code-design' ); ?></h2>
                <p class="text-slate-500 mt-2"><?php esc_html_e( 'Define Primary, Secondary and Monospace Fonts from Google Fonts or Upload Custom Fonts.', 'skaaa-no-code-design' ); ?></p>
            </div>
            
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Primary Font', 'skaaa-no-code-design' ); ?></label>
                    <input type="text" x-model="formData.typography.primary" placeholder="e.g. 'Inter', sans-serif" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none">
                    <p class="text-xs text-slate-500 mt-2"><?php esc_html_e( 'Used for body text.', 'skaaa-no-code-design' ); ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Secondary Font (Headings)', 'skaaa-no-code-design' ); ?></label>
                    <input type="text" x-model="formData.typography.secondary" placeholder="e.g. 'Outfit', sans-serif" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none">
                    <p class="text-xs text-slate-500 mt-2"><?php esc_html_e( 'Used for Title tags H1, H2, H3...', 'skaaa-no-code-design' ); ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Monospace Font (Code)', 'skaaa-no-code-design' ); ?></label>
                    <input type="text" x-model="formData.typography.mono" placeholder="e.g. 'IBM Plex Mono', monospace" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none">
                    <p class="text-xs text-slate-500 mt-2"><?php esc_html_e( 'Used for code blocks, badges, numbers...', 'skaaa-no-code-design' ); ?></p>
                </div>
                <!-- Custom Font Upload -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Custom Font Upload (.woff2)', 'skaaa-no-code-design' ); ?></label>
                    <div class="flex gap-3">
                        <input type="text" x-model="formData.typography.customFontUrl" placeholder="<?php echo esc_attr( __( 'URL of the .woff2 file', 'skaaa-no-code-design' ) ); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none bg-slate-50" readonly>
                        <button @click.prevent="openFontUploader" class="shrink-0 px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-medium hover:bg-slate-900 transition border-0 cursor-pointer flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">upload</span> Upload
                        </button>
                        <button x-show="formData.typography.customFontUrl" @click.prevent="formData.typography.customFontUrl = ''" class="shrink-0 px-4 py-2 bg-rose-100 text-rose-700 rounded-lg text-sm font-medium hover:bg-rose-200 transition border-0 cursor-pointer flex items-center gap-2">
                            <?php esc_html_e( 'Remove', 'skaaa-no-code-design' ); ?>
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-2"><?php esc_html_e( 'Upload font file in .woff2 format to integrate directly into Tailwind Compiler.', 'skaaa-no-code-design' ); ?></p>
                </div>
                
                <!-- Typography Scale Presets -->
                <div class="mt-8 border-t border-slate-200 pt-8">
                    <h3 class="text-lg font-bold text-slate-800 mb-4"><?php esc_html_e( 'Typography Scale Presets', 'skaaa-no-code-design' ); ?></h3>
                    <div class="space-y-4">
                        <template x-for="(val, key) in formData.typography_scale" :key="key">
                            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                                <div class="w-24 shrink-0">
                                    <label class="block text-sm font-bold text-slate-700 capitalize" x-text="key"></label>
                                </div>
                                <div class="flex-1">
                                    <textarea x-model="formData.typography_scale[key]" rows="1" class="w-full px-3 py-2 border border-slate-300 rounded text-sm text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none font-mono resize-y"></textarea>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Spacing & Tokens -->
        <div id="spacing" class="p-8 max-w-4xl mx-auto space-y-8">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-2xl font-bold text-slate-900 m-0 border-0 p-0"><?php esc_html_e( 'Advanced Tokens', 'skaaa-no-code-design' ); ?></h2>
                <p class="text-slate-500 mt-2"><?php esc_html_e( 'Configure Global Tokens for large Grid System and UI Details.', 'skaaa-no-code-design' ); ?></p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Border Radius -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Global Border Radius', 'skaaa-no-code-design' ); ?></label>
                    <select x-model="formData.tokens.borderRadius" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none">
                        <option value="0px"><?php esc_html_e( 'Square (0px)', 'skaaa-no-code-design' ); ?></option>
                        <option value="4px"><?php esc_html_e( 'Lightly rounded corners (4px)', 'skaaa-no-code-design' ); ?></option>
                        <option value="8px"><?php esc_html_e( 'Medium rounded corners (8px)', 'skaaa-no-code-design' ); ?></option>
                        <option value="12px"><?php esc_html_e( 'Smooth rounded corners (12px)', 'skaaa-no-code-design' ); ?></option>
                        <option value="9999px"><?php esc_html_e( 'Pill shape', 'skaaa-no-code-design' ); ?></option>
                    </select>
                </div>

                <!-- Box Shadow -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Default Elevation (Shadow)', 'skaaa-no-code-design' ); ?></label>
                    <select x-model="formData.tokens.boxShadow" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none">
                        <option value="none"><?php esc_html_e( 'No shadow (Flat)', 'skaaa-no-code-design' ); ?></option>
                        <option value="0 1px 2px 0 rgb(0 0 0 / 0.05)"><?php esc_html_e( 'Super light matte (sm)', 'skaaa-no-code-design' ); ?></option>
                        <option value="0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)"><?php esc_html_e( 'Standard ball (md)', 'skaaa-no-code-design' ); ?></option>
                        <option value="0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)"><?php esc_html_e( 'Deep shadow (lg)', 'skaaa-no-code-design' ); ?></option>
                        <option value="0 25px 50px -12px rgb(0 0 0 / 0.25)"><?php esc_html_e( 'Giant ball (2xl)', 'skaaa-no-code-design' ); ?></option>
                    </select>
                </div>

                <!-- Container Width -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Container Max-Width', 'skaaa-no-code-design' ); ?></label>
                    <input type="text" x-model="formData.tokens.containerWidth" placeholder="e.g. 1280px or 80rem" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none">
                    <p class="text-xs text-slate-500 mt-2"><?php esc_html_e( 'Maximum title width limit for Layout App.', 'skaaa-no-code-design' ); ?></p>
                </div>

                <!-- Base Transition Duration -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Base Transition Duration', 'skaaa-no-code-design' ); ?></label>
                    <select x-model="formData.tokens.transitionDuration" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none">
                        <option value="150ms"><?php esc_html_e( 'Instant/Snappy (150ms)', 'skaaa-no-code-design' ); ?></option>
                        <option value="300ms"><?php esc_html_e( 'Smooth / Smooth (300ms)', 'skaaa-no-code-design' ); ?></option>
                        <option value="500ms"><?php esc_html_e( 'Slow / Relaxed (500ms)', 'skaaa-no-code-design' ); ?></option>
                    </select>
                    <p class="text-xs text-slate-500 mt-2"><?php esc_html_e( 'Standard speed used for Hover, Animation.', 'skaaa-no-code-design' ); ?></p>
                </div>

                <!-- Global Block Gap -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Global Block Gap', 'skaaa-no-code-design' ); ?></label>
                    <input type="text" x-model="formData.tokens.blockGap" placeholder="e.g. 1.5rem or 24px" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none">
                    <p class="text-xs text-slate-500 mt-2"><?php esc_html_e( 'Default vertical gap between blocks (Block Gap).', 'skaaa-no-code-design' ); ?></p>
                </div>

                <!-- Global Content Padding -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-2"><?php esc_html_e( 'Global Content Padding', 'skaaa-no-code-design' ); ?></label>
                    <input type="text" x-model="formData.tokens.contentPadding" placeholder="e.g. 1rem or 16px" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:border-indigo-500 outline-none">
                    <p class="text-xs text-slate-500 mt-2"><?php esc_html_e( 'Safe margins on the left/right sides of the content (Content Padding).', 'skaaa-no-code-design' ); ?></p>
                </div>
            </div>
        </div>

        <!-- Tab 4: Components -->
        <div id="components" class="p-8 max-w-4xl mx-auto space-y-8">
            <div class="border-b border-slate-200 pb-5 flex justify-between items-end">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 m-0 border-0 p-0"><?php esc_html_e( 'UI Presets', 'skaaa-no-code-design' ); ?></h2>
                    <p class="text-slate-500 mt-2"><?php esc_html_e( 'Manage list of UI Presets (such as Button, Card, Badge). ', 'skaaa-no-code-design' ); ?></p>
                </div>
                <button @click="addComponentPreset()" class="px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-sm font-semibold transition border-0 cursor-pointer flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span> <?php esc_html_e( 'Add Preset', 'skaaa-no-code-design' ); ?>
                </button>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-sm font-semibold text-slate-700 w-1/4"><?php esc_html_e( 'Preset name', 'skaaa-no-code-design' ); ?></th>
                            <th class="px-4 py-3 text-sm font-semibold text-slate-700"><?php esc_html_e( 'Tailwind Classes', 'skaaa-no-code-design' ); ?></th>
                            <th class="px-4 py-3 text-sm font-semibold text-slate-700 w-16 text-center"><?php esc_html_e( 'Erase', 'skaaa-no-code-design' ); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <template x-for="(preset, index) in formData.components" :key="index">
                            <tr>
                                <td class="px-4 py-4 align-top">
                                    <input type="text" x-model="preset.name" class="w-full border border-slate-300 bg-slate-50 rounded p-1.5 text-sm focus:border-indigo-500 focus:bg-white outline-none font-bold text-slate-700" placeholder="e.g. Button Primary">
                                </td>
                                <td class="px-4 py-4">
                                    <textarea x-model="preset.value" rows="2" class="w-full border border-slate-300 rounded p-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none font-mono" placeholder="bg-blue-500 text-white..."></textarea>
                                </td>
                                <td class="px-4 py-4 text-center align-top">
                                    <button @click="formData.components.splice(index, 1)" class="text-rose-400 hover:text-rose-600 transition bg-transparent border-0 cursor-pointer mt-2" title="<?php echo esc_attr( __( 'Delete Preset', 'skaaa-no-code-design' ) ); ?>">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="formData.components.length === 0">
                            <td colspan="3" class="px-4 py-8 text-center text-slate-500 text-sm">
                                <?php echo sprintf( esc_html__( 'There are no UI Presets yet. Please click %s to start.', 'skaaa-no-code-design' ), esc_html__( 'Add Preset', 'skaaa-no-code-design' ) ); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notification Toast -->
        <div x-show="toast.show" x-transition.duration.300ms style="display: none;" class="fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 z-50 text-white" :class="toast.type === 'error' ? 'bg-rose-600' : 'bg-emerald-600'">
            <span class="material-symbols-outlined text-[20px]" x-text="toast.type === 'error' ? 'error' : 'check_circle'"></span>
            <span class="text-sm font-medium" x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
function skaaaDesignTokensApp() {
    return {
        activeTab: 'colors',
        isSaving: false,
        tabs: [
            { id: 'colors', name: '<?php echo esc_js( __( 'Brand & Colors', 'skaaa-no-code-design' ) ); ?>', icon: 'palette' },
            { id: 'typography', name: '<?php echo esc_js( __( 'Typography', 'skaaa-no-code-design' ) ); ?>', icon: 'match_case' },
            { id: 'spacing', name: '<?php echo esc_js( __( 'Advanced Tokens', 'skaaa-no-code-design' ) ); ?>', icon: 'space_dashboard' },
            { id: 'components', name: '<?php echo esc_js( __( 'UI Components', 'skaaa-no-code-design' ) ); ?>', icon: 'interests' },
        ],
        formData: {
            brand: {
                logoUrl: ''
            },
            colors: {
                primary: '#3b82f6',
                secondary: '#10b981',
                tertiary: '#f59e0b',
                surface: '#ffffff',
                background: '#f9fafb',
                text: '#111827',
                border: '#e5e7eb',
                success: '#10b981',
                warning: '#f59e0b',
                error: '#ef4444',
                info: '#3b82f6'
            },
            darkColors: {
                primary: '#60a5fa',
                secondary: '#34d399',
                tertiary: '#fbbf24',
                surface: '#1f2937',
                background: '#111827',
                text: '#f9fafb',
                border: '#374151',
                success: '#34d399',
                warning: '#fbbf24',
                error: '#f87171',
                info: '#60a5fa'
            },
            typography: {
                primary: 'Inter, sans-serif',
                secondary: 'Outfit, sans-serif',
                mono: 'IBM Plex Mono, monospace',
                customFontUrl: ''
            },
            typography_scale: {
                h1: 'text-5xl font-bold tracking-tight leading-tight',
                h2: 'text-4xl font-bold tracking-tight leading-tight',
                h3: 'text-2xl font-semibold tracking-tight leading-snug',
                h4: 'text-lg font-bold leading-relaxed',
                p: 'text-base font-normal leading-relaxed',
                small: 'text-sm font-normal leading-relaxed',
            },
            tokens: {
                borderRadius: '6px',
                boxShadow: 'none',
                containerWidth: '1280px',
                transitionDuration: '150ms',
                blockGap: '1.5rem',
                contentPadding: '1rem'
            },
            components: [
                { name: 'Button Primary', value: 'bg-primary text-white hover:bg-blue-700 px-4 py-2 rounded-md font-semibold transition' },
                { name: 'Button Secondary', value: 'bg-transparent border border-primary text-primary hover:bg-blue-50 px-4 py-2 rounded-md font-semibold transition' },
                { name: 'Button Outline', value: 'bg-transparent border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md font-semibold transition' },
                { name: 'Card Default', value: 'bg-surface border border-gray-200 rounded-md p-4' },
                { name: 'Card Elevated', value: 'bg-surface shadow-md rounded-md p-4' },
                { name: 'Input Text', value: 'bg-surface border border-gray-200 text-gray-900 text-sm rounded-md focus:ring-primary focus:border-primary block w-full p-2.5' },
                { name: 'Input Label', value: 'block mb-2 text-sm font-medium text-gray-900' },
                { name: 'Badge Status', value: 'bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-green-400' },
                { name: 'Badge Filter', value: 'bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-gray-400 hover:bg-gray-200 cursor-pointer' }
            ]
        },
        colorsList: [],
        darkColorsList: [],
        toast: {
            show: false,
            message: '',
            type: 'success'
        },

        initApp() {
            this.fetchTokens();
        },

        scrollTo(id) {
            this.activeTab = id;
            const el = document.getElementById(id);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        onScroll(e) {
            const container = e.target;
            const scrollPos = container.scrollTop;
            
            for (const tab of this.tabs) {
                const el = document.getElementById(tab.id);
                if (el) {
                    const top = el.offsetTop - container.offsetTop;
                    // Kích hoạt trạng thái Tab khi phần tử đến gần top (Offset 150px)
                    if (scrollPos >= top - 150) {
                        this.activeTab = tab.id;
                    }
                }
            }
        },

        addComponentPreset() {
            const newName = prompt('<?php echo esc_js( __( 'Enter a new UI Preset name (eg Button Primary, Hero Card...):', 'skaaa-no-code-design' ) ); ?>');
            if (!newName) return;
            this.formData.components.push({
                name: newName,
                value: ''
            });
        },

        logoUploaderInstance: null,
        fontUploaderInstance: null,

        openLogoUploader() {
            if (this.logoUploaderInstance) {
                this.logoUploaderInstance.open();
                return;
            }
            this.logoUploaderInstance = wp.media({
                title: '<?php echo esc_js( __( 'Choose Brand Logo', 'skaaa-no-code-design' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Use this Logo', 'skaaa-no-code-design' ) ); ?>' },
                multiple: false
            });
            this.logoUploaderInstance.on('select', () => {
                const attachment = this.logoUploaderInstance.state().get('selection').first().toJSON();
                this.formData.brand.logoUrl = attachment.url;
            });
            this.logoUploaderInstance.open();
        },

        addColor() {
            this.colorsList.push({ key: 'new-color', value: '#e2e8f0' });
        },

        addDarkColor() {
            this.darkColorsList.push({ key: 'new-color', value: '#1e293b' });
        },

        openFontUploader() {
            if (this.fontUploaderInstance) {
                this.fontUploaderInstance.open();
                return;
            }
            this.fontUploaderInstance = wp.media({
                title: '<?php echo esc_js( __( 'Select Custom Font (.woff2)', 'skaaa-no-code-design' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Use this Font', 'skaaa-no-code-design' ) ); ?>' },
                multiple: false
            });
            this.fontUploaderInstance.on('select', () => {
                const attachment = this.fontUploaderInstance.state().get('selection').first().toJSON();
                this.formData.typography.customFontUrl = attachment.url;
            });
            this.fontUploaderInstance.open();
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3000);
        },

        async fetchTokens() {
            try {
                const response = await fetch('/wp-json/skaaa-design/v1/tokens', {
                    headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' }
                });
                const res = await response.json();
                
                if (res.success && res.data) {
                    const d = res.data;
                    if (d.colors && typeof d.colors === 'object' && !Array.isArray(d.colors) && Object.keys(d.colors).length > 0) {
                        this.formData.colors = { ...this.formData.colors, ...d.colors };
                    }
                    if (d.darkColors && typeof d.darkColors === 'object' && !Array.isArray(d.darkColors) && Object.keys(d.darkColors).length > 0) {
                        this.formData.darkColors = { ...this.formData.darkColors, ...d.darkColors };
                    }
                    if (d.brand && typeof d.brand === 'object' && !Array.isArray(d.brand) && Object.keys(d.brand).length > 0) {
                        this.formData.brand = { ...this.formData.brand, ...d.brand };
                        if (!this.formData.brand.logoUrl && d.brand.logourl) {
                            this.formData.brand.logoUrl = d.brand.logourl;
                        }
                    }
                    if (d.typography && typeof d.typography === 'object' && !Array.isArray(d.typography) && Object.keys(d.typography).length > 0) {
                        this.formData.typography = { ...this.formData.typography, ...d.typography };
                    }
                    if (d.typography_scale && typeof d.typography_scale === 'object' && !Array.isArray(d.typography_scale) && Object.keys(d.typography_scale).length > 0) {
                        this.formData.typography_scale = { ...this.formData.typography_scale, ...d.typography_scale };
                    }
                    if (d.tokens && typeof d.tokens === 'object' && !Array.isArray(d.tokens) && Object.keys(d.tokens).length > 0) {
                        this.formData.tokens = { ...this.formData.tokens, ...d.tokens };
                    }
                    if (d.components && Array.isArray(d.components) && d.components.length > 0) {
                        this.formData.components = d.components;
                    }
                }
                
                // Cập nhật colorsList từ formData.colors
                this.colorsList = Object.entries(this.formData.colors).map(([key, value]) => ({ key, value }));
                this.darkColorsList = Object.entries(this.formData.darkColors).map(([key, value]) => ({ key, value }));
            } catch (err) {
                console.error('Failed to fetch tokens', err);
            }
        },

        async saveTokens() {
            this.isSaving = true;

            // Map colorsList trở lại formData.colors
            this.formData.colors = {};
            this.colorsList.forEach(color => {
                if (color.key && color.key.trim() !== '') {
                    // Normalize key
                    const key = color.key.trim().toLowerCase().replace(/[^a-z0-9-]/g, '-');
                    this.formData.colors[key] = color.value;
                }
            });
            this.formData.darkColors = {};
            this.darkColorsList.forEach(color => {
                if (color.key && color.key.trim() !== '') {
                    // Normalize key
                    const key = color.key.trim().toLowerCase().replace(/[^a-z0-9-]/g, '-');
                    this.formData.darkColors[key] = color.value;
                }
            });
            try {
                const response = await fetch('/wp-json/skaaa-design/v1/tokens', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>' 
                    },
                    body: JSON.stringify(this.formData)
                });
                
                const res = await response.json();
                
                if (res.success) {
                    this.showToast('<?php echo esc_js( __( 'Successfully saved Token & Generated Cache File!', 'skaaa-no-code-design' ) ); ?>');
                } else {
                    this.showToast(res.message || '<?php echo esc_js( __( 'Error saving data', 'skaaa-no-code-design' ) ); ?>', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('<?php echo esc_js( __( 'Network error', 'skaaa-no-code-design' ) ); ?>', 'error');
            } finally {
                this.isSaving = false;
            }
        }
    }
}
</script>
<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
