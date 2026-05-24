<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PiggyBank Model
 * Represents a physical donation box (piggy bank) located at a partner shop,
 * managed and collected by an assigned field agent.
 *
 * Kumbara Modeli
 * Bir ortak dükkanında bulunan ve atanmış saha personeli tarafından yönetilen,
 * para toplanıp sıfırlanabilen fiziksel bağış kutusunu (kumbarayı) temsil eder.
 *
 * @property int         $id
 * @property string      $unique_box_id      Human-readable QR code identifier. — QR kodla eşleşen benzersiz kutu kimliği.
 * @property int         $shop_id            Foreign key to the parent shop. — Ana dükkanın yabancı anahtarı.
 * @property int|null    $assigned_to_user_id FK to the responsible field agent. — Sorumlu saha personelinin yabancı anahtarı.
 * @property string|null $name               Optional descriptive label. — İsteğe bağlı açıklayıcı etiket.
 * @property float       $current_balance    Current accumulated donation amount in TRY. — TL cinsinden mevcut birikim bakiyesi.
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PiggyBank extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     * Toplu atama yapılmasına izin verilen sütunlar.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unique_box_id', 'shop_id', 'assigned_to_user_id', 'name', 'current_balance', 'donation_category', 'category_details',
    ];

    /**
     * Default model attributes.
     */
    protected $attributes = [
        'donation_category' => 'money',
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
            'current_balance' => 'float',
            'category_details' => 'array',
        ];
    }

    /**
     * Enforce relational integrity on delete.
     * Silme işlemi sırasında ilişkisel bütünlüğü korur.
     */
    protected static function booted(): void
    {
        static::deleting(function (PiggyBank $piggyBank) {
            // Prevent deletion if the piggy bank has transactions
            if ($piggyBank->transactions()->withTrashed()->exists()) {
                throw new \Exception('Bu kumbaraya ait işlem hareketleri (tahsilat/sıfırlama) bulunduğu için kumbara silinemez.');
            }
        });
    }

    /**
     * Get the shop where this piggy bank is physically located.
     * Bu kumbaranın fiziksel olarak bulunduğu dükkanı getirir.
     *
     * @return BelongsTo<Shop, PiggyBank>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the field agent (user) assigned to collect from this piggy bank.
     * Bu kumbaradan tahsilat yapmakla görevlendirilen saha personelini (kullanıcıyı) getirir.
     *
     * @return BelongsTo<User, PiggyBank>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get the full transaction history (collections and resets) for this piggy bank.
     * Bu kumbaraya ait tüm işlem geçmişini (tahsilatlar ve sıfırlamalar) getirir.
     *
     * @return HasMany<Transaction>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
