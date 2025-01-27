<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function loginAs($id)
    {
        abort_if(auth()->user()->cannot(PermissionsEnum::ENABLE_IMPERSONATION), 403, 'You are not authorized to impersonate');

        abort_if(app('impersonate')->isImpersonating(), 403, 'You are already impersonating a user');

        $user = User::findOrFail($id);

        abort_if($user->email === Auth::user()->email, 403, 'You cannot impersonate yourself');

        Auth::user()->impersonate($user);

        return redirect('/home');
    }

    public function leave()
    {
        abort_unless(app('impersonate')->isImpersonating(), 403, 'You are not impersonating a user');

        Auth::user()->leaveImpersonation();

        return redirect('/admin/users');
    }
}
