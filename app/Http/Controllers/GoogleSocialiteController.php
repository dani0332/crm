<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Models\User;
use Carbon\Carbon;
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

        if (! $requestingUser->is_active) {
            return redirect()->route('login')->with('status', 'Email does not exist or is inactive.');
        }

        $remember = in_array($requestingUser->email, getAutomationUser()) ? true : false;
        auth()->login($requestingUser, $remember);
        $lastUpdatedDate = Carbon::parse($requestingUser->google_photo_last_updated);
        $lastSync = $lastUpdatedDate->diffInDays(Carbon::now());

        if (isset($socialUser->user['picture']) && ($lastSync > 6 || $requestingUser->google_photo_last_updated == null)) {
            $requestingUser->google_photo_last_updated = now();
            $requestingUser->profile_photo_path = $socialUser->user['picture'];
        }
        $requestingUser->last_login = now();
        $requestingUser->save();

        if ($requestingUser->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager])) {
            return redirect()->intended('/quotes/car');
        }

        return redirect()->intended('/home');
    }
}
