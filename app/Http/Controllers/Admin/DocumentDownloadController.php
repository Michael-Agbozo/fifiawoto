<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryApplicationDocument;
use App\Models\BeneficiaryDocument;
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

        return $this->stream($request, $document->disk, $document->path, $document->original_name, $document->mime_type);
    }

    public function applicationDocument(Request $request, BeneficiaryApplication $application, BeneficiaryApplicationDocument $document): StreamedResponse
    {
        abort_unless($request->user()?->canDo('beneficiary_applications', 'view'), 403);

        return $this->stream($request, $document->disk, $document->path, $document->original_name, $document->mime_type);
    }

    protected function stream(Request $request, ?string $disk, ?string $path, ?string $name, ?string $mimeType): StreamedResponse
    {
        $storage = Storage::disk($disk ?: 'beneficiaries');

        abort_unless(filled($path) && $storage->exists($path), 404);

        $headers = [
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        $inline = ! $request->boolean('download') && in_array($mimeType, self::INLINE_MIME_TYPES, true);

        return $storage->response($path, $name, $headers, $inline ? 'inline' : 'attachment');
    }
}
