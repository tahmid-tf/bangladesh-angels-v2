<?php

namespace App\Http\Controllers;

use App\Exports\MembersExport;
use App\Mail\AccountApproved;
use App\Models\Deal;
use App\Models\CommitSubmission;
use App\Models\HomepagePortfolio;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    public function __invoke()
    {
        if (auth()->user()->isAdmin()) {
            $users = User::all(); // Retrieve all users
            $deals = Deal::all(); // Retrieve all deals
            $payments = Payment::all(); // Retrieve all payments
            $subscriptions = Subscription::all(); // Retrieve all subscriptions

            // Summary data for cards
            $totalRevenue = Payment::where('status', 'completed')->sum('amount');
            $activeMembers = User::where('account_status', '!=', 'free')->count();
            $completedPayments = Payment::where('status', 'completed')->count();

            // Member Sign up Analytics
            $memberCounts = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            // Prepare data for the chart
            $dates = $memberCounts->pluck('date'); // Array of dates
            $counts = $memberCounts->pluck('count'); // Array of counts

            // Subscription Analytics
            $subscriptionCounts = Subscription::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();
            // Prepare data for the chart
            $subscriptionDates = $subscriptionCounts->pluck('date'); // Array of dates
            $subsCounts = $subscriptionCounts->pluck('count'); // Array of counts

            // Deal Analytics
            $dealCounts = Deal::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();
            // Prepare data for the chart
            $dealDates = $dealCounts->pluck('date'); // Array of dates
            $dCounts = $dealCounts->pluck('count'); // Array of counts

            // Payment Analytics - Monthly Revenue
            $paymentsByMonth = Payment::where('status', 'completed')
                ->selectRaw('MONTH(payment_date) as month, YEAR(payment_date) as year, SUM(amount) as total')
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();

            $revenueLabels = $paymentsByMonth->map(function ($item) {
                return date('M Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            });
            $revenueData = $paymentsByMonth->pluck('total');

            // Subscription plan distribution
            $planCounts = Subscription::selectRaw('plan, COUNT(*) as count')
                ->groupBy('plan')
                ->get();

            $planLabels = $planCounts->pluck('plan');
            $planData = $planCounts->pluck('count');

            // Payment method distribution
            $methodCounts = Payment::where('status', 'completed')
                ->selectRaw('payment_method, COUNT(*) as count')
                ->groupBy('payment_method')
                ->get();

            $methodLabels = $methodCounts->pluck('payment_method');
            $methodData = $methodCounts->pluck('count');

            return view('admin.index', compact(
                'users',
                'deals',
                'payments',
                'subscriptions',
                'totalRevenue',
                'activeMembers',
                'completedPayments',
                'dates',
                'counts',
                'subscriptionDates',
                'subsCounts',
                'dealDates',
                'dCounts',
                'revenueLabels',
                'revenueData',
                'planLabels',
                'planData',
                'methodLabels',
                'methodData'
            ));
        } else {
            return redirect()->route('home');
        }
    }

    public function viewMembers()
    {
        if (auth()->user()->isAdmin()) {
            // Retrieve users with pagination, only approved users
            $allUsers = User::with('media')->where('is_approved', true)->get(); // All approved Users
            $users = User::with('media')->where('is_approved', true)->paginate(50); // 50 users per page

            return view('admin.members.index', compact('users', 'allUsers'));
        } else {
            return redirect()->route('home');
        }
    }

    public function exportMembers(string $format)
    {
        abort_unless(auth()->user()?->role === 'superadmin', 403);

        $format = strtolower($format);
        $supported = ['pdf', 'sql', 'csv', 'excel', 'json'];
        abort_unless(in_array($format, $supported, true), 404);

        $filters = request()->validate([
            'account_scope' => 'nullable|in:all,active,inactive',
            'approval_scope' => 'nullable|in:all,approved,pending',
        ]);

        $accountScope = $filters['account_scope'] ?? 'all';
        $approvalScope = $filters['approval_scope'] ?? 'all';

        $baseQuery = User::query()
            ->when($accountScope === 'active', fn ($query) => $query->where('account_status', '!=', 'free'))
            ->when($accountScope === 'inactive', fn ($query) => $query->where('account_status', 'free'))
            ->when($approvalScope === 'approved', fn ($query) => $query->where('is_approved', true))
            ->when($approvalScope === 'pending', fn ($query) => $query->where('is_approved', false))
            ->orderBy('id');

        $users = (clone $baseQuery)->get();
        $timestamp = now()->format('Ymd_His');

        if ($format === 'json') {
            return response()->streamDownload(function () use ($baseQuery) {
                echo '[';
                $first = true;
                (clone $baseQuery)->chunkById(500, function ($chunk) use (&$first) {
                    foreach ($chunk as $user) {
                        if (! $first) {
                            echo ',';
                        }
                        echo json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $first = false;
                    }
                }, 'id');
                echo ']';
            }, "members_{$timestamp}.json", [
                'Content-Type' => 'application/json',
            ]);
        }

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($baseQuery) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, [
                    'id', 'name', 'email', 'phone', 'gender', 'company_name', 'designation',
                    'primary_country', 'account_status', 'payment_status', 'role', 'is_approved',
                    'created_at', 'updated_at',
                ]);

                (clone $baseQuery)->chunkById(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->phone,
                            $user->gender,
                            $user->company_name,
                            $user->designation,
                            $user->primary_country,
                            $user->account_status,
                            $user->payment_status,
                            $user->role,
                            $user->is_approved ? 1 : 0,
                            optional($user->created_at)->toDateTimeString(),
                            optional($user->updated_at)->toDateTimeString(),
                        ]);
                    }
                }, 'id');

                fclose($handle);
            }, "members_{$timestamp}.csv", [
                'Content-Type' => 'text/csv',
            ]);
        }

        if ($format === 'excel') {
            return Excel::download(new MembersExport($accountScope, $approvalScope), "members_{$timestamp}.xlsx");
        }

        if ($format === 'pdf') {
            $total = (clone $baseQuery)->count();
            $pdfMaxRows = 1200;
            if ($total > $pdfMaxRows) {
                return redirect()
                    ->route('admin.members', ['account_scope' => $accountScope, 'approval_scope' => $approvalScope])
                    ->with('error', "PDF export is limited to {$pdfMaxRows} records. Please narrow filters or use CSV/Excel/JSON for larger exports.");
            }

            @ini_set('memory_limit', '512M');
            $pdf = Pdf::loadView('admin.members.exports.pdf', [
                'users' => $users,
                'generatedAt' => now(),
                'accountScope' => $accountScope,
                'approvalScope' => $approvalScope,
            ])->setPaper('a4', 'landscape');

            return $pdf->download("members_{$timestamp}.pdf");
        }

        // SQL backup export
        $columns = Schema::getColumnListing('users');
        $quotedColumns = implode(', ', array_map(fn ($col) => "`{$col}`", $columns));
        $lines = [];
        $lines[] = '-- BAN members backup';
        $lines[] = '-- Generated at '.now()->toDateTimeString();
        $lines[] = '';

        return response()->streamDownload(function () use ($baseQuery, $columns, $quotedColumns, $lines) {
            echo implode(PHP_EOL, $lines).PHP_EOL;
            (clone $baseQuery)->chunkById(500, function ($chunk) use ($columns, $quotedColumns) {
                foreach ($chunk as $user) {
                    $values = [];
                    foreach ($columns as $column) {
                        $value = $user->{$column};
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value);
                            $values[] = "'{$escaped}'";
                        }
                    }
                    echo 'INSERT INTO `users` ('.$quotedColumns.') VALUES ('.implode(', ', $values).');'.PHP_EOL;
                }
            }, 'id');
        }, "members_{$timestamp}.sql", [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function viewActiveMembers()
    {
        if (auth()->user()->isAdmin()) {
            // Retrieve users with pagination
            $allUsers = User::with('media')->where('account_status', '!=', 'free')->where('is_approved', true)->get(); // All approved active Users
            $users = User::with('media')->where('account_status', '!=', 'free')->where('is_approved', true)->paginate(50); // 50 users per page

            return view('admin.members.active_index', compact('users', 'allUsers'));
        } else {
            return redirect()->route('home');
        }
    }

    public function viewInactiveMembers()
    {
        if (auth()->user()->isAdmin()) {
            $allUsers = User::with('media')->where('account_status', 'free')->where('is_approved', true)->get(); // Retrieve all users
            $users = User::with('media')->where('account_status', 'free')->where('is_approved', true)->paginate(50); // Retrieve all users

            return view('admin.members.inactive_index', compact('users', 'allUsers'));
        } else {
            return redirect()->route('home');
        }
    }

    public function viewPendingApprovalMembers()
    {
        if (auth()->user()->isAdmin()) {
            $allUsers = User::with('media')->where('is_approved', false)->get(); // Retrieve all pending approval users
            $users = User::with('media')->where('is_approved', false)->paginate(50);

            return view('admin.members.pending_approval_index', compact('users', 'allUsers'));
        } else {
            return redirect()->route('home');
        }
    }

    public function approveUser(User $user)
    {
        if (auth()->user()->isAdmin()) {
            $user->update([
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Send approval notification email to the user
            try {
                Mail::to($user->email)->send(new AccountApproved($user));
            } catch (\Exception $e) {
                // Log error but don't stop the approval process
                \Log::error('Failed to send account approval email: '.$e->getMessage());
            }

            return redirect()->route('admin.pending.members')->with('success', 'User has been approved successfully.');
        } else {
            return redirect()->route('home');
        }
    }

    public function verifyMemberEmail(User $user)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if ($user->hasVerifiedEmail()) {
            return redirect()->back()->with('info', 'This member\'s email is already verified.');
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verified_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Email marked verified for '.$user->email.'.');
    }

    public function addMember()
    {

        if (auth()->user()->isAdmin()) {
            return view('admin.members.create');
        } else {
            return redirect()->route('home');
        }
    }

    public function createMember(Request $request, User $user, $approval)
    {
        $approval = true;
        if (auth()->user()->isAdmin()) {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|string|max:20',
                'public_profile' => 'nullable',
                'gender' => 'required|in:male,female,other',
                'company_name' => 'nullable|string|max:255',
                'designation' => 'nullable|string|max:255',
                'joining_date' => 'nullable|date',
                'renewed' => 'nullable|string|max:255',
                'primary_country' => 'required|string|max:100',
                'country_code' => 'required|string|max:100',
                'preference_sector' => 'nullable|string|max:255',
                'photo' => 'nullable|image|max:3072', // Max size: 3MB
                'profile_photo' => 'nullable|image|max:3072|mimes:jpeg,png,jpg,gif', // Validation for profile_photo
                'investment_amount.*' => 'nullable|numeric|min:0',
                'password' => 'required|string|min:8|confirmed', // Ensure password and re_password match
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $fullName = $request->first_name.' '.$request->last_name;
            $phone = $request->country_code.$request->phone;

            // Create the user as an investor
            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'address' => $request->address,
                'public_profile' => $request->public_profile,
                'password' => Hash::make($request->password), // Hash the password
                'phone' => $phone,
                'gender' => $request->gender,
                'company_name' => $request->company_name,
                'designation' => $request->designation,
                'joining_date' => $request->joining_date,
                'renewed' => $request->renewed,
                'primary_country' => $request->primary_country,
                'preference_sector' => $request->preference_sector,
                'role' => 'investor', // Assign the investor role
                'is_approved' => $approval,
            ]);

            // Handle file upload for the photo using Media Library
            if ($request->hasFile('profile_photo')) {
                $user->addMediaFromRequest('profile_photo')->toMediaCollection('profile_photo');
            }

            return redirect()->route('admin.members')->with('success', 'Member created successfully.');
        } else {
            return redirect()->route('home');
        }
    }

    public function memberApply(Request $request)
    {
        if ($request->user()?->hasPaidMembership()) {
            return redirect()->route('dashboard')->with('info', 'Your membership plan is already active.');
        }

        $approval = false;

        $googleSignup = $request->session()->get('google_signup');
        $usingGoogleSignup = is_array($googleSignup)
            && filled($googleSignup['google_id'] ?? null)
            && Str::lower((string) $request->input('email')) === Str::lower((string) ($googleSignup['email'] ?? ''));

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'investment_expertise' => 'nullable|string',
            'gender' => 'required|in:male,female,other',
            'organization' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'joining_date' => 'nullable|date',
            'renewed' => 'nullable|string|max:255',
            'primary_country' => 'required|string|max:100',
            'country_code' => 'required|string|max:100',
            'preference_sector' => 'nullable|string|max:255',
            'strategic_analyst' => 'nullable|in:TL,FS,TB',
            'photo' => 'nullable|image|max:3072', // Max size: 3MB
            'profile_photo' => 'nullable|image|max:5120|mimes:jpeg,png,jpg,gif', // Max size: 5MB (5120 KB)
            'company_name.*' => 'nullable|string|max:255',
            'investment_amount.*' => 'nullable|numeric|min:0',
            'password' => [$usingGoogleSignup ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'selected_plan' => 'required|string|max:64',
            'selected_plan_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $fullName = $request->first_name.' '.$request->last_name;
        $phone = $request->country_code.$request->phone;
        $selectedPlan = $request->input('selected_plan', 'free');
        $selectedPlanPrice = (float) $request->input('selected_plan_price', 0);

        $paidTier = null;
        if ($selectedPlan !== 'free') {
            $paidTier = SubscriptionTier::query()
                ->active()
                ->where('slug', $selectedPlan)
                ->first();

            if (! $paidTier) {
                return redirect()->back()
                    ->withErrors(['selected_plan' => 'The selected tier is no longer available.'])
                    ->withInput();
            }

            if (abs((float) $paidTier->price_yearly - $selectedPlanPrice) > 0.009) {
                return redirect()->back()
                    ->withErrors(['selected_plan' => 'Tier pricing was updated. Please select your tier again.'])
                    ->withInput();
            }
        }

        // Create the user as an investor
        $user = User::create([
            'name' => $fullName,
            'email' => $request->email,
            'password' => Hash::make($usingGoogleSignup ? Str::password(32) : $request->password),
            'google_id' => $usingGoogleSignup ? $googleSignup['google_id'] : null,
            'email_verified_at' => $usingGoogleSignup ? now() : null,
            'email_verified_by' => null,
            'phone' => $phone,
            'address' => $request->address,
            'gender' => $request->gender,
            'company_name' => $request->company_name,
            'designation' => $request->designation,
            'investment_expertise' => $request->investment_expertise,
            'joining_date' => $request->joining_date,
            'renewed' => $request->renewed,
            'linkedin' => $request->linkedin,
            'primary_country' => $request->primary_country,
            'preference_sector' => $request->preference_sector,
            'strategic_investment_analyst' => $request->strategic_analyst,
            'account_status' => 'free',
            'payment_status' => 'free',
            'role' => 'investor', // Assign the investor role
            'is_approved' => $approval,
        ]);

        // Handle file upload for the photo using Media Library
        if ($request->hasFile('profile_photo')) {

            $user->addMediaFromRequest('profile_photo')->toMediaCollection('profile_photo');
        }

        // Add investment portfolio data if provided
        if ($request->has('company_name')) {
            // Ensure company_name and investment_amount are arrays
            $companyNames = is_array($request->company_name) ? $request->company_name : [$request->company_name];
            $investmentAmounts = is_array($request->investment_amount) ? $request->investment_amount : [$request->investment_amount];

            foreach ($companyNames as $index => $companyName) {
                if (! empty($companyName) && ! empty($investmentAmounts[$index])) {
                    $user->portfolio()->create([
                        'company_name' => $companyName,
                        'investment_amount' => $investmentAmounts[$index],
                    ]);
                }
            }
        }

        // Log in the user if not already logged in
        if (! Auth::check()) {
            Auth::login($user);
        }

        if ($usingGoogleSignup) {
            $request->session()->forget('google_signup');
        }

        if ($paidTier) {
            if (! $user->hasVerifiedEmail()) {
                event(new Registered($user));
            }

            session([
                'checkout.plan' => [
                    'slug' => $paidTier->slug,
                    'name' => $paidTier->name,
                    'price' => (float) $paidTier->price_yearly,
                ],
                'url.intended' => route('checkout'),
            ]);

            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
            }

            return redirect()->route('checkout');
        }

        // Redirect to the success page instead of dashboard
        return redirect()->route('approval.success');
    }

    public function viewDeals()
    {
        if (auth()->user()->isAdmin()) {
            $deals = Deal::with('media')->get(); // Fetch all deals

            return view('admin.deals.index', compact('deals'));
        } else {
            return redirect()->route('home');
        }
    }

    public function viewDeals_invest()
    {
        if (auth()->user()->isAdmin()) {
            $deals = Deal::with('media')->where('type', 'invest')->get(); // Fetch all deals

            return view('admin.deals.invest_index', compact('deals'));
        } else {
            return redirect()->route('home');
        }
    }

    public function viewDeals_commit()
    {
        if (auth()->user()->isAdmin()) {
            $deals = Deal::with('media')->where('type', 'commit')->get(); // Fetch all deals

            return view('admin.deals.commit_index', compact('deals'));
        } else {
            return redirect()->route('home');
        }
    }

    public function viewDeals_review()
    {
        if (auth()->user()->isAdmin()) {
            $deals = Deal::with('media')->where('type', 'review')->get(); // Fetch all deals

            return view('admin.deals.review_index', compact('deals'));
        } else {
            return redirect()->route('home');
        }
    }

    public function viewDeals_portfolio()
    {
        if (auth()->user()->isAdmin()) {
            $deals = Deal::with('media')->inPortfolio()->get();

            return view('admin.deals.portfolio_index', compact('deals'));
        } else {
            return redirect()->route('home');
        }
    }

    public function viewDeals_investPortfolio()
    {
        if (auth()->user()->isAdmin()) {
            $deals = Deal::with('media')
                ->where('type', 'invest')
                ->where('is_portfolio', true)
                ->get();

            return view('admin.deals.portfolio_index', [
                'deals' => $deals,
                'isDualListing' => true,
            ]);
        } else {
            return redirect()->route('home');
        }
    }

    public function addDeal()
    {
        if (auth()->user()->isAdmin()) {
            $homepageSelectionsCount = HomepagePortfolio::count();

            return view('admin.deals.create', compact('homepageSelectionsCount'));
        } else {
            return redirect()->route('home');
        }
    }

    public function storeDeal(Request $request)
    {
        if (auth()->user()->isAdmin()) {
            // Validate incoming request
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'sector' => 'required|string|max:255',
                'type' => 'required|in:commit,invest,review,portfolio',
                'is_portfolio' => 'nullable|boolean',
                'investment_stage' => 'nullable|in:Pre Seed,Seed,Series A,Series B,Series C,Series D',
                'amount_seeking' => 'nullable|numeric|min:0',
                'description' => 'required|string',
                'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'company_cover' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'pitch_deck_url' => 'nullable|max:255',
                'substack_link' => 'nullable|max:255',
                'invest_link' => 'nullable|max:255',
                'commit_link' => 'nullable|max:255',
                'groupchat_invite_link' => 'nullable|max:255',
                'public_profile' => 'nullable|max:255',
                'serviceable_addressable_market' => 'nullable|string|max:255',
                'growth_rate' => 'nullable|string|max:255',
                'revenue_model' => 'nullable|string',
                'user_base' => 'nullable|string|max:255',
                'daily_active_users' => 'nullable|string|max:255',
                'market_penetration' => 'nullable|string|max:255',
                'time_saved' => 'nullable|string|max:255',
                'carbon_emission_reduction' => 'nullable|string|max:255',
                'future_plans' => 'nullable|string',
                'partnerships' => 'nullable|string',
                'video_url' => 'nullable|url|max:255',
                'image_gallery.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'status' => 'nullable|in:active,closed,draft',
                'key_metrics' => 'nullable|array',
                'key_metrics.*.name' => 'nullable|string|max:255',
                'key_metrics.*.value' => 'nullable|string|max:255',
            ]);

            $slug = Deal::makeUniqueSlug(Deal::slugBaseFromTitle($validatedData['title']));

            // Create a new Deal record
            $deal = Deal::create([
                'title' => $validatedData['title'],
                'slug' => $slug,
                'sector' => $validatedData['sector'],
                'type' => $validatedData['type'],
                'is_portfolio' => $request->boolean('is_portfolio'),
                'investment_stage' => $validatedData['investment_stage'],
                'amount_seeking' => $validatedData['amount_seeking'],
                'description' => $validatedData['description'],
                'pitch_deck_url' => $validatedData['pitch_deck_url'] ?? null,
                'substack_link' => $validatedData['substack_link'] ?? null,
                'invest_link' => $validatedData['invest_link'] ?? null,
                'commit_link' => $validatedData['commit_link'] ?? null,
                'groupchat_invite_link' => $validatedData['groupchat_invite_link'] ?? null,
                'growth_rate' => $validatedData['growth_rate'] ?? null,
                'revenue_model' => $validatedData['revenue_model'] ?? null,
                'future_plans' => $validatedData['future_plans'] ?? null,
                'partnerships' => $validatedData['partnerships'] ?? null,
                'video_url' => $validatedData['video_url'] ?? null,
                'status' => $validatedData['status'] ?? 'draft',
                'created_by' => auth()->id(),
                'key_metrics' => json_encode($request->key_metrics), // Store as JSON
            ]);

            // Handle file uploads using Spatie Media Library
            if ($request->hasFile('logo')) {
                $deal->addMediaFromRequest('logo')->toMediaCollection('company_logo');
            }

            if ($request->hasFile('company_cover')) {
                $deal->addMediaFromRequest('company_cover')->toMediaCollection('company_cover');
            }

            if ($request->hasFile('image_gallery')) {
                foreach ($request->file('image_gallery') as $image) {
                    $deal->addMedia($image)->toMediaCollection('image_gallery');
                }
            }

            if ($request->has('show_on_homepage') && $request->boolean('show_on_homepage')) {
                if ($deal->isListedInPortfolio() && HomepagePortfolio::count() < HomepagePortfolio::MAX_SELECTIONS) {
                    $nextOrder = (int) (HomepagePortfolio::max('sort_order') ?? 0) + 1;
                    HomepagePortfolio::create([
                        'deal_id' => $deal->id,
                        'sort_order' => $nextOrder,
                    ]);
                }
            }

            return redirect()->route('admin.deals')->with('success', 'Deal added successfully!');
        } else {
            return redirect()->route('home');
        }
    }

    public function destroyDeal(Deal $deal)
    {
        // Check if the authenticated user is an admin
        if (! auth()->user()->isAdmin()) {
            return redirect()->route('deal.index')->with('error', 'You are not authorized to perform this action.');
        }

        // Delete related investments
        $deal->investments()->delete();

        // Delete associated media files using Spatie Media Library
        $deal->clearMediaCollection('company_logo'); // Deletes all media in the 'company_logo' collection
        $deal->clearMediaCollection('company_cover'); // Deletes all media in the 'company_cover' collection
        $deal->clearMediaCollection('image_gallery'); // Deletes all media in the 'image_gallery' collection

        // Delete associated homepage portfolio selection if any
        $deal->homepagePortfolio()?->delete();

        // Delete the deal record
        $deal->delete();

        // Redirect back with a success message
        return redirect()->route('admin.deals')->with('success', 'Deal deleted successfully.');
    }

    public function editDeal(Deal $deal)
    {
        $deal->load('homepagePortfolio');
        $homepageSelectionsCount = HomepagePortfolio::count();

        return view('admin.deals.edit', compact('deal', 'homepageSelectionsCount'));
    }

    public function updateDeal(Request $request, Deal $deal)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'sector' => 'required|string',
            'type' => 'required|in:commit,invest,review,portfolio',
            'is_portfolio' => 'nullable|boolean',
            'investment_stage' => 'nullable|in:Pre Seed,Seed,Series A,Series B,Series C,Series D',
            'amount_seeking' => 'nullable|numeric|min:0',
            'description' => 'required|string',
            'logo' => 'nullable|image|max:3072',
            'pitch_deck_url' => 'nullable',
            'substack_link' => 'nullable',
            'invest_link' => 'nullable',
            'commit_link' => 'nullable',
            'groupchat_invite_link' => 'nullable',
            'status' => 'required|string',
        ]);

        if ($validatedData['title'] !== $deal->title) {
            $deal->slug = Deal::makeUniqueSlug(
                Deal::slugBaseFromTitle($validatedData['title']),
                $deal->id
            );
        }

        $validatedData['is_portfolio'] = $request->boolean('is_portfolio');

        $deal->update($validatedData);

        if ($request->hasFile('logo')) {
            $deal->updateLogo($request->file('logo'));
        }

        if ($request->hasFile('company_cover')) {
            $deal->updateCover($request->file('company_cover'));
        }

        $deal->refresh();
        if (! $deal->isListedInPortfolio()) {
            $deal->homepagePortfolio()?->delete();
        } elseif ($request->has('show_on_homepage')) {
            if ($request->boolean('show_on_homepage')) {
                if (! $deal->homepagePortfolio && HomepagePortfolio::count() < HomepagePortfolio::MAX_SELECTIONS) {
                    $nextOrder = (int) (HomepagePortfolio::max('sort_order') ?? 0) + 1;
                    HomepagePortfolio::create([
                        'deal_id' => $deal->id,
                        'sort_order' => $nextOrder,
                    ]);
                }
            } else {
                $deal->homepagePortfolio()?->delete();
            }
        }

        return redirect()->route('admin.deals')->with('success', 'Deal updated successfully!');
    }

    public function editMember(User $user)
    {
        $user->load('emailVerifiedByAdmin');

        return view('admin.members.edit', compact('user'));
    }

    public function removeMember(User $user)
    {
        // Check if the authenticated user is an admin
        if (! auth()->user()->isAdmin()) {
            return redirect()->route('admin.members')->with('error', 'You do not have permission to perform this action.');
        }

        // Prevent deleting self (Optional)
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.members')->with('error', 'You cannot delete your own account.');
        }

        try {
            // Check if the user exists
            if (! $user) {
                return redirect()->route('admin.members')->with('error', 'User not found.');
            }

            // Delete related data if necessary (e.g., user media, posts, etc.)
            if ($user->hasMedia('profile_photo')) {
                $user->clearMediaCollection('profile_photo');
            }

            // Delete the user
            $user->delete();

            return redirect()->route('admin.members')->with('success', 'Member successfully removed.');
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error deleting user: '.$e->getMessage());

            return redirect()->route('admin.members')->with('error', 'An error occurred while trying to delete the member. Please try again.');
        }
    }

    public function updateMember(Request $request, User $user)
    {
        if (trim((string) $request->input('password', '')) === '') {
            $request->merge([
                'password' => null,
                'password_confirmation' => null,
            ]);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id, // Ignore the current user's email
            'phone' => 'required|string|max:20',
            'public_profile' => 'nullable',
            'role' => 'nullable|string',
            'account_status' => 'required|string',
            'account_level' => 'nullable|in:emerald,ruby,diamond',
            'gender' => 'required|in:male,female,other',
            'organization' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'primary_contact' => 'nullable|string|max:255',
            'secondary_contact' => 'nullable|string|max:255',
            'primary_country' => 'required|string|max:100',
            'preference_sector' => 'nullable|string|max:255',
            'was_referred' => 'required|boolean',
            'referred_by' => 'nullable|string|max:255|required_if:was_referred,1', // Validate if referred
            'renewed' => 'nullable|string|max:255',
            'last_renewed_at' => 'nullable|date',
            'total_invested' => 'nullable|numeric|min:0',
            'revenue_generated' => 'nullable|numeric|min:0',
            'profile_photo' => 'nullable|image|max:3072', // Max size: 3MB
            'notes' => 'nullable|string|max:5000',
            'status' => 'nullable|string|max:255',
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update the user details
        $user->update([
            'name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'account_status' => $request->account_status,
            'public_profile' => $request->public_profile,
            'level' => $request->account_level,
            'company_name' => $request->company_name,
            'designation' => $request->designation,
            'primary_contact' => $request->primary_contact,
            'secondary_contact' => $request->secondary_contact,
            'primary_country' => $request->primary_country,
            'preference_sector' => $request->preference_sector,
            'was_referred' => $request->was_referred,
            'referred_by' => $request->was_referred ? $request->referred_by : null,
            'renewed' => $request->renewed,
            'last_renewed_at' => $request->last_renewed_at,
            'total_invested' => $request->total_invested,
            'revenue_generated' => $request->revenue_generated,
            'notes' => $request->notes,
        ]);

        if ($request->role) {
            $user->update([
                'role' => $request->role,
            ]);
        }

        // Handle profile photo update
        if ($request->hasFile('profile_photo')) {
            $user->clearMediaCollection('profile_photo'); // Clear old profile photo
            $user->addMediaFromRequest('profile_photo')->toMediaCollection('profile_photo'); // Add new photo
        }

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        // Redirect with a success message
        return redirect()->route('admin.members')->with('success', 'Member updated successfully.');
    }

    public function updateAccountStatus(Request $request, User $user)
    {
        // dd($request);
        $id = $user->id;
        $validatedData = $request->validate([
            'account_status' => 'required|in:free,Core,Advanced,Institutional',
        ]);

        $user->account_status = $validatedData['account_status'];
        $user->save();

        // return response()->with('success','Account status updated successfully!',200);
        return redirect()->route('admin.members')->with('success', 'Account Status updated successfully.');
    }

    public function showDeal(Deal $deal)
    {
        $otherDeals = $deal->getOtherDeals(5); // Fetch 5 other deals
        $commitSubmission = auth()->check()
            ? CommitSubmission::query()
                ->where('deal_id', $deal->id)
                ->where('user_id', auth()->id())
                ->first()
            : null;

        return view('deals.single', compact('deal', 'otherDeals', 'commitSubmission'));
    }

    /**
     * Admin-only breakdown of member signals for a deal (not exposed on the public deal page).
     */
    public function dealMemberActivity(Deal $deal)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $deal->load([
            'investments' => fn ($q) => $q->with('user')->orderByDesc('created_at'),
            'commits' => fn ($q) => $q->with('user')->orderByDesc('created_at'),
            'commitSubmissions' => fn ($q) => $q->with('user')->orderByDesc('updated_at'),
        ]);

        $interested = $deal->investments->where('type', 'interested')->values();
        $investRows = $deal->investments->where('type', 'invest')->values();
        $reviewRows = $deal->investments->where('type', 'review')->values();

        return view('admin.deals.member-activity', [
            'deal' => $deal,
            'interested' => $interested,
            'investRows' => $investRows,
            'reviewRows' => $reviewRows,
        ]);
    }
}
