<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The metadata of an uploaded file and, for now, its bytes. Read and write it through App\Services\DocumentStore.
 */
#[Fillable(['uuid', 'kind', 'original_name', 'mime_type', 'size', 'sha256', 'contents', 'uploaded_by'])]
#[Hidden(['contents'])]
class StoredDocument extends Model
{
    /**
     * Types a browser can show inside the page instead of downloading.
     *
     * @var list<string>
     */
    public const VIEWABLE = ['application/pdf', 'image/png', 'image/jpeg'];

    public function isViewable(): bool
    {
        return in_array($this->mime_type, self::VIEWABLE, true);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576 ? number_format($this->size / 1048576, 1).' MB' : number_format(max(1, $this->size / 1024)).' KB';
    }
}
