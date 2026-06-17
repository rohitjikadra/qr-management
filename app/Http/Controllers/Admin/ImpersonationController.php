<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        abort_unless($impersonatorId, 403);

        $impersonator = User::findOrFail($impersonatorId);
        $impersonatedUserId = $request->user()?->id;

        $request->session()->forget('impersonator_id');
        Auth::login($impersonator);
        $request->session()->regenerate();

        AuditLog::record('user.impersonation_stopped', null, [
            'impersonated_user_id' => $impersonatedUserId,
        ], $impersonator->id);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Stopped impersonating user.');
    }
}
