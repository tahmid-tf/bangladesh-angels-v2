<?php

namespace App\Support;

use Illuminate\Support\Collection;

final class BanWealthFunds
{
    /**
     * The initial BAN Wealth shelf supplied in the client roadmap.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function all(): Collection
    {
        return collect([
            ['slug' => 'ban-liquid-fund-1', 'name' => 'BAN Liquid Fund 1', 'manager' => 'EDGE Asset Management', 'horizon' => 'short', 'shariah' => false, 'nav' => 10.42, 'return_12m' => '+6.1%', 'return_3y' => '+5.8%', 'fee' => '0.75%', 'size' => 'BDT 412 cr', 'risk' => 'Lower risk', 'minimum' => 10000, 'blurb' => 'Treasury bills and short-dated bank deposits. Built for money you may need back within a year.', 'take' => 'Where we park money that has a job within the year. Boring on purpose.'],
            ['slug' => 'ban-shariah-liquid-fund-1', 'name' => 'BAN Shariah Liquid Fund 1', 'manager' => 'EDGE Asset Management', 'horizon' => 'short', 'shariah' => true, 'nav' => 10.88, 'return_12m' => '+5.7%', 'return_3y' => '+5.4%', 'fee' => '0.75%', 'size' => 'BDT 168 cr', 'risk' => 'Lower risk', 'minimum' => 10000, 'blurb' => 'Short-tenor Shariah-compliant instruments, screened by an independent Shariah board.', 'take' => 'The Shariah option for short-dated money, screened by an independent board.'],
            ['slug' => 'ban-income-fund-1', 'name' => 'BAN Income Fund 1', 'manager' => 'EDGE Asset Management', 'horizon' => 'medium', 'shariah' => false, 'nav' => 13.05, 'return_12m' => '+8.6%', 'return_3y' => '+7.9%', 'fee' => '1.50%', 'size' => 'BDT 295 cr', 'risk' => 'Medium risk', 'minimum' => 50000, 'blurb' => 'A balanced mix of listed equities and fixed income, rebalanced twice a year.', 'take' => 'Our default for a two-to-five year goal. Enough equity to grow, enough fixed income to sleep.'],
            ['slug' => 'ban-shariah-income-fund-1', 'name' => 'BAN Shariah Income Fund 1', 'manager' => 'EDGE Asset Management', 'horizon' => 'medium', 'shariah' => true, 'nav' => 12.14, 'return_12m' => '+7.8%', 'return_3y' => '+7.2%', 'fee' => '1.35%', 'size' => 'BDT 143 cr', 'risk' => 'Medium risk', 'minimum' => 50000, 'blurb' => 'Sukuk and Shariah-compliant deposits with a regular income distribution.', 'take' => 'Sukuk-led, with a regular payout. The Shariah answer to the balanced sleeve.'],
            ['slug' => 'ban-growth-fund-1', 'name' => 'BAN Growth Fund 1', 'manager' => 'EDGE Asset Management', 'horizon' => 'long', 'shariah' => false, 'nav' => 14.20, 'return_12m' => '+11.4%', 'return_3y' => '+10.2%', 'fee' => '2.00%', 'size' => 'BDT 528 cr', 'risk' => 'Higher risk', 'minimum' => 50000, 'blurb' => 'Listed Bangladeshi equities, concentrated in large and mid caps. Suits a five-year-plus horizon.', 'take' => 'Our core long-horizon holding. Concentrated, actively run, and it will have bad years.'],
            ['slug' => 'ban-shariah-growth-fund-1', 'name' => 'BAN Shariah Growth Fund 1', 'manager' => 'EDGE Asset Management', 'horizon' => 'long', 'shariah' => true, 'nav' => 12.65, 'return_12m' => '+9.8%', 'return_3y' => '+9.1%', 'fee' => '2.00%', 'size' => 'BDT 221 cr', 'risk' => 'Higher risk', 'minimum' => 50000, 'blurb' => 'Shariah-screened equities, reviewed quarterly by an independent board.', 'take' => 'Shariah-screened equities for money you genuinely will not touch for five years.'],
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function find(string $slug): ?array
    {
        return self::all()->firstWhere('slug', $slug);
    }
}
