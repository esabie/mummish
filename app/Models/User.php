<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_before_deletion',
        'phone',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
    ];

    public function vendorApplication(): HasOne
    {
        return $this->hasOne(VendorApplication::class)->latestOfMany();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function vendorOrderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'vendor_user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    public function isVendor(): bool
    {
        return $this->role === UserRole::Vendor;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Email shown in admin UI. Soft-deleted accounts keep the original
     * address here after the login email is freed for reuse.
     */
    public function displayEmail(): string
    {
        if ($this->trashed() && filled($this->email_before_deletion)) {
            return (string) $this->email_before_deletion;
        }

        return (string) $this->email;
    }

    /**
     * Free the unique email slot so the same address can register again.
     * Keeps the original address in email_before_deletion for admin history.
     */
    public function releaseEmailForReuse(): bool
    {
        $current = strtolower(trim((string) $this->email));

        if ($current === '' || str_ends_with($current, '@deleted.invalid')) {
            return false;
        }

        $this->forceFill([
            'email_before_deletion' => $this->email_before_deletion ?: $this->email,
            'email' => sprintf(
                'deleted-%d-%s@deleted.invalid',
                $this->id,
                now()->format('YmdHis'),
            ),
        ])->save();

        return true;
    }

    /**
     * Phone number used when sending a password-reset SMS.
     * Admins & customers: users.phone.
     * Vendors: vendor application phone.
     */
    public function passwordResetPhone(): ?string
    {
        if ($this->isVendor()) {
            $vendorPhone = trim((string) ($this->vendorApplication?->phone ?? ''));

            return $vendorPhone !== '' ? $vendorPhone : null;
        }

        $phone = trim((string) ($this->phone ?? ''));

        return $phone !== '' ? $phone : null;
    }
}
