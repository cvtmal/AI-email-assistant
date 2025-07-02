<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use Inertia\Response;

final class NewPasswordController
{
    /**
     * Show the password reset page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/login');
    }

    /**
     * Show the password reset page.
     */
    public function store(): Response
    {
        return Inertia::render('auth/login');
    }
}
