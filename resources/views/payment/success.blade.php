@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('page_title', 'Payment successful | Bangladesh Angels')
@section(auth()->check() ? 'content' : 'page_content')

    <div class="w-full py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-center">
                    <div class="mb-8">
                        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">Welcome to Bangladesh Angels Network!</h1>
                    <p class="text-lg text-gray-600 mb-8">Congratulations! Your payment was successful and your membership is now active.</p>
                    
                    @if($payment)
                    <div class="bg-gray-50 p-6 rounded-lg mb-8 max-w-lg mx-auto">
                        <h2 class="text-xl font-semibold mb-4 text-left">Receipt Summary</h2>
                        <div class="grid grid-cols-2 gap-3 text-left">
                            <p class="text-gray-600">Transaction ID:</p>
                            <p class="font-medium">{{ $payment->transaction_id }}</p>
                            
                            <p class="text-gray-600">Amount:</p>
                            <p class="font-medium">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</p>
                            
                            <p class="text-gray-600">Subscription Plan:</p>
                            <p class="font-medium">{{ ucfirst($payment->subscription_plan) }}</p>
                            
                            <p class="text-gray-600">Payment Date:</p>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</p>
                            
                            <p class="text-gray-600">Expiry Date:</p>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($payment->expiry_date)->format('d M, Y') }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($membershipOrder ?? null)
                        <p class="mb-6"><a class="text-green-700 underline" href="{{ route('membership-orders.show', $membershipOrder) }}">View your receipt and confirm membership access</a></p>
                    @endif
                    <div class="mt-8">
                        <a href="{{ route('startups').'#active-deals' }}" class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                            <svg class="mr-2 -ml-1 w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                            Explore startups &amp; deals
                        </a>
                    </div>
                    
                    <div class="mt-6 text-gray-600">
                        <p>If you have any questions about your membership, please contact us at <a href="mailto:{{ config('business.email') }}" class="text-blue-500 hover:underline">{{ config('business.email') }}</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
