<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use Config;
use Socialite;

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
            $socialUser = Socialite::driver(Config::get('constants.social_driver'))->user();
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

        Auth::login($isExisted[0]);
        return redirect('/dashboard');
    }
}
