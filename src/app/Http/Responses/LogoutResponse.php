<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        if ($request->is('admin/*')) {
            return redirect()->route('admin.loginForm');
        }

        return redirect()->route('login');
    }
}
