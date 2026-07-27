<?php

namespace App\Models;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'google_id',
        'name',
        'email',
        'email_verified_at',
        'password',
        'profile_photo_path',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * @return HasMany<AuthenticationLog, $this>
     */
    public function authenticationLogs(): HasMany
    {
        return $this->hasMany(AuthenticationLog::class);
    }

    /**
     * @return HasMany<RegistrationCode, $this>
     */
    public function createdRegistrationCodes(): HasMany
    {
        return $this->hasMany(RegistrationCode::class, 'created_by');
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function onboardingResponse(): HasOne
    {
        return $this->hasOne(StudentOnboardingResponse::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function assignedProgramBatches(): BelongsToMany
    {
        return $this->belongsToMany(ProgramBatch::class, 'program_batch_staff')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function classMemberships(): HasMany
    {
        return $this->hasMany(ClassStudent::class);
    }

    public function learningProgress(): HasMany
    {
        return $this->hasMany(StudentLearningProgress::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function teacherActivityLogs(): HasMany
    {
        return $this->hasMany(TeacherActivityLog::class, 'teacher_id');
    }

    public function createdImportantNotes(): HasMany
    {
        return $this->hasMany(ImportantNote::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    public function projectGroups(): BelongsToMany
    {
        return $this->belongsToMany(ProjectGroup::class, 'project_group_members')
            ->withPivot(['role', 'contribution_note', 'joined_at', 'left_at', 'is_active'])
            ->withTimestamps();
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function discussionTopics(): HasMany
    {
        return $this->hasMany(DiscussionTopic::class, 'created_by');
    }

    public function discussionPosts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class);
    }

    public function hasRole(RoleSlug|string $role): bool
    {
        $slug = $role instanceof RoleSlug ? $role->value : $role;

        return $this->roles->contains(
            fn (Role $assignedRole): bool => $assignedRole->slug->value === $slug,
        );
    }

    /**
     * @param  iterable<RoleSlug|string>  $roles
     */
    public function hasAnyRole(iterable $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(RoleSlug::staffRoles());
    }

    public function dashboardRouteName(): string
    {
        foreach ([
            RoleSlug::SuperAdmin,
            RoleSlug::Admin,
            RoleSlug::Teacher,
            RoleSlug::Coach,
            RoleSlug::Principal,
            RoleSlug::Student,
        ] as $role) {
            if ($this->hasRole($role)) {
                return $role->dashboardRouteName();
            }
        }

        return 'home';
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo_path
            ? asset('storage/'.$this->profile_photo_path)
            : null;
    }
}
