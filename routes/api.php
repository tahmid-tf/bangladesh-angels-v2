<?php

use App\Http\Controllers\Api\Admin\AdminBlogApiController;
use App\Http\Controllers\Api\Admin\AdminContentApiController;
use App\Http\Controllers\Api\Admin\AdminDashboardApiController;
use App\Http\Controllers\Api\Admin\AdminDealApiController;
use App\Http\Controllers\Api\Admin\AdminInvestorInvestmentApiController;
use App\Http\Controllers\Api\Admin\AdminMailApiController;
use App\Http\Controllers\Api\Admin\AdminMemberApiController;
use App\Http\Controllers\Api\Admin\AdminNotificationApiController;
use App\Http\Controllers\Api\Admin\AdminOrderApiController;
use App\Http\Controllers\Api\Admin\AdminPublicationApiController;
use App\Http\Controllers\Api\Admin\AdminResourceApiController;
use App\Http\Controllers\Api\Admin\AdminSubmissionApiController;
use App\Http\Controllers\Api\Admin\AdminSubscriptionApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogApiController;
use App\Http\Controllers\Api\CheckoutApiController;
use App\Http\Controllers\Api\InvestorApiController;
use App\Http\Controllers\Api\PublicationApiController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\ResourceApiController;
use App\Http\Controllers\Api\SubmissionApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider / bootstrap/app.php
| and assigned to the "api" middleware group.
| Designed for React Native Mobile Application consumption.
|
*/

// =========================================================================
// 1. AUTHENTICATION (PUBLIC)
// =========================================================================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/google', [AuthController::class, 'googleLogin'])->name('api.auth.google');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.auth.reset-password');
});

// =========================================================================
// 2. APP CONFIGURATION & PUBLIC SITE APIS
// =========================================================================
Route::get('/app-config', [PublicController::class, 'appConfig'])->name('api.app-config');
Route::get('/config', [PublicController::class, 'appConfig'])->name('api.config');
Route::get('/home', [PublicController::class, 'home'])->name('api.home');
Route::get('/plans', [PublicController::class, 'plans'])->name('api.plans');
Route::get('/faq', [PublicController::class, 'faqs'])->name('api.faq');
Route::get('/team', [PublicController::class, 'team'])->name('api.team');
Route::get('/team-about-section', [PublicController::class, 'teamAboutSection'])->name('api.team-about-section');
Route::get('/services', [PublicController::class, 'services'])->name('api.services');
Route::get('/what-we-do-cards', [PublicController::class, 'whatWeDoCards'])->name('api.what-we-do-cards');
Route::get('/landing-program-cards', [PublicController::class, 'landingProgramCards'])->name('api.landing-program-cards');
Route::get('/landing-page-stats', [PublicController::class, 'landingPageStats'])->name('api.landing-page-stats');
Route::get('/resource-hub-cards', [PublicController::class, 'resourceHubCards'])->name('api.resource-hub-cards');
Route::get('/portfolio', [PublicController::class, 'portfolio'])->name('api.portfolio');
Route::get('/homepage-portfolios', [PublicController::class, 'homepagePortfolios'])->name('api.homepage-portfolios');
Route::get('/our-investors', [PublicController::class, 'investors'])->name('api.investors');
Route::get('/policies', [PublicController::class, 'policies'])->name('api.policies.index');
Route::get('/policies/{policy}', [PublicController::class, 'policy'])->name('api.policies.show');

// Blogs
Route::get('/blogs', [BlogApiController::class, 'index'])->name('api.blogs.index');
Route::get('/blogs/{slug}', [BlogApiController::class, 'show'])->name('api.blogs.show');

// Publications
Route::get('/publications', [PublicationApiController::class, 'index'])->name('api.publications.index');
Route::get('/publications/{publication}', [PublicationApiController::class, 'show'])->name('api.publications.show');
Route::get('/publications/{publication}/download', [PublicationApiController::class, 'download'])->name('api.publications.download');

// Resources / Events / Deckvue
Route::get('/resources', [ResourceApiController::class, 'index'])->name('api.resources.index');
Route::get('/resources/{slug}', [ResourceApiController::class, 'show'])->name('api.resources.show');
Route::get('/deckvue', [ResourceApiController::class, 'index'])->name('api.deckvue');

// =========================================================================
// 3. PUBLIC SUBMISSIONS & CHECKOUT
// =========================================================================
Route::post('/pitch', [SubmissionApiController::class, 'submitPitch'])->middleware('throttle:10,1')->name('api.pitch');
Route::post('/startups/pitch', [SubmissionApiController::class, 'submitPitch'])->middleware('throttle:10,1')->name('api.startups.pitch');
Route::get('/angel-academy/options', [SubmissionApiController::class, 'angelAcademyOptions'])->name('api.angel-academy.options');
Route::post('/angel-academy/apply', [SubmissionApiController::class, 'applyAngelAcademy'])->middleware('throttle:10,1')->name('api.angel-academy.apply');
Route::post('/member/apply', [SubmissionApiController::class, 'applyMember'])->name('api.member.apply');

// Checkout / Membership upgrade
Route::post('/checkout', [CheckoutApiController::class, 'process'])->name('api.checkout.process');
Route::get('/checkout/status/{reference}', [CheckoutApiController::class, 'status'])->name('api.checkout.status');
Route::match(['get', 'post'], '/checkout/callback', [CheckoutApiController::class, 'callback'])->name('api.checkout.callback');

// =========================================================================
// 4. AUTHENTICATED USER & INVESTOR ROUTES (auth:sanctum)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // Auth profile endpoints
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile'])->name('api.auth.profile.update');
        Route::post('/profile/photo', [AuthController::class, 'updateProfilePhoto'])->name('api.auth.profile.photo');
        Route::put('/password', [AuthController::class, 'updatePassword'])->name('api.auth.password.update');
        Route::delete('/account', [AuthController::class, 'deleteAccount'])->name('api.auth.account.delete');
        Route::delete('/profile', [AuthController::class, 'deleteAccount'])->name('api.auth.profile.delete');
        Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
            ->middleware('throttle:6,1')
            ->name('api.verification.send');
    });

    Route::get('/user', [AuthController::class, 'me'])->name('api.user');

    // Investor Dashboard & Activity
    Route::prefix('investor')->group(function () {
        Route::get('/dashboard', [InvestorApiController::class, 'dashboard'])->name('api.investor.dashboard');
        Route::get('/investments', [InvestorApiController::class, 'myInvestments'])->name('api.investor.investments');
        Route::get('/portfolio-investments', [InvestorApiController::class, 'myPortfolioInvestments'])->name('api.investor.portfolio-investments');
        Route::get('/commits', [InvestorApiController::class, 'myCommits'])->name('api.investor.commits');
    });

    // Deals (Approved users / tier access)
    Route::prefix('deals')->middleware('approved')->group(function () {
        Route::get('/', [InvestorApiController::class, 'deals'])->name('api.deals.index');
        Route::get('/filters', [InvestorApiController::class, 'dealFilters'])->name('api.deals.filters');
        Route::get('/{slug}', [InvestorApiController::class, 'deal'])->name('api.deals.show');
        Route::post('/{deal}/invest', [InvestorApiController::class, 'invest'])->name('api.deals.invest');
        Route::post('/{deal}/commit', [InvestorApiController::class, 'commit'])->name('api.deals.commit');
        Route::post('/{deal}/commit-submission', [InvestorApiController::class, 'commitSubmission'])
            ->middleware('throttle:10,1')
            ->name('api.deals.commit-submission');
    });

    // Membership Orders
    Route::prefix('membership/orders')->group(function () {
        Route::get('/', [InvestorApiController::class, 'myOrders'])->name('api.membership-orders.index');
        Route::get('/{membershipOrder}', [InvestorApiController::class, 'orderDetail'])->name('api.membership-orders.show');
        Route::get('/{membershipOrder}/policies', [InvestorApiController::class, 'orderPolicies'])->name('api.membership-orders.policies');
        Route::post('/{membershipOrder}/confirm-delivery', [InvestorApiController::class, 'confirmDelivery'])
            ->middleware('throttle:10,1')
            ->name('api.membership-orders.confirm-delivery');
    });

    // BAN Wealth
    Route::prefix('ban-wealth')->group(function () {
        Route::get('/funds', [InvestorApiController::class, 'banWealthFunds'])->name('api.ban-wealth.funds');
        Route::post('/orders', [InvestorApiController::class, 'submitBanWealthOrder'])
            ->middleware('throttle:5,1')
            ->name('api.ban-wealth.orders.store');
        Route::get('/orders/{banWealthOrder}/proof', [InvestorApiController::class, 'banWealthProof'])->name('api.ban-wealth.orders.proof');
    });

    // =========================================================================
    // 5. ADMIN APIS (auth:sanctum, admin)
    // =========================================================================
    Route::prefix('admin')->middleware('admin')->group(function () {

        // Analytics & Dashboard
        Route::get('/dashboard', AdminDashboardApiController::class)->name('api.admin.dashboard');

        // Member Management
        Route::prefix('members')->group(function () {
            Route::get('/', [AdminMemberApiController::class, 'index'])->name('api.admin.members.index');
            Route::post('/', [AdminMemberApiController::class, 'store'])->name('api.admin.members.store');
            Route::get('/{user}', [AdminMemberApiController::class, 'show'])->name('api.admin.members.show');
            Route::match(['put', 'post'], '/{user}', [AdminMemberApiController::class, 'update'])->name('api.admin.members.update');
            Route::post('/{user}/approve', [AdminMemberApiController::class, 'approve'])->name('api.admin.members.approve');
            Route::post('/{user}/verify-email', [AdminMemberApiController::class, 'verifyEmail'])->name('api.admin.members.verify-email');
            Route::patch('/{user}/status', [AdminMemberApiController::class, 'updateStatus'])->name('api.admin.members.status');
            Route::patch('/{user}/mode', [AdminMemberApiController::class, 'updateMode'])->name('api.admin.members.mode');
            Route::delete('/{user}', [AdminMemberApiController::class, 'destroy'])->name('api.admin.members.destroy');
        });

        // Deals Management
        Route::prefix('deals')->group(function () {
            Route::get('/', [AdminDealApiController::class, 'index'])->name('api.admin.deals.index');
            Route::post('/', [AdminDealApiController::class, 'store'])->name('api.admin.deals.store');
            Route::get('/{deal}', [AdminDealApiController::class, 'show'])->name('api.admin.deals.show');
            Route::match(['put', 'post'], '/{deal}', [AdminDealApiController::class, 'update'])->name('api.admin.deals.update');
            Route::delete('/{deal}', [AdminDealApiController::class, 'destroy'])->name('api.admin.deals.destroy');
            Route::get('/{deal}/member-activity', [AdminDealApiController::class, 'memberActivity'])->name('api.admin.deals.member-activity');
            Route::post('/{deal}/member-activity/investments/{investment}/toggle-viewed', [AdminDealApiController::class, 'toggleInvestmentViewed'])->name('api.admin.deals.member-activity.toggle-viewed');
            Route::post('/{deal}/member-activity/mark-all-viewed', [AdminDealApiController::class, 'markAllInterestedViewed'])->name('api.admin.deals.member-activity.mark-all-viewed');
        });

        // Completed Investor Investments
        Route::prefix('investor-investments')->group(function () {
            Route::get('/', [AdminInvestorInvestmentApiController::class, 'index'])->name('api.admin.investor-investments.index');
            Route::post('/', [AdminInvestorInvestmentApiController::class, 'store'])->name('api.admin.investor-investments.store');
            Route::get('/suggestions', [AdminInvestorInvestmentApiController::class, 'suggestions'])->name('api.admin.investor-investments.suggestions');
            Route::get('/{investorInvestment}', [AdminInvestorInvestmentApiController::class, 'show'])->name('api.admin.investor-investments.show');
            Route::match(['put', 'patch'], '/{investorInvestment}', [AdminInvestorInvestmentApiController::class, 'update'])->name('api.admin.investor-investments.update');
            Route::delete('/{investorInvestment}', [AdminInvestorInvestmentApiController::class, 'destroy'])->name('api.admin.investor-investments.destroy');
        });

        // Subscriptions & Tiers
        Route::prefix('subscriptions')->group(function () {
            Route::get('/', [AdminSubscriptionApiController::class, 'subscriptions'])->name('api.admin.subscriptions.index');
            Route::post('/tiers', [AdminSubscriptionApiController::class, 'updateTiers'])->name('api.admin.subscriptions.tiers.update');
        });

        // Membership Orders & Evidence
        Route::prefix('membership-orders')->group(function () {
            Route::get('/', [AdminOrderApiController::class, 'membershipOrders'])->name('api.admin.membership-orders.index');
            Route::get('/{membershipOrder}', [AdminOrderApiController::class, 'showMembershipOrder'])->name('api.admin.membership-orders.show');
            Route::post('/{membershipOrder}/records', [AdminOrderApiController::class, 'storeMembershipOrderRecord'])->name('api.admin.membership-orders.records.store');
            Route::get('/records/{record}/download', [AdminOrderApiController::class, 'downloadMembershipOrderRecord'])->name('api.admin.membership-orders.records.download');
        });

        // BAN Wealth Orders
        Route::prefix('ban-wealth-orders')->group(function () {
            Route::get('/', [AdminOrderApiController::class, 'banWealthOrders'])->name('api.admin.ban-wealth-orders.index');
            Route::get('/{banWealthOrder}', [AdminOrderApiController::class, 'showBanWealthOrder'])->name('api.admin.ban-wealth-orders.show');
            Route::patch('/{banWealthOrder}', [AdminOrderApiController::class, 'updateBanWealthOrder'])->name('api.admin.ban-wealth-orders.update');
        });

        // Member Activity Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [AdminNotificationApiController::class, 'notifications'])->name('api.admin.notifications.index');
            Route::get('/unread-count', [AdminNotificationApiController::class, 'unreadCount'])->name('api.admin.notifications.unread-count');
            Route::post('/mark-all-viewed', [AdminNotificationApiController::class, 'markAllViewed'])->name('api.admin.notifications.mark-all-viewed');
            Route::post('/{investment}/toggle-viewed', [AdminNotificationApiController::class, 'toggleViewed'])->name('api.admin.notifications.toggle-viewed');
        });

        // Founder Pitches & Angel Academy Submissions
        Route::prefix('founder-pitches')->group(function () {
            Route::get('/', [AdminSubmissionApiController::class, 'founderPitches'])->name('api.admin.founder-pitches.index');
            Route::get('/{founderPitchSubmission}', [AdminSubmissionApiController::class, 'founderPitch'])->name('api.admin.founder-pitches.show');
            Route::get('/{founderPitchSubmission}/deck', [AdminSubmissionApiController::class, 'downloadFounderPitchDeck'])->name('api.admin.founder-pitches.deck');
        });

        Route::prefix('angel-academy-applications')->group(function () {
            Route::get('/', [AdminSubmissionApiController::class, 'angelAcademyApplications'])->name('api.admin.angel-academy-applications.index');
            Route::get('/{angelAcademyNetworkApplication}', [AdminSubmissionApiController::class, 'angelAcademyApplication'])->name('api.admin.angel-academy-applications.show');
        });

        // CMS: Blogs Management
        Route::prefix('blogs')->group(function () {
            Route::get('/', [AdminBlogApiController::class, 'index'])->name('api.admin.blogs.index');
            Route::post('/', [AdminBlogApiController::class, 'store'])->name('api.admin.blogs.store');
            Route::get('/{blog}', [AdminBlogApiController::class, 'show'])->name('api.admin.blogs.show');
            Route::match(['put', 'patch', 'post'], '/{blog}', [AdminBlogApiController::class, 'update'])->name('api.admin.blogs.update');
            Route::patch('/{blog}/status', [AdminBlogApiController::class, 'updateStatus'])->name('api.admin.blogs.status');
            Route::delete('/{blog}', [AdminBlogApiController::class, 'destroy'])->name('api.admin.blogs.destroy');
        });

        // CMS: Publications Management
        Route::prefix('publications')->group(function () {
            Route::get('/', [AdminPublicationApiController::class, 'index'])->name('api.admin.publications.index');
            Route::post('/', [AdminPublicationApiController::class, 'store'])->name('api.admin.publications.store');
            Route::get('/{publication}', [AdminPublicationApiController::class, 'show'])->name('api.admin.publications.show');
            Route::get('/{publication}/download', [AdminPublicationApiController::class, 'download'])->name('api.admin.publications.download');
            Route::match(['put', 'patch', 'post'], '/{publication}', [AdminPublicationApiController::class, 'update'])->name('api.admin.publications.update');
            Route::patch('/{publication}/status', [AdminPublicationApiController::class, 'updateStatus'])->name('api.admin.publications.status');
            Route::delete('/{publication}', [AdminPublicationApiController::class, 'destroy'])->name('api.admin.publications.destroy');
        });

        // CMS: Events & Resources Management
        Route::prefix('events')->group(function () {
            Route::get('/', [AdminResourceApiController::class, 'index'])->name('api.admin.events.index');
            Route::post('/', [AdminResourceApiController::class, 'store'])->name('api.admin.events.store');
            Route::get('/{resource}', [AdminResourceApiController::class, 'show'])->name('api.admin.events.show');
            Route::match(['put', 'post'], '/{resource}', [AdminResourceApiController::class, 'update'])->name('api.admin.events.update');
            Route::delete('/{resource}', [AdminResourceApiController::class, 'destroy'])->name('api.admin.events.destroy');
        });

        // CMS: Homepage Portfolios
        Route::prefix('homepage-portfolios')->group(function () {
            Route::get('/', [AdminContentApiController::class, 'homepagePortfolios'])->name('api.admin.homepage-portfolios.index');
            Route::post('/', [AdminContentApiController::class, 'storeHomepagePortfolio'])->name('api.admin.homepage-portfolios.store');
            Route::put('/reorder', [AdminContentApiController::class, 'reorderHomepagePortfolios'])->name('api.admin.homepage-portfolios.reorder');
            Route::delete('/{homepagePortfolio}', [AdminContentApiController::class, 'destroyHomepagePortfolio'])->name('api.admin.homepage-portfolios.destroy');
        });

        // CMS: Resource Hub Cards
        Route::prefix('resource-hub-cards')->group(function () {
            Route::get('/', [AdminContentApiController::class, 'resourceHubCards'])->name('api.admin.resource-hub.index');
            Route::post('/', [AdminContentApiController::class, 'storeResourceHubCard'])->name('api.admin.resource-hub.store');
            Route::put('/{resourceHubCard}', [AdminContentApiController::class, 'updateResourceHubCard'])->name('api.admin.resource-hub.update');
            Route::delete('/{resourceHubCard}', [AdminContentApiController::class, 'destroyResourceHubCard'])->name('api.admin.resource-hub.destroy');
        });

        // CMS: Startup Services
        Route::prefix('startup-services')->group(function () {
            Route::get('/', [AdminContentApiController::class, 'startupServices'])->name('api.admin.startup-services.index');
            Route::post('/', [AdminContentApiController::class, 'storeStartupService'])->name('api.admin.startup-services.store');
            Route::match(['put', 'post'], '/{startupService}', [AdminContentApiController::class, 'updateStartupService'])->name('api.admin.startup-services.update');
            Route::delete('/{startupService}', [AdminContentApiController::class, 'destroyStartupService'])->name('api.admin.startup-services.destroy');
        });

        // CMS: What We Do Cards
        Route::prefix('what-we-do-cards')->group(function () {
            Route::get('/', [AdminContentApiController::class, 'whatWeDoCards'])->name('api.admin.what-we-do-cards.index');
            Route::put('/{whatWeDoCard}', [AdminContentApiController::class, 'updateWhatWeDoCard'])->name('api.admin.what-we-do-cards.update');
        });

        // CMS: Landing Page Stats
        Route::prefix('landing-page-stats')->group(function () {
            Route::get('/', [AdminContentApiController::class, 'landingPageStats'])->name('api.admin.landing-page-stats.index');
            Route::put('/', [AdminContentApiController::class, 'updateLandingPageStats'])->name('api.admin.landing-page-stats.update');
        });

        // CMS: Landing Program Cards
        Route::prefix('landing-program-cards')->group(function () {
            Route::get('/', [AdminContentApiController::class, 'landingProgramCards'])->name('api.admin.landing-program-cards.index');
            Route::post('/', [AdminContentApiController::class, 'storeLandingProgramCard'])->name('api.admin.landing-program-cards.store');
            Route::put('/{landingProgramCard}', [AdminContentApiController::class, 'updateLandingProgramCard'])->name('api.admin.landing-program-cards.update');
            Route::delete('/{landingProgramCard}', [AdminContentApiController::class, 'destroyLandingProgramCard'])->name('api.admin.landing-program-cards.destroy');
        });

        // CMS: Team About Section
        Route::prefix('team-about-section')->group(function () {
            Route::get('/', [AdminContentApiController::class, 'teamAboutSection'])->name('api.admin.team-about-section.show');
            Route::match(['put', 'post'], '/', [AdminContentApiController::class, 'updateTeamAboutSection'])->name('api.admin.team-about-section.update');
        });

        // CMS: Team Members
        Route::prefix('team-members')->group(function () {
            Route::get('/', [AdminContentApiController::class, 'teamMembers'])->name('api.admin.team-members.index');
            Route::post('/', [AdminContentApiController::class, 'storeTeamMember'])->name('api.admin.team-members.store');
            Route::match(['put', 'post'], '/{teamMember}', [AdminContentApiController::class, 'updateTeamMember'])->name('api.admin.team-members.update');
            Route::delete('/{teamMember}', [AdminContentApiController::class, 'destroyTeamMember'])->name('api.admin.team-members.destroy');
        });

        // Broadcasts / Mail list
        Route::prefix('mail-list')->group(function () {
            Route::get('/', [AdminMailApiController::class, 'index'])->name('api.admin.mail-list.index');
            Route::post('/send', [AdminMailApiController::class, 'send'])->name('api.admin.mail-list.send');
            Route::delete('/logs/{campaignSendLog}', [AdminMailApiController::class, 'destroyLog'])->name('api.admin.mail-list.logs.destroy');
            Route::delete('/logs', [AdminMailApiController::class, 'destroyAllLogs'])->name('api.admin.mail-list.logs.destroy-all');
        });
    });
});
