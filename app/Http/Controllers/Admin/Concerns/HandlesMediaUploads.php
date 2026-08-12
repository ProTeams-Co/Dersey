<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * x-admin.media-picker uploads to a temporary location (admin/media.js ->
 * MediaUploadController, Batch 3.0) and the form submits back whatever
 * filename that temp upload was given - nothing before Batch 3.1 ever
 * moved it anywhere permanent, since no resource attached an upload to a
 * model field yet. promoteUpload() is that missing step: called from a
 * controller's beforeSave() (now by-reference) for each media field.
 *
 * The temp upload stays on `local` (tmp-uploads/), but the PROMOTED,
 * permanent file moves to the `public` disk, not `local` - not the `media`
 * disk (Cloudflare R2) either; R2_* credentials are empty in this dev
 * environment (same reasoning as Batch 3.0's CSV-export decision), and the
 * real image-conversion pipeline CLAUDE.md §5 requires (srcset, multiple
 * sizes, EXIF stripping, never serving the original) is a separate, larger
 * piece of work than three CRUD screens need to unblock saving a single
 * logo/icon field.
 *
 * `public` specifically (not `local`) because Laravel 11+'s "local" disk
 * defaults to storage/app/private and is private-by-default even with its
 * own built-in serve route (Illuminate\Filesystem\ServeFile requires a
 * signed URL unless the disk config says visibility=public) - verified
 * directly: a plain Storage::disk('local')->url() 403'd in the browser.
 * `local` is left exactly as-is for genuinely temp/private things (like
 * ExportAdminTableJob's CSV output, which must NOT become public) - only
 * this promoted-media path moves. Revisit once R2 is actually provisioned
 * and Batch 4-ish media conversions exist.
 */
trait HandlesMediaUploads
{
    protected function promoteUpload(?string $filename, string $directory): ?string
    {
        if (! $filename) {
            return null;
        }

        $tempPath = 'tmp-uploads/'.basename($filename);

        if (! Storage::disk('local')->exists($tempPath)) {
            // Not a fresh temp upload (unchanged on update, or already
            // permanent) - leave whatever was submitted as is.
            return $filename;
        }

        $permanentPath = $directory.'/'.basename($filename);

        // Storage::move() only works within a single disk - local (temp)
        // and public (permanent) are different disks, so this is a
        // read-write-delete instead.
        Storage::disk('public')->put($permanentPath, Storage::disk('local')->get($tempPath));
        Storage::disk('local')->delete($tempPath);

        return $permanentPath;
    }
}
