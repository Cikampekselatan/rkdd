<?php

namespace App\Actions\Auth;

use App\Models\User;

class ResolveDashboardRoute
{
    public function for(User $user): string
    {
        return $user->dashboardRouteName();
    }
}
