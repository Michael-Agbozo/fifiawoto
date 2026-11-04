<?php

namespace App\Support;

use Generator;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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

    /** Replace an existing plain file with its encrypted form, checking the result first. */
    public function encryptInPlace(string $path): bool
    {
        $disk = Storage::disk(self::DISK);
        $temporaryPath = $path.'.encrypting';
        $source = $disk->readStream($path);

        if (! $source || ! $this->writeEncrypted($source, $temporaryPath)) {
            return false;
        }

        fclose($source);

        $encrypted = $disk->readStream($temporaryPath);
        $original = hash_init('sha256');
        foreach ($this->decrypt($encrypted) as $chunk) {
            hash_update($original, $chunk);
        }
        fclose($encrypted);

        if (hash_final($original) !== hash('sha256', (string) $disk->get($path))) {
            $disk->delete($temporaryPath);

            return false;
        }

        return $disk->move($temporaryPath, $path);
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
