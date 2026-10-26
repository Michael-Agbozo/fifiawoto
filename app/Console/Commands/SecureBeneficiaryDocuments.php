<?php

namespace App\Console\Commands;

use App\Models\BeneficiaryApplicationDocument;
use App\Models\BeneficiaryDocument;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SecureBeneficiaryDocuments extends Command
{
    protected $signature = 'beneficiaries:secure-documents {--dry-run : List what would move without touching anything}';

    protected $description = 'Move beneficiary + application documents off the public disk onto the private beneficiaries disk';

    public function handle(): int
    {
        $moved = 0;
        $missing = 0;
        $failed = 0;

        foreach ([BeneficiaryDocument::class, BeneficiaryApplicationDocument::class] as $modelClass) {
            $modelClass::query()
                ->where(fn ($query) => $query->where('disk', '!=', 'beneficiaries')->orWhereNull('disk'))
                ->whereNotNull('path')
                ->eachById(function (Model $document) use (&$moved, &$missing, &$failed): void {
                    $source = Storage::disk($document->disk ?: 'public');
                    $sourcePath = $document->path;

                    if (! $source->exists($sourcePath)) {
                        $missing++;
                        $this->warn("Missing file for {$document->getTable()}#{$document->getKey()}: {$sourcePath}");

                        return;
                    }

                    $target = preg_replace('#^beneficiaries/#', '', $sourcePath);
                    $this->line("{$document->getTable()}#{$document->getKey()}: {$sourcePath} → beneficiaries:{$target}");

                    if ($this->option('dry-run')) {
                        return;
                    }

                    $stream = $source->readStream($sourcePath);
                    $targetDisk = Storage::disk('beneficiaries');

                    // The disk doesn't throw, so verify the copy before touching the row or the original.
                    if (! $stream || ! $targetDisk->writeStream($target, $stream) || $targetDisk->size($target) !== $source->size($sourcePath)) {
                        $failed++;
                        $this->error("Could not copy {$document->getTable()}#{$document->getKey()} — original left in place.");

                        return;
                    }

                    $document->forceFill(['disk' => 'beneficiaries', 'path' => $target])->save();
                    $source->delete($sourcePath);
                    $moved++;
                });
        }

        $this->info($this->option('dry-run') ? 'Dry run complete.' : "Moved {$moved} document(s); {$missing} missing; {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
