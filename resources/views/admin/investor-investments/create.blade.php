@extends('layouts.admin')

@section('page_title', 'Record Investor Investment')

@section('page_content')
@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline">{{ session('success') }}</span>
        <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none';">
            <span class="text-green-700">&times;</span>
        </button>
    </div>
@endif

<section class="container mx-auto p-4 md:p-6 max-w-5xl">
    <div class="bg-white rounded-xl shadow p-4 md:p-5 mb-5">
        <a href="{{ route('admin.investor-investments.index') }}" class="text-sm font-semibold text-[#0a5554] hover:underline">← Back to investors investments</a>
        <h1 class="mt-3 text-lg md:text-xl font-bold text-gray-900">Record an investment</h1>
        <p class="mt-1 text-sm text-gray-600">Select an existing investor and startup, then enter the completed transaction details.</p>
    </div>

    @include('admin.investor-investments.form', [
        'action' => route('admin.investor-investments.store'),
        'method' => 'POST',
        'submitLabel' => 'Record investment',
    ])
</section>
@endsection
