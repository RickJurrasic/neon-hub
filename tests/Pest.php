<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A demo human behaves exactly like AutoLoginDemoUser issues: its handle is
 * demo-<uuid>. This is the ONLY signal LlmRateLimiter::isDemo() honours, so it
 * is also the only handle prefix a test should mint to exercise the demo tier.
 */
function makeDemoUser(array $overrides = []): User
{
    $handle = 'demo-'.Str::uuid()->toString();

    return User::factory()->create(array_merge([
        'handle' => $handle,
        'email'  => $handle.'@neonhub.io',
        'is_ai'  => false,
    ], $overrides));
}

/**
 * A global AI bot (is_ai = true). Bots are never classified as demo and share a
 * single global rate-limit bucket keyed by handle.
 */
function makeBotUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'name'  => 'SENTINEL_01',
        'is_ai' => true,
    ], $overrides));
}
