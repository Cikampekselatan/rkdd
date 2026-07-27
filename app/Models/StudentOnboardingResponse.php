<?php

namespace App\Models;

use Database\Factories\StudentOnboardingResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentOnboardingResponse extends Model
{
    /** @use HasFactory<StudentOnboardingResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'registration_code_id',
        'device_access',
        'internet_access',
        'willing_to_share_device',
        'digital_apps',
        'interests',
        'initial_skills',
        'experience',
        'expectation',
        'learning_targets',
        'agreed_rules_at',
        'agreed_privacy_at',
        'agreed_ai_policy_at',
        'agreed_publication_policy_at',
        'current_step',
        'completed_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrationCode(): BelongsTo
    {
        return $this->belongsTo(RegistrationCode::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'device_access' => 'array',
            'willing_to_share_device' => 'boolean',
            'digital_apps' => 'array',
            'interests' => 'array',
            'initial_skills' => 'array',
            'agreed_rules_at' => 'datetime',
            'agreed_privacy_at' => 'datetime',
            'agreed_ai_policy_at' => 'datetime',
            'agreed_publication_policy_at' => 'datetime',
            'current_step' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
