<?php

namespace App\Models;

use App\Support\Families;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    public const MODES = ['unit', 'area', 'pack', 'quote'];

    protected $fillable = [
        'slug', 'name', 'family', 'pricing_mode', 'unit_label', 'price', 'description', 'image_url', 'image_path',
        'min_quantity', 'lead_time_days', 'is_featured', 'is_active', 'sort_order', 'options',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'min_quantity' => 'integer',
            'lead_time_days' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'options' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCatalog(Builder $query): Builder
    {
        return $query->active()->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name');
    }

    public function imageSrc(): ?string
    {
        if ($this->image_path) {
            return Storage::disk('public')->url($this->image_path);
        }

        return $this->image_url ?: null;
    }

    /** Ce que la page Vue a besoin de savoir d'un produit (jamais d'info interne). */
    public function toStorefront(): array
    {
        $o = $this->options ?? [];

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'family' => $this->family,
            'familyLabel' => Families::label($this->family),
            'mode' => $this->pricing_mode,
            'unitLabel' => $this->unit_label,
            'price' => $this->price,
            'description' => $this->description,
            'image' => $this->imageSrc(),
            'featured' => $this->is_featured,
            'minQuantity' => $this->min_quantity,
            'leadTimeDays' => $this->lead_time_days,
            'options' => [
                'sizes' => $o['sizes'] ?? [],
                'sizeLabel' => $o['size_label'] ?? 'Taille',
                'extras' => $o['extras'] ?? [],
                'choices' => $o['choices'] ?? [],
                'roster' => $o['roster'] ?? null,
                'dimensions' => $o['dimensions'] ?? null,
                'packSize' => $o['pack_size'] ?? null,
                'needsArtwork' => (bool) ($o['needs_artwork'] ?? false),
                'noteHint' => $o['note_hint'] ?? null,
            ],
        ];
    }
}
