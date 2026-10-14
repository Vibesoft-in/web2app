<?php

namespace App\Http\Controllers;

use App\Services\Auth\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(
        protected ImpersonationService $impersonationService,
    ) {}

    /**
     * Leave impersonation and return to original owner session.
     */
    public function leave(Request $request): RedirectResponse
    {
        $impersonator = $this->impersonationService->leave();

        if ($impersonator) {
            return redirect('/admin')->with('status', 'Returned to original administrator account.');
        }

        return redirect('/admin');
    }
}
