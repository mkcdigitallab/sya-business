<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumber::normalize($this->input('phone')),
            'customer_name' => trim((string) $this->input('customer_name')),
            'address' => trim((string) $this->input('address')),
            'city' => trim((string) $this->input('city')),
        ]);
    }

    public function rules(): array
    {
        return [
            'client_reference' => ['required', 'string', 'min:8', 'max:100'],
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', function (string $attribute, mixed $value, \Closure $fail) {
                if (! PhoneNumber::isValid($value)) {
                    $fail('Saisissez un numéro sénégalais valide, par exemple +221 77 123 45 67.');
                }
            }],
            'email' => ['nullable', 'email', 'max:190'],
            'fulfillment' => ['required', Rule::in(['pickup', 'delivery'])],
            'address' => ['nullable', 'required_if:fulfillment,delivery', 'string', 'min:5', 'max:200'],
            'city' => ['nullable', 'required_if:fulfillment,delivery', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_preference' => ['nullable', Rule::in(array_map(fn ($m) => $m->value, PaymentMethod::forCustomers()))],
            'files' => ['nullable', 'array', 'max:'.config('siya.upload_max_files')],
            'files.*' => ['file', 'max:'.config('siya.upload_max_kb'), 'extensions:'.implode(',', config('siya.upload_extensions'))],
            'accept_terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'address.required_if' => 'Indiquez l’adresse de livraison.',
            'city.required_if' => 'Indiquez le quartier ou la ville.',
            'accept_terms.accepted' => 'Acceptez les conditions pour envoyer votre demande.',
            'files.*.extensions' => 'Format de fichier non accepté.',
            'files.*.max' => 'Un fichier dépasse 10 Mo.',
        ];
    }
}
