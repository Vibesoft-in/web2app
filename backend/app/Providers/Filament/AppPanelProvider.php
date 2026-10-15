<?php

namespace App\Providers\Filament;

use App\Filament\CustomerPages\CustomerDashboard;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->brandName('WebIntoApp')
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            ->darkMode(true)
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn () => new HtmlString('<script>document.documentElement.classList.add("dark"); localStorage.setItem("theme", "dark");</script>')
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('
<style>
/* ==========================================================================
   WebIntoApp Dark Theme System
   Consistent deep black and charcoal backgrounds with subtle indigo accents
   ========================================================================== */

:root, html.dark {
    --bg-canvas: #090B12;
    --bg-surface: #111620;
    --bg-surface-subtle: #151A27;
    --border-main: #252C3A;
    --border-subtle: #1D2433;
    --accent-indigo: #635BFF;
    --accent-indigo-hover: #544BEE;
}

/* Background canvas & layouts */
html.dark, html.dark body, html.dark .fi-layout, html.dark .fi-main, html.dark .fi-main-ctn {
    background-color: #090B12 !important;
    color: #F8FAFC !important;
}

/* Navigation Sidebar */
html.dark aside.fi-sidebar,
html.dark .fi-sidebar-header,
html.dark .fi-sidebar-footer {
    background-color: #0C1019 !important;
    border-color: #252C3A !important;
}

/* Header Topbar */
html.dark header.fi-topbar {
    background-color: #090B12 !important;
    border-color: #252C3A !important;
}

/* Active navigation items: subtle purple/indigo pill */
html.dark .fi-sidebar-item-active > a,
html.dark .fi-sidebar-item-active > button,
html.dark .fi-sidebar-item-active .fi-sidebar-item-btn {
    background: linear-gradient(135deg, #4F46E5 0%, #635BFF 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px 0 rgba(99, 91, 255, 0.28) !important;
}

html.dark .fi-sidebar-item-active svg {
    color: #ffffff !important;
}

/* Inactive navigation hover */
html.dark .fi-sidebar-item > a:not(.fi-sidebar-item-active > a):hover {
    background-color: #151A27 !important;
    color: #F8FAFC !important;
}

/* Cards, Sections, and Containers */
html.dark .fi-section,
html.dark .fi-section-header,
html.dark .fi-ta,
html.dark .fi-ta-ctn,
html.dark .fi-card,
html.dark .fi-wizard-step {
    background-color: #111620 !important;
    border-color: #252C3A !important;
}

/* Table styling */
html.dark .fi-ta-header,
html.dark .fi-ta-header-cell {
    background-color: #111620 !important;
    border-color: #252C3A !important;
    color: #A5B4CF !important;
}

html.dark .fi-ta-row {
    background-color: #111620 !important;
    border-color: #1D2433 !important;
}

html.dark .fi-ta-row:hover {
    background-color: #151A27 !important;
}

/* Form inputs & controls */
html.dark .fi-input-wrp {
    background-color: #151A27 !important;
    border-color: #252C3A !important;
    color: #F8FAFC !important;
}

html.dark .fi-input-wrp:focus-within {
    border-color: #635BFF !important;
    box-shadow: 0 0 0 1px #635BFF !important;
}

/* Primary buttons */
html.dark .fi-btn-primary {
    background: linear-gradient(135deg, #635BFF 0%, #7C3AED 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px 0 rgba(99, 91, 255, 0.25) !important;
}

html.dark .fi-btn-primary:hover {
    background: linear-gradient(135deg, #544BEE 0%, #6D2ED8 100%) !important;
}

/* Modals & Dialogs */
html.dark .fi-modal-window,
html.dark .fi-modal-content {
    background-color: #111620 !important;
    border-color: #252C3A !important;
}

/* Hardened fallback: ensure dark canvas regardless of html state */
html:not(.dark) {
    background-color: #080b11 !important;
    color: #f1f5f9 !important;
}
</style>
')
            )
            ->sidebarWidth('16rem')
            ->maxContentWidth(MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/CustomerResources'), for: 'App\\Filament\\CustomerResources')
            ->pages([
                CustomerDashboard::class,
            ])
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
