@extends('layouts.admin')

@section('page_title', 'Review BAN Wealth Order')

@section('page_content')
<div class="max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6"><div><a href="{{ route('admin.ban-wealth-orders.index') }}" class="text-sm font-semibold text-[#0a5554]">← All BAN Wealth orders</a><h1 class="text-2xl font-bold mt-2">{{ $banWealthOrder->reference }}</h1><p class="text-sm text-gray-500">Submitted {{ $banWealthOrder->created_at->format('d M Y, g:i A') }}</p></div><span class="self-start px-3 py-1.5 rounded-full text-sm font-bold {{ $banWealthOrder->status === 'accepted' ? 'bg-green-100 text-green-700' : ($banWealthOrder->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $banWealthOrder->status === 'pending' ? 'Pending review' : ucfirst($banWealthOrder->status) }}</span></div>
    @if (session('success'))<div class="mb-5 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">{{ session('success') }}</div>@endif
    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <div class="space-y-6">
            @foreach ([
                'Order' => [['Fund',$banWealthOrder->fund_name],['Manager',$banWealthOrder->fund_manager],['Amount','BDT '.number_format((float)$banWealthOrder->amount,2)],['Estimated units',number_format((float)$banWealthOrder->estimated_units,4)],['Timeframe',ucfirst($banWealthOrder->horizon)],['Monthly plan',$banWealthOrder->monthly?'Yes':'No']],
                'Identity' => [['Full name',$banWealthOrder->full_name],['NID',$banWealthOrder->nid_number],['Date of birth',$banWealthOrder->date_of_birth->format('d M Y')],['Mobile',$banWealthOrder->mobile],['Email',$banWealthOrder->email],['Address',$banWealthOrder->present_address]],
                'Bank and suitability' => [['Bank',$banWealthOrder->bank_name],['Branch',$banWealthOrder->bank_branch],['Account number',$banWealthOrder->bank_account_number],['Routing number',$banWealthOrder->routing_number],['TIN',$banWealthOrder->tin ?: 'Not provided'],['BO account',$banWealthOrder->bo_account ?: 'Not provided'],['Source of funds',$banWealthOrder->source_of_funds],['Investment experience',$banWealthOrder->investment_experience],['20% loss response',$banWealthOrder->loss_response],['Politically exposed',$banWealthOrder->politically_exposed?'Yes':'No']],
            ] as $heading => $rows)
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"><h2 class="font-bold text-lg mb-4">{{ $heading }}</h2><dl class="grid md:grid-cols-2 gap-x-8">@foreach ($rows as [$label,$value])<div class="py-3 border-t border-gray-100"><dt class="text-xs uppercase tracking-wide text-gray-500">{{ $label }}</dt><dd class="mt-1 text-sm font-semibold text-gray-900 break-words">{{ $value }}</dd></div>@endforeach</dl></section>
            @endforeach
        </div>
        <aside class="space-y-5">
            <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"><h2 class="font-bold text-lg">Payment proof</h2><p class="text-sm text-gray-500 mt-1 mb-4">{{ $banWealthOrder->payment_proof_name }}</p><a href="{{ route('ban-wealth.orders.proof', $banWealthOrder) }}" class="flex justify-center px-4 py-3 rounded-lg bg-gray-900 text-white font-bold text-sm">Download attachment</a></section>
            <form method="POST" action="{{ route('admin.ban-wealth-orders.update', $banWealthOrder) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">@csrf @method('PATCH')<h2 class="font-bold text-lg mb-4">Review decision</h2><label class="block text-sm font-semibold mb-2">Status<select name="status" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg bg-white shadow-sm focus:ring focus:ring-green-200 focus:border-green-500">@foreach (\App\Models\BanWealthOrder::STATUSES as $status)<option value="{{ $status }}" @selected(old('status',$banWealthOrder->status)===$status)>{{ $status === 'pending' ? 'Pending review' : ucfirst($status) }}</option>@endforeach</select></label>@error('status')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror<label class="block text-sm font-semibold mt-4">Review note<textarea name="review_note" rows="5" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-green-200 focus:border-green-500" placeholder="Shown to the investor">{{ old('review_note',$banWealthOrder->review_note) }}</textarea></label>@error('review_note')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror<button class="w-full mt-4 px-4 py-3 rounded-lg bg-[#0a5554] text-white font-bold hover:bg-[#084646]">Save decision</button>@if($banWealthOrder->reviewer)<p class="text-xs text-gray-500 mt-3">Last reviewed by {{ $banWealthOrder->reviewer->name }} on {{ $banWealthOrder->reviewed_at?->format('d M Y, g:i A') }}</p>@endif</form>
        </aside>
    </div>
</div>
@endsection
