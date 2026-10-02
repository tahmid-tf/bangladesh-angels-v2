@extends('layouts.admin')
@section('page_title', 'Deals | Dashboard')
@section('page_content')
<section class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-4 bg-white shadow">
        <h1 class="text-lg md:text-xl font-bold mb-2 md:mb-0">{{ ($isDualListing ?? false) ? 'Investment & Portfolio' : 'Portfolios' }} ({{ count($deals) }})</h1>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.homepage-portfolios') }}" class="bg-[#0a5554] text-white px-4 py-2 rounded-lg shadow hover:bg-[#0c6b69] whitespace-nowrap text-sm font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                Manage Homepage Selection
            </a>
            <a href="{{ route('deal.add') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700 whitespace-nowrap text-sm font-semibold">
                + Add New Deal
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 my-6">
        @include('admin.deals.partials.tabs', ['activeDealTab' => ($isDualListing ?? false) ? 'invest-portfolio' : 'portfolio'])
    </div>

    <livewire:deals-table filter="{{ ($isDualListing ?? false) ? 'invest-portfolio' : 'portfolio' }}" />
</section>
@endsection
