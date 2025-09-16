<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\LoginResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('user.register');
        });

        Fortify::loginView(function () {
            return view('user.login');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });

        // roleでログインを分岐する
        Fortify::authenticateUsing(function (Request $request) {
            $credentials = $request->only('email', 'password');

            if ($request->role === 'admin') {
                if (Auth::guard('admin')->attempt($credentials)) {
                    return Auth::guard('admin')->user();
                }
            } else {
                if (Auth::guard('web')->attempt($credentials)) {
                    return Auth::guard('web')->user();
                }
            }

            return null;
        });
    }
}
