@php
    $thumbsFile = public_path('images/thumbs.json');
    $thumbs = file_exists($thumbsFile) ? json_decode(file_get_contents($thumbsFile), true) : [];
    $sneakerImg = $thumbs['sneaker'] ?? asset('images/sneaker.jpg');
    $smartwatchImg = $thumbs['smartwatch'] ?? asset('images/smartwatch.jpg');
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
        get storeUrl() {
            return ($wire && $wire.data && $wire.data.store_url) ? $wire.data.store_url : '';
        },
        get cleanDomain() {
            if (!this.storeUrl) return '';
            try {
                let u = this.storeUrl.replace(/^https?:\/\//i, '').replace(/^www\./i, '').split('/')[0];
                return u;
            } catch(e) {
                return this.storeUrl;
            }
        },
        get appName() {
            if ($wire && $wire.data && $wire.data.name && $wire.data.name.trim() !== '') {
                return $wire.data.name.trim();
            }
            if (this.cleanDomain) {
                let parts = this.cleanDomain.split('.');
                return parts[0].charAt(0).toUpperCase() + parts[0].slice(1);
            }
            return 'My Store App';
        },
        get appInitial() {
            return (this.appName && this.appName.length > 0) ? this.appName.charAt(0).toUpperCase() : 'S';
        },
        get promoBannerText() {
            if ($wire && $wire.data && $wire.data.promo_banner_text && $wire.data.promo_banner_text.trim() !== '') {
                return $wire.data.promo_banner_text.trim();
            }
            return '30% OFF Your First Mobile Order';
        },
        get promoCouponCode() {
            let clean = this.appName.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
            return clean ? (clean + '30') : 'APPSTORE';
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
    style="display: flex; flex-direction: row; align-items: flex-start; justify-content: space-between; gap: 24px; width: 100%; max-width: 100%; box-sizing: border-box; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #f8fafc; position: relative;"
>
    {{-- ========================================================================= --}}
    {{-- LEFT SECTION: Minimalist Theme Presets & Brand Colors (Scrollable Flow)   --}}
    {{-- ========================================================================= --}}
    <div style="flex: 1 1 380px; min-width: 300px; max-width: 560px; display: flex; flex-direction: column; gap: 18px; box-sizing: border-box;">

        {{-- Card 1: Visual Theme Preset (Minimal, clean, NO IMAGES) --}}
        <div style="background: #11141e; border: 1px solid #1e2433; border-radius: 16px; padding: 20px; box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.4); box-sizing: border-box;">
            {{-- Header --}}
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #1a1e2d; border: 1px solid #282e42; display: flex; align-items: center; justify-content: center; color: #94a3b8; flex-shrink: 0;">
                    <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21a4 4 0 01-4-4 8.001 8.001 0 0113.109-6.07A8.001 8.001 0 0121 17a4 4 0 01-4 4 4 4 0 01-4-4H9a4 4 0 01-2 3.874z" />
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Visual Theme Preset</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 0 0;">Select a minimal aesthetic foundation for your mobile app</p>
                </div>
            </div>

            <span style="font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 10px; display: block;">Select Theme Preset</span>

            {{-- Preset Option A: Modern Minimalist (No Images, Sleek & Compact) --}}
            <div 
                @click="setTheme('minimalist')"
                style="border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.15s ease; margin-bottom: 8px; box-sizing: border-box;"
                :style="preset === 'minimalist' 
                    ? 'border: 1.5px solid #6366f1; background: rgba(99, 102, 241, 0.1); box-shadow: 0 0 0 1px rgba(99, 102, 241, 0.25);' 
                    : 'border: 1px solid #1e2433; background: #0c0f17;'"
            >
                <div style="display: flex; align-items: center; gap: 12px;">
                    {{-- Radio Indicator --}}
                    <div 
                        style="width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"
                        :style="preset === 'minimalist' 
                            ? 'border: 2px solid #6366f1; background: #6366f1;' 
                            : 'border: 2px solid #475569; background: transparent;'"
                    >
                        <div x-show="preset === 'minimalist'" style="width: 6px; height: 6px; border-radius: 50%; background: #ffffff;"></div>
                    </div>
                    <div>
                        <h4 style="font-size: 13px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Theme A: Modern Minimalist</h4>
                        <p style="font-size: 11px; color: #94a3b8; margin: 2px 0 0 0;">Clean card layouts, subtle shadows, Inter font</p>
                    </div>
                </div>

                {{-- Color Preview Dots (No bulky images) --}}
                <div style="display: flex; align-items: center; gap: 5px; flex-shrink: 0;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #6366F1;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #06B6D4;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #FFFFFF; border: 1px solid #94a3b8;"></span>
                </div>
            </div>

            {{-- Preset Option B: Boutique Glamour (No Images, Sleek & Compact) --}}
            <div 
                @click="setTheme('boutique')"
                style="border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.15s ease; margin-bottom: 8px; box-sizing: border-box;"
                :style="preset === 'boutique' 
                    ? 'border: 1.5px solid #f43f5e; background: rgba(244, 63, 94, 0.1); box-shadow: 0 0 0 1px rgba(244, 63, 94, 0.25);' 
                    : 'border: 1px solid #1e2433; background: #0c0f17;'"
            >
                <div style="display: flex; align-items: center; gap: 12px;">
                    {{-- Radio Indicator --}}
                    <div 
                        style="width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"
                        :style="preset === 'boutique' 
                            ? 'border: 2px solid #f43f5e; background: #f43f5e;' 
                            : 'border: 2px solid #475569; background: transparent;'"
                    >
                        <div x-show="preset === 'boutique'" style="width: 6px; height: 6px; border-radius: 50%; background: #ffffff;"></div>
                    </div>
                    <div>
                        <h4 style="font-size: 13px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Theme B: Boutique Glamour</h4>
                        <p style="font-size: 11px; color: #94a3b8; margin: 2px 0 0 0;">Vibrant accents, rounded cards, Poppins font</p>
                    </div>
                </div>

                {{-- Color Preview Dots (No bulky images) --}}
                <div style="display: flex; align-items: center; gap: 5px; flex-shrink: 0;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #F43F5E;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #FB7185;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #FEF3C7; border: 1px solid #f59e0b;"></span>
                </div>
            </div>

            {{-- Preset Option C: Apex Dark Luxury (No Images, Sleek & Compact) --}}
            <div 
                @click="setTheme('apex')"
                style="border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.15s ease; box-sizing: border-box;"
                :style="preset === 'apex' 
                    ? 'border: 1.5px solid #10b981; background: rgba(16, 185, 129, 0.1); box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.25);' 
                    : 'border: 1px solid #1e2433; background: #0c0f17;'"
            >
                <div style="display: flex; align-items: center; gap: 12px;">
                    {{-- Radio Indicator --}}
                    <div 
                        style="width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"
                        :style="preset === 'apex' 
                            ? 'border: 2px solid #10b981; background: #10b981;' 
                            : 'border: 2px solid #475569; background: transparent;'"
                    >
                        <div x-show="preset === 'apex'" style="width: 6px; height: 6px; border-radius: 50%; background: #ffffff;"></div>
                    </div>
                    <div>
                        <h4 style="font-size: 13px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Theme C: Apex Dark Luxury</h4>
                        <p style="font-size: 11px; color: #94a3b8; margin: 2px 0 0 0;">Sleek dark mode luxury palette with Outfit typography</p>
                    </div>
                </div>

                {{-- Color Preview Dots (No bulky images) --}}
                <div style="display: flex; align-items: center; gap: 5px; flex-shrink: 0;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #10B981;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #06B6D4;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #0F172A; border: 1px solid #334155;"></span>
                </div>
            </div>
        </div>

        {{-- Card 2: Brand Colors & Palette --}}
        <div style="background: #11141e; border: 1px solid #1e2433; border-radius: 16px; padding: 20px; box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.4); box-sizing: border-box;">
            {{-- Header --}}
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #1a1e2d; border: 1px solid #282e42; display: flex; align-items: center; justify-content: center; color: #94a3b8; flex-shrink: 0;">
                    <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">Brand Colors &amp; Palette</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 0 0;">Customize your brand accent tones in real-time</p>
                </div>
            </div>

            {{-- 2 Custom Color Pickers Side-by-Side --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                {{-- Primary Color --}}
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">Primary Brand Color</label>
                    <div style="display: flex; align-items: center; gap: 8px; background: #0c0f17; border: 1px solid #1e2433; border-radius: 10px; padding: 6px 10px; box-sizing: border-box;">
                        <input 
                            type="color" 
                            :value="primaryColor" 
                            @input="setPrimary($event.target.value)"
                            style="width: 28px; height: 28px; border-radius: 50%; border: none; cursor: pointer; background: transparent; padding: 0; flex-shrink: 0;"
                        />
                        <input 
                            type="text" 
                            :value="primaryColor" 
                            @input="setPrimary($event.target.value)"
                            style="background: transparent; border: none; color: #ffffff; font-size: 12px; font-family: monospace; font-weight: 700; width: 100%; outline: none;"
                        />
                    </div>
                </div>

                {{-- Secondary Color --}}
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">Secondary / Accent Color</label>
                    <div style="display: flex; align-items: center; gap: 8px; background: #0c0f17; border: 1px solid #1e2433; border-radius: 10px; padding: 6px 10px; box-sizing: border-box;">
                        <input 
                            type="color" 
                            :value="secondaryColor" 
                            @input="setSecondary($event.target.value)"
                            style="width: 28px; height: 28px; border-radius: 50%; border: none; cursor: pointer; background: transparent; padding: 0; flex-shrink: 0;"
                        />
                        <input 
                            type="text" 
                            :value="secondaryColor" 
                            @input="setSecondary($event.target.value)"
                            style="background: transparent; border: none; color: #ffffff; font-size: 12px; font-family: monospace; font-weight: 700; width: 100%; outline: none;"
                        />
                    </div>
                </div>
            </div>

            {{-- Preset Swatches Row --}}
            <div>
                <label style="display: block; font-size: 11px; font-weight: 600; color: #cbd5e1; margin-bottom: 8px;">Preset Color Palette</label>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <button type="button" @click="setPrimary('#6366F1')" style="width: 32px; height: 32px; border-radius: 50%; background: #6366F1; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#6366F1"></button>
                    <button type="button" @click="setPrimary('#06B6D4')" style="width: 32px; height: 32px; border-radius: 50%; background: #06B6D4; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#06B6D4"></button>
                    <button type="button" @click="setPrimary('#10B981')" style="width: 32px; height: 32px; border-radius: 50%; background: #10B981; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#10B981"></button>
                    <button type="button" @click="setPrimary('#F59E0B')" style="width: 32px; height: 32px; border-radius: 50%; background: #F59E0B; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#F59E0B"></button>
                    <button type="button" @click="setPrimary('#EF4444')" style="width: 32px; height: 32px; border-radius: 50%; background: #EF4444; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#EF4444"></button>
                    <button type="button" @click="setPrimary('#EC4899')" style="width: 32px; height: 32px; border-radius: 50%; background: #EC4899; cursor: pointer; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#EC4899"></button>
                    <button type="button" @click="setPrimary('#F8FAFC')" style="width: 32px; height: 32px; border-radius: 50%; background: #F8FAFC; cursor: pointer; border: 2px solid rgba(255,255,255,0.4); box-shadow: 0 2px 5px rgba(0,0,0,0.3); transition: transform 0.15s ease;" title="#F8FAFC"></button>
                </div>
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- RIGHT SECTION: Fixed / Sticky Responsive Live Smartphone Emulator        --}}
    {{-- ========================================================================= --}}
    <div style="width: 340px; max-width: 100%; flex-shrink: 0; position: sticky; top: 16px; align-self: flex-start; display: flex; flex-direction: column; align-items: center; box-sizing: border-box; z-index: 20;">
        <div style="width: 100%; display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <h2 style="font-size: 16px; font-weight: 700; color: #ffffff; margin: 0;">Live Emulator</h2>
            <span style="font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                Live Preview
            </span>
        </div>

        {{-- Physical Phone Chassis (Responsive height, sticky in view) --}}
        <div 
            id="emulator-phone-chassis"
            style="width: 330px; max-width: 100%; height: min(650px, calc(100vh - 120px)); border-radius: 44px; border: 9px solid #1c1d22; background: #ffffff; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.08); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; flex-shrink: 0; box-sizing: border-box; user-select: none;"
        >
            {{-- Dynamic Island Top Pill --}}
            <div style="position: absolute; top: 8px; left: 50%; transform: translateX(-50%); width: 100px; height: 24px; background: #000000; border-radius: 20px; z-index: 50; display: flex; align-items: center; justify-content: space-between; padding: 0 9px; box-shadow: 0 2px 6px rgba(0,0,0,0.5); box-sizing: border-box;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #111827; border: 1.5px solid #1f2937;"></span>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: #059669;"></span>
            </div>

            {{-- Top Status Bar --}}
            <div style="padding: 10px 20px 2px; display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 700; color: #0f172a; position: relative; z-index: 30; box-sizing: border-box;">
                <span>9:41</span>
                <div style="display: flex; align-items: center; gap: 5px;">
                    {{-- Cellular Bars --}}
                    <svg style="width: 13px; height: 13px;" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="2" y="16" width="3" height="6" rx="1"/>
                        <rect x="7" y="12" width="3" height="10" rx="1"/>
                        <rect x="12" y="8" width="3" height="14" rx="1"/>
                        <rect x="17" y="4" width="3" height="18" rx="1"/>
                    </svg>
                    {{-- Wi-Fi --}}
                    <svg style="width: 13px; height: 13px;" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4C7.31 4 3.07 5.9 0 8.98L12 21 24 8.98C20.93 5.9 16.69 4 12 4zm0 4c3.34 0 6.38 1.38 8.56 3.61L12 19.33 3.44 11.61C5.62 9.38 8.66 8 12 8z"/>
                    </svg>
                    {{-- Battery --}}
                    <div style="width: 18px; height: 9px; border-radius: 3px; border: 1.5px solid currentColor; padding: 1px; display: flex; align-items: center;">
                        <div style="height: 100%; width: 70%; background: currentColor; border-radius: 1px;"></div>
                    </div>
                </div>
            </div>

            {{-- In-App Header Bar (Dynamic data from Step 1 & 2) --}}
            <div style="padding: 4px 16px 6px; display: flex; align-items: center; justify-content: space-between; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                    {{-- Logo / App Initial Avatar --}}
                    <div 
                        style="width: 34px; height: 34px; border-radius: 10px; color: #ffffff; font-size: 16px; font-weight: 800; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.15); flex-shrink: 0;"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        <span x-text="appInitial">S</span>
                    </div>

                    {{-- Title & Store Domain Subtitle --}}
                    <div style="display: flex; flex-direction: column; min-width: 0;">
                        <span 
                            style="font-size: 13px; font-weight: 800; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 135px; line-height: 1.2;"
                            x-text="appName"
                        >
                            My Store
                        </span>
                        <span 
                            style="font-size: 10px; color: #64748b; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 135px;"
                            x-text="cleanDomain ? cleanDomain : 'Live Store'"
                        >
                            Store Online
                        </span>
                    </div>
                </div>

                {{-- Right Icons: Cart with Badge & Hamburger --}}
                <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                    <div style="position: relative; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 20px; height: 20px; color: #334155;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span style="position: absolute; top: -5px; right: -6px; width: 15px; height: 15px; border-radius: 50%; background: #3b82f6; color: #ffffff; font-size: 9px; font-weight: 700; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                            2
                        </span>
                    </div>

                    <button type="button" style="background: transparent; border: none; cursor: pointer; padding: 2px;">
                        <svg style="width: 20px; height: 20px; color: #1e293b;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Scrollable Screen Body (Ensures responsiveness on any viewport height) --}}
            <div style="flex: 1; overflow-y: auto; overflow-x: hidden; padding: 0 16px 4px; display: flex; flex-direction: column; gap: 10px; scrollbar-width: none; box-sizing: border-box;">

                {{-- In-App Search Bar --}}
                <div style="border-radius: 10px; background: #ffffff; border: 1.5px solid #e2e8f0; padding: 7px 12px; display: flex; align-items: center; gap: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); box-sizing: border-box; flex-shrink: 0;">
                    <svg style="width: 14px; height: 14px; color: #64748b; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span style="font-size: 12px; color: #94a3b8; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="'Search in ' + appName + '...'">Search in store...</span>
                </div>

                {{-- Hero Promotional Card --}}
                <div 
                    style="border-radius: 16px; padding: 12px 14px; color: #ffffff; position: relative; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.12); box-sizing: border-box; flex-shrink: 0;"
                    :style="{ background: 'linear-gradient(135deg, ' + primaryColor + ' 0%, #1e1b4b 100%)' }"
                >
                    <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 9px; font-weight: 800; background: #10b981; color: #ffffff; letter-spacing: 0.3px; margin-bottom: 5px;">
                        Special Offer
                    </span>
                    <h4 style="font-size: 13px; font-weight: 800; color: #ffffff; margin: 0 0 2px 0; line-height: 1.25;" x-text="promoBannerText">30% OFF Your First Mobile Order</h4>
                    <p style="font-size: 11px; color: rgba(255, 255, 255, 0.9); margin: 0 0 8px 0; font-weight: 500;">
                        Use code: <span style="font-weight: 800; text-decoration: underline;" x-text="promoCouponCode">APPSTORE</span>
                    </p>

                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <button 
                            type="button" 
                            style="padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; background: #ffffff; color: #0f172a; border: none; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                        >
                            Shop Now &gt;
                        </button>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #ffffff;"></span>
                            <span style="width: 4px; height: 4px; border-radius: 50%; background: rgba(255,255,255,0.4);"></span>
                            <span style="width: 4px; height: 4px; border-radius: 50%; background: rgba(255,255,255,0.4);"></span>
                            <span style="width: 4px; height: 4px; border-radius: 50%; background: rgba(255,255,255,0.4);"></span>
                        </div>
                    </div>
                </div>

                {{-- Categories Horizontal Row (5 Categories) --}}
                <div style="display: flex; align-items: center; justify-content: space-between; box-sizing: border-box; flex-shrink: 0;">
                    {{-- 1. All --}}
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 3px;">
                        <div 
                            style="width: 38px; height: 38px; border-radius: 50%; color: #ffffff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.15);"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            All
                        </div>
                        <span style="font-size: 10px; font-weight: 600; color: #475569;">All</span>
                    </div>

                    {{-- 2. Trending --}}
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 3px;">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: #fff1f2; border: 1px solid #ffe4e6; font-size: 16px; display: flex; align-items: center; justify-content: center;">
                            🔥
                        </div>
                        <span style="font-size: 10px; font-weight: 600; color: #475569;">Trending</span>
                    </div>

                    {{-- 3. Fashion --}}
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 3px;">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: #eff6ff; border: 1px solid #dbeafe; font-size: 16px; display: flex; align-items: center; justify-content: center;">
                            👗
                        </div>
                        <span style="font-size: 10px; font-weight: 600; color: #475569;">Fashion</span>
                    </div>

                    {{-- 4. Shoes --}}
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 3px;">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: #faf5ff; border: 1px solid #f3e8ff; font-size: 16px; display: flex; align-items: center; justify-content: center;">
                            👟
                        </div>
                        <span style="font-size: 10px; font-weight: 600; color: #475569;">Shoes</span>
                    </div>

                    {{-- 5. Electronics --}}
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 3px;">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 16px; display: flex; align-items: center; justify-content: center;">
                            🎧
                        </div>
                        <span style="font-size: 10px; font-weight: 600; color: #475569;">Tech</span>
                    </div>
                </div>

                {{-- Featured Products Section (2-Column Cards) --}}
                <div style="box-sizing: border-box; flex-shrink: 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px;">
                        <h5 style="font-size: 13px; font-weight: 800; color: #0f172a; margin: 0;">Featured Products</h5>
                        <span style="font-size: 11px; font-weight: 700; color: #3b82f6; cursor: pointer;">View All</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        {{-- Product 1: Sports Shoes --}}
                        <div style="border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 2px rgba(0,0,0,0.03); box-sizing: border-box;">
                            <div style="width: 100%; height: 78px; border-radius: 10px; background: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 4px;">
                                <img src="{{ $sneakerImg }}" alt="Sports Shoes" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
                            </div>
                            <div>
                                <h6 style="font-size: 11px; font-weight: 700; color: #1e293b; margin: 0 0 3px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sports Shoes</h6>
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-size: 12px; font-weight: 900; color: #0f172a;">₹2,499</span>
                                    <button 
                                        type="button" 
                                        style="width: 22px; height: 22px; border-radius: 6px; color: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                                        :style="{ backgroundColor: primaryColor }"
                                    >
                                        <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Product 2: Smart Watch --}}
                        <div style="border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 2px rgba(0,0,0,0.03); box-sizing: border-box;">
                            <div style="width: 100%; height: 78px; border-radius: 10px; background: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 4px;">
                                <img src="{{ $smartwatchImg }}" alt="Smart Watch" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
                            </div>
                            <div>
                                <h6 style="font-size: 11px; font-weight: 700; color: #1e293b; margin: 0 0 3px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Smart Watch</h6>
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-size: 12px; font-weight: 900; color: #0f172a;">₹4,999</span>
                                    <button 
                                        type="button" 
                                        style="width: 22px; height: 22px; border-radius: 6px; color: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                                        :style="{ backgroundColor: primaryColor }"
                                    >
                                        <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Bottom Home Indicator Bar --}}
            <div style="width: 110px; height: 4px; background: #0f172a; border-radius: 999px; margin: 4px auto 6px; flex-shrink: 0;"></div>
        </div>
    </div>
</div>
