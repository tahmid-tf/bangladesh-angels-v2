<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\LandingPageStat;
use App\Models\LandingProgramCard;
use App\Models\Resource;
use App\Models\StartupService;
use App\Models\SubscriptionTier;
use App\Models\TeamAboutSection;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\BanFaqs;
use Illuminate\Http\Request;

class PrimaryController extends Controller
{
    // Landing Page
    public function __invoke()
    {
        $portfolioDeals = Deal::getHomepageDeals();

        $teamMembers = TeamMember::query()
            ->forSection(TeamMember::SECTION_MANAGEMENT)
            ->ordered()
            ->get();

        $faqs = collect(BanFaqs::all())->take(6);

        $landingPageStats = LandingPageStat::query()->ordered()->get();

        $landingProgramCards = LandingProgramCard::query()
            ->ordered()
            ->limit(LandingProgramCard::MAX_CARDS)
            ->get();

        $programCountWord = [1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five'][$landingProgramCards->count()] ?? null;
        $landingProgramHeading = $programCountWord
            ? $programCountWord.' focused '.($landingProgramCards->count() === 1 ? 'platform' : 'platforms').'. One stronger ecosystem.'
            : 'Focused platforms. One stronger ecosystem.';

        $landingResourceEvents = Resource::query()
            ->with('media')
            ->whereIn('type', ['event', 'webinar'])
            ->orderByDesc('show_on_landing')
            ->orderByRaw('CASE WHEN date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $showMembershipPlans = ! auth()->check() || ! auth()->user()->hasPaidMembership();
        $tiers = $showMembershipPlans
            ? SubscriptionTier::query()->active()->ordered()->get()
            : collect();
        $showFreeTierOption = auth()->check()
            && auth()->user()->hasVerifiedEmail()
            && auth()->user()->account_status === 'free';
        return view('welcome', compact(
            'portfolioDeals',
            'teamMembers',
            'faqs',
            'landingPageStats',
            'landingProgramCards',
            'landingProgramHeading',
            'landingResourceEvents',
            'tiers',
            'showFreeTierOption',
            'showMembershipPlans'
        ));
    }

    // Upgrade Page
    public function upgradePage()
    {
        if (auth()->check() && auth()->user()->hasPaidMembership()) {
            return redirect()->route('dashboard')->with('info', 'Your membership plan is already active.');
        }

        return view('upgrade');
    }

    // Startups hub: active deals (paywalled in the view), pitch, and services
    public function viewStartups()
    {
        $activeDeals = Deal::with('media')
            ->where('type', '!=', 'portfolio')
            ->where('status', 'active')
            ->get();

        $canViewActiveDeals = auth()->check() && auth()->user()->canViewPaywalledDeals();

        $startupServices = StartupService::query()->with('media')->ordered()->get();

        return view('startups', compact('activeDeals', 'canViewActiveDeals', 'startupServices'));
    }

    // Portfolio page: public showcase of BAN-backed companies
    public function viewPortfolio()
    {
        $portfolioDeals = Deal::with('media')
            ->inPortfolio()
            ->get();

        return view('portfolio', compact('portfolioDeals'));
    }

    // Subscription Plans Page
    public function viewPlans()
    {
        if (auth()->check() && auth()->user()->hasPaidMembership()) {
            return redirect()->route('dashboard')->with('info', 'Your membership plan is already active.');
        }

        $tiers = SubscriptionTier::query()->active()->ordered()->get();
        $showFreeTierOption = auth()->check()
            && auth()->user()->hasVerifiedEmail()
            && auth()->user()->account_status === 'free';

        return view('plans', compact('tiers', 'showFreeTierOption'));
    }

    // Checkout Page
    public function viewCheckout()
    {
        return view('payment.checkout');
    }

    // View Deals Page
    public function viewDeals()
    {
        // Show only active non-portfolio deals on /deals
        $deals = Deal::with('media')
            ->where('type', '!=', 'portfolio')
            ->where('status', 'active')
            ->get();

        return view('deals.index', compact('deals'));
    }

    // View Deals Page - Invest Category
    public function viewDeals_invest()
    {
        $deals = Deal::with('media')->where(function ($query) {
            $query->where('type', 'invest')
                ->where('status', '!=', 'closed')
                ->where('status', '!=', 'draft');
        })
            ->get();

        return view('deals.invest', compact('deals'));
    }

    // View Deals Page - Commit Category
    public function viewDeals_commit()
    {
        $deals = Deal::with('media')
            ->where(function ($query) {
                $query->where('type', 'commit')
                    ->where('status', '!=', 'closed')
                    ->where('status', '!=', 'draft');
            })
            ->get();

        return view('deals.commit', compact('deals'));
    }

    // View Deals Page - Review Category
    public function viewDeals_review()
    {
        $deals = Deal::with('media')
            ->where(function ($query) {
                $query->where('type', 'review')
                    ->where('status', '!=', 'closed')
                    ->where('status', '!=', 'draft');
            })
            ->get();

        return view('deals.review', compact('deals'));
    }

    // View FAQ Page
    public function viewFAQ()
    {
        return view('faq', ['faqs' => BanFaqs::all()]);
    }

    /** Public marketing page for the BAN Angel Academy programme. */
    public function viewAngelAcademy()
    {
        return view('angel-academy');
    }

    /** Public marketing page for Bangladesh Women Investors Network. */
    public function viewBwin()
    {
        return view('bwin');
    }

    // View Team Page
    public function viewTeam()
    {
        $management = TeamMember::query()
            ->forSection(TeamMember::SECTION_MANAGEMENT)
            ->ordered()
            ->get();

        $governingBoard = TeamMember::query()
            ->forSection(TeamMember::SECTION_GOVERNING_BOARD)
            ->ordered()
            ->get();

        $teamAboutSection = TeamAboutSection::query()->firstOrFail();

        return view('team', compact('management', 'governingBoard', 'teamAboutSection'));
    }

    // View Sign up Page
    public function viewInvestorSignup()
    {
        if (auth()->check() && auth()->user()->hasPaidMembership()) {
            return redirect()->route('dashboard')->with('info', 'Your membership plan is already active.');
        }

        $tiers = SubscriptionTier::query()->active()->ordered()->get();

        return view('investor.signup', compact('tiers'));
    }

    // View Investors Page (public; curated featured members)
    public function viewInvestors()
    {
        $investors = User::query()
            ->where('featured', true)
            ->where('is_approved', true)
            ->orderBy('name')
            ->get();

        $showMembershipPlans = ! auth()->check() || auth()->user()->isFree();

        $tiers = $showMembershipPlans
            ? SubscriptionTier::query()->active()->ordered()->get()
            : collect();

        return view('investors', compact('investors', 'tiers', 'showMembershipPlans'));
    }

    // View Resources Page (public DeckVue marketing page at /deckvue)
    public function viewResources()
    {
        return view('resources');
    }

    public function checkout(Request $request)
    {
        dd($request);
    }
}
