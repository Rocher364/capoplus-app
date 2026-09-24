<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // VULN-13 : Traçabilité des événements d'authentification
        Event::listen(Login::class, function (Login $event) {
            $user = $event->user instanceof User ? $event->user : null;
            ActivityLogger::log(
                $user,
                'auth.login',
                $user,
                [
                    'guard' => $event->guard,
                    'email' => $user?->email,
                    'statut' => 'succes',
                ]
            );
        });

        Event::listen(Failed::class, function (Failed $event) {
            $email = $event->credentials['email'] ?? null;
            $user = $event->user instanceof User ? $event->user : ($email ? User::where('email', $email)->first() : null);

            ActivityLogger::log(
                $user,
                'auth.failed',
                $user,
                [
                    'guard' => $event->guard,
                    'email' => $email,
                    'statut' => 'echec',
                    'motif' => 'Identifiants invalides ou compte inactif',
                ]
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            $user = $event->user instanceof User ? $event->user : null;
            ActivityLogger::log(
                $user,
                'auth.logout',
                $user,
                [
                    'guard' => $event->guard,
                    'email' => $user?->email,
                ]
            );
        });

        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            $user = $event->user instanceof User ? $event->user : null;
            ActivityLogger::log(
                $user,
                'auth.motdepasse_reinitialise',
                $user,
                [
                    'email' => $user?->email,
                    'statut' => 'reinitialise',
                ]
            );
        });

        Event::listen(TwoFactorAuthenticationConfirmed::class, function (TwoFactorAuthenticationConfirmed $event) {
            $user = $event->user instanceof User ? $event->user : null;
            ActivityLogger::log(
                $user,
                'auth.2fa_active',
                $user,
                [
                    'email' => $user?->email,
                    'statut' => 'active',
                ]
            );
        });

        Event::listen(TwoFactorAuthenticationDisabled::class, function (TwoFactorAuthenticationDisabled $event) {
            $user = $event->user instanceof User ? $event->user : null;
            ActivityLogger::log(
                $user,
                'auth.2fa_desactive',
                $user,
                [
                    'email' => $user?->email,
                    'statut' => 'desactive',
                ]
            );
        });
    }
}
