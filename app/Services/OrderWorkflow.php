<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Toutes les règles de changement d'état d'une commande, côté atelier ET côté client.
 * Les contrôleurs n'écrivent jamais `status` eux-mêmes : ils appellent cette classe, qui vérifie la règle
 * et laisse une trace dans l'historique (order_events).
 */
class OrderWorkflow
{
    public function __construct(private readonly OrderFiles $files) {}

    // ───────────────────────────── Atelier ─────────────────────────────

    public function change(Order $order, OrderStatus $to, User $actor, bool $force = false): Order
    {
        return DB::transaction(function () use ($order, $to, $actor, $force) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($to, $order->status->next(), true)) {
                throw new BusinessRuleException("Impossible de passer de « {$order->status->label()} » à « {$to->label()} ».");
            }
            if ($to === OrderStatus::Quoted) {
                throw new BusinessRuleException('Pour envoyer un devis, fixez le prix avec « Envoyer le devis ».');
            }
            if ($to === OrderStatus::Confirmed && $order->has_pending_price && $order->confirmed_total === null) {
                throw new BusinessRuleException('Cette commande contient un article sur devis : fixez d’abord le prix.');
            }
            if ($to === OrderStatus::Production && ! $force && $order->files()->where('kind', OrderFile::PROOF)->where('status', '!=', OrderFile::APPROVED)->exists()) {
                throw new BusinessRuleException('Une maquette n’est pas encore validée par le client. Cochez « accord reçu autrement » pour lancer quand même.');
            }

            $order->update(['status' => $to]);
            $this->log($order, 'atelier', $to->label().($force && $to === OrderStatus::Production ? ' (lancée sans validation de maquette)' : '').'.');

            return $order;
        });
    }

    /** Fixe le prix définitif et passe la commande en « Devis à valider ». */
    public function sendQuote(Order $order, int $total, ?string $message, User $actor): Order
    {
        if (! in_array($order->status, [OrderStatus::Received, OrderStatus::Quoted], true)) {
            throw new BusinessRuleException('Le devis ne peut plus être modifié à ce stade.');
        }
        if ($total < 1) {
            throw new BusinessRuleException('Saisissez un montant supérieur à 0.', 'confirmed_total');
        }

        $order->update(['confirmed_total' => $total, 'quote_message' => $message ?: null, 'status' => OrderStatus::Quoted]);
        $this->log($order, 'atelier', 'Devis envoyé : '.number_format($total, 0, ',', ' ').' FCFA.');

        return $order;
    }

    /** Dépose une maquette à faire valider. Passe la commande en « Maquette à valider ». */
    public function addProof(Order $order, array $files, User $actor): array
    {
        if (! in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Proofing], true)) {
            throw new BusinessRuleException('Confirmez la commande avant d’envoyer une maquette.', 'proof');
        }

        $stored = $this->files->store($order, $files, OrderFile::PROOF);
        if (! $stored) {
            throw new BusinessRuleException('Choisissez un fichier.', 'proof');
        }

        $order->update(['status' => OrderStatus::Proofing]);
        $this->log($order, 'atelier', 'Maquette envoyée pour validation.');

        return $stored;
    }

    public function recordPayment(Order $order, int $amount, PaymentMethod $method, ?string $note, User $actor): Payment
    {
        if ($amount < 1) {
            throw new BusinessRuleException('Saisissez un montant supérieur à 0.', 'amount');
        }
        if ($order->status === OrderStatus::Cancelled) {
            throw new BusinessRuleException('Cette commande est annulée.');
        }
        if ($amount > $order->balance()) {
            throw new BusinessRuleException('Ce montant dépasse le reste à payer ('.number_format($order->balance(), 0, ',', ' ').' FCFA).', 'amount');
        }

        $payment = $order->payments()->create([
            'amount' => $amount, 'method' => $method, 'note' => $note ?: null, 'paid_at' => now(), 'recorded_by' => $actor->id,
        ]);
        $this->log($order, 'atelier', 'Paiement reçu : '.number_format($amount, 0, ',', ' ').' FCFA ('.$method->label().').');

        return $payment;
    }

    // ───────────────────────────── Client ─────────────────────────────

    public function acceptQuote(Order $order): Order
    {
        if ($order->status !== OrderStatus::Quoted) {
            throw new BusinessRuleException('Aucun devis en attente de validation.');
        }
        $order->update(['status' => OrderStatus::Confirmed]);
        $this->log($order, 'client', 'Devis accepté.');

        return $order;
    }

    public function decideProof(Order $order, OrderFile $proof, bool $approve, ?string $comment): OrderFile
    {
        if ($proof->order_id !== $order->id || $proof->kind !== OrderFile::PROOF || $order->status !== OrderStatus::Proofing) {
            throw new BusinessRuleException('Cette maquette n’est plus à valider.');
        }
        if (! $approve && blank($comment)) {
            throw new BusinessRuleException('Dites-nous ce qu’il faut modifier.', 'comment');
        }

        $proof->update([
            'status' => $approve ? OrderFile::APPROVED : OrderFile::CHANGES,
            'client_comment' => $comment ?: null,
        ]);
        $this->log($order, 'client', $approve
            ? 'Maquette validée.'
            : 'Modification demandée : '.mb_substr((string) $comment, 0, 120));

        return $proof;
    }

    public function cancelByClient(Order $order): Order
    {
        if (! $order->status->clientCanCancel()) {
            throw new BusinessRuleException('Cette commande est déjà lancée : appelez-nous pour l’annuler.');
        }
        $order->update(['status' => OrderStatus::Cancelled]);
        $this->log($order, 'client', 'Commande annulée par le client.');

        return $order;
    }

    private function log(Order $order, string $actor, string $message, bool $visible = true): void
    {
        $order->events()->create(['actor' => $actor, 'message' => $message, 'visible_to_client' => $visible]);
    }
}
