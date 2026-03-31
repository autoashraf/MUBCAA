<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login-check', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('member-login', function (Request $request) {
            $identifier = strtolower(trim((string) $request->input('identifier')));

            return Limit::perMinute(5)->by($identifier.'|'.$request->ip());
        });

        RateLimiter::for('admin-login', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('login-otp-verify', function (Request $request) {
            $scope = $request->session()->get('login_otp_user_id', 'guest');

            return Limit::perMinute(10)->by($scope.'|'.$request->ip());
        });

        RateLimiter::for('login-otp-resend', function (Request $request) {
            $scope = $request->session()->get('login_otp_user_id', 'guest');

            return Limit::perMinute(5)->by($scope.'|'.$request->ip());
        });

        RateLimiter::for('registration-check', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('registration-submit', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('contact-verification', function (Request $request) {
            $scope = $request->user()?->id ?: 'pending:'.$request->session()->get('pending_registration_id', 'guest');

            return Limit::perMinute(10)->by($scope.'|'.$request->ip());
        });

        RateLimiter::for('contact-verification-resend', function (Request $request) {
            $scope = $request->user()?->id ?: 'pending:'.$request->session()->get('pending_registration_id', 'guest');

            return Limit::perMinute(5)->by($scope.'|'.$request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
