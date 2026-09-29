<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionTier;
use App\Support\MembershipPolicies;

class WebsitePolicyController extends Controller
{
    public function show(string $policy = 'about-us')
    {
        $pages = MembershipPolicies::pages();
        abort_unless(isset($pages[$policy]), 404);

        return view('policies.show', [
            'page' => $pages[$policy], 'policy' => $policy, 'pages' => $pages,
            'tiers' => SubscriptionTier::active()->ordered()->get(),
        ]);
    }

    public function services()
    {
        return view('policies.services', ['tiers' => SubscriptionTier::active()->ordered()->get()]);
    }

    public function contact()
    {
        return view('policies.contact');
    }
}
