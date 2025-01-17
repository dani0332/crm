<?php

namespace App\Http\Controllers\V2;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function __construct()
    {
        $allowedRoles = Arr::join([RolesEnum::Engineering], '|');

        $this->middleware("role:$allowedRoles", [
            'only' => ['loginVia'],
        ]);
    }

    public function loginVia(string $email)
    {
        $user = User::whereEmail($email)->first();
        if ($user) {
            abort_if($user->email === Auth::user()->email, 403, 'You cannot impersonate yourself');

            Auth::user()->impersonate($user);
            $user->last_login = now();
            $user->save();
        }

        return redirect('/home');
    }

    public function leave()
    {
        Auth::user()->leaveImpersonation();

        return redirect('/home');
    }
}
