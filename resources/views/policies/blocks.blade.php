@foreach($blocks as $block)
    @if($block['type'] === 'heading')
        <h2>{{ $block['text'] }}</h2>
    @elseif($block['type'] === 'paragraph')
        <p>{{ $block['text'] }}</p>
    @elseif($block['type'] === 'plans')
        <div class="ban-policy-table-wrap"><table>
            <thead><tr><th>Available plan</th><th>Annual price</th><th>Included services</th></tr></thead>
            <tbody>@forelse($tiers as $tier)
                <tr><td>{{ $tier->name }}</td><td>USD {{ number_format((float) $tier->price_yearly, 2) }}</td><td>{{ implode('; ', $tier->includedFeatureLines()) }}</td></tr>
            @empty<tr><td colspan="3">No memberships are currently available for purchase. Please contact us.</td></tr>@endforelse</tbody>
        </table></div>
        <p><a href="{{ route('services') }}">View membership availability and purchase options</a></p>
    @elseif($block['type'] === 'purchased_plan')
        <p><strong>Plan purchased:</strong> {{ $block['plan']['name'] }} — USD {{ number_format((float) $block['plan']['price_yearly'], 2) }} / year.</p>
        <ul>@foreach($block['plan']['features_included'] as $feature)<li>{{ $feature }}</li>@endforeach</ul>
    @endif
@endforeach
