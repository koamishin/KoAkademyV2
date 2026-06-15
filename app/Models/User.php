<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleEnums;
use App\Support\CampusMembershipProvisioner;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $profile_photo_path
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication, HasTenants, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Impersonate, Notifiable;

    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use InteractsWithEmailAuthentication;
    use LogsActivity;
    use PasskeyAuthenticatable;

    public function canAccessPanel(Panel $panel): bool
    {
        app(CampusMembershipProvisioner::class)->provision($this);

        return $this->campusMemberships()
            ->where('active', true)
            ->whereIn('role', RoleEnums::administrativeValues())
            ->exists();
    }

    public function canImpersonate(): bool
    {
        return $this->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function isSuperAdministrator(?Campus $campus = null): bool
    {
        return $this->campusMemberships()
            ->where('active', true)
            ->where('role', RoleEnums::SUPER_ADMIN)
            ->when($campus, fn ($query) => $query->whereBelongsTo($campus))
            ->exists();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the user's profile photo URL.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo_path) {
            return asset('storage/'.$this->profile_photo_path);
        }

        return null;
    }

    /**
     * Get the disk that profile photos should be stored on.
     */
    protected function profilePhotoDisk(): string
    {
        return 'public';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'has_email_authentication' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function person(): HasOne
    {
        return $this->hasOne(Person::class);
    }

    public function campusMemberships(): HasMany
    {
        return $this->hasMany(CampusMembership::class);
    }

    public function campuses(): BelongsToMany
    {
        return $this->belongsToMany(Campus::class)
            ->withPivot(['role', 'active', 'is_default'])
            ->withTimestamps();
    }

    public function assignedCampus(): ?Campus
    {
        return $this->campuses()
            ->wherePivot('active', true)
            ->orderByPivot('is_default', 'desc')
            ->orderBy('campuses.name')
            ->first();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->campuses()
            ->wherePivot('active', true)
            ->wherePivotIn('role', RoleEnums::administrativeValues())
            ->orderBy('campuses.name')
            ->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant instanceof Campus
            && $this->campusMemberships()
                ->where('campus_id', $tenant->getKey())
                ->where('active', true)
                ->whereIn('role', RoleEnums::administrativeValues())
                ->exists();
    }
}
