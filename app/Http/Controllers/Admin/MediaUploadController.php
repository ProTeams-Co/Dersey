<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\MediaUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Backs both admin/media.js (FilePond) and admin/editor.js (CKEditor 5's
 * upload adapter) - a single temporary-upload endpoint, not Cloudflare R2
 * (CLAUDE.md's real-conversion-pipeline rules apply once a resource
 * actually attaches an upload to a model, which is 3.1+, out of this
 * batch's "infrastructure only" scope). Files land on the `local` disk
 * under tmp-uploads/, exactly the "temporary files during processing" use
 * case CLAUDE.md §5 already reserves that disk for.
 *
 * Gated by admin.auth/admin.active (see routes/admin.php) rather than a
 * Policy - there is no single Eloquent model this action authorizes
 * against (it's a generic utility, not a resource), so "every admin who
 * can reach the panel at all may use it" is the correct scope, not a
 * missing Policy.
 */
class MediaUploadController
{
    public function store(MediaUploadRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $id = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs('tmp-uploads', $id, 'local');

        return response()->json([
            'id' => $id,
            'url' => Storage::disk('local')->url($path),
        ]);
    }

    public function destroy(string $file): JsonResponse
    {
        Storage::disk('local')->delete('tmp-uploads/'.basename($file));

        return response()->json([], 204);
    }
}
