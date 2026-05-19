<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();

        if ($user && $user->isAsesor()) {
            return redirect()->route('asesor.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }
}
