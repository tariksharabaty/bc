<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transaction Model
 * Represents a single financial event (collection or full reset) performed on a piggy bank.
 * This model is append-only — records must never be edited or deleted to preserve audit integrity.
 *
 * Tahsilat Hareketi Modeli
 * Bir kumbarada gerçekleştirilen tek bir finansal olayı (tahsilat veya tam sıfırlama) temsil eder.
 * Bu model yalnızca ekleme amaçlıdır — denetim bütünlüğünü korumak için kayıtlar asla düzenlenmemeli
 * veya silinmemelidir.
 *
 * @property int         $id
 * @property int         $piggy_bank_id  FK to the piggy bank involved. — İşlem yapılan kumbaranın yabancı anahtarı.
 * @property int         $user_id        FK to the user who performed the action. — İşlemi gerçekleştiren kullanıcının yabancı anahtarı.
 * @property string      $action_type    'collection' | 'reset'
 * @property float       $amount         Monetary amount in TRY. — TL cinsinden işlem tutarı.
 * @property string|null $description    Optional note. — İsteğe bağlı açıklama notu.
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Transaction extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     * Toplu atama yapılmasına izin verilen sütunlar.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'piggy_bank_id', 'user_id', 'action_type', 'amount', 'description', 'donation_category', 'category_details',
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
            'amount' => 'float',
            'category_details' => 'array',
        ];
    }

    /**
     * Get the piggy bank associated with this transaction.
     * Bu işlemle ilişkilendirilen kumbarayı getirir.
     *
     * @return BelongsTo<PiggyBank, Transaction>
     */
    public function piggyBank(): BelongsTo
    {
        return $this->belongsTo(PiggyBank::class);
    }

    /**
     * Get the user (field agent or admin) who performed this transaction.
     * Bu işlemi gerçekleştiren kullanıcıyı (saha personeli veya yönetici) getirir.
     *
     * @return BelongsTo<User, Transaction>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
