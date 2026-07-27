<?php

namespace App\Http\Requests\Admin;

use App\Models\RegistrationCode;

class StoreRegistrationCodeRequest extends RegistrationCodeRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RegistrationCode::class) === true;
    }
}
