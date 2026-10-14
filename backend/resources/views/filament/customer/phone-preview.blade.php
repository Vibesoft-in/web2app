@props([
    'app' => null,
    'config' => [],
])

@php
    $themeConfig = $config['theme'] ?? [];
    $preset = $themeConfig['preset'] ?? 'minimalist';
    $primaryColor = $themeConfig['primary_color'] ?? '#6366f1';
    $appName = $app?->name ?? 'My Store App';
    $packageName = $app?->package_name ?? 'com.example.app';
    $platform = strtoupper($app?->platform ?? 'WooCommerce');
    $latestReadyBuild = $app?->builds()->where('status', 'ready')->latest()->first();
@endphp

<div x-data="mobileAppPreview({
    primaryColor: '{{ $primaryColor }}',
    preset: '{{ $preset }}',
    cartCount: 2
})" class="flex flex-col items-center justify-center p-2 w-full select-none font-sans">

    {{-- Interactive Controls Toolbar --}}
    <div class="w-full max-w-md mb-4 bg-slate-900/90 backdrop-blur-md rounded-2xl p-3 border border-slate-800 text-white shadow-xl flex flex-col gap-2.5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-600 text-xs font-bold text-white shadow">
                    📱
                </span>
                <div>
                    <h4 class="text-xs font-bold tracking-wide text-white uppercase">Live App Simulator</h4>
                    <p class="text-[10px] text-slate-400">{{ $appName }} &bull; {{ $platform }}</p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 bg-slate-800/80 rounded-lg p-1 border border-slate-700">
                <button 
                    type="button" 
                    @click="setTheme('minimalist')" 
                    :class="preset === 'minimalist' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'"
                    class="px-2 py-0.5 rounded text-[11px] font-medium transition-all"
                >
                    Minimalist
                </button>
                <button 
                    type="button" 
                    @click="setTheme('boutique')" 
                    :class="preset === 'boutique' ? 'bg-rose-600 text-white shadow' : 'text-slate-400 hover:text-white'"
                    class="px-2 py-0.5 rounded text-[11px] font-medium transition-all"
                >
                    Boutique
                </button>
                <button 
                    type="button" 
                    @click="setTheme('apex')" 
                    :class="preset === 'apex' ? 'bg-slate-700 text-emerald-400 shadow' : 'text-slate-400 hover:text-white'"
                    class="px-2 py-0.5 rounded text-[11px] font-medium transition-all"
                >
                    Apex Dark
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between pt-1 border-t border-slate-800/80 text-[11px]">
            <span class="text-slate-400 flex items-center gap-1.5">
                Accent Brand Color:
                <span class="inline-block w-3.5 h-3.5 rounded-full border border-white/20 shadow-sm" :style="{ backgroundColor: primaryColor }"></span>
            </span>
            <div class="flex items-center gap-1.5">
                <button type="button" @click="setColor('#6366f1')" class="w-4 h-4 rounded-full bg-indigo-500 hover:scale-110 transition-transform ring-1 ring-white/20" title="Indigo"></button>
                <button type="button" @click="setColor('#0ea5e9')" class="w-4 h-4 rounded-full bg-sky-500 hover:scale-110 transition-transform ring-1 ring-white/20" title="Sky"></button>
                <button type="button" @click="setColor('#10b981')" class="w-4 h-4 rounded-full bg-emerald-500 hover:scale-110 transition-transform ring-1 ring-white/20" title="Emerald"></button>
                <button type="button" @click="setColor('#f43f5e')" class="w-4 h-4 rounded-full bg-rose-500 hover:scale-110 transition-transform ring-1 ring-white/20" title="Rose"></button>
                <button type="button" @click="setColor('#f59e0b')" class="w-4 h-4 rounded-full bg-amber-500 hover:scale-110 transition-transform ring-1 ring-white/20" title="Amber"></button>
                <button type="button" @click="setColor('#8b5cf6')" class="w-4 h-4 rounded-full bg-purple-500 hover:scale-110 transition-transform ring-1 ring-white/20" title="Purple"></button>
            </div>
        </div>
    </div>

    {{-- Smartphone Frame Shell --}}
    <div 
        id="phone-device-chassis"
        :class="{
            'bg-white text-slate-900 border-slate-900': preset === 'minimalist',
            'bg-amber-50/40 text-slate-900 border-slate-900': preset === 'boutique',
            'bg-slate-950 text-slate-100 border-slate-800': preset === 'apex'
        }"
        class="relative w-[340px] sm:w-[360px] h-[690px] rounded-[48px] border-[10px] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col transition-all duration-300 ring-1 ring-black/10"
        :style="{ '--app-primary': primaryColor }"
    >
        {{-- Side Buttons Glare/Reflection details --}}
        <div class="absolute -left-[12px] top-24 w-[3px] h-9 bg-slate-700 rounded-l"></div>
        <div class="absolute -left-[12px] top-36 w-[3px] h-12 bg-slate-700 rounded-l"></div>
        <div class="absolute -left-[12px] top-52 w-[3px] h-12 bg-slate-700 rounded-l"></div>
        <div class="absolute -right-[12px] top-32 w-[3px] h-16 bg-slate-700 rounded-r"></div>

        {{-- Dynamic Island Notch Bar --}}
        <div class="relative z-30 pt-3 px-6 flex items-center justify-between text-[11px] font-semibold tracking-tight"
            :class="preset === 'apex' ? 'text-slate-300' : 'text-slate-800'">
            <span>9:41</span>

            {{-- Pill Notch --}}
            <div class="absolute left-1/2 -translate-x-1/2 top-2.5 h-6 w-24 bg-black rounded-full flex items-center justify-between px-2.5 shadow-inner">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-900 border border-slate-700 flex items-center justify-center">
                    <span class="w-1 h-1 rounded-full bg-indigo-500/80"></span>
                </span>
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>

            <div class="flex items-center gap-1.5">
                {{-- Signal Icon --}}
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="2" y="16" width="3" height="6" rx="1"/>
                    <rect x="7" y="12" width="3" height="10" rx="1"/>
                    <rect x="12" y="8" width="3" height="14" rx="1"/>
                    <rect x="17" y="4" width="3" height="18" rx="1"/>
                </svg>
                {{-- Wifi Icon --}}
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4C7.31 4 3.07 5.9 0 8.98L12 21 24 8.98C20.93 5.9 16.69 4 12 4zm0 4c3.34 0 6.38 1.38 8.56 3.61L12 19.33 3.44 11.61C5.62 9.38 8.66 8 12 8z"/>
                </svg>
                {{-- Battery Icon --}}
                <div class="w-5 h-2.5 rounded-sm border border-current p-0.5 flex items-center">
                    <div class="h-full bg-current rounded-2xs w-3.5"></div>
                </div>
            </div>
        </div>

        {{-- App Header Navbar --}}
        <div class="relative z-20 px-4 pt-3 pb-2 flex items-center justify-between border-b"
            :class="preset === 'apex' ? 'border-slate-800 bg-slate-950/80' : 'border-slate-100 bg-white/80'"
        >
            <div class="flex items-center gap-2.5">
                <button type="button" class="p-1 rounded-lg hover:bg-black/5 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h12M4 18h16" />
                    </svg>
                </button>
                <div class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-xs font-black shadow-sm"
                        :style="{ backgroundColor: primaryColor }">
                        {{ strtoupper(substr($appName, 0, 1)) }}
                    </span>
                    <h3 class="text-xs font-bold tracking-tight truncate max-w-[130px]" :class="preset === 'apex' ? 'text-white' : 'text-slate-900'">
                        {{ $appName }}
                    </h3>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="p-1 rounded-lg hover:bg-black/5 transition-colors relative">
                    <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="absolute top-0.5 right-0.5 w-2 h-2 rounded-full" :style="{ backgroundColor: primaryColor }"></span>
                </button>

                <button type="button" class="p-1 rounded-lg hover:bg-black/5 transition-colors relative">
                    <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span 
                        x-text="cartCount"
                        class="absolute -top-1 -right-1 w-4 h-4 rounded-full text-white text-[9px] font-bold flex items-center justify-center shadow-sm"
                        :style="{ backgroundColor: primaryColor }"
                    >2</span>
                </button>
            </div>
        </div>

        {{-- Phone Screen Body (Scrollable) --}}
        <div class="flex-1 overflow-y-auto px-3.5 py-3 space-y-3.5 scrollbar-thin scrollbar-thumb-slate-300">

            {{-- Toast Notification Inside Phone --}}
            <div 
                x-show="showToast" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="bg-slate-900/95 text-white text-[11px] py-1.5 px-3 rounded-xl shadow-lg border border-slate-700 flex items-center justify-between"
            >
                <span class="flex items-center gap-1.5">
                    <span class="text-emerald-400">✓</span> Added to your cart!
                </span>
                <span class="text-[9px] text-slate-400">View Cart &rarr;</span>
            </div>

            {{-- In-App Search Bar --}}
            <div class="relative">
                <input 
                    type="text" 
                    placeholder="Search in {{ $appName }}..." 
                    class="w-full text-xs rounded-xl py-2 pl-8 pr-3 border transition-colors outline-none"
                    :class="preset === 'apex' 
                        ? 'bg-slate-900 border-slate-800 text-slate-200 placeholder-slate-500 focus:border-indigo-500' 
                        : 'bg-slate-100/90 border-slate-200 text-slate-800 placeholder-slate-400 focus:border-indigo-400'"
                />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- Hero Promo Carousel Card --}}
            <div 
                class="rounded-2xl p-4 text-white relative overflow-hidden shadow-md flex flex-col justify-between min-h-[110px]"
                :style="{
                    background: `linear-gradient(135deg, ${primaryColor} 0%, #1e1b4b 100%)`
                }"
            >
                <div class="relative z-10 max-w-[190px]">
                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider bg-white/20 text-white backdrop-blur-sm mb-1">
                        Special Launch Offer
                    </span>
                    <h4 class="text-sm font-extrabold leading-tight">30% OFF Your First Mobile Order</h4>
                    <p class="text-[10px] text-white/80 mt-0.5 font-medium">Use code: <span class="font-bold underline">APP30</span></p>
                </div>

                <div class="relative z-10 mt-2 flex items-center justify-between">
                    <button 
                        type="button" 
                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white text-slate-900 shadow hover:bg-slate-100 active:scale-95 transition-all"
                    >
                        Shop Collection &rarr;
                    </button>
                    <div class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                    </div>
                </div>

                {{-- Abstract Graphic Glow --}}
                <div class="absolute -right-4 -bottom-6 w-24 h-24 rounded-full bg-white/10 blur-xl pointer-events-none"></div>
                <div class="absolute right-2 top-2 text-3xl opacity-20 pointer-events-none">🛍️</div>
            </div>

            {{-- Categories Horizontal Pill Row --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h5 class="text-[11px] font-bold tracking-tight" :class="preset === 'apex' ? 'text-slate-300' : 'text-slate-800'">
                        Explore Categories
                    </h5>
                    <span class="text-[10px] text-slate-400 font-medium hover:underline cursor-pointer">See all</span>
                </div>
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-[11px]">
                    <button 
                        type="button" 
                        @click="activeCategory = 'all'" 
                        :class="activeCategory === 'all' ? 'text-white shadow-sm' : (preset === 'apex' ? 'bg-slate-900 text-slate-400' : 'bg-slate-100 text-slate-600')"
                        :style="activeCategory === 'all' ? { backgroundColor: primaryColor } : {}"
                        class="px-2.5 py-1 rounded-full font-semibold whitespace-nowrap transition-all"
                    >
                        All
                    </button>
                    <button 
                        type="button" 
                        @click="activeCategory = 'trending'" 
                        :class="activeCategory === 'trending' ? 'text-white shadow-sm' : (preset === 'apex' ? 'bg-slate-900 text-slate-400' : 'bg-slate-100 text-slate-600')"
                        :style="activeCategory === 'trending' ? { backgroundColor: primaryColor } : {}"
                        class="px-2.5 py-1 rounded-full font-semibold whitespace-nowrap transition-all"
                    >
                        🔥 Trending
                    </button>
                    <button 
                        type="button" 
                        @click="activeCategory = 'fashion'" 
                        :class="activeCategory === 'fashion' ? 'text-white shadow-sm' : (preset === 'apex' ? 'bg-slate-900 text-slate-400' : 'bg-slate-100 text-slate-600')"
                        :style="activeCategory === 'fashion' ? { backgroundColor: primaryColor } : {}"
                        class="px-2.5 py-1 rounded-full font-semibold whitespace-nowrap transition-all"
                    >
                        👗 Fashion
                    </button>
                    <button 
                        type="button" 
                        @click="activeCategory = 'footwear'" 
                        :class="activeCategory === 'footwear' ? 'text-white shadow-sm' : (preset === 'apex' ? 'bg-slate-900 text-slate-400' : 'bg-slate-100 text-slate-600')"
                        :style="activeCategory === 'footwear' ? { backgroundColor: primaryColor } : {}"
                        class="px-2.5 py-1 rounded-full font-semibold whitespace-nowrap transition-all"
                    >
                        👟 Shoes
                    </button>
                    <button 
                        type="button" 
                        @click="activeCategory = 'accessories'" 
                        :class="activeCategory === 'accessories' ? 'text-white shadow-sm' : (preset === 'apex' ? 'bg-slate-900 text-slate-400' : 'bg-slate-100 text-slate-600')"
                        :style="activeCategory === 'accessories' ? { backgroundColor: primaryColor } : {}"
                        class="px-2.5 py-1 rounded-full font-semibold whitespace-nowrap transition-all"
                    >
                        ⌚ Watches
                    </button>
                </div>
            </div>

            {{-- 2-Column Product Grid --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h5 class="text-[11px] font-bold tracking-tight" :class="preset === 'apex' ? 'text-slate-300' : 'text-slate-800'">
                        Featured Products
                    </h5>
                    <span class="text-[10px] text-slate-400 font-medium">4 items</span>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    {{-- Product 1 --}}
                    <div 
                        class="rounded-xl p-2 flex flex-col justify-between border transition-all hover:shadow-md group"
                        :class="preset === 'apex' ? 'bg-slate-900/80 border-slate-800 text-slate-100' : 'bg-white border-slate-200/80 text-slate-900'"
                    >
                        <div class="relative w-full aspect-square rounded-lg overflow-hidden bg-slate-100 mb-2">
                            <img src="https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=350&q=80" alt="Hoodie" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <span class="absolute top-1 left-1 px-1 py-0.5 rounded text-[8px] font-extrabold text-white" :style="{ backgroundColor: primaryColor }">
                                -25%
                            </span>
                            <button type="button" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-white/80 backdrop-blur-sm text-slate-700 flex items-center justify-center text-[10px] hover:text-rose-500">
                                ♥
                            </button>
                        </div>
                        <div>
                            <h6 class="text-[11px] font-semibold truncate leading-snug">Classic Cotton Hoodie</h6>
                            <div class="flex items-center gap-1 my-0.5 text-[9px] text-amber-500">
                                <span>★★★★★</span>
                                <span class="text-slate-400">(4.9)</span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <div>
                                    <span class="text-xs font-bold leading-none" :style="{ color: primaryColor }">₹1,499</span>
                                    <span class="text-[9px] text-slate-400 line-through ml-0.5">₹1,999</span>
                                </div>
                                <button 
                                    type="button" 
                                    @click="addToCart('Classic Cotton Hoodie')"
                                    class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm active:scale-90 transition-transform"
                                    :style="{ backgroundColor: primaryColor }"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Product 2 --}}
                    <div 
                        class="rounded-xl p-2 flex flex-col justify-between border transition-all hover:shadow-md group"
                        :class="preset === 'apex' ? 'bg-slate-900/80 border-slate-800 text-slate-100' : 'bg-white border-slate-200/80 text-slate-900'"
                    >
                        <div class="relative w-full aspect-square rounded-lg overflow-hidden bg-slate-100 mb-2">
                            <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=350&q=80" alt="Watch" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <button type="button" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-white/80 backdrop-blur-sm text-slate-700 flex items-center justify-center text-[10px] hover:text-rose-500">
                                ♥
                            </button>
                        </div>
                        <div>
                            <h6 class="text-[11px] font-semibold truncate leading-snug">Minimalist Leather Watch</h6>
                            <div class="flex items-center gap-1 my-0.5 text-[9px] text-amber-500">
                                <span>★★★★☆</span>
                                <span class="text-slate-400">(4.8)</span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <div>
                                    <span class="text-xs font-bold leading-none" :style="{ color: primaryColor }">₹3,499</span>
                                </div>
                                <button 
                                    type="button" 
                                    @click="addToCart('Minimalist Leather Watch')"
                                    class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm active:scale-90 transition-transform"
                                    :style="{ backgroundColor: primaryColor }"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Product 3 --}}
                    <div 
                        class="rounded-xl p-2 flex flex-col justify-between border transition-all hover:shadow-md group"
                        :class="preset === 'apex' ? 'bg-slate-900/80 border-slate-800 text-slate-100' : 'bg-white border-slate-200/80 text-slate-900'"
                    >
                        <div class="relative w-full aspect-square rounded-lg overflow-hidden bg-slate-100 mb-2">
                            <img src="https://images.unsplash.com/photo-1572569511254-d8f925fe2cbb?w=350&q=80" alt="Earbuds" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <span class="absolute top-1 left-1 px-1 py-0.5 rounded text-[8px] font-extrabold text-white" :style="{ backgroundColor: primaryColor }">
                                -24%
                            </span>
                            <button type="button" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-white/80 backdrop-blur-sm text-slate-700 flex items-center justify-center text-[10px] hover:text-rose-500">
                                ♥
                            </button>
                        </div>
                        <div>
                            <h6 class="text-[11px] font-semibold truncate leading-snug">ANC Wireless Earbuds</h6>
                            <div class="flex items-center gap-1 my-0.5 text-[9px] text-amber-500">
                                <span>★★★★★</span>
                                <span class="text-slate-400">(4.9)</span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <div>
                                    <span class="text-xs font-bold leading-none" :style="{ color: primaryColor }">₹1,899</span>
                                    <span class="text-[9px] text-slate-400 line-through ml-0.5">₹2,499</span>
                                </div>
                                <button 
                                    type="button" 
                                    @click="addToCart('ANC Wireless Earbuds')"
                                    class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm active:scale-90 transition-transform"
                                    :style="{ backgroundColor: primaryColor }"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Product 4 --}}
                    <div 
                        class="rounded-xl p-2 flex flex-col justify-between border transition-all hover:shadow-md group"
                        :class="preset === 'apex' ? 'bg-slate-900/80 border-slate-800 text-slate-100' : 'bg-white border-slate-200/80 text-slate-900'"
                    >
                        <div class="relative w-full aspect-square rounded-lg overflow-hidden bg-slate-100 mb-2">
                            <img src="https://images.unsplash.com/photo-1549298916-b41d501d3772?w=350&q=80" alt="Sneakers" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <button type="button" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-white/80 backdrop-blur-sm text-slate-700 flex items-center justify-center text-[10px] hover:text-rose-500">
                                ♥
                            </button>
                        </div>
                        <div>
                            <h6 class="text-[11px] font-semibold truncate leading-snug">Canvas Street Sneakers</h6>
                            <div class="flex items-center gap-1 my-0.5 text-[9px] text-amber-500">
                                <span>★★★★☆</span>
                                <span class="text-slate-400">(4.7)</span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <div>
                                    <span class="text-xs font-bold leading-none" :style="{ color: primaryColor }">₹2,299</span>
                                    <span class="text-[9px] text-slate-400 line-through ml-0.5">₹2,999</span>
                                </div>
                                <button 
                                    type="button" 
                                    @click="addToCart('Canvas Street Sneakers')"
                                    class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm active:scale-90 transition-transform"
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

        {{-- Bottom Fixed Navigation Bar --}}
        <div class="relative z-20 px-6 py-2.5 border-t flex items-center justify-between shadow-lg"
            :class="preset === 'apex' ? 'border-slate-800 bg-slate-950/95' : 'border-slate-100 bg-white/95'"
        >
            <button 
                type="button" 
                @click="activeTab = 'home'" 
                class="flex flex-col items-center gap-0.5 transition-colors"
                :class="activeTab === 'home' ? 'font-bold' : 'text-slate-400'"
                :style="activeTab === 'home' ? { color: primaryColor } : {}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[9px]">Home</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'explore'" 
                class="flex flex-col items-center gap-0.5 transition-colors"
                :class="activeTab === 'explore' ? 'font-bold' : 'text-slate-400'"
                :style="activeTab === 'explore' ? { color: primaryColor } : {}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span class="text-[9px]">Explore</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'cart'" 
                class="flex flex-col items-center gap-0.5 transition-colors relative"
                :class="activeTab === 'cart' ? 'font-bold' : 'text-slate-400'"
                :style="activeTab === 'cart' ? { color: primaryColor } : {}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span class="text-[9px]">Cart</span>
                <span 
                    x-text="cartCount"
                    class="absolute -top-1 right-1 w-3.5 h-3.5 rounded-full text-white text-[8px] font-extrabold flex items-center justify-center shadow-sm"
                    :style="{ backgroundColor: primaryColor }"
                >2</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'account'" 
                class="flex flex-col items-center gap-0.5 transition-colors"
                :class="activeTab === 'account' ? 'font-bold' : 'text-slate-400'"
                :style="activeTab === 'account' ? { color: primaryColor } : {}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[9px]">Account</span>
            </button>
        </div>

        {{-- iPhone Home Indicator Bar --}}
        <div class="h-4 flex items-center justify-center pb-1">
            <div class="w-28 h-1 rounded-full" :class="preset === 'apex' ? 'bg-slate-700' : 'bg-slate-300'"></div>
        </div>
    </div>

    {{-- App Meta & Fast APK Download Shortcut Banner --}}
    <div class="w-full max-w-md mt-4 p-3 bg-slate-900 rounded-xl border border-slate-800 text-slate-300 text-xs flex items-center justify-between shadow-lg">
        <div class="space-y-0.5">
            <div class="flex items-center gap-2">
                <span class="font-bold text-white">{{ $packageName }}</span>
                <span class="px-1.5 py-0.2 rounded text-[10px] font-medium bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                    Live Engine
                </span>
            </div>
            <p class="text-[11px] text-slate-400">Target: Android 8.0 to Android 15 (API 26-35)</p>
        </div>

        @if($latestReadyBuild)
            <a 
                href="{{ route('builds.download', ['ulid' => $latestReadyBuild->ulid]) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all active:scale-95"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download APK
            </a>
        @else
            <span class="text-[11px] text-slate-500 italic">Click "Build APK" to compile</span>
        @endif
    </div>

</div>

<script>
    function mobileAppPreview(config) {
        return {
            primaryColor: config.primaryColor || '#6366f1',
            preset: config.preset || 'minimalist',
            cartCount: config.cartCount || 2,
            activeCategory: 'all',
            activeTab: 'home',
            showToast: false,

            setTheme(name) {
                this.preset = name;
                if (name === 'boutique') {
                    this.primaryColor = '#f43f5e';
                } else if (name === 'apex') {
                    this.primaryColor = '#10b981';
                } else {
                    this.primaryColor = '#6366f1';
                }
            },

            setColor(hex) {
                this.primaryColor = hex;
            },

            addToCart(itemTitle) {
                this.cartCount++;
                this.showToast = true;
                setTimeout(() => {
                    this.showToast = false;
                }, 2500);
            }
        };
    }
</script>
