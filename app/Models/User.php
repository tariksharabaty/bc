<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

/**
 * User Model
 * Represents an application user — either an administrator (super_admin) or a field agent (field_agent).
 * Implements FilamentUser to control panel access per role.
 *
 * Kullanıcı Modeli
 * Bir uygulama kullanıcısını — yönetici (super_admin) veya saha personeli (field_agent) — temsil eder.
 * Rol bazlı panel erişimini kontrol etmek için FilamentUser arayüzünü uygular.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property string      $password
 * @property string      $role              'super_admin' | 'field_agent'
 * @property bool        $is_active
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     * Tür dönüşümü yapılacak nitelikleri döndürür.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /**
     * Get all piggy banks assigned to this user (field agent).
     * Bu kullanıcıya (saha personeline) zimmetlenmiş tüm kumbaraları getirir.
     *
     * @return HasMany<PiggyBank>
     */
    public function piggyBanks(): HasMany
    {
        return $this->hasMany(PiggyBank::class, 'assigned_to_user_id');
    }

    /**
     * Get all transactions (collections/resets) performed by this user.
     * Bu kullanıcı tarafından gerçekleştirilen tüm tahsilat ve sıfırlama hareketlerini getirir.
     *
     * @return HasMany<Transaction>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    /**
     * Determine whether the user is permitted to access the given Filament panel.
     * Kullanıcının belirtilen Filament paneline erişim iznine sahip olup olmadığını belirler.
     *
     * - 'admin' panel: restricted to super_admin role.
     * - 'saha'  panel: restricted to field_agent role.
     *
     * @param Panel $panel The panel being accessed. — Erişilmekte olan panel.
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->role === 'super_admin';
        }

        if ($panel->getId() === 'saha') {
            return $this->role === 'field_agent';
        }

        return false;
    }
}
