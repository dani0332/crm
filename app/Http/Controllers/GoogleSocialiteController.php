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
        $isExisted = User::where('email', $socialUser->getEmail())->get();
        if (count($isExisted) == 0) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => [
                        __('User wth the email not found'),
                    ],
                ]);
        }

        auth()->login($isExisted[0]);

        return redirect()->intended('/leadsearch');
    }
}
