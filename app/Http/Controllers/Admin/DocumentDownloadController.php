<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryApplicationDocument;
use App\Models\BeneficiaryDocument;
use App\Support\SecureDocumentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams private beneficiary + application documents.
 *
 * Routes are `signed` (links expire) and still require an authenticated admin
 * with the matching `view` permission — a leaked link alone is not enough.
 */
class DocumentDownloadController extends Controller
{
    /** Minutes a generated document link stays valid. */
    public const LINK_TTL_MINUTES = 30;

    /** Types safe to render in the browser; everything else is forced to download. */
    protected const INLINE_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /** Photo types the record form accepts. */
    protected const PHOTO_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public static function beneficiaryDocumentUrl(BeneficiaryDocument $document, bool $download = false): string
    {
        return URL::temporarySignedRoute(
            'admin.beneficiaries.documents.show',
            now()->addMinutes(self::LINK_TTL_MINUTES),
            ['beneficiary' => $document->beneficiary_id, 'document' => $document->id, 'download' => (int) $download],
        );
    }

    public static function applicationDocumentUrl(BeneficiaryApplicationDocument $document, bool $download = false): string
    {
        return URL::temporarySignedRoute(
            'admin.beneficiary-applications.documents.show',
            now()->addMinutes(self::LINK_TTL_MINUTES),
            ['application' => $document->beneficiary_application_id, 'document' => $document->id, 'download' => (int) $download],
        );
    }

    public function beneficiaryDocument(Request $request, Beneficiary $beneficiary, BeneficiaryDocument $document): StreamedResponse
    {
        abort_unless($request->user()?->canDo('beneficiaries', 'view'), 403);

        return $this->stream($request, $document);
    }

    /**
     * Unsigned, so an avatar's URL stays stable across Livewire re-renders.
     * Still requires an authenticated admin with the `view` permission.
     */
    public function beneficiaryPhoto(Request $request, Beneficiary $beneficiary): StreamedResponse
    {
        abort_unless($request->user()?->canDo('beneficiaries', 'view'), 403);

        $storage = Storage::disk(Beneficiary::PRIVATE_PHOTO_DISK);

        abort_unless(
            $beneficiary->photo_disk === Beneficiary::PRIVATE_PHOTO_DISK
                && filled($beneficiary->photo_path)
                && $storage->exists($beneficiary->photo_path)
                && in_array($storage->mimeType($beneficiary->photo_path), self::PHOTO_MIME_TYPES, true),
            404,
        );

        return $storage->response($beneficiary->photo_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function applicationDocument(Request $request, BeneficiaryApplication $application, BeneficiaryApplicationDocument $document): StreamedResponse
    {
        abort_unless($request->user()?->canDo('beneficiary_applications', 'view'), 403);

        return $this->stream($request, $document);
    }

    protected function stream(Request $request, BeneficiaryDocument|BeneficiaryApplicationDocument $document): StreamedResponse
    {
        $storage = Storage::disk($document->disk ?: 'beneficiaries');

        abort_unless(filled($document->path) && $storage->exists($document->path), 404);

        $mimeType = $document->mime_type;
        $headers = [
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        $inline = ! $request->boolean('download') && in_array($mimeType, self::INLINE_MIME_TYPES, true);
        $disposition = $inline ? 'inline' : 'attachment';

        if (! $document->encrypted_at) {
            return $storage->response($document->path, $document->original_name, $headers, $disposition);
        }

        return response()->streamDownload(function () use ($document, $storage): void {
            $stream = $storage->readStream($document->path);

            foreach (app(SecureDocumentStore::class)->decrypt($stream) as $chunk) {
                echo $chunk;
            }

            fclose($stream);
        }, $document->original_name, $headers, $disposition);
    }
}
