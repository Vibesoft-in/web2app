<x-filament-panels::page class="fi-dashboard-page">
    <div class="space-y-6 -mt-4 font-sans select-none text-slate-100">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Dashboard</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage your apps and builds in one place.</p>
        </div>

        {{-- Main Dashboard Grid (Left 8 cols, Right 4 cols) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- ============================================================= --}}
            {{-- LEFT COLUMN: Welcome Banner + Stats Cards + Recent Activity   --}}
            {{-- ============================================================= --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- 1. Welcome Banner --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#171a2e] via-[#131624] to-[#10121d] border border-slate-800/80 p-5 sm:p-6 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    {{-- Ambient Glow --}}
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="flex items-center gap-4 relative z-10">
                        {{-- Avatar with User Initial --}}
                        <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-indigo-600/30 shrink-0">
                            {{ $userInitial }}
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                                Welcome back, {{ $userName }}
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Manage your mobile apps, track builds, and grow your business.
                            </p>
                        </div>
                    </div>

                    {{-- Sign Out Action --}}
                    <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="relative z-10 shrink-0">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900/90 border border-slate-700/60 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800 hover:border-slate-600 transition-all shadow-sm active:scale-95">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Sign out
                        </button>
                    </form>
                </div>

                {{-- 2. Stats Cards (3 Columns) --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    
                    {{-- Stat 1: Mobile Apps --}}
                    <div class="rounded-2xl bg-[#13161f] border border-slate-800/80 p-4.5 shadow-lg flex items-center gap-3.5 transition-all hover:border-slate-700">
                        <div class="w-11 h-11 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center shrink-0 shadow-inner">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-slate-400 block truncate">Mobile Apps</span>
                            <div class="text-2xl font-black text-white tracking-tight mt-0.5 leading-none">
                                {{ $totalApps }}
                            </div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Total apps created</span>
                        </div>
                    </div>

                    {{-- Stat 2: Successful Builds --}}
                    <div class="rounded-2xl bg-[#13161f] border border-slate-800/80 p-4.5 shadow-lg flex items-center gap-3.5 transition-all hover:border-slate-700">
                        <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 shadow-inner">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-slate-400 block truncate">Successful Builds</span>
                            <div class="text-2xl font-black text-white tracking-tight mt-0.5 leading-none">
                                {{ $successfulBuilds }}
                            </div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Completed builds</span>
                        </div>
                    </div>

                    {{-- Stat 3: Pending Builds --}}
                    <div class="rounded-2xl bg-[#13161f] border border-slate-800/80 p-4.5 shadow-lg flex items-center gap-3.5 transition-all hover:border-slate-700">
                        <div class="w-11 h-11 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 shadow-inner">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-medium text-slate-400 block truncate">Pending Builds</span>
                            <div class="text-2xl font-black text-white tracking-tight mt-0.5 leading-none">
                                {{ $pendingBuilds }}
                            </div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Builds in queue</span>
                        </div>
                    </div>

                </div>

                {{-- 3. Recent Activity Section --}}
                <div class="rounded-2xl bg-[#13161f] border border-slate-800/80 p-5 shadow-xl">
                    {{-- Header --}}
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                        <div>
                            <h3 class="text-sm font-bold text-white tracking-wide">Recent Activity</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Your latest apps and build activity</p>
                        </div>
                        <a href="{{ url('/app/customer-apps') }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-400 hover:text-indigo-300 transition-colors">
                            View All
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>

                    {{-- Table / List of Apps --}}
                    @if($apps->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-800/60">
                                        <th class="py-3 px-2">App Name</th>
                                        <th class="py-3 px-2">Platform</th>
                                        <th class="py-3 px-2">Status</th>
                                        <th class="py-3 px-2">Updated</th>
                                        <th class="py-3 px-2 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/50">
                                    @foreach($apps as $index => $app)
                                        @php
                                            $latestBuild = $app->builds->first();
                                            $buildStatus = $latestBuild ? (string) ($latestBuild->status?->value ?? $latestBuild->status) : 'draft';
                                            
                                            // Palette for icons: purple, pink, blue, emerald
                                            $iconColors = [
                                                ['bg' => 'bg-purple-600', 'svg' => 'bag'],
                                                ['bg' => 'bg-pink-600', 'svg' => 'shirt'],
                                                ['bg' => 'bg-blue-600', 'svg' => 'cart'],
                                                ['bg' => 'bg-emerald-600', 'svg' => 'store'],
                                            ];
                                            $colorPick = $iconColors[$index % 4];
                                        @endphp
                                        <tr class="hover:bg-slate-900/40 transition-colors">
                                            {{-- App Name & Package --}}
                                            <td class="py-3.5 px-2">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-xl {{ $colorPick['bg'] }} flex items-center justify-center text-white shrink-0 shadow-sm text-sm font-black">
                                                        @if($colorPick['svg'] === 'bag')
                                                            🛍️
                                                        @elseif($colorPick['svg'] === 'shirt')
                                                            👕
                                                        @elseif($colorPick['svg'] === 'cart')
                                                            🛒
                                                        @else
                                                            🏪
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <h4 class="text-xs font-bold text-white truncate max-w-[150px] sm:max-w-[200px]">
                                                            {{ $app->name }}
                                                        </h4>
                                                        <p class="text-[10px] text-slate-400 font-mono truncate max-w-[150px] sm:max-w-[200px] mt-0.5">
                                                            {{ $app->package_name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- Platform Badges --}}
                                            <td class="py-3.5 px-2">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900 border border-slate-700/80 text-[10px] font-semibold text-slate-300 shadow-2xs">
                                                        <svg class="w-2.5 h-2.5 text-emerald-400" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993.0001.5511-.4482.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993 0 .5511-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5902 8.4116 13.8533 8.077 12 8.077c-1.8533 0-3.5902.3346-5.1368.8727L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                                                        </svg>
                                                        Android
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-900 border border-slate-700/80 text-[10px] font-semibold text-slate-300 shadow-2xs">
                                                        <svg class="w-2.5 h-2.5 text-slate-300" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 4.12c.62-.75 1.04-1.8 1.01-2.87-.96.04-2.13.64-2.79 1.41-.58.68-.97 1.74-.94 2.82 1.08.08 2.1-.61 2.72-1.36z"/>
                                                        </svg>
                                                        iOS
                                                    </span>
                                                </div>
                                            </td>

                                            {{-- Status Badge --}}
                                            <td class="py-3.5 px-2">
                                                @if($buildStatus === 'ready')
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                        Ready
                                                    </span>
                                                @elseif(in_array($buildStatus, ['building', 'preparing', 'signing']))
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 border border-blue-500/30 text-blue-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                                                        Building
                                                    </span>
                                                @elseif($buildStatus === 'queued')
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 border border-amber-500/30 text-amber-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                                                        Queued
                                                    </span>
                                                @elseif($buildStatus === 'failed')
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 border border-rose-500/30 text-rose-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                                        Failed
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 border border-slate-700 text-slate-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                                        Draft
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Updated Relative Time --}}
                                            <td class="py-3.5 px-2 text-slate-400 whitespace-nowrap text-xs">
                                                {{ $app->updated_at->diffForHumans() }}
                                            </td>

                                            {{-- Actions --}}
                                            <td class="py-3.5 px-2 text-right">
                                                <div class="inline-flex items-center gap-1.5 justify-end">
                                                    {{-- Preview Button --}}
                                                    <button 
                                                        type="button" 
                                                        wire:click="openPreview({{ $app->id }})"
                                                        class="p-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors" 
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
                                                        class="px-2 py-1 rounded-lg bg-indigo-600/90 hover:bg-indigo-600 text-white font-bold text-[10px] transition-colors shadow-xs" 
                                                        title="Build Standalone Android APK"
                                                    >
                                                        Build APK
                                                    </button>

                                                    {{-- Download APK if ready --}}
                                                    @if($buildStatus === 'ready')
                                                        <button 
                                                            type="button" 
                                                            wire:click="downloadApk({{ $app->id }})"
                                                            class="p-1.5 rounded-lg bg-emerald-600/90 hover:bg-emerald-600 text-white transition-colors shadow-xs" 
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
                            <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center mb-3 shadow-inner">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-white">No mobile apps created yet</h4>
                            <p class="text-xs text-slate-400 max-w-sm mt-1 leading-relaxed">
                                Turn your WooCommerce website or store into high-performance native Android & iOS mobile apps in just 3 easy steps.
                            </p>
                            <a href="{{ url('/app/customer-apps/create') }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all active:scale-95">
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
                <div class="rounded-2xl bg-[#13161f] border border-slate-800/80 p-5 shadow-xl space-y-4">
                    {{-- Header --}}
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 shadow-inner">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white tracking-wide">Quick Actions</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Get started with common tasks.</p>
                        </div>
                    </div>

                    {{-- Actions Buttons --}}
                    <div class="space-y-2.5 pt-1">
                        <a 
                            href="{{ url('/app/customer-apps/create') }}" 
                            class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-[0.98] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/30 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            Create New App
                        </a>

                        <a 
                            href="{{ url('/app/customer-builds') }}" 
                            class="w-full py-2.5 px-4 rounded-xl bg-[#0d1017] hover:bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-200 font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition-all"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            View Builds
                        </a>
                    </div>
                </div>

                {{-- 2. Getting Started Card --}}
                <div class="rounded-2xl bg-[#13161f] border border-slate-800/80 p-5 shadow-xl space-y-4">
                    {{-- Header --}}
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center shrink-0 shadow-inner">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white tracking-wide">Getting Started</h3>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $tasksCompleted }} of 3 tasks complete</p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-400">
                            <span>Progress</span>
                            <span class="text-white font-mono">{{ $progressPercent }}%</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-900 border border-slate-800 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500 shadow-sm" style="width: {{ $progressPercent }}%;"></div>
                        </div>
                    </div>

                    {{-- Tasks Checklist --}}
                    <div class="space-y-3 pt-2">
                        
                        {{-- Task 1: Create your first app --}}
                        <div class="flex items-center gap-3">
                            @if($hasCreatedApp)
                                <div class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-xs text-slate-400 line-through">Create your first app</span>
                            @else
                                <a href="{{ url('/app/customer-apps/create') }}" class="flex items-center gap-3 group">
                                    <div class="w-5 h-5 rounded-full border-2 border-slate-600 group-hover:border-indigo-400 flex items-center justify-center shrink-0 transition-colors"></div>
                                    <span class="text-xs font-semibold text-slate-200 group-hover:text-indigo-300 transition-colors">Create your first app</span>
                                </a>
                            @endif
                        </div>

                        {{-- Task 2: Build your app --}}
                        <div class="flex items-center gap-3">
                            @if($hasBuiltApp)
                                <div class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-xs text-slate-400 line-through">Build your app</span>
                            @else
                                <a href="{{ url('/app/customer-apps') }}" class="flex items-center gap-3 group">
                                    <div class="w-5 h-5 rounded-full border-2 border-slate-600 group-hover:border-indigo-400 flex items-center justify-center shrink-0 transition-colors"></div>
                                    <span class="text-xs font-semibold text-slate-200 group-hover:text-indigo-300 transition-colors">Build your app</span>
                                </a>
                            @endif
                        </div>

                        {{-- Task 3: Download and publish --}}
                        <div class="flex items-center gap-3">
                            @if($hasReadyBuild)
                                <div class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-xs text-slate-400 line-through">Download and publish</span>
                            @else
                                <a href="{{ url('/app/customer-builds') }}" class="flex items-center gap-3 group">
                                    <div class="w-5 h-5 rounded-full border-2 border-slate-600 group-hover:border-indigo-400 flex items-center justify-center shrink-0 transition-colors"></div>
                                    <span class="text-xs font-semibold text-slate-200 group-hover:text-indigo-300 transition-colors">Download and publish</span>
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
                <div class="relative rounded-2xl bg-[#131622] border border-slate-700/80 p-5 shadow-2xl max-w-md w-full flex flex-col items-center">
                    <div class="w-full flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📱</span>
                            <h3 class="text-sm font-bold text-white">{{ $previewApp->name }} — Simulator Preview</h3>
                        </div>
                        <button type="button" wire:click="closePreview" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors">
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
                        <button type="button" wire:click="closePreview" class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white">
                            Close Simulator
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
