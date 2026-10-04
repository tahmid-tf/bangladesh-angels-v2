<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Deal;
use App\Models\Investment;
use App\Models\Publication;
use App\Models\Resource;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Support\MembershipPolicies;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic subscription tiers if none exist
        if (SubscriptionTier::count() === 0) {
            SubscriptionTier::create([
                'name' => 'Core',
                'slug' => 'core',
                'price_yearly' => 399.00,
                'is_active' => true,
                'sort_order' => 1,
            ]);

            SubscriptionTier::create([
                'name' => 'Advanced',
                'slug' => 'advanced',
                'price_yearly' => 799.00,
                'is_active' => true,
                'sort_order' => 2,
            ]);
        }
    }

    // =========================================================================
    // 1. PUBLIC ENDPOINTS
    // =========================================================================

    public function test_public_home_returns_ok(): void
    {
        $response = $this->getJson('/api/home');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'portfolio_deals',
                    'team_members',
                    'faqs',
                    'landing_stats',
                    'landing_programs',
                    'landing_events',
                    'membership_tiers',
                ],
            ]);
    }

    public function test_public_plans_and_faq(): void
    {
        $this->getJson('/api/plans')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => ['tiers', 'policy_version']]);

        $this->getJson('/api/faq')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_public_cms_endpoints(): void
    {
        $endpoints = [
            '/api/team',
            '/api/team-about-section',
            '/api/services',
            '/api/what-we-do-cards',
            '/api/landing-program-cards',
            '/api/landing-page-stats',
            '/api/resource-hub-cards',
            '/api/portfolio',
            '/api/homepage-portfolios',
            '/api/our-investors',
            '/api/policies',
            '/api/policies/terms-and-conditions',
            '/api/resources',
            '/api/deckvue',
        ];

        foreach ($endpoints as $url) {
            $this->getJson($url)->assertOk()->assertJson(['success' => true]);
        }
    }

    public function test_public_blogs_and_publications(): void
    {
        $blog = Blog::create([
            'title' => 'Test Tech Blog',
            'slug' => 'test-tech-blog',
            'content' => '<p>Hello Bangladesh Angels</p>',
            'category' => 'Technology',
            'status' => Blog::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->getJson('/api/blogs')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->getJson('/api/blogs/'.$blog->slug)
            ->assertOk()
            ->assertJsonPath('data.blog.title', 'Test Tech Blog');

        $pub = Publication::create([
            'title' => 'Ecosystem Report 2026',
            'category' => 'Research',
            'status' => Publication::STATUS_PUBLISHED,
            'published_at' => now(),
            'pdf_path' => 'publications/test.pdf',
        ]);

        $this->getJson('/api/publications')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->getJson('/api/publications/'.$pub->id)
            ->assertOk()
            ->assertJsonPath('data.title', 'Ecosystem Report 2026');
    }

    // =========================================================================
    // 2. AUTHENTICATION (REGISTER, LOGIN, PROFILE, LOGOUT)
    // =========================================================================

    public function test_user_can_register_via_api(): void
    {
        $payload = [
            'name' => 'Rahim Chowdhury',
            'email' => 'rahim@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '+8801700000001',
            'gender' => 'male',
            'company_name' => 'Chowdhury Holdings',
            'designation' => 'Managing Director',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role', 'is_approved'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'rahim@example.com']);
    }

    public function test_user_can_login_and_access_profile_via_token(): void
    {
        $user = User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => bcrypt('SecretPassword123!'),
            'role' => 'investor',
            'is_approved' => true,
        ]);

        // Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'loginuser@example.com',
            'password' => 'SecretPassword123!',
        ]);

        $loginResponse->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token'],
            ]);

        $token = $loginResponse->json('data.token');

        // Access profile with token
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me');

        $meResponse->assertOk()
            ->assertJsonPath('data.user.email', 'loginuser@example.com');

        // Update profile
        $updateResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/auth/profile', [
                'name' => 'Updated Login User',
                'email' => 'loginuser@example.com',
                'gender' => 'other',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.user.name', 'Updated Login User');

        // Logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $logoutResponse->assertOk();
    }

    public function test_login_rejects_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('CorrectPassword123!'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'testuser@example.com',
            'password' => 'WrongPassword!',
        ])->assertStatus(422);
    }

    // =========================================================================
    // 3. PUBLIC SUBMISSIONS
    // =========================================================================

    public function test_founder_pitch_submission(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent(
            'pitch_deck.pdf',
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R >>\nendobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000074 00000 n\n0000000120 00000 n\ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n178\n%%EOF"
        );

        $response = $this->postJson('/api/pitch', [
            'contact_email' => 'founder@startup.io',
            'one_line' => 'Building AI for Bangladesh logistics',
            'pitch_deck' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('founder_pitch_submissions', [
            'contact_email' => 'founder@startup.io',
        ]);
    }

    public function test_angel_academy_options(): void
    {
        $this->getJson('/api/angel-academy/options')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'invested_before',
                    'primary_motivation',
                    'startup_stages',
                    'sectors',
                    'cheque_size',
                ],
            ]);
    }

    public function test_angel_academy_application(): void
    {
        $response = $this->postJson('/api/angel-academy/apply', [
            'name' => 'Investor Candidate',
            'email' => 'candidate@example.com',
            'contact_number' => '+8801700000000',
            'linkedin_url' => 'https://linkedin.com/in/candidate',
            'invested_before' => 'yes_1_2',
            'primary_motivation' => 'learn_angel_investing',
            'startup_stages' => ['pre_seed', 'seed'],
            'sectors' => ['fintech', 'logistics'],
            'cheque_size' => '5k_25k',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('angel_academy_network_applications', [
            'email' => 'candidate@example.com',
        ]);
    }

    public function test_member_application(): void
    {
        $response = $this->postJson('/api/member/apply', [
            'first_name' => 'Anis',
            'last_name' => 'Khan',
            'address' => 'Gulshan 2, Dhaka',
            'email' => 'anis.khan@example.com',
            'phone' => '1711122233',
            'country_code' => '+880',
            'gender' => 'male',
            'primary_country' => 'Bangladesh',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'selected_plan' => 'free',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'email' => 'anis.khan@example.com',
        ]);
    }

    public function test_checkout_initiation(): void
    {
        $response = $this->postJson('/api/checkout', [
            'name' => 'Paid Investor',
            'email' => 'paidinvestor@example.com',
            'address' => 'Banani, Dhaka',
            'phone' => '1711000000',
            'country_code' => '+880',
            'primary_country' => 'Bangladesh',
            'company_name' => 'BAN Ventures',
            'designation' => 'Partner',
            'gender' => 'other',
            'investment_expertise' => 'expert',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'plan' => 'core',
            'price' => 399.00,
            'policy_consent' => 1,
            'policy_version' => MembershipPolicies::version(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    // =========================================================================
    // 4. INVESTOR PROTECTED ENDPOINTS
    // =========================================================================

    public function test_investor_dashboard_and_deal_flow(): void
    {
        $investor = User::factory()->create([
            'role' => 'investor',
            'account_status' => 'core',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($investor);

        // Investor Dashboard
        $this->getJson('/api/investor/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['summary', 'chart', 'startup_allocation'],
            ]);

        // Create deal
        $deal = Deal::create([
            'title' => 'FinTech Innovator',
            'slug' => 'fintech-innovator',
            'sector' => 'Fintech',
            'type' => 'invest',
            'status' => 'active',
            'description' => 'A revolutionary fintech platform for SME lending in Bangladesh.',
            'amount_seeking' => 250000,
            'created_by' => $investor->id,
        ]);

        // View Deals
        $this->getJson('/api/deals')
            ->assertOk()
            ->assertJson(['success' => true]);

        // View Single Deal
        $this->getJson('/api/deals/'.$deal->slug)
            ->assertOk()
            ->assertJsonPath('data.title', 'FinTech Innovator');

        // Express interest
        $this->postJson('/api/deals/'.$deal->id.'/invest', ['type' => 'invest'])
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        // Commit to deal
        $this->postJson('/api/deals/'.$deal->id.'/commit', [
            'amount' => 15000,
            'deadline' => now()->addDays(30)->toDateString(),
        ])->assertStatus(201);

        // Commit submission
        $this->postJson('/api/deals/'.$deal->id.'/commit-submission', [
            'name' => 'Investor Rahim',
            'email' => 'rahim@example.com',
            'whatsapp_number' => '+8801700000000',
            'amount' => 15000,
            'currency' => 'USD',
        ])->assertOk();

        // View user's investments and commits
        $this->getJson('/api/investor/investments')->assertOk();
        $this->getJson('/api/investor/commits')->assertOk();
        $this->getJson('/api/membership/orders')->assertOk();
        $this->getJson('/api/ban-wealth/funds')->assertOk();
    }

    public function test_free_member_cannot_view_paywalled_deals(): void
    {
        $freeUser = User::factory()->create([
            'role' => 'investor',
            'account_status' => 'free',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($freeUser);

        $this->getJson('/api/deals')->assertStatus(403);
    }

    // =========================================================================
    // 5. ADMIN ENDPOINTS
    // =========================================================================

    public function test_non_admin_cannot_access_admin_endpoints(): void
    {
        $investor = User::factory()->create([
            'role' => 'investor',
            'account_status' => 'core',
        ]);

        Sanctum::actingAs($investor);

        $this->getJson('/api/admin/dashboard')->assertStatus(403);
        $this->getJson('/api/admin/members')->assertStatus(403);
        $this->getJson('/api/admin/deals')->assertStatus(403);
    }

    public function test_admin_can_access_and_manage_all_modules(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'institutional',
            'is_approved' => true,
        ]);

        Sanctum::actingAs($admin);

        // Dashboard
        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => ['metrics', 'charts']]);

        // Members CRUD
        $this->getJson('/api/admin/members')->assertOk();

        $memberToApprove = User::factory()->create([
            'role' => 'investor',
            'is_approved' => false,
        ]);

        $this->postJson('/api/admin/members/'.$memberToApprove->id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.is_approved', true);

        $this->patchJson('/api/admin/members/'.$memberToApprove->id.'/status', [
            'account_status' => 'core',
        ])->assertOk()->assertJsonPath('data.account_status', 'core');

        // Deals CRUD
        $this->getJson('/api/admin/deals')->assertOk();

        $dealResponse = $this->postJson('/api/admin/deals', [
            'title' => 'Admin Seed Deal',
            'sector' => 'Agriculture',
            'type' => 'commit',
            'description' => 'AgriTech automation platform in northern Bangladesh.',
            'amount_seeking' => 100000,
        ]);

        $dealResponse->assertStatus(201);
        $createdDealId = $dealResponse->json('data.id');

        $this->getJson('/api/admin/deals/'.$createdDealId)->assertOk();

        // Other admin modules
        $this->getJson('/api/admin/investor-investments')->assertOk();
        $this->getJson('/api/admin/subscriptions')->assertOk();
        $this->getJson('/api/admin/membership-orders')->assertOk();
        $this->getJson('/api/admin/ban-wealth-orders')->assertOk();
        $this->getJson('/api/admin/notifications')->assertOk();
        $this->getJson('/api/admin/founder-pitches')->assertOk();
        $this->getJson('/api/admin/angel-academy-applications')->assertOk();
        $this->getJson('/api/admin/blogs')->assertOk();
        $this->getJson('/api/admin/publications')->assertOk();
        $this->getJson('/api/admin/events')->assertOk();
        $this->getJson('/api/admin/resource-hub-cards')->assertOk();
        $this->getJson('/api/admin/startup-services')->assertOk();
        $this->getJson('/api/admin/what-we-do-cards')->assertOk();
        $this->getJson('/api/admin/landing-page-stats')->assertOk();
        $this->getJson('/api/admin/landing-program-cards')->assertOk();
        $this->getJson('/api/admin/team-about-section')->assertOk();
        $this->getJson('/api/admin/team-members')->assertOk();
        $this->getJson('/api/admin/mail-list')->assertOk();
    }
}
