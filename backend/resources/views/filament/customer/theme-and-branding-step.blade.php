<div 
    x-data="{
        get preset() {
            return ($wire && $wire.data && $wire.data.theme_preset) ? $wire.data.theme_preset : 'minimalist';
        },
        get primaryColor() {
            return ($wire && $wire.data && $wire.data.primary_color) ? $wire.data.primary_color : '#6366F1';
        },
        get secondaryColor() {
            return ($wire && $wire.data && $wire.data.secondary_color) ? $wire.data.secondary_color : '#06B6D4';
        },
        get appName() {
            return ($wire && $wire.data && $wire.data.name && $wire.data.name.trim() !== '') ? $wire.data.name : 'qwrt';
        },
        get appInitial() {
            return this.appName.charAt(0).toUpperCase();
        },

        setTheme(theme) {
            if ($wire) {
                $wire.set('data.theme_preset', theme);
                if (theme === 'boutique') {
                    $wire.set('data.primary_color', '#F43F5E');
                    $wire.set('data.secondary_color', '#FB7185');
                } else if (theme === 'apex') {
                    $wire.set('data.primary_color', '#10B981');
                    $wire.set('data.secondary_color', '#06B6D4');
                } else {
                    $wire.set('data.primary_color', '#6366F1');
                    $wire.set('data.secondary_color', '#06B6D4');
                }
            }
        },

        setPrimary(color) {
            if ($wire) {
                $wire.set('data.primary_color', color.toUpperCase());
            }
        },

        setSecondary(color) {
            if ($wire) {
                $wire.set('data.secondary_color', color.toUpperCase());
            }
        }
    }"
    class="w-full flex flex-col lg:flex-row items-start justify-between gap-6 font-sans text-slate-100 select-none"
>
    {{-- ========================================================================= --}}
    {{-- LEFT SECTION: Visual Theme Preset & Brand Colors & Palette Cards          --}}
    {{-- ========================================================================= --}}
    <div class="w-full lg:flex-1 space-y-4">

        {{-- Card 1: Visual Theme Preset --}}
        <div class="rounded-2xl bg-[#13161f] border border-slate-800/80 p-5 shadow-xl transition-all">
            {{-- Header --}}
            <div class="flex items-start gap-3 mb-4">
                <div class="w-9 h-9 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-slate-300 shadow-sm shrink-0">
                    <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21a4 4 0 01-4-4 8.001 8.001 0 0113.109-6.07A8.001 8.001 0 0121 17a4 4 0 01-4 4 4 4 0 01-4-4H9a4 4 0 01-2 3.874z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white tracking-wide">Visual Theme Preset</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Choose an aesthetic foundation tailored for your store</p>
                </div>
            </div>

            <div class="space-y-3 pt-1">
                <label class="text-xs font-semibold text-slate-300 tracking-wide block">Select Theme Preset</label>

                {{-- Preset Option A: Modern Minimalist --}}
                <div 
                    @click="setTheme('minimalist')"
                    :class="preset === 'minimalist' ? 'border-indigo-500/80 bg-slate-900/60 shadow-md ring-1 ring-indigo-500/30' : 'border-slate-800 hover:border-slate-700 bg-slate-950/40'"
                    class="rounded-xl border p-3 flex items-center gap-3.5 cursor-pointer transition-all duration-200"
                >
                    {{-- Custom Radio Indicator --}}
                    <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-all"
                        :class="preset === 'minimalist' ? 'border-indigo-500 bg-indigo-600' : 'border-slate-600 bg-transparent'">
                        <div x-show="preset === 'minimalist'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                    </div>

                    {{-- Visual Mockup Thumbnail --}}
                    <div class="w-[84px] h-[52px] rounded-lg bg-gradient-to-br from-indigo-50 to-slate-100 border border-slate-300/60 p-1 flex flex-col justify-between shrink-0 shadow-inner overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="w-3.5 h-1.5 rounded-xs bg-indigo-400"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="space-y-1 flex-1">
                                <span class="block w-full h-1 rounded-2xs bg-slate-300"></span>
                                <span class="block w-2/3 h-1 rounded-2xs bg-slate-300"></span>
                            </div>
                            <div class="w-7 h-7 rounded-md bg-indigo-200/60 border border-indigo-300/40 flex items-center justify-center text-indigo-500">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-white tracking-tight">Theme A: Modern Minimalist</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5 leading-snug">Clean card layouts, subtle shadows, Inter font</p>
                    </div>
                </div>

                {{-- Preset Option B: Boutique Glamour --}}
                <div 
                    @click="setTheme('boutique')"
                    :class="preset === 'boutique' ? 'border-rose-500/80 bg-slate-900/60 shadow-md ring-1 ring-rose-500/30' : 'border-slate-800 hover:border-slate-700 bg-slate-950/40'"
                    class="rounded-xl border p-3 flex items-center gap-3.5 cursor-pointer transition-all duration-200"
                >
                    {{-- Custom Radio Indicator --}}
                    <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-all"
                        :class="preset === 'boutique' ? 'border-rose-500 bg-rose-600' : 'border-slate-600 bg-transparent'">
                        <div x-show="preset === 'boutique'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                    </div>

                    {{-- Visual Mockup Thumbnail --}}
                    <div class="w-[84px] h-[52px] rounded-lg bg-[#fff7ed] border border-[#fed7aa] p-1 flex flex-col justify-between shrink-0 shadow-inner overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="w-3.5 h-1.5 rounded-xs bg-[#f43f5e]"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="space-y-1 flex-1">
                                <span class="block w-full h-1 rounded-2xs bg-amber-200"></span>
                                <span class="block w-2/3 h-1 rounded-2xs bg-rose-200"></span>
                            </div>
                            <div class="w-7 h-7 rounded-md bg-white border border-amber-200 overflow-hidden shrink-0 shadow-xs">
                                <img src="/images/handbag.jpg" alt="Boutique" class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-white tracking-tight">Theme B: Boutique Glamour</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5 leading-snug">Vibrant accents, rounded cards, Poppins font</p>
                    </div>
                </div>

                {{-- Preset Option C: Apex Dark Luxury --}}
                <div 
                    @click="setTheme('apex')"
                    :class="preset === 'apex' ? 'border-emerald-500/80 bg-slate-900/60 shadow-md ring-1 ring-emerald-500/30' : 'border-slate-800 hover:border-slate-700 bg-slate-950/40'"
                    class="rounded-xl border p-3 flex items-center gap-3.5 cursor-pointer transition-all duration-200"
                >
                    {{-- Custom Radio Indicator --}}
                    <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-all"
                        :class="preset === 'apex' ? 'border-emerald-500 bg-emerald-600' : 'border-slate-600 bg-transparent'">
                        <div x-show="preset === 'apex'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                    </div>

                    {{-- Visual Mockup Thumbnail --}}
                    <div class="w-[84px] h-[52px] rounded-lg bg-[#0b0f19] border border-amber-800/40 p-1 flex flex-col justify-between shrink-0 shadow-inner overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="w-3.5 h-1.5 rounded-xs bg-amber-500"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400/80"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="space-y-1 flex-1">
                                <span class="block w-full h-1 rounded-2xs bg-slate-700"></span>
                                <span class="block w-2/3 h-1 rounded-2xs bg-amber-600/60"></span>
                            </div>
                            <div class="w-7 h-7 rounded-md bg-slate-900 border border-amber-500/40 overflow-hidden shrink-0 shadow-xs">
                                <img src="/images/luxury_watch.jpg" alt="Luxury" class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-white tracking-tight">Theme C: Apex Dark Luxury</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5 leading-snug">Sleek dark mode luxury palette with Outfit typography</p>
                    </div>
                </div>

            </div>
        </div>

        {{-- Card 2: Brand Colors & Palette --}}
        <div class="rounded-2xl bg-[#13161f] border border-slate-800/80 p-5 shadow-xl transition-all">
            {{-- Header --}}
            <div class="flex items-start gap-3 mb-4">
                <div class="w-9 h-9 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-slate-300 shadow-sm shrink-0">
                    <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white tracking-wide">Brand Colors & Palette</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Customize your brand accent tones</p>
                </div>
            </div>

            {{-- Color Inputs (Side-by-Side) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1 mb-4">
                {{-- Primary Color Input --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-300">Primary Brand Color</label>
                    <div class="flex items-center gap-2.5 p-2 rounded-xl bg-[#0d1016] border border-slate-800 shadow-inner">
                        <label class="relative cursor-pointer shrink-0">
                            <span class="block w-8 h-8 rounded-full border border-white/20 shadow-sm transition-transform hover:scale-105"
                                  :style="{ backgroundColor: primaryColor }"></span>
                            <input 
                                type="color" 
                                :value="primaryColor" 
                                @input="setPrimary($event.target.value)" 
                                class="opacity-0 absolute inset-0 w-full h-full cursor-pointer" 
                            />
                        </label>
                        <input 
                            type="text" 
                            :value="primaryColor" 
                            @input="setPrimary($event.target.value)" 
                            class="bg-transparent text-xs font-mono font-bold text-white uppercase outline-none w-full tracking-wider" 
                            maxlength="7"
                        />
                    </div>
                </div>

                {{-- Secondary Color Input --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-300">Secondary / Accent Color</label>
                    <div class="flex items-center gap-2.5 p-2 rounded-xl bg-[#0d1016] border border-slate-800 shadow-inner">
                        <label class="relative cursor-pointer shrink-0">
                            <span class="block w-8 h-8 rounded-full border border-white/20 shadow-sm transition-transform hover:scale-105"
                                  :style="{ backgroundColor: secondaryColor }"></span>
                            <input 
                                type="color" 
                                :value="secondaryColor" 
                                @input="setSecondary($event.target.value)" 
                                class="opacity-0 absolute inset-0 w-full h-full cursor-pointer" 
                            />
                        </label>
                        <input 
                            type="text" 
                            :value="secondaryColor" 
                            @input="setSecondary($event.target.value)" 
                            class="bg-transparent text-xs font-mono font-bold text-white uppercase outline-none w-full tracking-wider" 
                            maxlength="7"
                        />
                    </div>
                </div>
            </div>

            {{-- Preset Color Palette Chips (7 circular dots) --}}
            <div class="space-y-2 pt-1 border-t border-slate-800/80">
                <label class="text-xs font-semibold text-slate-300 tracking-wide block">Preset Color Palette</label>
                <div class="flex items-center gap-3">
                    <button type="button" @click="setPrimary('#6366F1')" class="w-7 h-7 rounded-full bg-[#6366F1] hover:scale-115 active:scale-95 transition-all shadow-md ring-1 ring-white/20" title="#6366F1"></button>
                    <button type="button" @click="setPrimary('#06B6D4')" class="w-7 h-7 rounded-full bg-[#06B6D4] hover:scale-115 active:scale-95 transition-all shadow-md ring-1 ring-white/20" title="#06B6D4"></button>
                    <button type="button" @click="setPrimary('#10B981')" class="w-7 h-7 rounded-full bg-[#10B981] hover:scale-115 active:scale-95 transition-all shadow-md ring-1 ring-white/20" title="#10B981"></button>
                    <button type="button" @click="setPrimary('#F59E0B')" class="w-7 h-7 rounded-full bg-[#F59E0B] hover:scale-115 active:scale-95 transition-all shadow-md ring-1 ring-white/20" title="#F59E0B"></button>
                    <button type="button" @click="setPrimary('#EF4444')" class="w-7 h-7 rounded-full bg-[#EF4444] hover:scale-115 active:scale-95 transition-all shadow-md ring-1 ring-white/20" title="#EF4444"></button>
                    <button type="button" @click="setPrimary('#EC4899')" class="w-7 h-7 rounded-full bg-[#EC4899] hover:scale-115 active:scale-95 transition-all shadow-md ring-1 ring-white/20" title="#EC4899"></button>
                    <button type="button" @click="setPrimary('#FFFFFF')" class="w-7 h-7 rounded-full bg-white hover:scale-115 active:scale-95 transition-all shadow-md ring-1 ring-slate-400/40" title="#FFFFFF"></button>
                </div>
            </div>

        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- RIGHT SECTION: Live Emulator (Full Phone Displayed With NO Scroll)        --}}
    {{-- ========================================================================= --}}
    <div class="w-full lg:w-[320px] shrink-0 flex flex-col items-center">
        
        {{-- Section Title --}}
        <div class="w-full mb-2.5 px-1">
            <h2 class="text-base font-bold text-white tracking-wide">Live Emulator</h2>
        </div>

        {{-- iPhone 15/16 Pro Physical Chassis --}}
        <div 
            id="emulator-phone-chassis"
            class="relative w-[305px] h-[540px] rounded-[42px] border-[7px] border-[#18191d] bg-[#18191d] shadow-[0_20px_50px_rgba(0,0,0,0.85)] ring-1 ring-white/10 flex flex-col overflow-hidden box-border select-none"
        >
            {{-- Hardware Buttons Glare --}}
            <div class="absolute -left-[9px] top-16 w-[2px] h-6 bg-slate-600 rounded-l"></div>
            <div class="absolute -left-[9px] top-26 w-[2px] h-9 bg-slate-600 rounded-l"></div>
            <div class="absolute -left-[9px] top-38 w-[2px] h-9 bg-slate-600 rounded-l"></div>
            <div class="absolute -right-[9px] top-22 w-[2px] h-12 bg-slate-600 rounded-r"></div>

            {{-- Inner Screen Display (Zero Scroll: fits 100% inside chassis) --}}
            <div 
                :class="{
                    'bg-white text-slate-900': preset === 'minimalist',
                    'bg-[#fffbf5] text-slate-900': preset === 'boutique',
                    'bg-[#090d16] text-slate-100': preset === 'apex'
                }"
                class="relative w-full h-full rounded-[35px] flex flex-col justify-between overflow-hidden p-2.5 transition-colors duration-300"
            >

                {{-- Dynamic Island & Top Status Bar --}}
                <div class="relative z-30 pt-0.5 px-2 flex items-center justify-between text-[10px] font-semibold tracking-tight"
                    :class="preset === 'apex' ? 'text-slate-300' : 'text-slate-900'">
                    <span>9:41</span>

                    {{-- Pill Notch --}}
                    <div class="absolute left-1/2 -translate-x-1/2 top-0.5 h-3.5 w-16 bg-black rounded-full flex items-center justify-between px-1.5 shadow-inner">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-900 border border-slate-700"></span>
                        <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>

                    {{-- Status Icons --}}
                    <div class="flex items-center gap-1">
                        {{-- Signal --}}
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="2" y="16" width="3" height="6" rx="1"/>
                            <rect x="7" y="12" width="3" height="10" rx="1"/>
                            <rect x="12" y="8" width="3" height="14" rx="1"/>
                            <rect x="17" y="4" width="3" height="18" rx="1"/>
                        </svg>
                        {{-- Wifi --}}
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4C7.31 4 3.07 5.9 0 8.98L12 21 24 8.98C20.93 5.9 16.69 4 12 4zm0 4c3.34 0 6.38 1.38 8.56 3.61L12 19.33 3.44 11.61C5.62 9.38 8.66 8 12 8z"/>
                        </svg>
                        {{-- Battery --}}
                        <div class="w-3.5 h-2 rounded-2xs border border-current p-0.5 flex items-center">
                            <div class="h-full bg-current rounded-3xs w-2"></div>
                        </div>
                    </div>
                </div>

                {{-- App Header Bar --}}
                <div class="flex items-center justify-between pt-0.5 pb-0.5">
                    {{-- Logo Badge / Avatar --}}
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-extrabold text-xs shadow-xs"
                         :style="{ backgroundColor: primaryColor }">
                        <span x-text="appInitial">Q</span>
                    </div>

                    {{-- Right Icons: Cart & Hamburger --}}
                    <div class="flex items-center gap-2">
                        {{-- Cart with badge --}}
                        <div class="relative flex items-center justify-center">
                            <svg class="w-4 h-4" :class="preset === 'apex' ? 'text-slate-300' : 'text-slate-700'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="absolute -top-1.5 -right-1.5 w-3.5 h-3.5 rounded-full bg-blue-600 text-white text-[8px] font-bold flex items-center justify-center shadow-xs">
                                2
                            </span>
                        </div>

                        {{-- Hamburger ☰ --}}
                        <button type="button" class="p-0.5 rounded-md hover:bg-black/5 transition-colors">
                            <svg class="w-4 h-4" :class="preset === 'apex' ? 'text-slate-300' : 'text-slate-800'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- In-App Search Bar --}}
                <div class="relative w-full">
                    <input 
                        type="text" 
                        :placeholder="`Search in ${appName}...`" 
                        class="w-full text-[10px] rounded-lg py-1 pl-6 pr-2 border transition-colors outline-none shadow-2xs"
                        :class="preset === 'apex' 
                            ? 'bg-[#131926] border-slate-800 text-slate-200 placeholder-slate-500' 
                            : 'bg-white border-slate-200 text-slate-800 placeholder-slate-400'"
                    />
                    <svg class="w-2.5 h-2.5 text-slate-400 absolute left-2 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- Hero Promotional Card --}}
                <div 
                    class="rounded-xl p-2.5 text-white relative overflow-hidden shadow-sm flex flex-col justify-between"
                    :style="{
                        background: `linear-gradient(135deg, ${primaryColor} 0%, #172554 100%)`
                    }"
                >
                    <div class="relative z-10">
                        <span 
                            class="inline-block px-1.5 py-0.5 rounded text-[7px] font-extrabold uppercase tracking-wider text-slate-900 bg-[#4ade80] shadow-xs mb-1"
                        >
                            Special Offer
                        </span>
                        <h4 class="text-[11px] font-extrabold leading-tight text-white">30% OFF Your First Mobile Order</h4>
                        <p class="text-[8px] text-white/80 mt-0.5 font-medium">Use code: <span class="font-bold underline">APPSTORE</span></p>
                    </div>

                    <div class="relative z-10 mt-1.5 flex items-center justify-between">
                        <button 
                            type="button" 
                            class="px-2 py-0.5 rounded-full text-[8px] font-bold bg-white text-slate-900 shadow hover:bg-slate-100 active:scale-95 transition-all"
                        >
                            Shop Now &gt;
                        </button>
                        <div class="flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-white"></span>
                            <span class="w-1 h-1 rounded-full bg-white/40"></span>
                            <span class="w-1 h-1 rounded-full bg-white/40"></span>
                            <span class="w-1 h-1 rounded-full bg-white/40"></span>
                        </div>
                    </div>
                </div>

                {{-- Categories Horizontal Row (5 Circular Icons) --}}
                <div class="flex items-center justify-between px-0.5">
                    {{-- 1. All --}}
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[9px] font-bold shadow-xs transition-transform hover:scale-105"
                             :style="{ backgroundColor: primaryColor }">
                            All
                        </div>
                        <span class="text-[8px] font-medium" :class="preset === 'apex' ? 'text-slate-400' : 'text-slate-600'">All</span>
                    </div>

                    {{-- 2. Trending --}}
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-7 h-7 rounded-full bg-[#fef2f2] border border-red-100 flex items-center justify-center text-xs shadow-xs transition-transform hover:scale-105">
                            🔥
                        </div>
                        <span class="text-[8px] font-medium" :class="preset === 'apex' ? 'text-slate-400' : 'text-slate-600'">Trending</span>
                    </div>

                    {{-- 3. Fashion --}}
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-7 h-7 rounded-full bg-[#eff6ff] border border-blue-100 flex items-center justify-center text-xs shadow-xs transition-transform hover:scale-105">
                            👗
                        </div>
                        <span class="text-[8px] font-medium" :class="preset === 'apex' ? 'text-slate-400' : 'text-slate-600'">Fashion</span>
                    </div>

                    {{-- 4. Shoes --}}
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-7 h-7 rounded-full bg-[#faf5ff] border border-purple-100 flex items-center justify-center text-xs shadow-xs transition-transform hover:scale-105">
                            👟
                        </div>
                        <span class="text-[8px] font-medium" :class="preset === 'apex' ? 'text-slate-400' : 'text-slate-600'">Shoes</span>
                    </div>

                    {{-- 5. Electronics --}}
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-7 h-7 rounded-full bg-[#f8fafc] border border-slate-200 flex items-center justify-center text-xs shadow-xs transition-transform hover:scale-105">
                            🎧
                        </div>
                        <span class="text-[8px] font-medium" :class="preset === 'apex' ? 'text-slate-400' : 'text-slate-600'">Electronics</span>
                    </div>
                </div>

                {{-- Featured Products Section (2 Side-by-Side Cards) --}}
                <div>
                    <div class="flex items-center justify-between mb-1 px-0.5">
                        <h5 class="text-[10px] font-bold tracking-tight" :class="preset === 'apex' ? 'text-slate-200' : 'text-slate-900'">
                            Featured Products
                        </h5>
                        <span class="text-[8px] font-bold text-blue-600 hover:underline cursor-pointer">View All</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        {{-- Product 1: Sports Shoes --}}
                        <div 
                            class="rounded-xl border p-1.5 flex flex-col justify-between shadow-2xs"
                            :class="preset === 'apex' ? 'bg-[#111726] border-slate-800' : 'bg-white border-slate-100'"
                        >
                            {{-- Product Image --}}
                            <div class="relative w-full h-15 rounded-lg bg-slate-50 flex items-center justify-center overflow-hidden mb-1">
                                <img src="/images/sneaker.jpg" alt="Sports Shoes" class="w-full h-full object-contain p-0.5" />
                            </div>

                            <div>
                                <h6 class="text-[9px] font-bold truncate" :class="preset === 'apex' ? 'text-slate-200' : 'text-slate-800'">
                                    Sports Shoes
                                </h6>
                                <div class="flex items-center justify-between mt-0.5">
                                    <span class="text-[10px] font-black text-slate-900" :class="preset === 'apex' ? 'text-white' : 'text-slate-900'">
                                        ₹2,499
                                    </span>
                                    <button 
                                        type="button" 
                                        class="w-4 h-4 rounded-md flex items-center justify-center text-white text-[9px] shadow-xs"
                                        :style="{ backgroundColor: primaryColor }"
                                    >
                                        <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Product 2: Smart Watch --}}
                        <div 
                            class="rounded-xl border p-1.5 flex flex-col justify-between shadow-2xs"
                            :class="preset === 'apex' ? 'bg-[#111726] border-slate-800' : 'bg-white border-slate-100'"
                        >
                            {{-- Product Image --}}
                            <div class="relative w-full h-15 rounded-lg bg-slate-50 flex items-center justify-center overflow-hidden mb-1">
                                <img src="/images/smartwatch.jpg" alt="Smart Watch" class="w-full h-full object-contain p-0.5" />
                            </div>

                            <div>
                                <h6 class="text-[9px] font-bold truncate" :class="preset === 'apex' ? 'text-slate-200' : 'text-slate-800'">
                                    Smart Watch
                                </h6>
                                <div class="flex items-center justify-between mt-0.5">
                                    <span class="text-[10px] font-black text-slate-900" :class="preset === 'apex' ? 'text-white' : 'text-slate-900'">
                                        ₹4,999
                                    </span>
                                    <button 
                                        type="button" 
                                        class="w-4 h-4 rounded-md flex items-center justify-center text-white text-[9px] shadow-xs"
                                        :style="{ backgroundColor: primaryColor }"
                                    >
                                        <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bottom Home Indicator --}}
                <div class="h-1 flex items-center justify-center pb-0.5">
                    <div class="w-18 h-0.5 rounded-full" :class="preset === 'apex' ? 'bg-slate-700' : 'bg-slate-300'"></div>
                </div>

            </div>
        </div>
    </div>
</div>
