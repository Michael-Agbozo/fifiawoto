<?php

namespace App\Models;

use Database\Factories\BeneficiaryDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryDocument extends Model
{
    /** @use HasFactory<BeneficiaryDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'beneficiary_id', 'folder_id', 'disk', 'path', 'original_name',
        'mime_type', 'size_bytes', 'description', 'uploaded_by',
        'scan_status', 'scan_checked_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scan_checked_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    /** @return BelongsTo<Beneficiary, $this> */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    /** @return BelongsTo<BeneficiaryFolder, $this> */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryFolder::class, 'folder_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
