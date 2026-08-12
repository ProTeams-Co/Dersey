<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `image` + `mimes:` validate the file's actual content (PHP's
     * fileinfo/finfo, via Symfony's MimeTypeGuesser under the hood), not
     * the client-supplied extension or Content-Type header - a
     * "photo.jpg" that is actually a PHP script fails this, per CLAUDE.md
     * §8's "تحقق من النوع الحقيقي (MIME) مش الامتداد".
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            $this->fileFieldName() => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * This one temporary-upload endpoint backs two different callers that
     * each post the file under a different field name: CKEditor 5's upload
     * adapter always names it "file" (editor.js), while FilePond posts
     * under whatever `name` the <input> it enhanced already had -
     * x-admin.media-picker's own `name` prop ("logo", "icon", "image", ...),
     * not "file" - confirmed by reading FilePond's source (it reads the
     * element's own `name` attribute into its upload options). Hardcoding
     * "file" here meant every FilePond upload 422'd on a field that was
     * never actually sent.
     */
    public function fileFieldName(): string
    {
        return array_key_first($this->allFiles()) ?? 'file';
    }
}
