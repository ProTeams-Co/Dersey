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
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
