<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Notifiable;
use DateTimeInterface;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const DEPARTMENT_ROLES = [
        'farm_operations',
        'hr',
        'finance',
        'logistics',
        'sales',
        'admin',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'location',
        'password',
        'role',
        'status',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'kyc_verified',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'kyc_verified' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // Polymorphic Relationships
    public function farmOwner()
    {
        return $this->hasOne(FarmOwner::class);
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    // Consumer Orders
    public function consumerOrders()
    {
        return $this->hasMany(Order::class, 'consumer_id');
    }

    // Documents
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function mobileAccessTokens(): HasMany
    {
        return $this->hasMany(MobileAccessToken::class);
    }

    // Subscriptions (legacy)
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->orderByDesc('ends_at');
    }

    // Staff relationships
    public function createdStaff()
    {
        return $this->hasMany(Staff::class, 'created_by');
    }

    public function verifiedDocuments()
    {
        return $this->hasMany(Document::class, 'verified_by');
    }

    public function supportMessages()
    {
        return $this->hasMany(SupportMessage::class, 'sender_id');
    }

    public function internalMessagesSent()
    {
        return $this->hasMany(InternalMessage::class, 'sender_id');
    }

    // Query Scopes
    public function scopeByRole(Builder $query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified(Builder $query)
    {
        return $query->where('email_verified_at', '!=', null)
                    ->where('kyc_verified', true);
    }

    public function scopeFarmOwners(Builder $query)
    {
        return $query->where('role', 'farm_owner');
    }

    public function scopeConsumers(Builder $query)
    {
        return $query->where('role', 'consumer');
    }

    public function scopeStaff(Builder $query)
    {
        return $query->where('role', 'staff');
    }

    public function scopeSuperAdmins(Builder $query)
    {
        return $query->whereIn('role', ['superadmin', 'super_admin', 'super admin']);
    }

    public function scopeWithFarmOwner(Builder $query)
    {
        return $query->with('farmOwner');
    }

    public function scopeWithNotifications(Builder $query)
    {
        return $query->with(['notifications' => fn($q) => $q->unread()]);
    }

    public function scopeRecentlyActive(Builder $query, $days = 7)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    // Methods
    public function isSuperAdmin(): bool
    {
        return in_array($this->normalizeRoleValue($this->role), ['superadmin'], true);
    }

    public function isFarmOwner(): bool
    {
        return in_array($this->normalizeRoleValue($this->role), ['farm_owner'], true);
    }

    private function normalizeRoleValue(?string $role): string
    {
        $normalized = str_replace([' ', '-'], '_', strtolower(trim((string) $role)));

        return match ($normalized) {
            'super_admin' => 'superadmin',
            'farmowner' => 'farm_owner',
            default => $normalized,
        };
    }

    public function isConsumer(): bool
    {
        return $this->role === 'consumer';
    }

    public function isHR(): bool
    {
        return $this->normalizeRoleValue($this->role) === 'hr';
    }

    public function isFinance(): bool
    {
        return $this->normalizeRoleValue($this->role) === 'finance';
    }

    public function isDepartmentRole(): bool
    {
        return in_array($this->normalizeRoleValue($this->role), self::DEPARTMENT_ROLES, true);
    }

    public function departmentDashboardRouteName(): ?string
    {
        return match ($this->normalizeRoleValue($this->role)) {
            'hr' => 'hr.users.index',
            'farm_operations' => 'department.farm_operations.dashboard',
            'finance' => 'department.finance.dashboard',
            'logistics' => 'department.logistics.dashboard',
            'sales' => 'department.sales.dashboard',
            'admin' => 'department.admin.dashboard',
            default => null,
        };
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at && $this->kyc_verified;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    public function issueMobileAccessToken(
        string $name = 'consumer-app',
        ?DateTimeInterface $expiresAt = null,
    ): string {
        $plainTextToken = bin2hex(random_bytes(40));

        $this->mobileAccessTokens()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plainTextToken),
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        return $plainTextToken;
    }

    public function revokeMobileAccessTokens(?string $name = null): void
    {
        $query = $this->mobileAccessTokens();

        if ($name !== null) {
            $query->where('name', $name);
        }

        $query->delete();
    }

    public function markEmailAsVerified()
    {
        if ($this->hasVerifiedEmail()) {
            return false;
        }

        return $this->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    public function markPhoneAsVerified()
    {
        if (!$this->phone_verified_at) {
            $this->update(['phone_verified_at' => now()]);
        }
    }

    public function markKYCVerified()
    {
        $this->update(['kyc_verified' => true]);
    }
}
