<?php

namespace App\Http\Requests;

use App\Support\BanWealthFunds;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBanWealthOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isInvestor() === true;
    }

    public function rules(): array
    {
        return [
            'fund_slug' => ['required', Rule::in(BanWealthFunds::all()->pluck('slug')->all())],
            'horizon' => ['required', Rule::in(['short', 'medium', 'long'])],
            'shariah_preference' => ['required', Rule::in(['shariah', 'conventional', 'any'])],
            'amount' => ['required', 'numeric', 'min:1000', 'max:999999999999.99'],
            'monthly' => ['nullable', 'boolean'],
            'full_name' => ['required', 'string', 'max:255'],
            'nid_number' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'mobile' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'present_address' => ['required', 'string', 'max:1000'],
            'bank_name' => ['required', 'string', 'max:150'],
            'bank_branch' => ['required', 'string', 'max:150'],
            'bank_account_number' => ['required', 'string', 'max:100'],
            'routing_number' => ['required', 'string', 'max:50'],
            'tin' => ['nullable', 'string', 'max:50'],
            'bo_account' => ['nullable', 'string', 'max:100'],
            'source_of_funds' => ['required', 'string', 'max:255'],
            'investment_experience' => ['required', 'string', 'max:255'],
            'loss_response' => ['required', 'string', 'max:255'],
            'politically_exposed' => ['nullable', 'boolean'],
            'prospectus_consent' => ['accepted'],
            'submission_consent' => ['accepted'],
            'payment_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
