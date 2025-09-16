<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->intended('/admin/attendance/list');
        }

        if (Auth::guard('web')->check()) {
            return redirect()->intended('/attendance');
        }

        return redirect()->intended('/login');
    }
}
