<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SharedAttachmentController extends Controller
{
    /**
     * Stream a card attachment on a public client board. The share token gates
     * access, and we additionally prove the file hangs off an Issue belonging to
     * THIS project — so one board's link can never reach another's files.
     *
     * Same safe headers as the authed AttachmentController: images render inline,
     * everything else is forced to download, nosniff on both (stored-XSS guard).
     */
    public function __invoke(string $token, Attachment $attachment): StreamedResponse
    {
        $project = Project::where('share_token', $token)
            ->where('share_enabled', true)
            ->firstOrFail();

        abort_unless($attachment->attachable_type === Issue::class, 404);

        $belongs = Issue::whereKey($attachment->attachable_id)
            ->where('project_id', $project->id)
            ->exists();

        abort_unless($belongs, 404);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        // Images/audio/video are safe to serve inline with their real type (so
        // <img>/<audio>/<video> work); anything else is forced to download as
        // octet-stream. nosniff on both stops the browser reinterpreting a file
        // as active content.
        $mime = (string) $attachment->mime_type;
        $inline = str_starts_with($mime, 'image/')
            || str_starts_with($mime, 'audio/')
            || str_starts_with($mime, 'video/');

        return $disk->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $inline ? $mime : 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
            $inline ? 'inline' : 'attachment',
        );
    }
}
