<?php

namespace App\Support;

use Generator;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

/**
 * Scans and encrypts beneficiary + application documents (IDs, medical records…).
 *
 * Files are encrypted with APP_KEY in 1 MB chunks, so large files don't have to fit in
 * memory. Losing APP_KEY makes these files unreadable: back it up, and when rotating it
 * keep the old key in APP_PREVIOUS_KEYS. Each chunk records its position and whether it
 * is the last one, so a file can't be reordered or cut short without being detected.
 */
class SecureDocumentStore
{
    public const SCAN_CLEAN = 'clean';

    public const SCAN_NOT_SCANNED = 'not_scanned';

    private const DISK = 'beneficiaries';

    private const CHUNK_BYTES = 1048576;

    public function __construct(private Encrypter $encrypter) {}

    /**
     * Scan uploads before anything is saved, stopping with a form error on the
     * first file that is infected or can't be checked.
     *
     * @param  iterable<int|string, UploadedFile>  $uploads
     * @return array<int|string, string> The scan status for each upload.
     */
    public function scanAll(iterable $uploads, string $field): array
    {
        $results = [];

        foreach ($uploads as $index => $upload) {
            $results[$index] = $this->scan($upload, $field);
        }

        return $results;
    }

    /** Encrypt the upload onto the private disk and return its stored path. */
    public function store(UploadedFile $upload, string $directory): string
    {
        $path = trim($directory, '/').'/'.Str::random(40).'.enc';
        $source = fopen($upload->getRealPath(), 'rb');

        try {
            if (! $this->writeEncrypted($source, $path)) {
                throw new RuntimeException("Could not store encrypted document at {$path}.");
            }
        } finally {
            fclose($source);
        }

        return $path;
    }

    /**
     * Delete Livewire's plaintext temporary copies and the original names kept beside
     * them. Call once the encrypted files and
     * their rows are safely saved, so a failed save can still be retried from the upload.
     *
     * @param  iterable<int|string, UploadedFile>  $uploads
     */
    public function discardTemporaryUploads(iterable $uploads): void
    {
        foreach ($uploads as $upload) {
            if (! $upload instanceof TemporaryUploadedFile) {
                continue;
            }

            $metadataPath = FileUploadConfiguration::path($upload->getFilename().'.json', false);

            if (! $upload->delete() || ! FileUploadConfiguration::storage()->delete($metadataPath)) {
                report(new RuntimeException('Could not delete a temporary upload after storing it.'));
            }
        }
    }

    /**
     * Replace an existing plain file with its encrypted form, checking the result first.
     * A file that is already encrypted is left alone, as after a crash between replacing
     * the file and recording that it was encrypted.
     */
    public function encryptInPlace(string $path): bool
    {
        if ($this->isEncrypted($path)) {
            return true;
        }

        $disk = Storage::disk(self::DISK);
        $temporaryPath = $path.'.encrypting';
        $source = $disk->readStream($path);

        if (! $source) {
            return false;
        }

        try {
            $written = $this->writeEncrypted($source, $temporaryPath);
        } finally {
            fclose($source);
        }

        try {
            return $written
                && $this->decryptedHash($temporaryPath) === $this->plainHash($path)
                && $disk->move($temporaryPath, $path);
        } finally {
            $disk->delete($temporaryPath);
        }
    }

    /** Whether the file on the private disk decrypts completely with the app's keys. */
    public function isEncrypted(string $path): bool
    {
        $stream = Storage::disk(self::DISK)->readStream($path);

        if (! $stream) {
            return false;
        }

        try {
            // A plain file's first bytes rarely make a sensible chunk length, which saves reading all of it.
            $header = (string) fread($stream, 4);

            if (strlen($header) < 4 || unpack('N', $header)[1] > self::CHUNK_BYTES * 3) {
                return false;
            }

            rewind($stream);

            foreach ($this->decrypt($stream) as $chunk) {
                // Every chunk has to decrypt and be in order.
            }

            return true;
        } catch (Throwable) {
            return false;
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  resource  $stream
     * @return Generator<int, string>
     */
    public function decrypt($stream): Generator
    {
        $index = 0;
        $sawLast = false;

        while (($header = fread($stream, 4)) !== false && $header !== '') {
            $length = unpack('N', $header)[1];
            $payload = '';

            while (strlen($payload) < $length && ! feof($stream)) {
                $payload .= fread($stream, $length - strlen($payload));
            }

            $plain = $this->encrypter->decryptString($payload);
            ['index' => $chunkIndex, 'last' => $last] = unpack('Nindex/Clast', $plain);

            if ($chunkIndex !== $index || $sawLast) {
                throw new RuntimeException('Encrypted document is corrupt: chunks are out of order.');
            }

            $sawLast = $last === 1;
            $index++;

            yield substr($plain, 5);
        }

        if (! $sawLast) {
            throw new RuntimeException('Encrypted document is corrupt: the file is incomplete.');
        }
    }

    private function decryptedHash(string $path): string
    {
        $stream = Storage::disk(self::DISK)->readStream($path);
        $hash = hash_init('sha256');

        try {
            foreach ($this->decrypt($stream) as $chunk) {
                hash_update($hash, $chunk);
            }
        } finally {
            fclose($stream);
        }

        return hash_final($hash);
    }

    private function plainHash(string $path): string
    {
        $stream = Storage::disk(self::DISK)->readStream($path);
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);

        return hash_final($hash);
    }

    /** @param resource $source */
    private function writeEncrypted($source, string $path): bool
    {
        $temporary = tmpfile();
        $index = 0;
        $chunk = (string) fread($source, self::CHUNK_BYTES);

        while (true) {
            $next = (string) fread($source, self::CHUNK_BYTES);
            $isLast = $next === '' && feof($source);
            $payload = $this->encrypter->encryptString(pack('NC', $index, $isLast ? 1 : 0).$chunk);
            fwrite($temporary, pack('N', strlen($payload)).$payload);

            if ($isLast) {
                break;
            }

            $chunk = $next;
            $index++;
        }

        rewind($temporary);
        $written = Storage::disk(self::DISK)->writeStream($path, $temporary);
        fclose($temporary);

        return $written;
    }

    private function scan(UploadedFile $upload, string $field): string
    {
        if (config('uploads.scanner') !== 'clamav') {
            return self::SCAN_NOT_SCANNED;
        }

        return $this->interpret($this->askClamd($upload->getRealPath()), $upload, $field);
    }

    protected function interpret(?string $reply, UploadedFile $upload, string $field): string
    {
        $name = $upload->getClientOriginalName();

        if ($reply !== null && str_ends_with($reply, 'FOUND')) {
            throw ValidationException::withMessages([$field => "{$name} looks unsafe and wasn't saved."]);
        }

        if ($reply === null || ! str_ends_with($reply, 'OK')) {
            report(new RuntimeException('Virus scanner unavailable or returned an unexpected reply.'));

            throw ValidationException::withMessages([$field => "We couldn't check {$name} for viruses just now, so it wasn't saved. Please try again shortly."]);
        }

        return self::SCAN_CLEAN;
    }

    /** Send a file to clamd over its INSTREAM protocol. Returns its reply, or null if it can't be reached. */
    private function askClamd(string $filePath): ?string
    {
        $socket = @stream_socket_client((string) config('uploads.clamav_socket'), $errorCode, $errorMessage, 5);

        if (! $socket) {
            return null;
        }

        stream_set_timeout($socket, 60);
        fwrite($socket, "zINSTREAM\0");

        $file = fopen($filePath, 'rb');
        while (! feof($file)) {
            $chunk = (string) fread($file, 65536);
            if ($chunk !== '') {
                fwrite($socket, pack('N', strlen($chunk)).$chunk);
            }
        }
        fclose($file);
        fwrite($socket, pack('N', 0));

        $reply = stream_get_contents($socket);
        fclose($socket);

        return $reply === false || $reply === '' ? null : trim($reply, "\0\r\n ");
    }
}
