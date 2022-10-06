<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

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
            $socialUser = Socialite::driver(config('constants.social_driver'))->stateless()->user();
        } catch (InvalidStateException $exception) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => [
                        __('Google Login failed, please try again.'),
                    ],
                ]);
        }
        $requestingUser = User::where('email', $socialUser->getEmail())->first();
        if (! $requestingUser) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => [
                        __('User wth the email not found'),
                    ],
                ]);
        }

        auth()->login($requestingUser);

        $requestingUser->last_login = now();
        $requestingUser->save();

        return redirect()->intended('/leadsearch');
    }
}
