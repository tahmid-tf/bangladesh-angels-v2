<?php

namespace Tests\Unit;

use App\Models\Resource;
use Tests\TestCase;

class ResourceTimeFormattingTest extends TestCase
{
    public function test_it_formats_single_time_when_start_and_end_are_the_same(): void
    {
        $resource = new Resource([
            'date' => '2026-09-24',
            'start_time' => '15:43',
            'end_time' => '15:43',
        ]);

        $this->assertSame('3:43 PM', $resource->formattedTimeRange());
        $this->assertSame('Sep 24, 2026 · 3:43 PM', $resource->formattedDateTime());
    }

    public function test_it_formats_time_range_when_start_and_end_differ(): void
    {
        $resource = new Resource([
            'date' => '2026-09-24',
            'start_time' => '10:00',
            'end_time' => '12:30',
        ]);

        $this->assertSame('10:00 AM – 12:30 PM', $resource->formattedTimeRange());
        $this->assertSame('Sep 24, 2026 · 10:00 AM – 12:30 PM', $resource->formattedDateTime());
    }

    public function test_it_formats_start_time_only(): void
    {
        $resource = new Resource([
            'date' => '2026-09-24',
            'start_time' => '14:00',
        ]);

        $this->assertSame('2:00 PM', $resource->formattedTimeRange());
        $this->assertSame('Sep 24, 2026 · 2:00 PM', $resource->formattedDateTime());
    }

    public function test_it_formats_date_only_when_no_time_is_set(): void
    {
        $resource = new Resource([
            'date' => '2026-09-24',
        ]);

        $this->assertNull($resource->formattedTimeRange());
        $this->assertSame('Sep 24, 2026', $resource->formattedDateTime());
    }

    public function test_it_renders_ban_event_card_with_date_and_time(): void
    {
        $resource = new Resource([
            'title' => 'Test Event',
            'slug' => 'test-event',
            'type' => 'event',
            'date' => '2026-09-24',
            'start_time' => '15:43',
            'end_time' => '15:43',
            'location' => 'Dhaka',
        ]);

        $html = view('components.ban-event-card', ['resource' => $resource])->render();

        $this->assertStringContainsString('Sep 24, 2026', $html);
        $this->assertStringContainsString('3:43 PM', $html);
        $this->assertStringContainsString('Dhaka', $html);
    }
}
