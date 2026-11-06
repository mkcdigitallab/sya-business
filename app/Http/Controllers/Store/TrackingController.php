<?php

namespace App\Http\Controllers\Store;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFile;
use App\Services\OrderFiles;
use App\Services\OrderWorkflow;
use App\Support\OrderPresenter;
use App\Support\TrackingTokens;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as FileResponse;

/**
 * Suivi d'une commande sans compte : l'adresse contient un jeton HMAC (voir TrackingTokens).
 * Les actions du client (accepter le devis, valider une maquette, annuler) passent toutes par OrderWorkflow.
 */
class TrackingController extends Controller
{
    public function show(Request $request, string $reference): Response
    {
        $order = $this->authorized($request, $reference);

        return Inertia::render('Store/Track', [
            'order' => OrderPresenter::make($order),
            'justOrdered' => (bool) $request->session()->get('justOrdered'),
            'paymentNumber' => config('siya.payment_number'),
        ]);
    }

    public function acceptQuote(Request $request, OrderWorkflow $workflow, string $reference): RedirectResponse
    {
        return $this->run(fn () => $workflow->acceptQuote($this->authorized($request, $reference)), 'Devis accepté. Merci !');
    }

    public function decideProof(Request $request, OrderWorkflow $workflow, string $reference, OrderFile $file): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,changes'], 'comment' => ['nullable', 'string', 'max:500']]);
        $order = $this->authorized($request, $reference);

        return $this->run(
            fn () => $workflow->decideProof($order, $file, $data['decision'] === 'approve', $data['comment'] ?? null),
            $data['decision'] === 'approve' ? 'Maquette validée. Nous lançons la suite.' : 'Votre demande de modification est envoyée.',
        );
    }

    public function cancel(Request $request, OrderWorkflow $workflow, string $reference): RedirectResponse
    {
        return $this->run(fn () => $workflow->cancelByClient($this->authorized($request, $reference)), 'Votre commande est annulée.');
    }

    public function file(Request $request, OrderFiles $files, string $reference, OrderFile $file): FileResponse
    {
        $order = $this->authorized($request, $reference);
        abort_unless($file->order_id === $order->id, 404);

        return $files->response($file);
    }

    private function authorized(Request $request, string $reference): Order
    {
        $reference = strtoupper(trim($reference));
        $token = (string) ($request->query('token') ?? $request->input('token', ''));

        abort_unless(TrackingTokens::verify($reference, $token), 404);

        return Order::where('reference', $reference)->firstOrFail();
    }

    private function run(\Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (BusinessRuleException $e) {
            return back()->withErrors([$e->field => $e->getMessage()])->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
