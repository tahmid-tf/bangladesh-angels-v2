@extends('layouts.admin')

@section('page_title', 'BAN Wealth Orders')

@section('page_content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
        <div><p class="text-xs uppercase tracking-wider font-bold text-[#0a5554]">BAN Wealth</p><h1 class="text-2xl font-bold text-gray-900">Bank transfer reviews</h1><p class="text-sm text-gray-500 mt-1">Verify investor attachments and publish an accepted or rejected status.</p></div>
        <div class="flex gap-2 text-sm">
            @foreach (['pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected'] as $status => $label)
                <a href="{{ route('admin.ban-wealth-orders.index', ['status' => $status]) }}" class="px-3 py-2 rounded-lg border {{ ($filters['status'] ?? null) === $status ? 'bg-[#0a5554] text-white border-[#0a5554]' : 'bg-white text-gray-700 border-gray-200' }}">{{ $label }} <strong>{{ $counts[$status] ?? 0 }}</strong></a>
            @endforeach
        </div>
    </div>

    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col md:flex-row gap-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-green-200 focus:border-green-500" placeholder="Search reference, investor, email or fund">
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg bg-white shadow-sm focus:ring focus:ring-green-200 focus:border-green-500"><option value="">All statuses</option>@foreach (\App\Models\BanWealthOrder::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
        <button type="submit" class="px-5 py-2 rounded-lg bg-[#0a5554] text-white font-semibold hover:bg-[#084646]">Filter</button>
        <a href="{{ route('admin.ban-wealth-orders.index') }}" class="px-5 py-2 rounded-lg bg-gray-100 text-gray-700 text-center font-semibold hover:bg-gray-200">Reset</a>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3 text-left">Reference</th><th class="px-5 py-3 text-left">Investor</th><th class="px-5 py-3 text-left">Fund</th><th class="px-5 py-3 text-right">Amount</th><th class="px-5 py-3 text-left">Status</th><th class="px-5 py-3 text-left">Submitted</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50"><td class="px-5 py-4 font-bold text-gray-900">{{ $order->reference }}</td><td class="px-5 py-4"><strong class="block text-gray-800">{{ $order->full_name }}</strong><span class="text-gray-500">{{ $order->email }}</span></td><td class="px-5 py-4"><strong class="block">{{ $order->fund_name }}</strong><span class="text-gray-500">{{ $order->fund_manager }}</span></td><td class="px-5 py-4 text-right font-bold">BDT {{ number_format((float) $order->amount, 2) }}</td><td class="px-5 py-4"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $order->status === 'accepted' ? 'bg-green-100 text-green-700' : ($order->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $order->status === 'pending' ? 'Pending review' : ucfirst($order->status) }}</span></td><td class="px-5 py-4 text-gray-500">{{ $order->created_at->format('d M Y, g:i A') }}</td><td class="px-5 py-4 text-right"><a href="{{ route('admin.ban-wealth-orders.show', $order) }}" class="text-[#0a5554] font-bold hover:underline">Review →</a></td></tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-gray-500">No BAN Wealth orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($orders->hasPages())<div class="p-4 border-t">{{ $orders->links() }}</div>@endif
    </div>
</div>
@endsection
