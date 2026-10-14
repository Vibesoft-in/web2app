@php
    $impersonationService = app(\App\Services\Auth\ImpersonationService::class);
@endphp

@if ($impersonationService->isImpersonating())
    <div style="background-color: #dc2626; color: #ffffff; padding: 12px 24px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; z-index: 9999; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2); font-family: ui-sans-serif, system-ui, sans-serif;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 18px;">⚠️</span>
            <span><strong>IMPERSONATION ACTIVE:</strong> You are currently managing tenant <em>#{{ auth()->user()?->tenant_id }}</em> as <u>{{ auth()->user()?->email }}</u>. An audit log is being recorded for all actions.</span>
        </div>
        <div>
            <a href="{{ route('impersonate.leave') }}" style="background-color: #ffffff; color: #dc2626; padding: 6px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-block;">
                Leave Impersonation &rarr;
            </a>
        </div>
    </div>
@endif
