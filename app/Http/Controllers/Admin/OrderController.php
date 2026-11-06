<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFile;
use App\Services\OrderFiles;
use App\Services\OrderWorkflow;
use App\Support\OrderPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as FileResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderWorkflow $workflow) {}

    public function index(Request $request): Response
    {
        $status = OrderStatus::tryFrom((string) $request->query('statut'));
        $search = trim((string) $request->query('q'));

        $orders = Order::with(['items', 'payments'])
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders->through(fn (Order $o) => [
                'reference' => $o->reference,
                'customer' => $o->customer_name,
                'phone' => $o->phone,
                'status' => $o->status->value,
                'statusLabel' => $o->status->label(),
                'total' => $o->total(),
                'pending' => $o->has_pending_price && $o->confirmed_total === null,
                'balance' => $o->balance(),
                'fulfillment' => $o->fulfillment,
                'items' => $o->items->map(fn ($i) => $i->quantity.' × '.$i->product_name)->implode(', '),
                'date' => $o->created_at->toIso8601String(),
            ]),
            'filters' => ['statut' => $status?->value, 'q' => $search],
            'statuses' => collect(OrderStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])->values(),
        ]);
    }

    public function show(Order $order): Response
    {
        return Inertia::render('Admin/Orders/Show', [
            'order' => OrderPresenter::make($order, forAdmin: true),
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()])->values(),
        ]);
    }

    public function status(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(OrderStatus::class)], 'force' => ['nullable', 'boolean']]);

        return $this->run(
            fn () => $this->workflow->change($order, OrderStatus::from($data['status']), $request->user(), (bool) ($data['force'] ?? false)),
            'Statut mis à jour.',
        );
    }

    public function quote(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'confirmed_total' => ['required', 'integer', 'min:1', 'max:100000000'],
            'quote_message' => ['nullable', 'string', 'max:1000'],
        ], ['confirmed_total.required' => 'Saisissez le montant du devis.', 'confirmed_total.integer' => 'Saisissez un montant en chiffres.']);

        return $this->run(
            fn () => $this->workflow->sendQuote($order, (int) $data['confirmed_total'], $data['quote_message'] ?? null, $request->user()),
            'Devis envoyé au client.',
        );
    }

    public function proof(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['proof' => ['required', 'array', 'max:5'], 'proof.*' => ['file', 'max:'.config('siya.upload_max_kb'), 'extensions:jpg,jpeg,png,webp,pdf']], [
            'proof.required' => 'Choisissez au moins un fichier.',
            'proof.*.extensions' => 'La maquette doit être une image (JPG, PNG, WebP) ou un PDF.',
        ]);

        return $this->run(fn () => $this->workflow->addProof($order, $request->file('proof'), $request->user()), 'Maquette envoyée au client.');
    }

    public function payment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:200'],
        ], ['amount.required' => 'Saisissez le montant reçu.', 'amount.integer' => 'Saisissez un montant en chiffres.']);

        return $this->run(
            fn () => $this->workflow->recordPayment($order, (int) $data['amount'], PaymentMethod::from($data['method']), $data['note'] ?? null, $request->user()),
            'Paiement enregistré.',
        );
    }

    public function notes(Request $request, Order $order): RedirectResponse
    {
        $order->update($request->validate(['internal_notes' => ['nullable', 'string', 'max:2000']]));

        return back()->with('success', 'Notes enregistrées.');
    }

    public function file(OrderFiles $files, Order $order, OrderFile $file): FileResponse
    {
        abort_unless($file->order_id === $order->id, 404);

        return $files->response($file);
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
