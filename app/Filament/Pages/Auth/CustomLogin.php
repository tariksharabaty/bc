<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class CustomLogin extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    public function getHeading(): string | Htmlable | null
    {
        return null; // Suppress native heading to prevent double title text
    }

    public function getSubheading(): string | Htmlable | null
    {
        return null; // Suppress native subheading to prevent double subtitle text
    }
}
