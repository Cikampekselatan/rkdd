<?php

namespace App\Enums;

enum UserStatus: string
{
    case Onboarding = 'onboarding';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Graduated = 'graduated';
    case Archived = 'archived';
}
