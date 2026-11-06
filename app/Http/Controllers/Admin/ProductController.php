<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Families;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/** Catalogue : prix, visibilité, photo, tailles. Les options avancées (extras, liste de joueurs) se règlent dans le seeder. */
class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Products/Index', [
            'products' => Product::orderBy('family')->orderBy('sort_order')->get()->map(fn (Product $p) => [
                'slug' => $p->slug,
                'name' => $p->name,
                'family' => Families::label($p->family),
                'mode' => $p->pricing_mode,
                'unitLabel' => $p->unit_label,
                'price' => $p->price,
                'isActive' => $p->is_active,
                'isFeatured' => $p->is_featured,
                'image' => $p->imageSrc(),
            ])->values(),
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Edit', [
            'product' => [
                'slug' => $product->slug,
                'name' => $product->name,
                'family' => Families::label($product->family),
                'mode' => $product->pricing_mode,
                'unitLabel' => $product->unit_label,
                'price' => $product->price,
                'description' => $product->description,
                'minQuantity' => $product->min_quantity,
                'leadTimeDays' => $product->lead_time_days,
                'isActive' => $product->is_active,
                'isFeatured' => $product->is_featured,
                'sortOrder' => $product->sort_order,
                'sizes' => implode(', ', $product->options['sizes'] ?? []),
                'image' => $product->imageSrc(),
                'hasUploadedImage' => (bool) $product->image_path,
            ],
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'unit_label' => ['required', 'string', 'max:30'],
            'price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'min_quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'sizes' => ['nullable', 'string', 'max:300'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ], ['image.max' => 'La photo est trop lourde (5 Mo maximum).', 'image.mimes' => 'Utilisez une image JPG, PNG ou WebP.']);

        $options = $product->options ?? [];
        if (array_key_exists('sizes', $data)) {
            $sizes = collect(explode(',', (string) $data['sizes']))->map(fn ($s) => trim($s))->filter()->unique()->values()->all();
            $sizes ? $options['sizes'] = $sizes : $options = array_diff_key($options, ['sizes' => 1]);
        }

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            $imagePath && Storage::disk('public')->delete($imagePath);
            $imagePath = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image') && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        $product->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'unit_label' => $data['unit_label'],
            'price' => $data['price'] ?? null,
            'min_quantity' => $data['min_quantity'],
            'lead_time_days' => $data['lead_time_days'] ?? null,
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
            'is_featured' => $data['is_featured'],
            'options' => $options,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('admin.products.index')->with('success', "« {$product->name} » est enregistré.");
    }
}
