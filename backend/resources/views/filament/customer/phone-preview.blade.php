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

    $thumbsFile = public_path('images/thumbs.json');
    $thumbs = file_exists($thumbsFile) ? json_decode(file_get_contents($thumbsFile), true) : [];
    $sneakerImg = $thumbs['sneaker'] ?? asset('images/sneaker.jpg');
    $smartwatchImg = $thumbs['smartwatch'] ?? asset('images/smartwatch.jpg');
@endphp

<div 
    x-data="mobileAppPreview({
        primaryColor: '{{ $primaryColor }}',
        preset: '{{ $preset }}',
        appName: '{{ addslashes($appName) }}',
        cartCount: 2
    })" 
    style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; box-sizing: border-box; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; user-select: none;"
>

    {{-- Interactive Controls Toolbar --}}
    <div style="width: 100%; max-width: 380px; margin-bottom: 16px; background: #11141e; border: 1px solid #1e2433; border-radius: 16px; padding: 12px 14px; box-shadow: 0 8px 24px rgba(0,0,0,0.4); box-sizing: border-box;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; background: #6366f1; font-size: 14px; color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    📱
                </span>
                <div>
                    <h4 style="font-size: 11px; font-weight: 700; color: #ffffff; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Live App Simulator</h4>
                    <p style="font-size: 10px; color: #94a3b8; margin: 2px 0 0 0;">{{ $appName }} &bull; {{ $platform }}</p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 4px; background: #0c0f17; border-radius: 8px; padding: 3px; border: 1px solid #1e2433;">
                <button 
                    type="button" 
                    @click="setTheme('minimalist')" 
                    style="padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s ease;"
                    :style="preset === 'minimalist' ? 'background: #6366f1; color: #ffffff;' : 'background: transparent; color: #94a3b8;'"
                >
                    Minimalist
                </button>
                <button 
                    type="button" 
                    @click="setTheme('boutique')" 
                    style="padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s ease;"
                    :style="preset === 'boutique' ? 'background: #f43f5e; color: #ffffff;' : 'background: transparent; color: #94a3b8;'"
                >
                    Boutique
                </button>
                <button 
                    type="button" 
                    @click="setTheme('apex')" 
                    style="padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s ease;"
                    :style="preset === 'apex' ? 'background: #10b981; color: #ffffff;' : 'background: transparent; color: #94a3b8;'"
                >
                    Apex Dark
                </button>
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 8px; border-top: 1px solid #1e2433; font-size: 11px;">
            <span style="color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                Accent Color:
                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.3);" :style="{ backgroundColor: primaryColor }"></span>
            </span>
            <div style="display: flex; align-items: center; gap: 6px;">
                <button type="button" @click="setColor('#6366f1')" style="width: 16px; height: 16px; border-radius: 50%; background: #6366f1; border: 1px solid rgba(255,255,255,0.3); cursor: pointer;" title="Indigo"></button>
                <button type="button" @click="setColor('#06b6d4')" style="width: 16px; height: 16px; border-radius: 50%; background: #06b6d4; border: 1px solid rgba(255,255,255,0.3); cursor: pointer;" title="Cyan"></button>
                <button type="button" @click="setColor('#10b981')" style="width: 16px; height: 16px; border-radius: 50%; background: #10b981; border: 1px solid rgba(255,255,255,0.3); cursor: pointer;" title="Emerald"></button>
                <button type="button" @click="setColor('#f43f5e')" style="width: 16px; height: 16px; border-radius: 50%; background: #f43f5e; border: 1px solid rgba(255,255,255,0.3); cursor: pointer;" title="Rose"></button>
                <button type="button" @click="setColor('#f59e0b')" style="width: 16px; height: 16px; border-radius: 50%; background: #f59e0b; border: 1px solid rgba(255,255,255,0.3); cursor: pointer;" title="Amber"></button>
                <button type="button" @click="setColor('#8b5cf6')" style="width: 16px; height: 16px; border-radius: 50%; background: #8b5cf6; border: 1px solid rgba(255,255,255,0.3); cursor: pointer;" title="Purple"></button>
            </div>
        </div>
    </div>

    {{-- Smartphone Frame Shell --}}
    <div 
        id="phone-device-chassis"
        style="width: 360px; max-width: 100%; height: 710px; border-radius: 48px; border: 10px solid #1c1d22; background: #ffffff; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.08); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; flex-shrink: 0; box-sizing: border-box; user-select: none;"
    >
        {{-- Dynamic Island Top Pill --}}
        <div style="position: absolute; top: 10px; left: 50%; transform: translateX(-50%); width: 108px; height: 26px; background: #000000; border-radius: 20px; z-index: 50; display: flex; align-items: center; justify-content: space-between; padding: 0 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.5); box-sizing: border-box;">
            <span style="width: 9px; height: 9px; border-radius: 50%; background: #111827; border: 1.5px solid #1f2937;"></span>
            <span style="width: 7px; height: 7px; border-radius: 50%; background: #059669;"></span>
        </div>

        {{-- Top Status Bar --}}
        <div style="padding: 12px 22px 2px; display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 700; color: #0f172a; position: relative; z-index: 30; box-sizing: border-box;">
            <span>9:41</span>
            <div style="display: flex; align-items: center; gap: 6px;">
                {{-- Cellular Bars --}}
                <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="2" y="16" width="3" height="6" rx="1"/>
                    <rect x="7" y="12" width="3" height="10" rx="1"/>
                    <rect x="12" y="8" width="3" height="14" rx="1"/>
                    <rect x="17" y="4" width="3" height="18" rx="1"/>
                </svg>
                {{-- Wi-Fi --}}
                <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4C7.31 4 3.07 5.9 0 8.98L12 21 24 8.98C20.93 5.9 16.69 4 12 4zm0 4c3.34 0 6.38 1.38 8.56 3.61L12 19.33 3.44 11.61C5.62 9.38 8.66 8 12 8z"/>
                </svg>
                {{-- Battery --}}
                <div style="width: 20px; height: 10px; border-radius: 3px; border: 1.5px solid currentColor; padding: 1px; display: flex; align-items: center;">
                    <div style="height: 100%; width: 70%; background: currentColor; border-radius: 1px;"></div>
                </div>
            </div>
        </div>

        {{-- In-App Header Bar --}}
        <div style="padding: 6px 18px 8px; display: flex; align-items: center; justify-content: space-between; box-sizing: border-box;">
            {{-- Logo / App Initial Avatar --}}
            <div 
                style="width: 38px; height: 38px; border-radius: 12px; color: #ffffff; font-size: 18px; font-weight: 800; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.15);"
                :style="{ backgroundColor: primaryColor }"
            >
                <span x-text="appName ? appName.charAt(0).toUpperCase() : 'Q'">{{ strtoupper(substr($appName, 0, 1)) }}</span>
            </div>

            {{-- Right Icons: Cart with Badge & Hamburger --}}
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="position: relative; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 22px; height: 22px; color: #334155;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span 
                        x-text="cartCount"
                        style="position: absolute; top: -6px; right: -7px; width: 17px; height: 17px; border-radius: 50%; background: #3b82f6; color: #ffffff; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"
                    >
                        2
                    </span>
                </div>

                <button type="button" style="background: transparent; border: none; cursor: pointer; padding: 2px;">
                    <svg style="width: 22px; height: 22px; color: #1e293b;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- In-App Search Bar --}}
        <div style="margin: 2px 18px 10px; border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; padding: 9px 14px; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); box-sizing: border-box;">
            <svg style="width: 15px; height: 15px; color: #64748b; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span style="font-size: 13px; color: #94a3b8; font-weight: 500;" x-text="'Search in ' + appName + '...'">Search in {{ $appName }}...</span>
        </div>

        {{-- Hero Promotional Card --}}
        <div 
            style="margin: 0 18px 12px; border-radius: 18px; padding: 14px 16px; color: #ffffff; position: relative; overflow: hidden; box-shadow: 0 6px 16px rgba(0,0,0,0.12); box-sizing: border-box;"
            :style="{ background: 'linear-gradient(135deg, ' + primaryColor + ' 0%, #1e1b4b 100%)' }"
        >
            <span style="display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; background: #10b981; color: #ffffff; letter-spacing: 0.3px; margin-bottom: 6px;">
                Special Offer
            </span>
            <h4 style="font-size: 14px; font-weight: 800; color: #ffffff; margin: 0 0 3px 0; line-height: 1.25;">30% OFF Your First Mobile Order</h4>
            <p style="font-size: 12px; color: rgba(255, 255, 255, 0.9); margin: 0 0 10px 0; font-weight: 500;">
                Use code: <span style="font-weight: 800; text-decoration: underline;">APPSTORE</span>
            </p>

            <div style="display: flex; align-items: center; justify-content: space-between;">
                <button 
                    type="button" 
                    style="padding: 5px 14px; border-radius: 999px; font-size: 11px; font-weight: 700; background: #ffffff; color: #0f172a; border: none; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                >
                    Shop Now &gt;
                </button>
                <div style="display: flex; align-items: center; gap: 4px;">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #ffffff;"></span>
                    <span style="width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,0.4);"></span>
                    <span style="width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,0.4);"></span>
                    <span style="width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,0.4);"></span>
                </div>
            </div>
        </div>

        {{-- Categories Horizontal Row (5 Categories) --}}
        <div style="margin: 0 18px 12px; display: flex; align-items: center; justify-content: space-between; box-sizing: border-box;">
            {{-- 1. All --}}
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <div 
                    style="width: 42px; height: 42px; border-radius: 50%; color: #ffffff; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.15);"
                    :style="{ backgroundColor: primaryColor }"
                >
                    All
                </div>
                <span style="font-size: 11px; font-weight: 600; color: #475569;">All</span>
            </div>

            {{-- 2. Trending --}}
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #fff1f2; border: 1px solid #ffe4e6; font-size: 18px; display: flex; align-items: center; justify-content: center;">
                    🔥
                </div>
                <span style="font-size: 11px; font-weight: 600; color: #475569;">Trending</span>
            </div>

            {{-- 3. Fashion --}}
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #eff6ff; border: 1px solid #dbeafe; font-size: 18px; display: flex; align-items: center; justify-content: center;">
                    👗
                </div>
                <span style="font-size: 11px; font-weight: 600; color: #475569;">Fashion</span>
            </div>

            {{-- 4. Shoes --}}
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #faf5ff; border: 1px solid #f3e8ff; font-size: 18px; display: flex; align-items: center; justify-content: center;">
                    👟
                </div>
                <span style="font-size: 11px; font-weight: 600; color: #475569;">Shoes</span>
            </div>

            {{-- 5. Electronics --}}
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 18px; display: flex; align-items: center; justify-content: center;">
                    🎧
                </div>
                <span style="font-size: 11px; font-weight: 600; color: #475569;">Electronics</span>
            </div>
        </div>

        {{-- Featured Products Section (2-Column Cards) --}}
        <div style="margin: 0 18px 6px; box-sizing: border-box;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                <h5 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Featured Products</h5>
                <span style="font-size: 12px; font-weight: 700; color: #3b82f6; cursor: pointer;">View All</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                {{-- Product 1: Sports Shoes --}}
                <div style="border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 2px rgba(0,0,0,0.03); box-sizing: border-box;">
                    <div style="width: 100%; height: 90px; border-radius: 12px; background: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 6px;">
                        <img src="{{ $sneakerImg }}" alt="Sports Shoes" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
                    </div>
                    <div>
                        <h6 style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sports Shoes</h6>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 13px; font-weight: 900; color: #0f172a;">₹2,499</span>
                            <button 
                                type="button" 
                                @click="addToCart('Sports Shoes')"
                                style="width: 24px; height: 24px; border-radius: 7px; color: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Product 2: Smart Watch --}}
                <div style="border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 2px rgba(0,0,0,0.03); box-sizing: border-box;">
                    <div style="width: 100%; height: 90px; border-radius: 12px; background: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 6px;">
                        <img src="{{ $smartwatchImg }}" alt="Smart Watch" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
                    </div>
                    <div>
                        <h6 style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Smart Watch</h6>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 13px; font-weight: 900; color: #0f172a;">₹4,999</span>
                            <button 
                                type="button" 
                                @click="addToCart('Smart Watch')"
                                style="width: 24px; height: 24px; border-radius: 7px; color: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Home Indicator Bar --}}
        <div style="width: 120px; height: 4px; background: #0f172a; border-radius: 999px; margin: 6px auto 10px; flex-shrink: 0;"></div>
    </div>

    {{-- Bottom Engine / Package Info & Download Bar --}}
    <div style="width: 100%; max-width: 380px; margin-top: 14px; padding: 12px 16px; background: #11141e; border-radius: 14px; border: 1px solid #1e2433; color: #cbd5e1; font-size: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.3); box-sizing: border-box;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 700; color: #ffffff;">{{ $packageName }}</span>
                <span style="padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                    Live Engine
                </span>
            </div>
            <p style="font-size: 11px; color: #94a3b8; margin: 3px 0 0 0;">Target: Android 8.0 to Android 15 (API 26-35)</p>
        </div>

        @if($latestReadyBuild)
            @php
                $downloadUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'builds.download',
                    now()->addHours(24),
                    ['ulid' => $latestReadyBuild->ulid]
                );
            @endphp
            <a 
                href="{{ $downloadUrl }}"
                style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; background: #10b981; color: #ffffff; font-weight: 700; font-size: 12px; text-decoration: none; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.4);"
            >
                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download APK
            </a>
        @else
            <span style="font-size: 11px; color: #64748b; font-style: italic;">Ready to compile</span>
        @endif
    </div>

</div>

<script>
    function mobileAppPreview(config) {
        return {
            primaryColor: config.primaryColor || '#6366f1',
            preset: config.preset || 'minimalist',
            appName: config.appName || 'My Store App',
            cartCount: config.cartCount || 2,

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
            }
        };
    }
</script>
