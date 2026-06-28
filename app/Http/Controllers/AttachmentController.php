<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Stream an attachment to an authenticated user. Files live on a private
     * disk and are only reachable here, so we control the response headers:
     * images render inline (for thumbnails); everything else is forced to
     * download as octet-stream. nosniff stops the browser second-guessing the
     * type — together this closes the stored-XSS-via-same-origin path.
     */
    public function __invoke(Attachment $attachment): StreamedResponse
    {
        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        $isImage = $attachment->isImage();

        return $disk->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $isImage ? $attachment->mime_type : 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
            $isImage ? 'inline' : 'attachment',
        );
    }
}
