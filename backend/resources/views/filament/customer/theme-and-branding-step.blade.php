@php
    $thumbsFile = public_path('images/thumbs.json');
    $thumbs = file_exists($thumbsFile) ? json_decode(file_get_contents($thumbsFile), true) : [];
    $sneakerImg = $thumbs['sneaker'] ?? asset('images/sneaker.jpg');
    $smartwatchImg = $thumbs['smartwatch'] ?? asset('images/smartwatch.jpg');
    $handbagImg = $thumbs['handbag'] ?? asset('images/handbag.jpg');
    $luxuryWatchImg = $thumbs['luxury_watch'] ?? asset('images/luxury_watch.jpg');
@endphp

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
            return (this.appName && this.appName.length > 0) ? this.appName.charAt(0).toUpperCase() : 'Q';
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
    style="display: flex; flex-direction: row; flex-wrap: wrap; align-items: flex-start; justify-content: center; gap: 28px; width: 100%; max-width: 100%; box-sizing: border-box; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #f8fafc; user-select: none;"
>
    {{-- ========================================================================= --}}
    {{-- LEFT SECTION: Visual Theme Preset & Brand Colors & Palette Cards          --}}
    {{-- ========================================================================= --}}
    <div style="flex: 1 1 380px; min-width: 320px; max-width: 540px; display: flex; flex-direction: column; gap: 20px; box-sizing: border-box;">

        {{-- Card 1: Visual Theme Preset --}}
        <div style="background: #11141e; border: 1px solid #1e2433; border-radius: 18px; padding: 22px; box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.4); box-sizing: border-box;">
            {{-- Header --}}
            <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 18px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #1a1e2d; border: 1px solid #282e42; display: flex; align-items: center; justify-content: center; color: #94a3b8; flex-shrink: 0;">
                    <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21a4 4 0 01-4-4 8.001 8.001 0 0113.109-6.07A8.001 8.001 0 0121 17a4 4 0 01-4 4 4 4 0 01-4-4H9a4 4 0 01-2 3.874z" />
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Visual Theme Preset</h3>
                    <p style="font-size: 13px; color: #94a3b8; margin: 4px 0 0 0; line-height: 1.4;">Choose an aesthetic foundation tailored for your store</p>
                </div>
            </div>

            <span style="font-size: 13px; font-weight: 600; color: #ffffff; margin-bottom: 12px; display: block;">Select Theme Preset</span>

            {{-- Preset Option A: Modern Minimalist --}}
            <div 
                @click="setTheme('minimalist')"
                style="border-radius: 14px; padding: 12px 16px; display: flex; align-items: center; gap: 14px; cursor: pointer; transition: all 0.2s ease; margin-bottom: 12px; box-sizing: border-box;"
                :style="preset === 'minimalist' 
                    ? 'border: 1.5px solid #6366f1; background: rgba(99, 102, 241, 0.08); box-shadow: 0 0 0 1px rgba(99, 102, 241, 0.2);' 
                    : 'border: 1px solid #1e2433; background: #0c0f17;'"
            >
                {{-- Radio Indicator --}}
                <div 
                    style="width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s ease;"
                    :style="preset === 'minimalist' 
                        ? 'border: 2px solid #6366f1; background: #6366f1;' 
                        : 'border: 2px solid #475569; background: transparent;'"
                >
                    <div x-show="preset === 'minimalist'" style="width: 8px; height: 8px; border-radius: 50%; background: #ffffff;"></div>
                </div>

                {{-- Mockup Preview Thumbnail --}}
                <div style="width: 95px; height: 60px; border-radius: 12px; padding: 6px 8px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; background: #e0e7ff; border: 1px solid #c7d2fe; box-sizing: border-box; overflow: hidden;">
                    <div style="display: flex; flex-direction: column; gap: 4px; flex: 1;">
                        <span style="width: 24px; height: 5px; border-radius: 3px; background: #6366f1;"></span>
                        <span style="width: 36px; height: 4px; border-radius: 2px; background: #94a3b8;"></span>
                        <span style="width: 24px; height: 4px; border-radius: 2px; background: #cbd5e1;"></span>
                    </div>
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #c7d2fe; display: flex; align-items: center; justify-content: center; color: #6366f1;">
                        <svg style="width: 18px; height: 18px;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                {{-- Details --}}
                <div style="flex: 1; min-width: 0;">
                    <h4 style="font-size: 14px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Theme A: Modern Minimalist</h4>
                    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0 0 0; line-height: 1.4;">Clean card layouts, subtle shadows, Inter font</p>
                </div>
            </div>

            {{-- Preset Option B: Boutique Glamour --}}
            <div 
                @click="setTheme('boutique')"
                style="border-radius: 14px; padding: 12px 16px; display: flex; align-items: center; gap: 14px; cursor: pointer; transition: all 0.2s ease; margin-bottom: 12px; box-sizing: border-box;"
                :style="preset === 'boutique' 
                    ? 'border: 1.5px solid #f43f5e; background: rgba(244, 63, 94, 0.08); box-shadow: 0 0 0 1px rgba(244, 63, 94, 0.2);' 
                    : 'border: 1px solid #1e2433; background: #0c0f17;'"
            >
                {{-- Radio Indicator --}}
                <div 
                    style="width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s ease;"
                    :style="preset === 'boutique' 
                        ? 'border: 2px solid #f43f5e; background: #f43f5e;' 
                        : 'border: 2px solid #475569; background: transparent;'"
                >
                    <div x-show="preset === 'boutique'" style="width: 8px; height: 8px; border-radius: 50%; background: #ffffff;"></div>
                </div>

                {{-- Mockup Preview Thumbnail --}}
                <div style="width: 95px; height: 60px; border-radius: 12px; padding: 6px 8px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; background: #fef3c7; border: 1px solid #fde68a; box-sizing: border-box; overflow: hidden;">
                    <div style="display: flex; flex-direction: column; gap: 4px; flex: 1;">
                        <span style="width: 24px; height: 5px; border-radius: 3px; background: #f43f5e;"></span>
                        <span style="width: 36px; height: 4px; border-radius: 2px; background: #d97706;"></span>
                        <span style="width: 24px; height: 4px; border-radius: 2px; background: #f59e0b;"></span>
                    </div>
                    <div style="width: 32px; height: 32px; border-radius: 8px; overflow: hidden; background: #ffffff; border: 1px solid #fed7aa; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ $handbagImg }}" alt="Handbag" style="width: 100%; height: 100%; object-fit: cover;" />
                    </div>
                </div>

                {{-- Details --}}
                <div style="flex: 1; min-width: 0;">
                    <h4 style="font-size: 14px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Theme B: Boutique Glamour</h4>
                    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0 0 0; line-height: 1.4;">Vibrant accents, rounded cards, Poppins font</p>
                </div>
            </div>

            {{-- Preset Option C: Apex Dark Luxury --}}
            <div 
                @click="setTheme('apex')"
                style="border-radius: 14px; padding: 12px 16px; display: flex; align-items: center; gap: 14px; cursor: pointer; transition: all 0.2s ease; box-sizing: border-box;"
                :style="preset === 'apex' 
                    ? 'border: 1.5px solid #10b981; background: rgba(16, 185, 129, 0.08); box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.2);' 
                    : 'border: 1px solid #1e2433; background: #0c0f17;'"
            >
                {{-- Radio Indicator --}}
                <div 
                    style="width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s ease;"
                    :style="preset === 'apex' 
                        ? 'border: 2px solid #10b981; background: #10b981;' 
                        : 'border: 2px solid #475569; background: transparent;'"
                >
                    <div x-show="preset === 'apex'" style="width: 8px; height: 8px; border-radius: 50%; background: #ffffff;"></div>
                </div>

                {{-- Mockup Preview Thumbnail --}}
                <div style="width: 95px; height: 60px; border-radius: 12px; padding: 6px 8px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; background: #0f172a; border: 1px solid #1e293b; box-sizing: border-box; overflow: hidden;">
                    <div style="display: flex; flex-direction: column; gap: 4px; flex: 1;">
                        <span style="width: 24px; height: 5px; border-radius: 3px; background: #10b981;"></span>
                        <span style="width: 36px; height: 4px; border-radius: 2px; background: #64748b;"></span>
                        <span style="width: 24px; height: 4px; border-radius: 2px; background: #475569;"></span>
                    </div>
                    <div style="width: 32px; height: 32px; border-radius: 8px; overflow: hidden; background: #000000; border: 1px solid #334155; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ $luxuryWatchImg }}" alt="Luxury Watch" style="width: 100%; height: 100%; object-fit: cover;" />
                    </div>
                </div>

                {{-- Details --}}
                <div style="flex: 1; min-width: 0;">
                    <h4 style="font-size: 14px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Theme C: Apex Dark Luxury</h4>
                    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0 0 0; line-height: 1.4;">Sleek dark mode luxury palette with Outfit typography</p>
                </div>
            </div>
        </div>

        {{-- Card 2: Brand Colors & Palette --}}
        <div style="background: #11141e; border: 1px solid #1e2433; border-radius: 18px; padding: 22px; box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.4); box-sizing: border-box;">
            {{-- Header --}}
            <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 18px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #1a1e2d; border: 1px solid #282e42; display: flex; align-items: center; justify-content: center; color: #94a3b8; flex-shrink: 0;">
                    <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Brand Colors &amp; Palette</h3>
                    <p style="font-size: 13px; color: #94a3b8; margin: 4px 0 0 0; line-height: 1.4;">Customize your brand accent tones</p>
                </div>
            </div>

            {{-- 2 Custom Color Pickers Side-by-Side --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                {{-- Primary Color --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 8px;">Primary Brand Color</label>
                    <div style="display: flex; align-items: center; gap: 10px; background: #0c0f17; border: 1px solid #1e2433; border-radius: 12px; padding: 7px 12px; box-sizing: border-box;">
                        <input 
                            type="color" 
                            :value="primaryColor" 
                            @input="setPrimary($event.target.value)"
                            style="width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; background: transparent; padding: 0; flex-shrink: 0;"
                        />
                        <input 
                            type="text" 
                            :value="primaryColor" 
                            @input="setPrimary($event.target.value)"
                            style="background: transparent; border: none; color: #ffffff; font-size: 13px; font-family: monospace; font-weight: 700; width: 100%; outline: none;"
                        />
                    </div>
                </div>

                {{-- Secondary Color --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 8px;">Secondary / Accent Color</label>
                    <div style="display: flex; align-items: center; gap: 10px; background: #0c0f17; border: 1px solid #1e2433; border-radius: 12px; padding: 7px 12px; box-sizing: border-box;">
                        <input 
                            type="color" 
                            :value="secondaryColor" 
                            @input="setSecondary($event.target.value)"
                            style="width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; background: transparent; padding: 0; flex-shrink: 0;"
                        />
                        <input 
                            type="text" 
                            :value="secondaryColor" 
                            @input="setSecondary($event.target.value)"
                            style="background: transparent; border: none; color: #ffffff; font-size: 13px; font-family: monospace; font-weight: 700; width: 100%; outline: none;"
                        />
                    </div>
                </div>
            </div>

            {{-- Preset Swatches Row --}}
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 10px;">Preset Color Palette</label>
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <button type="button" @click="setPrimary('#6366F1')" style="width: 34px; height: 34px; border-radius: 50%; background: #6366F1; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#6366F1"></button>
                    <button type="button" @click="setPrimary('#06B6D4')" style="width: 34px; height: 34px; border-radius: 50%; background: #06B6D4; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#06B6D4"></button>
                    <button type="button" @click="setPrimary('#10B981')" style="width: 34px; height: 34px; border-radius: 50%; background: #10B981; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#10B981"></button>
                    <button type="button" @click="setPrimary('#F59E0B')" style="width: 34px; height: 34px; border-radius: 50%; background: #F59E0B; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#F59E0B"></button>
                    <button type="button" @click="setPrimary('#EF4444')" style="width: 34px; height: 34px; border-radius: 50%; background: #EF4444; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#EF4444"></button>
                    <button type="button" @click="setPrimary('#EC4899')" style="width: 34px; height: 34px; border-radius: 50%; background: #EC4899; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#EC4899"></button>
                    <button type="button" @click="setPrimary('#F8FAFC')" style="width: 34px; height: 34px; border-radius: 50%; background: #F8FAFC; cursor: pointer; border: 2px solid rgba(255,255,255,0.4); box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#F8FAFC"></button>
                </div>
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- RIGHT SECTION: Live Emulator (iPhone 15/16 Pro Frame with Dynamic Island)   --}}
    {{-- ========================================================================= --}}
    <div style="width: 360px; max-width: 100%; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; box-sizing: border-box;">
        <h2 style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 0 0 14px 0; width: 100%; text-align: left;">Live Emulator</h2>

        {{-- Physical Phone Chassis --}}
        <div 
            id="emulator-phone-chassis"
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
                    <span x-text="appInitial">Q</span>
                </div>

                {{-- Right Icons: Cart with Badge & Hamburger --}}
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="position: relative; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 22px; height: 22px; color: #334155;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span style="position: absolute; top: -6px; right: -7px; width: 17px; height: 17px; border-radius: 50%; background: #3b82f6; color: #ffffff; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
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
                <span style="font-size: 13px; color: #94a3b8; font-weight: 500;" x-text="'Search in ' + appName + '...'">Search in qwrt...</span>
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
    </div>
</div>
