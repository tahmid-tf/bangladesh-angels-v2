<?php

use App\Http\Controllers\Admin\AngelAcademyNetworkApplicationController as AdminAngelAcademyNetworkApplicationController;
use App\Http\Controllers\Admin\BanWealthOrderController as AdminBanWealthOrderController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\FounderPitchSubmissionController;
use App\Http\Controllers\Admin\HomepagePortfolioController;
use App\Http\Controllers\Admin\InvestorInvestmentController as AdminInvestorInvestmentController;
use App\Http\Controllers\Admin\LandingPageStatController;
use App\Http\Controllers\Admin\LandingProgramCardController;
use App\Http\Controllers\Admin\ResourceHubCardController;
use App\Http\Controllers\Admin\StartupServiceController;
use App\Http\Controllers\Admin\TeamAboutSectionController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\WhatWeDoCardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AngelAcademyNetworkApplicationController;
use App\Http\Controllers\BanWealthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\Admin\PublicationController as AdminPublicationController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CommitSubmissionController;
use App\Http\Controllers\FounderPitchController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\InvestorDashboardController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\MembershipOrderController;
use App\Http\Controllers\PrimaryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebsitePolicyController;
use App\Http\Middleware\ApprovedUserMiddleware;
use App\Models\Deal;
use App\Models\Payment;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Public Routes
 */
Route::get('/', PrimaryController::class)->name('home');

Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blogs/{slug}', [BlogController::class, 'show'])->name('blogs.show');
Route::get('/publications', [PublicationController::class, 'index'])->name('publications.index');
Route::get('/publications/{publication}/download', [PublicationController::class, 'download'])->name('publications.download');

Route::get('/about-us', [WebsitePolicyController::class, 'show'])->name('about-us');
Route::get('/services', [WebsitePolicyController::class, 'services'])->name('services');
Route::get('/contact', [WebsitePolicyController::class, 'contact'])->name('contact');
Route::get('/policies/{policy}', [WebsitePolicyController::class, 'show'])->name('policies.show');

Route::middleware('auth')->group(function () {
    Route::get('/membership/orders', [MembershipOrderController::class, 'index'])->name('membership-orders.index');
    Route::get('/membership/orders/{membershipOrder}', [MembershipOrderController::class, 'show'])->name('membership-orders.show');
    Route::get('/membership/orders/{membershipOrder}/policies', [MembershipOrderController::class, 'policies'])->name('membership-orders.policies');
    Route::post('/membership/orders/{membershipOrder}/confirm-delivery', [MembershipOrderController::class, 'confirmDelivery'])->middleware('throttle:10,1')->name('membership-orders.confirm-delivery');
    Route::get('/admin/membership-orders', [MembershipOrderController::class, 'adminIndex'])->name('admin.membership-orders.index');
    Route::post('/admin/membership-orders/{membershipOrder}/records', [MembershipOrderController::class, 'storeRecord'])->middleware('throttle:20,1')->name('admin.membership-orders.records.store');
    Route::get('/admin/membership-records/{record}/download', [MembershipOrderController::class, 'downloadRecord'])->name('admin.membership-orders.records.download');
});

Route::redirect('/our-team', '/team', 301);

Route::redirect('/ban-investors', '/our-investors', 301);
Route::redirect('/investors', '/our-investors', 301);
Route::get('/our-investors', [PrimaryController::class, 'viewInvestors'])->name('investors');
Route::redirect('/ban-resources', '/deckvue', 301);
Route::get('/deckvue', [PrimaryController::class, 'viewResources'])->name('resources');
Route::get('/team', [PrimaryController::class, 'viewTeam'])->name('team');
Route::get('/angel-academy', [PrimaryController::class, 'viewAngelAcademy'])->name('angel-academy');
Route::get('/bwin', [PrimaryController::class, 'viewBwin'])->name('bwin');
// Route::get('/ban-wealth', [BanWealthController::class, 'landing'])->name('ban-wealth.index');
// Route::get('/ban-wealth/invest', [BanWealthController::class, 'invest'])->name('ban-wealth.invest');
Route::get('/angel-academy/apply', [AngelAcademyNetworkApplicationController::class, 'create'])->name('angel-academy.apply');
Route::post('/angel-academy/apply', [AngelAcademyNetworkApplicationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('angel-academy.apply.store');
Route::get('/startups', [PrimaryController::class, 'viewStartups'])->name('startups');
Route::post('/startups/pitch', [FounderPitchController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('startups.pitch');
Route::post('/pitch', [FounderPitchController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('home.pitch');

Route::get('/portfolio', [PrimaryController::class, 'viewPortfolio'])->name('portfolio');
Route::get('/approval/pending', function () {
    return view('approval.pending');
})->name('approval.pending');
Route::get('/approval/success', function () {
    return view('approval.success');
})->name('approval.success');

Route::prefix('upgrade')->group(function () {
    Route::get('/', [PrimaryController::class, 'upgradePage'])->name('upgrade.page');
    Route::get('/plans', [PrimaryController::class, 'viewPlans'])->name('plans');
    Route::match(['get', 'post'], '/pay', [CheckoutController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'processCheckout'])->name('checkout.process');
    Route::post('/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::post('/fail', [CheckoutController::class, 'fail'])->name('checkout.fail');
    Route::post('/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
});

Route::get('/deals', function () {
    if (! Auth::check() || auth()->user()->account_status == 'free') {
        return redirect()->route('upgrade.page');
    }

    return app(PrimaryController::class)->viewDeals();
})->middleware(['auth', 'verified', ApprovedUserMiddleware::class])->name('deals');

Route::get('/deals/invest', function () {
    if (! Auth::check() || auth()->user()->account_status == 'free') {
        return redirect()->route('upgrade.page');
    }

    return app(PrimaryController::class)->viewDeals_invest();
})->middleware(['auth', 'verified', ApprovedUserMiddleware::class])->name('deals.invest');

Route::get('/deals/commit', function () {
    if (! Auth::check() || auth()->user()->account_status == 'free') {
        return redirect()->route('upgrade.page');
    }

    return app(PrimaryController::class)->viewDeals_commit();
})->middleware(['auth', 'verified', ApprovedUserMiddleware::class])->name('deals.commit');

Route::get('/deals/review', function () {
    if (! Auth::check() || auth()->user()->account_status == 'free') {
        return redirect()->route('upgrade.page');
    }

    return app(PrimaryController::class)->viewDeals_review();
})->middleware(['auth', 'verified', ApprovedUserMiddleware::class])->name('deals.review');

/** Canonical deal profile URL (must stay below /deals/invest|commit|review) */
Route::get('/deals/{deal:slug}', [AdminController::class, 'showDeal'])->name('deal.view');

Route::get('/faq', [PrimaryController::class, 'viewFAQ'])->name('faq');
Route::get('/investor/signup', [PrimaryController::class, 'viewInvestorSignup'])->name('investor.signup');

/**
 * Keep the legacy dashboard path as a role-aware entry point.
 */
Route::get('/dashboard', function () {
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if (auth()->user()->isInvestor()) {
        return redirect()->route('investor.dashboard');
    }

    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

/**
 * Member Routes
 */
Route::post('/member/create', [AdminController::class, 'memberApply'])->name('member.apply');
Route::get('/view/{deal:id}', function (Deal $deal) {
    abort_unless(filled($deal->slug), 404);

    return redirect()->route('deal.view', $deal, 301);
})->name('deal.public.view');

// Legacy numeric URLs (/resources/1) → canonical slug URL (301)
Route::get('/resources/{legacyId}', function (string $legacyId) {
    $resource = Resource::query()->findOrFail((int) $legacyId);
    if (! filled($resource->slug)) {
        $resource->slug = Resource::makeUniqueSlug(
            Resource::slugBaseFromTitle((string) $resource->title),
            $resource->id
        );
        $resource->saveQuietly();
    }

    return redirect()->route('resource.public.view', $resource, 301);
})->whereNumber('legacyId')->name('resource.public.view.legacy');

Route::get('/resources/{resource:slug}', [ResourceController::class, 'view'])->name('resource.public.view');
/**
 * Authenticated Routes
 */
Route::middleware('auth')->group(function () {

    Route::get('/ban-wealth/continue', fn () => redirect()->route('ban-wealth.invest', ['resume' => 1]))
        ->name('ban-wealth.continue');

    /**
     * Admin Dashboard Routes
     */
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', AdminController::class)->name('admin.dashboard');
        Route::get('/blogs', [AdminBlogController::class, 'index'])->name('admin.blogs.index');
        Route::get('/blogs/create', [AdminBlogController::class, 'create'])->name('admin.blogs.create');
        Route::post('/blogs', [AdminBlogController::class, 'store'])->name('admin.blogs.store');
        Route::get('/blogs/{blog}', [AdminBlogController::class, 'show'])->name('admin.blogs.show');
        Route::get('/blogs/{blog}/edit', [AdminBlogController::class, 'edit'])->name('admin.blogs.edit');
        Route::put('/blogs/{blog}', [AdminBlogController::class, 'update'])->name('admin.blogs.update');
        Route::patch('/blogs/{blog}/status', [AdminBlogController::class, 'updateStatus'])->name('admin.blogs.status');
        Route::delete('/blogs/{blog}', [AdminBlogController::class, 'destroy'])->name('admin.blogs.destroy');
        Route::get('/publications', [AdminPublicationController::class, 'index'])->name('admin.publications.index');
        Route::get('/publications/create', [AdminPublicationController::class, 'create'])->name('admin.publications.create');
        Route::post('/publications', [AdminPublicationController::class, 'store'])->name('admin.publications.store');
        Route::get('/publications/{publication}/edit', [AdminPublicationController::class, 'edit'])->name('admin.publications.edit');
        Route::put('/publications/{publication}', [AdminPublicationController::class, 'update'])->name('admin.publications.update');
        Route::patch('/publications/{publication}/status', [AdminPublicationController::class, 'updateStatus'])->name('admin.publications.status');
        Route::delete('/publications/{publication}', [AdminPublicationController::class, 'destroy'])->name('admin.publications.destroy');
        Route::redirect('/resources', '/admin/events', 301);
        Route::get('/events', ResourceController::class)->name('admin.events');
        Route::get('/events/create', [ResourceController::class, 'create'])->name('admin.events.create');
        Route::post('/events/create', [ResourceController::class, 'store'])->name('admin.events.store');

        Route::get('/events/{resource:id}/edit', [ResourceController::class, 'edit'])->name('admin.events.edit');
        Route::put('/events/{resource:id}/update', [ResourceController::class, 'update'])->name('admin.events.update');

        Route::post('/events/{resource:id}/delete', [ResourceController::class, 'destroy'])->name('admin.events.destroy');

        // Subscription Routes
        Route::prefix('subscriptions')->group(function () {
            Route::get('/', [SubscriptionController::class, 'index'])->name('admin.subscriptions');
            Route::post('/tiers', [SubscriptionController::class, 'updateTiers'])->name('admin.subscription-tiers.update');
        });

        // Member Routes
        Route::prefix('members')->group(function () {
            Route::get('/', [AdminController::class, 'viewMembers'])->name('admin.members');
            Route::get('/active', [AdminController::class, 'viewActiveMembers'])->name('admin.active.members');
            Route::get('/inactive', [AdminController::class, 'viewInactiveMembers'])->name('admin.inactive.members');
            Route::get('/pending-approval', [AdminController::class, 'viewPendingApprovalMembers'])->name('admin.pending.members');
            Route::get('/export/{format}', [AdminController::class, 'exportMembers'])->name('admin.members.export');
            Route::post('/{user}/approve', [AdminController::class, 'approveUser'])->name('member.approve');

            Route::get('/add', [AdminController::class, 'addMember'])->name('member.add');
            Route::post('/add/{approval}', [AdminController::class, 'createMember'])->name('member.create');
            Route::get('/{user:id}/edit', [AdminController::class, 'editMember'])->name('member.edit');
            Route::post('/{user:id}/edit', [AdminController::class, 'updateMember'])->name('member.update');
            Route::post('/{user:id}/verify-email', [AdminController::class, 'verifyMemberEmail'])->name('member.verify-email');
            Route::get('/{user:id}/remove', [AdminController::class, 'removeMember'])->name('member.remove');
            Route::patch('/update-status/{user:id}/', [AdminController::class, 'updateAccountStatus'])->name('update.account.status');

        });

        Route::prefix('mail-list')->group(function () {
            Route::get('/', MailController::class)->name('admin.mail');
            Route::post('/send', [MailController::class, 'send'])->name('mail.send');
            Route::delete('/logs/{campaignSendLog}', [MailController::class, 'destroyLog'])->name('admin.mail.logs.destroy');
            Route::delete('/logs', [MailController::class, 'destroyAllLogs'])->name('admin.mail.logs.destroy-all');
        });

        Route::get('/founder-pitches', [FounderPitchSubmissionController::class, 'index'])->name('admin.founder-pitches');
        Route::get('/founder-pitches/{founderPitchSubmission}', [FounderPitchSubmissionController::class, 'show'])
            ->name('admin.founder-pitches.show');
        Route::get('/founder-pitches/{founderPitchSubmission}/deck', [FounderPitchSubmissionController::class, 'downloadDeck'])
            ->name('admin.founder-pitches.deck');

        Route::get('/angel-academy-applications', [AdminAngelAcademyNetworkApplicationController::class, 'index'])
            ->name('admin.angel-academy-applications');
        Route::get('/angel-academy-applications/{angelAcademyNetworkApplication}', [AdminAngelAcademyNetworkApplicationController::class, 'show'])
            ->name('admin.angel-academy-applications.show');

        Route::get('/resource-hub-cards', [ResourceHubCardController::class, 'index'])->name('admin.resource-hub');
        Route::get('/resource-hub-cards/create', [ResourceHubCardController::class, 'create'])->name('admin.resource-hub.create');
        Route::post('/resource-hub-cards', [ResourceHubCardController::class, 'store'])->name('admin.resource-hub.store');
        Route::get('/resource-hub-cards/{resourceHubCard}/edit', [ResourceHubCardController::class, 'edit'])->name('admin.resource-hub.edit');
        Route::put('/resource-hub-cards/{resourceHubCard}', [ResourceHubCardController::class, 'update'])->name('admin.resource-hub.update');
        Route::delete('/resource-hub-cards/{resourceHubCard}', [ResourceHubCardController::class, 'destroy'])->name('admin.resource-hub.destroy');

        Route::get('/startup-services', [StartupServiceController::class, 'index'])->name('admin.startup-services');
        Route::get('/startup-services/create', [StartupServiceController::class, 'create'])->name('admin.startup-services.create');
        Route::post('/startup-services', [StartupServiceController::class, 'store'])->name('admin.startup-services.store');
        Route::get('/startup-services/{startupService}/edit', [StartupServiceController::class, 'edit'])->name('admin.startup-services.edit');
        Route::put('/startup-services/{startupService}', [StartupServiceController::class, 'update'])->name('admin.startup-services.update');
        Route::delete('/startup-services/{startupService}', [StartupServiceController::class, 'destroy'])->name('admin.startup-services.destroy');

        Route::get('/what-we-do-cards', [WhatWeDoCardController::class, 'index'])->name('admin.what-we-do-cards');
        Route::get('/what-we-do-cards/{whatWeDoCard}/edit', [WhatWeDoCardController::class, 'edit'])->name('admin.what-we-do-cards.edit');
        Route::put('/what-we-do-cards/{whatWeDoCard}', [WhatWeDoCardController::class, 'update'])->name('admin.what-we-do-cards.update');

        Route::get('/landing-page-stats', [LandingPageStatController::class, 'index'])->name('admin.landing-page-stats');
        Route::put('/landing-page-stats', [LandingPageStatController::class, 'update'])->name('admin.landing-page-stats.update');

        Route::get('/landing-program-cards', [LandingProgramCardController::class, 'index'])->name('admin.landing-program-cards');
        Route::get('/landing-program-cards/create', [LandingProgramCardController::class, 'create'])->name('admin.landing-program-cards.create');
        Route::post('/landing-program-cards', [LandingProgramCardController::class, 'store'])->name('admin.landing-program-cards.store');
        Route::get('/landing-program-cards/{landingProgramCard}/edit', [LandingProgramCardController::class, 'edit'])->name('admin.landing-program-cards.edit');
        Route::put('/landing-program-cards/{landingProgramCard}', [LandingProgramCardController::class, 'update'])->name('admin.landing-program-cards.update');
        Route::delete('/landing-program-cards/{landingProgramCard}', [LandingProgramCardController::class, 'destroy'])->name('admin.landing-program-cards.destroy');

        Route::get('/homepage-portfolios', [HomepagePortfolioController::class, 'index'])->name('admin.homepage-portfolios');
        Route::post('/homepage-portfolios', [HomepagePortfolioController::class, 'store'])->name('admin.homepage-portfolios.store');
        Route::put('/homepage-portfolios/reorder', [HomepagePortfolioController::class, 'reorder'])->name('admin.homepage-portfolios.reorder');
        Route::post('/homepage-portfolios/sync', [HomepagePortfolioController::class, 'sync'])->name('admin.homepage-portfolios.sync');
        Route::delete('/homepage-portfolios/batch-destroy', [HomepagePortfolioController::class, 'batchDestroy'])->name('admin.homepage-portfolios.batch-destroy');
        Route::post('/homepage-portfolios/{homepagePortfolio}/move-up', [HomepagePortfolioController::class, 'moveUp'])->name('admin.homepage-portfolios.move-up');
        Route::post('/homepage-portfolios/{homepagePortfolio}/move-down', [HomepagePortfolioController::class, 'moveDown'])->name('admin.homepage-portfolios.move-down');
        Route::delete('/homepage-portfolios/{homepagePortfolio}', [HomepagePortfolioController::class, 'destroy'])->name('admin.homepage-portfolios.destroy');

        Route::get('/team-about-section', [TeamAboutSectionController::class, 'edit'])->name('admin.team-about-section.edit');
        Route::put('/team-about-section', [TeamAboutSectionController::class, 'update'])->name('admin.team-about-section.update');

        Route::get('/team-members', [TeamMemberController::class, 'index'])->name('admin.team-members');
        Route::get('/team-members/create', [TeamMemberController::class, 'create'])->name('admin.team-members.create');
        Route::post('/team-members', [TeamMemberController::class, 'store'])->name('admin.team-members.store');
        Route::get('/team-members/{teamMember}/edit', [TeamMemberController::class, 'edit'])->name('admin.team-members.edit');
        Route::put('/team-members/{teamMember}', [TeamMemberController::class, 'update'])->name('admin.team-members.update');
        Route::delete('/team-members/{teamMember}', [TeamMemberController::class, 'destroy'])->name('admin.team-members.destroy');

        // Deal Routes
        Route::prefix('deals')->group(function () {
            Route::get('/', [AdminController::class, 'viewDeals'])->name('admin.deals');
            Route::get('/edit/{deal:id}', [AdminController::class, 'editDeal'])->name('edit.deal');
            Route::post('/update/{deal:id}', [AdminController::class, 'updateDeal'])->name('update.deal');
            Route::delete('/delete/{deal:id}', [AdminController::class, 'destroyDeal'])->name('delete.deal');
            Route::get('/invest', [AdminController::class, 'viewDeals_invest'])->name('admin.deals.invest');
            Route::get('/commit', [AdminController::class, 'viewDeals_commit'])->name('admin.deals.commit');
            Route::get('/review', [AdminController::class, 'viewDeals_review'])->name('admin.deals.review');
            Route::get('/portfolio', [AdminController::class, 'viewDeals_portfolio'])->name('admin.deals.portfolio');
            Route::get('/invest-portfolio', [AdminController::class, 'viewDeals_investPortfolio'])->name('admin.deals.invest-portfolio');
            Route::get('/{deal:id}/member-activity', [AdminController::class, 'dealMemberActivity'])->name('admin.deals.member-activity');
            Route::post('/{deal:id}/invest', [InvestmentController::class, 'invest'])->name('deal.invest');

            Route::get('/{deal:id}', function (Deal $deal) {
                abort_unless(filled($deal->slug), 404);

                return redirect()->route('deal.view', $deal, 301);
            })->name('deal.view.legacy');

            Route::get('/add/new', [AdminController::class, 'addDeal'])->name('deal.add');
            Route::post('/add', [AdminController::class, 'storeDeal'])->name('deal.store');
        });

        // Investment Controller
        Route::prefix('investments')->group(function () {
            Route::get('/', InvestmentController::class)->name('admin.investments');
        });

        Route::get('/investor-investments/suggestions', [AdminInvestorInvestmentController::class, 'suggestions'])
            ->name('admin.investor-investments.suggestions');
        Route::resource('investor-investments', AdminInvestorInvestmentController::class)
            ->parameters(['investor-investments' => 'investorInvestment'])
            ->names('admin.investor-investments');

        Route::get('/ban-wealth-orders', [AdminBanWealthOrderController::class, 'index'])->name('admin.ban-wealth-orders.index');
        Route::get('/ban-wealth-orders/{banWealthOrder}', [AdminBanWealthOrderController::class, 'show'])->name('admin.ban-wealth-orders.show');
        Route::patch('/ban-wealth-orders/{banWealthOrder}', [AdminBanWealthOrderController::class, 'update'])->name('admin.ban-wealth-orders.update');

    });

    Route::get('/investor/dashboard', InvestorDashboardController::class)
        ->middleware('verified')
        ->name('investor.dashboard');

    Route::post('/ban-wealth/orders', [BanWealthController::class, 'store'])
        ->middleware(['verified', 'throttle:5,1'])
        ->name('ban-wealth.orders.store');
    Route::get('/ban-wealth/orders/{banWealthOrder}/proof', [BanWealthController::class, 'proof'])
        ->name('ban-wealth.orders.proof');

    /**
     * User Profile Routes
     */
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Investment
    Route::prefix('investments')->group(function () {

        Route::get('/type/interested', [InvestmentController::class, 'viewInterested'])->name('investment.type.interested');
        Route::get('/type/invest', [InvestmentController::class, 'viewInvest'])->name('investment.type.invest');
        Route::get('/type/commit', [InvestmentController::class, 'viewCommit'])->name('investment.type.commit');
        Route::get('/type/review', [InvestmentController::class, 'viewReview'])->name('investment.type.review');

        Route::get('/{deal:id}/commit', [InvestmentController::class, 'commitForm'])->name('deal.commit.form');
        Route::post('/{deal:id}/commit', [InvestmentController::class, 'commit'])->name('deal.commit');
    });

    Route::post('/deals/{deal:id}/commit-submission', [CommitSubmissionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('deal.commit-submission.store');
});

/**
 * Payment callback routes (AamarPay POST/GET must bypass CSRF — see bootstrap/app.php)
 */
Route::prefix('payment')->group(function () {
    Route::match(['get', 'post'], '/aamarpay/callback', [CheckoutController::class, 'aamarpayCallback'])
        ->name('payment.aamarpay.callback');
    Route::get('/complete', [CheckoutController::class, 'paymentComplete'])->name('payment.complete');
    Route::get('/failed', [CheckoutController::class, 'paymentFailed'])->name('payment.failed');
    Route::get('/error', [CheckoutController::class, 'paymentError'])->name('payment.error');
    Route::get('/status', [CheckoutController::class, 'paymentStatus'])->name('payment.status');
});

/**
 * Authentication Routes
 */
require __DIR__.'/auth.php';
