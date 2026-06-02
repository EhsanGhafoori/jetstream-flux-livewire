<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Jetstream;
use Laravel\Socialite\Facades\Socialite;
use LaravelSocialite\GoogleOneTap\Exceptions\InvalidIdTokenException;

class GoogleOneTapController extends Controller
{
    /**
     * Handle Google One Tap JWT authentication.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('laravel-google-one-tap')->userFromToken($request->input('credential'));
        } catch (InvalidIdTokenException) {
            return redirect()->route('login')->withErrors([
                'email' => __('Google authentication failed.'),
            ]);
        }

        $user = User::firstOrNew(['email' => $googleUser->getEmail()]);

        DB::transaction(function () use ($googleUser, $user) {
            if (! $user->exists) {
                $user->name = $googleUser->getName();
                $user->password = Hash::make($googleUser->getId());
                $user->email_verified_at = now();

                if ($googleUser->getAvatar() && in_array('profile_photo_path', $user->getFillable(), true)) {
                    $user->profile_photo_path = $googleUser->getAvatar();
                }

                $user->save();
            }

            if (Jetstream::hasTeamFeatures() && $user->fresh()->personalTeam() === null) {
                $this->createPersonalTeam($user);
            }
        });

        Auth::login($user->fresh(), remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Create a personal team for the user (matches Fortify registration).
     */
    protected function createPersonalTeam(User $user): void
    {
        $team = Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]);

        $user->switchTeam($team);
    }
}
