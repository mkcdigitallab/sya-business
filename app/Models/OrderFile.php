<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFile extends Model
{
    public const ARTWORK = 'artwork';

    public const PROOF = 'proof';

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const CHANGES = 'changes_requested';

    protected $fillable = ['order_id', 'kind', 'path', 'original_name', 'mime', 'size', 'status', 'client_comment'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Seules les images courantes s'affichent dans le navigateur ; tout le reste se télécharge. */
    public function isInlineImage(): bool
    {
        return in_array(strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }
}
