<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shop Model
 * Represents a registered business partner (shop, charity point, etc.) where piggy banks are placed.
 *
 * Dükkan Modeli
 * Kumbaraların yerleştirildiği kayıtlı ticari/hayır amaçlı ortak noktaları (dükkan, ofis vb.) temsil eder.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $city
 * @property string      $district
 * @property string|null $address
 * @property string|null $phone
 * @property bool        $is_active
 * @property int|null    $user_id   The authorized contact person linked to this shop. — Dükkanla ilişkili yetkili kişi.
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Shop extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     * Toplu atama (mass assignment) yapılmasına izin verilen sütunlar.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'city', 'district', 'name', 'address', 'phone', 'is_active', 'user_id', 'latitude', 'longitude',
    ];

    /**
     * Attribute casting map.
     * Nitelik tür dönüşüm haritası.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Enforce relational integrity on delete.
     * Silme işlemi sırasında ilişkisel bütünlüğü korur.
     */
    protected static function booted(): void
    {
        static::deleting(function (Shop $shop) {
            // Prevent deletion if the shop has any piggy banks with transactions
            if ($shop->piggyBanks()->withTrashed()->whereHas('transactions', function ($query) {
                $query->withTrashed();
            })->exists()) {
                throw new \Exception('Bu dükkana ait kumbaraların işlem hareketleri (tahsilat/sıfırlama) bulunduğu için dükkan silinemez.');
            }
            
            // Delete piggy banks if they have no transactions
            $shop->piggyBanks()->each(function (PiggyBank $piggyBank) {
                $piggyBank->delete();
            });
        });
    }

    /**
     * Get the user (authorized contact) responsible for managing this shop.
     * Bu dükkanı yönetmekle sorumlu yetkili kullanıcıyı (iletişim kişisini) getirir.
     *
     * @return BelongsTo<User, Shop>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all piggy banks placed inside this shop.
     * Bu dükkanda bulunan tüm kumbaraları getirir.
     *
     * @return HasMany<PiggyBank>
     */
    public function piggyBanks(): HasMany
    {
        return $this->hasMany(PiggyBank::class);
    }
}
