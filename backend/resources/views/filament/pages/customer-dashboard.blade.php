<x-filament-panels::page class="fi-dashboard-page">
    <div class="space-y-6 -mt-4 font-sans select-none text-[#F8FAFC]">

        {{-- Page Header --}}
        <div>
            <h1 class="text-3xl font-extrabold text-[#F8FAFC] tracking-tight">Dashboard</h1>
            <p class="text-sm text-[#A5B4CF] mt-1">Manage your apps and builds in one place.</p>
        </div>

        {{-- Main Dashboard Grid (Left 8 cols, Right 4 cols) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- ============================================================= --}}
            {{-- LEFT COLUMN: Welcome Banner + Stats Cards + Recent Activity   --}}
            {{-- ============================================================= --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- 1. Welcome Banner --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#14182E] via-[#161B36] to-[#12162B] border border-[#2D3352] p-6 sm:p-7 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                    {{-- Subtle Ambient Glows --}}
                    <div class="absolute -right-8 -top-8 w-56 h-56 bg-[#635BFF]/15 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute right-36 -bottom-10 w-44 h-44 bg-[#8B5CF6]/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="flex items-center gap-4 relative z-10">
                        {{-- Avatar with User Initial --}}
                        <div class="w-16 h-16 rounded-full bg-[#635BFF] flex items-center justify-center text-[#F8FAFC] text-2xl font-bold shadow-lg shadow-[#635BFF]/30 shrink-0 ring-4 ring-[#635BFF]/20">
                            {{ $userInitial }}
                        </div>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-[#F8FAFC] tracking-tight">
                                Welcome back, {{ $userName }}
                            </h2>
                            <p class="text-xs sm:text-sm text-[#A5B4CF] mt-1">
                                Manage your mobile apps, track builds, and grow your business.
                            </p>
                        </div>
                    </div>

                    {{-- Sign Out Action --}}
                    <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="relative z-10 shrink-0">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#151A27] border border-[#252C3A] text-xs sm:text-sm font-semibold text-[#F8FAFC] hover:bg-[#1C2333] hover:border-[#333C4E] transition-all shadow-sm active:scale-95">
                            <svg class="w-4 h-4 text-[#A5B4CF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Sign out</span>
                        </button>
                    </form>
                </div>

                {{-- 2. Stats Cards (3 Columns) --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    
                    {{-- Stat 1: Mobile Apps --}}
                    <div class="rounded-2xl bg-[#111620] border border-[#252C3A] p-5 shadow-lg flex items-center gap-4 transition-all hover:border-[#333C4E]">
                        <div class="w-12 h-12 rounded-xl bg-[#0E1C38] border border-[#19325D] text-[#3B82F6] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-[#A5B4CF] block truncate">Mobile Apps</span>
                            <div class="text-2xl font-black text-[#F8FAFC] tracking-tight mt-0.5 leading-none">
                                {{ $totalApps }}
                            </div>
                            <span class="text-[11px] text-[#6B7A99] mt-1 block">Total apps created</span>
                        </div>
                    </div>

                    {{-- Stat 2: Successful Builds --}}
                    <div class="rounded-2xl bg-[#111620] border border-[#252C3A] p-5 shadow-lg flex items-center gap-4 transition-all hover:border-[#333C4E]">
                        <div class="w-12 h-12 rounded-xl bg-[#0C261A] border border-[#154730] text-[#22C55E] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-[#A5B4CF] block truncate">Successful Builds</span>
                            <div class="text-2xl font-black text-[#F8FAFC] tracking-tight mt-0.5 leading-none">
                                {{ $successfulBuilds }}
                            </div>
                            <span class="text-[11px] text-[#6B7A99] mt-1 block">Completed builds</span>
                        </div>
                    </div>

                    {{-- Stat 3: Pending Builds --}}
                    <div class="rounded-2xl bg-[#111620] border border-[#252C3A] p-5 shadow-lg flex items-center gap-4 transition-all hover:border-[#333C4E]">
                        <div class="w-12 h-12 rounded-xl bg-[#281C0F] border border-[#4D3519] text-[#F59E0B] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-[#A5B4CF] block truncate">Pending Builds</span>
                            <div class="text-2xl font-black text-[#F8FAFC] tracking-tight mt-0.5 leading-none">
                                {{ $pendingBuilds }}
                            </div>
                            <span class="text-[11px] text-[#6B7A99] mt-1 block">Builds in queue</span>
                        </div>
                    </div>

                </div>

                {{-- 3. Recent Activity Section --}}
                <div class="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 shadow-xl">
                    {{-- Header --}}
                    <div class="flex items-center justify-between pb-4 border-b border-[#252C3A]">
                        <div>
                            <h3 class="text-base font-bold text-[#F8FAFC] tracking-wide">Recent Activity</h3>
                            <p class="text-xs text-[#A5B4CF] mt-0.5">Your latest apps and build activity</p>
                        </div>
                        <a href="{{ url('/app/customer-apps') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#A5B4CF] bg-[#151A27] border border-[#252C3A] hover:text-[#F8FAFC] hover:border-[#333C4E] transition-all">
                            <span>View All</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>

                    {{-- Table / List of Apps --}}
                    @if($apps->isNotEmpty())
                        <div class="overflow-x-auto mt-4">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[11px] font-semibold text-[#7E8B9B] uppercase tracking-wider border-b border-[#252C3A]">
                                        <th class="pb-3 pr-4 font-semibold">App Name</th>
                                        <th class="pb-3 px-4 font-semibold">Platform</th>
                                        <th class="pb-3 px-4 font-semibold">Status</th>
                                        <th class="pb-3 px-4 font-semibold">Updated</th>
                                        <th class="pb-3 pl-4 font-semibold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#1D2433] text-sm">
                                    @foreach($apps as $index => $app)
                                        @php
                                            $latestBuild = $app->builds->first();
                                            $buildStatus = $latestBuild ? (string) ($latestBuild->status?->value ?? $latestBuild->status) : 'draft';
                                            
                                            // Palette for icons matching reference: purple, pink, blue
                                            $iconStyles = [
                                                ['bg' => 'bg-[#635BFF]', 'icon' => 'bag'],
                                                ['bg' => 'bg-[#EC4899]', 'icon' => 'shirt'],
                                                ['bg' => 'bg-[#2563EB]', 'icon' => 'cart'],
                                            ];
                                            $stylePick = $iconStyles[$index % 3];
                                        @endphp
                                        <tr class="hover:bg-[#151A27]/60 transition-colors group">
                                            {{-- App Name & Package --}}
                                            <td class="py-3.5 pr-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-xl {{ $stylePick['bg'] }} flex items-center justify-center text-white shrink-0 shadow-md">
                                                        @if($stylePick['icon'] === 'bag')
                                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                            </svg>
                                                        @elseif($stylePick['icon'] === 'shirt')
                                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                                            </svg>
                                                        @else
                                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                                            </svg>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <a href="{{ url('/app/customer-apps/' . $app->id) }}" class="font-bold text-[#F8FAFC] hover:text-[#8B5CF6] transition-colors block text-sm truncate max-w-[150px] sm:max-w-[200px]">
                                                            {{ $app->name }}
                                                        </a>
                                                        <span class="text-xs font-mono text-[#7E8B9B] block mt-0.5 truncate max-w-[150px] sm:max-w-[200px]">
                                                            {{ $app->package_name }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- Platform Badges --}}
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#151A27] border border-[#252C3A] text-xs font-medium text-[#A5B4CF]">
                                                        <svg class="w-3.5 h-3.5 text-[#22C55E]" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9992.4482.9992.9993s-.4482.9997-.9992.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 0 0-.1521-.5676.416.416 0 0 0-.5676.1521l-2.0223 3.503C15.5802 8.4116 13.844 8.0003 12 8.0003c-1.8445 0-3.5807.4113-5.1373.9494L4.8404 5.4467a.4161.4161 0 0 0-.5677-.1521.4157.4157 0 0 0-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396" />
                                                        </svg>
                                                        <span>Android</span>
                                                    </span>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#151A27] border border-[#252C3A] text-xs font-medium text-[#A5B4CF]">
                                                        <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.63-.78 1.06-1.86.94-2.95-1 .04-2.19.67-2.88 1.48-.61.71-1.14 1.83-1 2.91 1.12.09 2.31-.66 2.94-1.44z" />
                                                        </svg>
                                                        <span>iOS</span>
                                                    </span>
                                                </div>
                                            </td>

                                            {{-- Status Badge --}}
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                @if($buildStatus === 'ready')
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#0C261A] border border-[#164730] text-[#22C55E]">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-[#22C55E]"></span>
                                                        Ready
                                                    </span>
                                                @elseif(in_array($buildStatus, ['building', 'preparing', 'signing']))
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#0E1C38] border border-[#1A3360] text-[#3B82F6]">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-[#3B82F6] animate-pulse"></span>
                                                        Building
                                                    </span>
                                                @elseif($buildStatus === 'queued')
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#281C0F] border border-[#4D3519] text-[#F59E0B]">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-[#F59E0B] animate-ping"></span>
                                                        Queued
                                                    </span>
                                                @elseif($buildStatus === 'failed')
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#2B1118] border border-[#4E1E28] text-[#F43F5E]">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-[#F43F5E]"></span>
                                                        Failed
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#151A27] border border-[#252C3A] text-[#A5B4CF]">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-[#7E8B9B]"></span>
                                                        Draft
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Updated Relative Time --}}
                                            <td class="py-3.5 px-4 whitespace-nowrap text-xs text-[#A5B4CF]">
                                                {{ $app->updated_at->diffForHumans() }}
                                            </td>

                                            {{-- Actions --}}
                                            <td class="py-3.5 pl-4 text-right">
                                                <div class="inline-flex items-center gap-1.5 justify-end">
                                                    {{-- Preview Button --}}
                                                    <button 
                                                        type="button" 
                                                        wire:click="openPreview({{ $app->id }})"
                                                        class="p-2 rounded-xl bg-[#151A27] hover:bg-[#1A2234] border border-[#252C3A] text-[#A5B4CF] hover:text-[#F8FAFC] transition-colors" 
                                                        title="Preview Mobile App"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                        </svg>
                                                    </button>

                                                    {{-- Build APK Button --}}
                                                    <button 
                                                        type="button" 
                                                        wire:click="triggerBuild({{ $app->id }})"
                                                        class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#635BFF] to-[#7C3AED] hover:from-[#544BEE] hover:to-[#6D2ED8] text-white font-bold text-[10px] shadow-md shadow-[#635BFF]/25 transition-all" 
                                                        title="Build Standalone Android APK"
                                                    >
                                                        Build APK
                                                    </button>

                                                    {{-- Download APK if ready --}}
                                                    @if($buildStatus === 'ready')
                                                        <button 
                                                            type="button" 
                                                            wire:click="downloadApk({{ $app->id }})"
                                                            class="p-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition-colors" 
                                                            title="Download APK File"
                                                        >
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        {{-- Empty State (Actual Data: 0 apps) --}}
                        <div class="py-10 text-center flex flex-col items-center justify-center">
                            <div class="w-14 h-14 rounded-2xl bg-[#201838] border border-[#3B2A63] text-[#8B5CF6] flex items-center justify-center mb-3 shadow-inner">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-[#F8FAFC]">No mobile apps created yet</h4>
                            <p class="text-xs text-[#A5B4CF] max-w-sm mt-1 leading-relaxed">
                                Turn your WooCommerce website or store into high-performance native Android & iOS mobile apps in just 3 easy steps.
                            </p>
                            <a href="{{ url('/app/customer-apps/create') }}" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#635BFF] to-[#7C3AED] hover:from-[#544BEE] hover:to-[#6D2ED8] text-white font-bold text-xs shadow-md shadow-[#635BFF]/25 transition-all active:scale-95">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Your First App
                            </a>
                        </div>
                    @endif
                </div>

            </div>

            {{-- ============================================================= --}}
            {{-- RIGHT COLUMN: Quick Actions & Getting Started Checklist       --}}
            {{-- ============================================================= --}}
            <div class="lg:col-span-4 space-y-6">

                {{-- 1. Quick Actions Card --}}
                <div class="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 shadow-xl space-y-5">
                    {{-- Header --}}
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#201838] border border-[#3B2A63] text-[#8B5CF6] flex items-center justify-center shrink-0 shadow-inner">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-[#F8FAFC] tracking-wide">Quick Actions</h3>
                            <p class="text-xs text-[#A5B4CF] mt-0.5">Get started with common tasks.</p>
                        </div>
                    </div>

                    {{-- Actions Buttons --}}
                    <div class="space-y-3 pt-1">
                        <a 
                            href="{{ url('/app/customer-apps/create') }}" 
                            class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-[#635BFF] to-[#7C3AED] hover:from-[#544BEE] hover:to-[#6D2ED8] active:scale-[0.98] text-white font-semibold text-sm flex items-center justify-center gap-2 shadow-lg shadow-[#635BFF]/30 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Create New App</span>
                        </a>

                        <a 
                            href="{{ url('/app/customer-builds') }}" 
                            class="w-full py-3.5 px-4 rounded-xl bg-[#151A27] hover:bg-[#1A2234] border border-[#252C3A] hover:border-[#333C4E] text-[#F8FAFC] font-semibold text-sm flex items-center justify-center gap-2 shadow-sm transition-all active:scale-[0.98]"
                        >
                            <svg class="w-4 h-4 text-[#A5B4CF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>View Builds</span>
                        </a>
                    </div>
                </div>

                {{-- 2. Getting Started Card --}}
                <div class="rounded-2xl bg-[#111620] border border-[#252C3A] p-6 shadow-xl space-y-5">
                    {{-- Header --}}
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#201838] border border-[#3B2A63] text-[#8B5CF6] flex items-center justify-center shrink-0 shadow-inner">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-[#F8FAFC] tracking-wide">Getting Started</h3>
                            <p class="text-xs text-[#A5B4CF] mt-0.5">{{ $tasksCompleted }} of 3 tasks complete</p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="flex items-center gap-3 pt-1">
                        <div class="flex-1 h-2 rounded-full bg-[#151A27] border border-[#252C3A] overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#635BFF] to-[#8B5CF6] transition-all duration-500 shadow-sm" style="width: {{ $progressPercent }}%;"></div>
                        </div>
                        <span class="text-xs font-bold text-[#F8FAFC] font-mono">{{ $progressPercent }}%</span>
                    </div>

                    {{-- Tasks Checklist --}}
                    <div class="space-y-3.5 pt-2">
                        
                        {{-- Task 1: Create your first app --}}
                        <div class="flex items-center gap-3">
                            @if($hasCreatedApp)
                                <div class="w-5 h-5 rounded-full bg-[#635BFF] text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-3 h-3 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-xs text-[#7E8B9B] line-through font-medium">Create your first app</span>
                            @else
                                <a href="{{ url('/app/customer-apps/create') }}" class="flex items-center gap-3 group">
                                    <div class="w-5 h-5 rounded-full border-2 border-[#3A455A] group-hover:border-[#635BFF] flex items-center justify-center shrink-0 transition-colors"></div>
                                    <span class="text-xs font-medium text-[#F8FAFC] group-hover:text-[#8B5CF6] transition-colors">Create your first app</span>
                                </a>
                            @endif
                        </div>

                        {{-- Task 2: Build your app --}}
                        <div class="flex items-center gap-3">
                            @if($hasBuiltApp)
                                <div class="w-5 h-5 rounded-full bg-[#635BFF] text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-3 h-3 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-xs text-[#7E8B9B] line-through font-medium">Build your app</span>
                            @else
                                <a href="{{ url('/app/customer-apps') }}" class="flex items-center gap-3 group">
                                    <div class="w-5 h-5 rounded-full border-2 border-[#3A455A] group-hover:border-[#635BFF] flex items-center justify-center shrink-0 transition-colors"></div>
                                    <span class="text-xs font-medium text-[#F8FAFC] group-hover:text-[#8B5CF6] transition-colors">Build your app</span>
                                </a>
                            @endif
                        </div>

                        {{-- Task 3: Download and publish --}}
                        <div class="flex items-center gap-3">
                            @if($hasReadyBuild)
                                <div class="w-5 h-5 rounded-full bg-[#635BFF] text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-3 h-3 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-xs text-[#7E8B9B] line-through font-medium">Download and publish</span>
                            @else
                                <a href="{{ url('/app/customer-builds') }}" class="flex items-center gap-3 group">
                                    <div class="w-5 h-5 rounded-full border-2 border-[#3A455A] group-hover:border-[#635BFF] flex items-center justify-center shrink-0 transition-colors"></div>
                                    <span class="text-xs font-medium text-[#F8FAFC] group-hover:text-[#8B5CF6] transition-colors">Download and publish</span>
                                </a>
                            @endif
                        </div>

                    </div>
                </div>

            </div>

        </div>

        {{-- Preview Modal (Triggered when user clicks Preview) --}}
        @if($previewApp)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 animate-in fade-in duration-200">
                <div class="relative rounded-2xl bg-[#111620] border border-[#252C3A] p-5 shadow-2xl max-w-md w-full flex flex-col items-center">
                    <div class="w-full flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📱</span>
                            <h3 class="text-sm font-bold text-[#F8FAFC]">{{ $previewApp->name }} — Simulator Preview</h3>
                        </div>
                        <button type="button" wire:click="closePreview" class="p-1.5 rounded-lg bg-[#151A27] hover:bg-[#1A2234] text-[#A5B4CF] hover:text-[#F8FAFC] transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Embed phone preview --}}
                    @include('filament.customer.phone-preview', [
                        'app' => $previewApp,
                        'config' => $previewConfig,
                    ])

                    <div class="mt-4 w-full flex justify-end">
                        <button type="button" wire:click="closePreview" class="px-4 py-1.5 rounded-xl bg-[#151A27] hover:bg-[#1A2234] border border-[#252C3A] text-xs font-semibold text-[#F8FAFC]">
                            Close Simulator
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
