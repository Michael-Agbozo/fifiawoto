<?php

namespace App\Models;

use Database\Factories\BeneficiaryApplicationDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryApplicationDocument extends Model
{
    /** @use HasFactory<BeneficiaryApplicationDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'beneficiary_application_id', 'disk', 'path', 'original_name',
        'mime_type', 'size_bytes', 'description', 'uploaded_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }

    /** @return BelongsTo<BeneficiaryApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryApplication::class, 'beneficiary_application_id');
    }
}
