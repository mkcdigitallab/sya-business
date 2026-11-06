<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fichiers de commande : visuels envoyés par le client (logo, maquette…) et maquettes de l'atelier.
 * Ils sont rangés sur le disque PRIVÉ (storage/app/private) : jamais accessibles par une URL publique,
 * uniquement par les routes qui vérifient le jeton de suivi ou la session administrateur.
 */
class OrderFiles
{
    private const INLINE_TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];

    /**
     * @param  array<UploadedFile>  $files
     * @return list<OrderFile>
     */
    public function store(Order $order, array $files, string $kind): array
    {
        $allowed = config('siya.upload_extensions');
        $stored = [];

        foreach (array_slice($files, 0, (int) config('siya.upload_max_files')) as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension());
            if (! in_array($ext, $allowed, true)) {
                throw new BusinessRuleException("Le format « .{$ext} » n’est pas accepté.", 'files');
            }
            if ($file->getSize() > config('siya.upload_max_kb') * 1024) {
                throw new BusinessRuleException('Un fichier dépasse '.(config('siya.upload_max_kb') / 1024).' Mo.', 'files');
            }

            $path = $file->storeAs("orders/{$order->reference}", Str::uuid().'.'.$ext, 'local');

            $stored[] = $order->files()->create([
                'kind' => $kind,
                'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 150),
                'mime' => $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'status' => $kind === OrderFile::PROOF ? OrderFile::PENDING : null,
            ]);
        }

        return $stored;
    }

    public function response(OrderFile $file): Response
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($file->path), 404);

        $ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
        $inline = isset(self::INLINE_TYPES[$ext]);

        return response()->file($disk->path($file->path), [
            'Content-Type' => self::INLINE_TYPES[$ext] ?? 'application/octet-stream',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.addslashes(Str::ascii($file->original_name)).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
