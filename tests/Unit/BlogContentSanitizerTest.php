<?php

namespace Tests\Unit;

use App\Support\BlogContentSanitizer;
use PHPUnit\Framework\TestCase;

class BlogContentSanitizerTest extends TestCase
{
    public function test_it_keeps_editorial_formatting_and_removes_executable_markup(): void
    {
        $clean = BlogContentSanitizer::clean(
            '<h2 class="large">Heading</h2><div onclick="bad()">Text <strong>bold</strong></div>'.
            '<script>alert(1)</script><a href="javascript:bad()" target="_blank">Unsafe</a>'.
            '<a href="https://bdangels.co" target="_blank">Safe</a>'
        );

        $this->assertStringContainsString('<h2>Heading</h2>', $clean);
        $this->assertStringContainsString('<strong>bold</strong>', $clean);
        $this->assertStringContainsString('<p>Text <strong>bold</strong></p>', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('alert(1)', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }
}
