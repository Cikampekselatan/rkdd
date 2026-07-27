<?php

namespace App\Http\Requests\Admin;

class UpdateRegistrationCodeRequest extends RegistrationCodeRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('registration_code')) === true;
    }
}
