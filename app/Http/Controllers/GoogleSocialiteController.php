<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Socialite;
use Auth;
use Exception;
use App\Models\User;
use Config;
use Str;
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

        // Very Important! Stops anyone with any google accessing Nova!
        if (! Str::endsWith($socialUser->getEmail(), 'afia.ae')) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => [
                        __('You can only sign in with AFIA account.'),
                    ],
                ]);
        }
        $isExisted = User::where('email' ,$socialUser->getEmail())->get();

        if (count($isExisted) == 0) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => [
                        __('User wth the email not found'),
                    ],
                ]);
        }

        Auth::login($isExisted[0]);
        return redirect('/home');
    }
}
