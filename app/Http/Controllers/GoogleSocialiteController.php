<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Models\User;
use Exception;
use Laravel\Socialite\Facades\Socialite;

class GoogleSocialiteController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function handleCallback()
    {
        try {
            $socialUser = Socialite::driver(config('constants.social_driver'))->user();
        } catch (Exception $exception) {
            return redirect()->route('login')->with('status', 'Google login failed. Please try again.');
        }

        $requestingUser = User::where('email', $socialUser->getEmail())->first();

        if (! $requestingUser) {
            return redirect()->route('login')->with('status', 'You are not authorized to login. Please contact admin.');
        }

        $remember = in_array($requestingUser->email, getAutomationUser()) ? true : false;
        auth()->login($requestingUser, $remember);

        $requestingUser->last_login = now();
        $requestingUser->save();

        if ($requestingUser->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager, RolesEnum::CarDeputyManager])) {
            return redirect()->intended('/quotes/car');
        }

        return redirect()->intended('/home');
    }
}
