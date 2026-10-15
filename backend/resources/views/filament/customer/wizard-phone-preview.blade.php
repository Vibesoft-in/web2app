<div 
    x-data="{
        cartCount: 2,
        activeCategory: 'all',
        activeTab: 'home',
        showToast: false,

        get preset() {
            return ($wire && $wire.data && $wire.data.theme_preset) ? $wire.data.theme_preset : 'minimalist';
        },

        get primaryColor() {
            return ($wire && $wire.data && $wire.data.primary_color) ? $wire.data.primary_color : '#6366f1';
        },

        get secondaryColor() {
            return ($wire && $wire.data && $wire.data.secondary_color) ? $wire.data.secondary_color : '#10b981';
        },

        get appName() {
            return ($wire && $wire.data && $wire.data.name && $wire.data.name.trim() !== '') ? $wire.data.name : 'My Store App';
        },

        get bannerText() {
            return ($wire && $wire.data && $wire.data.promo_banner_text && $wire.data.promo_banner_text.trim() !== '') 
                ? $wire.data.promo_banner_text 
                : '30% OFF Your First Mobile Order';
        },

        get logoUrl() {
            if ($wire && $wire.data) {
                if ($wire.data.logo_url && typeof $wire.data.logo_url === 'string' && $wire.data.logo_url.startsWith('http')) {
                    return $wire.data.logo_url;
                }
                if ($wire.data.logo_image && typeof $wire.data.logo_image === 'string' && $wire.data.logo_image.length > 0) {
                    return '/storage/' + $wire.data.logo_image;
                }
            }
            return null;
        },

        setTheme(name) {
            if ($wire) {
                $wire.set('data.theme_preset', name);
                if (name === 'boutique') {
                    $wire.set('data.primary_color', '#f43f5e');
                    $wire.set('data.secondary_color', '#fb7185');
                } else if (name === 'apex') {
                    $wire.set('data.primary_color', '#10b981');
                    $wire.set('data.secondary_color', '#38bdf8');
                } else {
                    $wire.set('data.primary_color', '#6366f1');
                    $wire.set('data.secondary_color', '#10b981');
                }
            }
        },

        setColor(hex) {
            if ($wire) {
                $wire.set('data.primary_color', hex);
            }
        },

        addToCart(title) {
            this.cartCount++;
            this.showToast = true;
            setTimeout(() => {
                this.showToast = false;
            }, 2200);
        }
    }"
    class="sticky top-4 w-full flex flex-col items-center justify-start select-none font-sans"
>
    {{-- Device Header & Quick Presets Toolbar --}}
    <div class="w-full max-w-[300px] sm:max-w-[310px] mb-2.5 p-2 rounded-2xl bg-slate-900 border border-slate-800 text-white shadow-lg space-y-1.5">
        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[11px] font-bold tracking-wide text-slate-200 uppercase">Live Phone Emulator</span>
            </div>
            <span class="text-[9px] px-1.5 py-0.2 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-medium">Real-time</span>
        </div>

        {{-- 3-Way Theme Switcher Grid --}}
        <div class="grid grid-cols-3 gap-1 p-0.5 bg-slate-950/80 rounded-xl border border-slate-800 text-[10px] font-medium text-center">
            <button 
                type="button" 
                @click="setTheme('minimalist')" 
                :class="preset === 'minimalist' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'"
                class="py-1 px-1 rounded-lg transition-all truncate"
            >
                Minimalist
            </button>
            <button 
                type="button" 
                @click="setTheme('boutique')" 
                :class="preset === 'boutique' ? 'bg-rose-600 text-white shadow' : 'text-slate-400 hover:text-white'"
                class="py-1 px-1 rounded-lg transition-all truncate"
            >
                Boutique
            </button>
            <button 
                type="button" 
                @click="setTheme('apex')" 
                :class="preset === 'apex' ? 'bg-slate-700 text-emerald-400 shadow' : 'text-slate-400 hover:text-white'"
                class="py-1 px-1 rounded-lg transition-all truncate"
            >
                Apex Dark
            </button>
        </div>

        {{-- Color Quick Picks Row --}}
        <div class="flex items-center justify-between px-1 pt-1 border-t border-slate-800/80 text-[10px]">
            <span class="text-slate-400 flex items-center gap-1">
                Accent:
                <span class="inline-block w-2.5 h-2.5 rounded-full border border-white/20 shadow-xs" :style="{ backgroundColor: primaryColor }"></span>
            </span>
            <div class="flex items-center gap-1">
                <button type="button" @click="setColor('#6366f1')" class="w-3.5 h-3.5 rounded-full bg-indigo-500 hover:scale-125 transition-transform ring-1 ring-white/20" title="Indigo"></button>
                <button type="button" @click="setColor('#0ea5e9')" class="w-3.5 h-3.5 rounded-full bg-sky-500 hover:scale-125 transition-transform ring-1 ring-white/20" title="Sky"></button>
                <button type="button" @click="setColor('#10b981')" class="w-3.5 h-3.5 rounded-full bg-emerald-500 hover:scale-125 transition-transform ring-1 ring-white/20" title="Emerald"></button>
                <button type="button" @click="setColor('#f43f5e')" class="w-3.5 h-3.5 rounded-full bg-rose-500 hover:scale-125 transition-transform ring-1 ring-white/20" title="Rose"></button>
                <button type="button" @click="setColor('#f59e0b')" class="w-3.5 h-3.5 rounded-full bg-amber-500 hover:scale-125 transition-transform ring-1 ring-white/20" title="Amber"></button>
                <button type="button" @click="setColor('#8b5cf6')" class="w-3.5 h-3.5 rounded-full bg-purple-500 hover:scale-125 transition-transform ring-1 ring-white/20" title="Purple"></button>
            </div>
        </div>
    </div>

    {{-- Smartphone Frame Shell --}}
    <div 
        id="wizard-phone-device-chassis"
        :class="{
            'bg-white text-slate-900 border-slate-900': preset === 'minimalist',
            'bg-amber-50/50 text-slate-900 border-slate-900': preset === 'boutique',
            'bg-slate-950 text-slate-100 border-slate-800': preset === 'apex'
        }"
        class="relative w-full max-w-[290px] sm:max-w-[310px] h-[580px] sm:h-[610px] rounded-[42px] border-[8px] shadow-[0_20px_50px_rgba(0,0,0,0.6)] overflow-hidden flex flex-col transition-all duration-300 ring-1 ring-black/10 box-border"
        :style="{ '--app-primary': primaryColor, '--app-secondary': secondaryColor }"
    >
        {{-- Side Hardware Buttons --}}
        <div class="absolute -left-[10px] top-16 w-[3px] h-7 bg-slate-700 rounded-l"></div>
        <div class="absolute -left-[10px] top-28 w-[3px] h-10 bg-slate-700 rounded-l"></div>
        <div class="absolute -left-[10px] top-42 w-[3px] h-10 bg-slate-700 rounded-l"></div>
        <div class="absolute -right-[10px] top-24 w-[3px] h-12 bg-slate-700 rounded-r"></div>

        {{-- Dynamic Island & Status Bar --}}
        <div class="relative z-30 pt-2 px-4 flex items-center justify-between text-[10px] font-semibold tracking-tight"
            :class="preset === 'apex' ? 'text-slate-300' : 'text-slate-800'">
            <span>9:41</span>

            {{-- Pill Notch --}}
            <div class="absolute left-1/2 -translate-x-1/2 top-1.5 h-4 w-18 bg-black rounded-full flex items-center justify-between px-2 shadow-inner">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-900 border border-slate-700 flex items-center justify-center">
                    <span class="w-0.5 h-0.5 rounded-full bg-indigo-500/80"></span>
                </span>
                <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>

            <div class="flex items-center gap-1">
                {{-- Signal Icon --}}
                <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="2" y="16" width="3" height="6" rx="1"/>
                    <rect x="7" y="12" width="3" height="10" rx="1"/>
                    <rect x="12" y="8" width="3" height="14" rx="1"/>
                    <rect x="17" y="4" width="3" height="18" rx="1"/>
                </svg>
                {{-- Wifi Icon --}}
                <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4C7.31 4 3.07 5.9 0 8.98L12 21 24 8.98C20.93 5.9 16.69 4 12 4zm0 4c3.34 0 6.38 1.38 8.56 3.61L12 19.33 3.44 11.61C5.62 9.38 8.66 8 12 8z"/>
                </svg>
                {{-- Battery Icon --}}
                <div class="w-3.5 h-2 rounded-2xs border border-current p-0.5 flex items-center">
                    <div class="h-full bg-current rounded-3xs w-2"></div>
                </div>
            </div>
        </div>

        {{-- App Header Bar --}}
        <div class="relative z-20 px-3 pt-2 pb-1.5 flex items-center justify-between border-b"
            :class="preset === 'apex' ? 'border-slate-800/90 bg-slate-950/90' : 'border-slate-100 bg-white/90 backdrop-blur-sm'"
        >
            <div class="flex items-center gap-1.5">
                <button type="button" class="p-0.5 rounded-md hover:bg-black/5 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h12M4 18h16" />
                    </svg>
                </button>
                
                {{-- Dynamic Logo / Monogram --}}
                <div class="flex items-center gap-1">
                    <template x-if="logoUrl">
                        <img :src="logoUrl" alt="Logo" class="w-5 h-5 rounded-md object-contain border border-slate-200/50 shadow-xs" />
                    </template>
                    <template x-if="!logoUrl">
                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-white text-[10px] font-black shadow-xs"
                            :style="{ backgroundColor: primaryColor }">
                            <span x-text="(appName ? appName.charAt(0).toUpperCase() : 'M')"></span>
                        </span>
                    </template>
                    
                    <h3 class="text-[11px] font-bold tracking-tight truncate max-w-[105px]" 
                        :class="preset === 'apex' ? 'text-white' : 'text-slate-900'"
                        x-text="appName">
                    </h3>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <button type="button" class="p-0.5 rounded-md hover:bg-black/5 transition-colors relative">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="absolute top-0 right-0 w-1 h-1 rounded-full" :style="{ backgroundColor: primaryColor }"></span>
                </button>

                <button type="button" @click="addToCart()" class="p-0.5 rounded-md hover:bg-black/5 transition-colors relative">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span 
                        x-text="cartCount"
                        class="absolute -top-1 -right-1 w-3 h-3 rounded-full text-white text-[7px] font-bold flex items-center justify-center shadow-xs"
                        :style="{ backgroundColor: primaryColor }"
                    >2</span>
                </button>
            </div>
        </div>

        {{-- Phone Screen Body (Scrollable) --}}
        <div class="flex-1 overflow-y-auto px-2.5 py-2 space-y-2.5 scrollbar-thin scrollbar-thumb-slate-300">

            {{-- In-App Floating Toast --}}
            <div 
                x-show="showToast" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="bg-slate-900/95 text-white text-[9px] py-1 px-2 rounded-xl shadow-lg border border-slate-700 flex items-center justify-between"
            >
                <span class="flex items-center gap-1">
                    <span class="text-emerald-400 font-bold">✓</span> Added to cart!
                </span>
                <span class="text-[8px] text-slate-400">Checkout &rarr;</span>
            </div>

            {{-- In-App Search Bar --}}
            <div class="relative">
                <input 
                    type="text" 
                    :placeholder="`Search in ${appName}...`" 
                    class="w-full text-[10px] rounded-xl py-1.5 pl-6 pr-2.5 border transition-colors outline-none"
                    :class="preset === 'apex' 
                        ? 'bg-slate-900 border-slate-800 text-slate-200 placeholder-slate-500 focus:border-indigo-500' 
                        : 'bg-slate-100/90 border-slate-200 text-slate-800 placeholder-slate-400 focus:border-indigo-400'"
                />
                <svg class="w-2.5 h-2.5 text-slate-400 absolute left-2 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- Dynamic Hero Promo Banner --}}
            <div 
                class="rounded-xl p-2.5 text-white relative overflow-hidden shadow-xs flex flex-col justify-between min-h-[95px]"
                :style="{
                    background: `linear-gradient(135deg, ${primaryColor} 0%, #0f172a 100%)`
                }"
            >
                <div class="relative z-10 max-w-[155px]">
                    <span 
                        class="inline-block px-1.5 py-0.2 rounded text-[7px] font-extrabold uppercase tracking-wider text-white backdrop-blur-sm mb-1"
                        :style="{ backgroundColor: secondaryColor }"
                    >
                        Special Offer
                    </span>
                    <h4 class="text-[11px] font-extrabold leading-tight text-white line-clamp-2" x-text="bannerText"></h4>
                    <p class="text-[8px] text-white/80 mt-0.5 font-medium">Use code: <span class="font-bold underline">APPSTORE</span></p>
                </div>

                <div class="relative z-10 mt-1.5 flex items-center justify-between">
                    <button 
                        type="button" 
                        class="px-2 py-0.5 rounded-md text-[8px] font-bold bg-white text-slate-900 shadow hover:bg-slate-100 active:scale-95 transition-all"
                    >
                        Shop Now &rarr;
                    </button>
                    <div class="flex items-center gap-0.5">
                        <span class="w-1 h-1 rounded-full bg-white"></span>
                        <span class="w-1 h-1 rounded-full bg-white/40"></span>
                        <span class="w-1 h-1 rounded-full bg-white/40"></span>
                    </div>
                </div>

                <div class="absolute -right-2 -bottom-4 w-16 h-16 rounded-full bg-white/10 blur-lg pointer-events-none"></div>
                <div class="absolute right-1.5 top-1.5 text-xl opacity-20 pointer-events-none">✨</div>
            </div>

            {{-- Categories Horizontal Pills --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h5 class="text-[9px] font-bold tracking-tight" :class="preset === 'apex' ? 'text-slate-300' : 'text-slate-800'">
                        Categories
                    </h5>
                    <span class="text-[8px] text-slate-400 font-medium">View all</span>
                </div>
                <div class="flex items-center gap-1 overflow-x-auto pb-0.5 scrollbar-none text-[9px]">
                    <button 
                        type="button" 
                        @click="activeCategory = 'all'" 
                        :class="activeCategory === 'all' ? 'text-white shadow-xs' : (preset === 'apex' ? 'bg-slate-900 text-slate-400' : 'bg-slate-100 text-slate-600')"
                        :style="activeCategory === 'all' ? { backgroundColor: primaryColor } : {}"
                        class="px-2 py-0.5 rounded-full font-semibold whitespace-nowrap transition-all"
                    >
                        All
                    </button>
                    <button 
                        type="button" 
                        @click="activeCategory = 'trending'" 
                        :class="activeCategory === 'trending' ? 'text-white shadow-xs' : (preset === 'apex' ? 'bg-slate-900 text-slate-400' : 'bg-slate-100 text-slate-600')"
                        :style="activeCategory === 'trending' ? { backgroundColor: primaryColor } : {}"
                        class="px-2 py-0.5 rounded-full font-semibold whitespace-nowrap transition-all"
                    >
                        🔥 Popular
                    </button>
                    <button 
                        type="button" 
                        @click="activeCategory = 'clothing'" 
                        :class="activeCategory === 'clothing' ? 'text-white shadow-xs' : (preset === 'apex' ? 'bg-slate-900 text-slate-400' : 'bg-slate-100 text-slate-600')"
                        :style="activeCategory === 'clothing' ? { backgroundColor: primaryColor } : {}"
                        class="px-2 py-0.5 rounded-full font-semibold whitespace-nowrap transition-all"
                    >
                        👗 Fashion
                    </button>
                    <button 
                        type="button" 
                        @click="activeCategory = 'shoes'" 
                        :class="activeCategory === 'shoes' ? 'text-white shadow-xs' : (preset === 'apex' ? 'bg-slate-900 text-slate-400' : 'bg-slate-100 text-slate-600')"
                        :style="activeCategory === 'shoes' ? { backgroundColor: primaryColor } : {}"
                        class="px-2 py-0.5 rounded-full font-semibold whitespace-nowrap transition-all"
                    >
                        👟 Shoes
                    </button>
                </div>
            </div>

            {{-- 2-Column Product Grid --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h5 class="text-[9px] font-bold tracking-tight" :class="preset === 'apex' ? 'text-slate-300' : 'text-slate-800'">
                        Featured Items
                    </h5>
                    <span class="text-[8px] text-slate-400 font-medium">4 items</span>
                </div>

                <div class="grid grid-cols-2 gap-1.5">
                    {{-- Product 1 --}}
                    <div 
                        class="rounded-lg border p-1.5 flex flex-col justify-between transition-all"
                        :class="preset === 'apex' ? 'bg-slate-900/80 border-slate-800 text-white' : 'bg-white border-slate-100 shadow-2xs text-slate-900'"
                    >
                        <div class="relative w-full h-18 rounded-md bg-gradient-to-br from-indigo-50 to-slate-100 flex items-center justify-center overflow-hidden mb-1">
                            <span class="text-2xl">👟</span>
                            <span 
                                class="absolute top-0.5 left-0.5 px-1 py-0.2 rounded text-[6px] font-extrabold uppercase text-white shadow-xs"
                                :style="{ backgroundColor: secondaryColor }"
                            >
                                -25%
                            </span>
                        </div>
                        <div>
                            <h6 class="text-[9px] font-bold truncate">Retro Sneakers</h6>
                            <div class="flex items-center justify-between mt-0.5">
                                <span class="text-[10px] font-black" :style="{ color: primaryColor }">$89.00</span>
                                <button 
                                    type="button" 
                                    @click="addToCart('Retro Sneakers')"
                                    class="w-4 h-4 rounded-md flex items-center justify-center text-white text-[10px] font-bold shadow-xs active:scale-90 transition-transform"
                                    :style="{ backgroundColor: primaryColor }"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Product 2 --}}
                    <div 
                        class="rounded-lg border p-1.5 flex flex-col justify-between transition-all"
                        :class="preset === 'apex' ? 'bg-slate-900/80 border-slate-800 text-white' : 'bg-white border-slate-100 shadow-2xs text-slate-900'"
                    >
                        <div class="relative w-full h-18 rounded-md bg-gradient-to-br from-rose-50 to-slate-100 flex items-center justify-center overflow-hidden mb-1">
                            <span class="text-2xl">👜</span>
                            <span 
                                class="absolute top-0.5 left-0.5 px-1 py-0.2 rounded text-[6px] font-extrabold uppercase text-white shadow-xs"
                                :style="{ backgroundColor: secondaryColor }"
                            >
                                Hot
                            </span>
                        </div>
                        <div>
                            <h6 class="text-[9px] font-bold truncate">Leather Bag</h6>
                            <div class="flex items-center justify-between mt-0.5">
                                <span class="text-[10px] font-black" :style="{ color: primaryColor }">$120.00</span>
                                <button 
                                    type="button" 
                                    @click="addToCart('Leather Bag')"
                                    class="w-4 h-4 rounded-md flex items-center justify-center text-white text-[10px] font-bold shadow-xs active:scale-90 transition-transform"
                                    :style="{ backgroundColor: primaryColor }"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Mobile Bottom Navigation Bar --}}
        <div 
            class="relative z-20 px-2.5 py-1.5 flex items-center justify-around border-t"
            :class="preset === 'apex' ? 'border-slate-800 bg-slate-950 text-slate-400' : 'border-slate-100 bg-white/95 text-slate-500'"
        >
            <button 
                type="button" 
                @click="activeTab = 'home'" 
                class="flex flex-col items-center gap-0.5 transition-colors"
                :class="activeTab === 'home' ? 'font-bold' : 'text-slate-400'"
                :style="activeTab === 'home' ? { color: primaryColor } : {}"
            >
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                </svg>
                <span class="text-[7px]">Home</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'categories'" 
                class="flex flex-col items-center gap-0.5 transition-colors"
                :class="activeTab === 'categories' ? 'font-bold' : 'text-slate-400'"
                :style="activeTab === 'categories' ? { color: primaryColor } : {}"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span class="text-[7px]">Catalog</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'cart'" 
                class="flex flex-col items-center gap-0.5 transition-colors relative"
                :class="activeTab === 'cart' ? 'font-bold' : 'text-slate-400'"
                :style="activeTab === 'cart' ? { color: primaryColor } : {}"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-[7px]">Cart</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'account'" 
                class="flex flex-col items-center gap-0.5 transition-colors"
                :class="activeTab === 'account' ? 'font-bold' : 'text-slate-400'"
                :style="activeTab === 'account' ? { color: primaryColor } : {}"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[7px]">Account</span>
            </button>
        </div>

        {{-- iPhone Home Indicator --}}
        <div class="h-2.5 flex items-center justify-center pb-0.5">
            <div class="w-20 h-0.5 rounded-full" :class="preset === 'apex' ? 'bg-slate-700' : 'bg-slate-300'"></div>
        </div>
    </div>
</div>
