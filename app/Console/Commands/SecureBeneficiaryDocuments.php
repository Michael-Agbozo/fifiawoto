<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\BeneficiaryApplicationDocument;
use App\Models\BeneficiaryDocument;
use App\Support\SecureDocumentStore;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Safe to run on every deploy: with nothing left to do it only runs a few queries,
 * and a run that fails or crashes part-way can be repeated.
 */
class SecureBeneficiaryDocuments extends Command
{
    protected $signature = 'beneficiaries:secure-documents {--dry-run : List what would move without touching anything}';

    protected $description = 'Move beneficiary + application documents and record photos onto the private beneficiaries disk, then encrypt any documents not yet encrypted';

    /** Seconds before a crashed run's lock expires. */
    private const LOCK_SECONDS = 3600;

    private const DOCUMENT_MODELS = [BeneficiaryDocument::class, BeneficiaryApplicationDocument::class];

    private int $moved = 0;

    private int $encrypted = 0;

    private int $leftovers = 0;

    private int $missing = 0;

    private int $failed = 0;

    private bool $foundWork = false;

    public function handle(SecureDocumentStore $store): int
    {
        $this->moved = $this->encrypted = $this->leftovers = $this->missing = $this->failed = 0;
        $this->foundWork = false;

        // Two runs encrypting the same file at once could corrupt it.
        $lock = $this->option('dry-run') ? null : Cache::lock('beneficiaries:secure-documents', self::LOCK_SECONDS);

        if ($lock && ! $lock->get()) {
            $this->warn('Another run is in progress, so this one did nothing.');

            return self::SUCCESS;
        }

        try {
            $this->moveDocuments();
            $this->movePhotos();
            $this->removeLeftoverPublicCopies();
            $this->encryptDocuments($store);
        } finally {
            $lock?->release();
        }

        $this->info(match (true) {
            (bool) $this->option('dry-run') => 'Dry run complete.',
            ! $this->foundWork => 'Nothing to do.',
            default => "Moved {$this->moved} file(s); encrypted {$this->encrypted}; removed {$this->leftovers} leftover public copies; {$this->missing} missing; {$this->failed} failed.",
        });

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function moveDocuments(): void
    {
        foreach (self::DOCUMENT_MODELS as $modelClass) {
            $modelClass::query()
                ->where(fn ($query) => $query->where('disk', '!=', 'beneficiaries')->orWhereNull('disk'))
                ->whereNotNull('path')
                ->eachById(function (Model $document): void {
                    $this->moveToPrivateDisk(
                        "{$document->getTable()}#{$document->getKey()}",
                        Storage::disk($document->disk ?: 'public'),
                        $document->path,
                        preg_replace('#^beneficiaries/#', '', $document->path),
                        fn (string $target) => $document->forceFill(['disk' => 'beneficiaries', 'path' => $target])->save(),
                    );
                });
        }
    }

    /** Archived records included: their photos are just as exposed on the public disk. */
    private function movePhotos(): void
    {
        Beneficiary::withTrashed()
            ->whereNull('photo_disk')
            ->whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->eachById(function (Beneficiary $beneficiary): void {
                $sourcePath = preg_replace('#^storage/#', '', ltrim($beneficiary->photo_path, '/'));

                // Remote URLs and bundled images aren't files on the public disk, so there's nothing to move.
                if (preg_match('#^https?://#i', $sourcePath) || str_starts_with($sourcePath, 'images/')) {
                    return;
                }

                $this->moveToPrivateDisk(
                    "beneficiaries#{$beneficiary->getKey()} photo",
                    Storage::disk('public'),
                    $sourcePath,
                    'photos/'.preg_replace('#^beneficiaries/#', '', $sourcePath),
                    fn (string $target) => $beneficiary->forceFill(['photo_disk' => Beneficiary::PRIVATE_PHOTO_DISK, 'photo_path' => $target])->save(),
                );
            });
    }

    /**
     * Copy, verify, delete the original, then repoint the row. The row moves last so that a
     * failed delete leaves it pointing at the public copy, and the next run tries again. If
     * a crash stops it after the delete, the next run finds the private copy and finishes.
     *
     * @param  Closure(string): mixed  $repoint
     */
    private function moveToPrivateDisk(string $label, Filesystem $source, string $sourcePath, string $target, Closure $repoint): void
    {
        $this->foundWork = true;

        $this->attempt($label, function () use ($label, $source, $sourcePath, $target, $repoint): void {
            $private = Storage::disk(Beneficiary::PRIVATE_PHOTO_DISK);

            if (! $source->exists($sourcePath)) {
                if (! $private->exists($target)) {
                    $this->missing++;
                    $this->warn("Missing file for {$label}: {$sourcePath}");

                    return;
                }

                $this->line("{$label}: already copied to beneficiaries:{$target}, updating the record");

                if (! $this->option('dry-run')) {
                    $repoint($target);
                    $this->moved++;
                }

                return;
            }

            $this->line("{$label}: {$sourcePath} → beneficiaries:{$target}");

            if ($this->option('dry-run')) {
                return;
            }

            $stream = $source->readStream($sourcePath);
            $copied = $stream && $private->writeStream($target, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            // The disks don't throw, so verify the copy before touching the row or the original.
            if (! $copied || ! $this->hasSameContents($private, $target, $source, $sourcePath)) {
                $this->failed++;
                $this->error("Could not copy {$label} — original left in place.");

                return;
            }

            if (! $source->delete($sourcePath) || $source->exists($sourcePath)) {
                $this->failed++;
                $this->error("Could not delete the public copy of {$label}: {$sourcePath}. It is still exposed. Run this command again to retry.");

                return;
            }

            $repoint($target);
            $this->moved++;
        });
    }

    /**
     * Earlier versions moved the row and ignored a failed delete, leaving public copies
     * no row points at. They're only removed once a row and its private copy are confirmed.
     */
    private function removeLeftoverPublicCopies(): void
    {
        $public = Storage::disk('public');

        foreach ($public->allFiles('beneficiaries') as $path) {
            $target = preg_replace('#^beneficiaries/#', '', $path);
            $hasMovedRow = collect(self::DOCUMENT_MODELS)->contains(
                fn (string $modelClass): bool => $modelClass::query()->where('disk', 'beneficiaries')->where('path', $target)->exists(),
            );

            if (! $hasMovedRow || ! Storage::disk('beneficiaries')->exists($target)) {
                continue;
            }

            $this->foundWork = true;
            $this->line("Remove leftover public copy: {$path}");

            if ($this->option('dry-run')) {
                continue;
            }

            if ($public->delete($path)) {
                $this->leftovers++;
            } else {
                $this->failed++;
                $this->error("Could not delete leftover public copy: {$path}. It is still exposed. Run this command again to retry.");
            }
        }
    }

    private function encryptDocuments(SecureDocumentStore $store): void
    {
        foreach (self::DOCUMENT_MODELS as $modelClass) {
            $modelClass::query()
                ->where('disk', 'beneficiaries')
                ->whereNull('encrypted_at')
                ->whereNotNull('path')
                ->eachById(function (Model $document) use ($store): void {
                    $label = "{$document->getTable()}#{$document->getKey()}";
                    $this->foundWork = true;
                    $this->line("Encrypt {$label}");

                    if ($this->option('dry-run')) {
                        return;
                    }

                    $this->attempt($label, function () use ($document, $label, $store): void {
                        if (! Storage::disk('beneficiaries')->exists($document->path)) {
                            $this->missing++;

                            return;
                        }

                        if (! $store->encryptInPlace($document->path)) {
                            $this->failed++;
                            $this->error("Could not encrypt {$label} — original left in place.");

                            return;
                        }

                        $document->forceFill(['encrypted_at' => now()])->save();
                        $this->encrypted++;
                    });
                });
        }
    }

    /** One bad file shouldn't stop the rest. It's counted as failed and retried on the next run. */
    private function attempt(string $label, Closure $work): void
    {
        try {
            $work();
        } catch (Throwable $exception) {
            report($exception);
            $this->failed++;
            $this->error("Could not finish {$label}. Run this command again to retry.");
        }
    }

    private function hasSameContents(Filesystem $copy, string $copyPath, Filesystem $original, string $originalPath): bool
    {
        $copyHash = $this->hash($copy, $copyPath);

        return $copyHash !== null && $copyHash === $this->hash($original, $originalPath);
    }

    private function hash(Filesystem $disk, string $path): ?string
    {
        $stream = $disk->readStream($path);

        if (! $stream) {
            return null;
        }

        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);

        return hash_final($hash);
    }
}
