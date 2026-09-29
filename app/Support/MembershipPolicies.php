<?php

namespace App\Support;

class MembershipPolicies
{
    public const ACKNOWLEDGEMENT = 'I have read and agree to the Terms & Conditions, Privacy Policy, Delivery Policy, Refund/Return Policy, and Cancellation Policy.';

    public const DELIVERY_ACKNOWLEDGEMENT = 'I confirm that I can access the membership service purchased in this order. This acknowledgement does not waive my rights under the published policies.';

    public static function pages(): array
    {
        return json_decode(file_get_contents(resource_path('content/membership-policies.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function checkoutPages(): array
    {
        return array_diff_key(self::pages(), ['about-us' => true]);
    }

    public static function version(): string
    {
        return hash('sha256', json_encode([self::checkoutPages(), config('business'), self::ACKNOWLEDGEMENT]));
    }
}
