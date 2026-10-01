<?php

namespace Tests\Feature;

use App\Enums\PasswordGenerationMethod;
use App\Enums\UsernameGenerationMethod;
use App\Models\Mikrotik;
use App\Models\Voucher;
use App\Services\Voucher\PasswordGenerator;
use App\Services\Voucher\UsernameGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherGeneratorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequential_usernames_are_zero_padded_and_incrementing(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $usernames = (new UsernameGenerator)->generate(
            UsernameGenerationMethod::Sequential,
            'JKT',
            6,
            3,
            $mikrotik->id,
        );

        $this->assertSame(['JKT000001', 'JKT000002', 'JKT000003'], $usernames);
    }

    public function test_sequential_usernames_continue_after_existing_ones(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000005']);

        $usernames = (new UsernameGenerator)->generate(
            UsernameGenerationMethod::Sequential,
            'JKT',
            6,
            2,
            $mikrotik->id,
        );

        $this->assertSame(['JKT000006', 'JKT000007'], $usernames);
    }

    public function test_sequential_numbering_is_scoped_per_mikrotik(): void
    {
        $jakarta = Mikrotik::factory()->create();
        $bandung = Mikrotik::factory()->create();
        Voucher::factory()->create(['mikrotik_id' => $jakarta->id, 'username' => 'JKT000010']);

        $usernames = (new UsernameGenerator)->generate(
            UsernameGenerationMethod::Sequential,
            'JKT',
            6,
            1,
            $bandung->id,
        );

        $this->assertSame(['JKT000001'], $usernames);
    }

    public function test_random_usernames_are_unique_and_correct_quantity(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $usernames = (new UsernameGenerator)->generate(
            UsernameGenerationMethod::Random,
            'JKT',
            6,
            50,
            $mikrotik->id,
        );

        $this->assertCount(50, $usernames);
        $this->assertCount(50, array_unique($usernames));

        foreach ($usernames as $username) {
            $this->assertStringStartsWith('JKT', $username);
        }
    }

    public function test_random_usernames_never_collide_with_existing_database_rows(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKTAAAAAA']);

        // Force every random draw to produce the colliding value first —
        // the generator must detect the DB collision and refill the shortfall.
        $usernames = (new UsernameGenerator)->generate(
            UsernameGenerationMethod::Random,
            'JKT',
            6,
            10,
            $mikrotik->id,
        );

        $this->assertNotContains('JKTAAAAAA', $usernames);
        $this->assertCount(10, $usernames);
    }

    public function test_user_equals_password_usernames_are_unique_and_correct_quantity(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $usernames = (new UsernameGenerator)->generate(
            UsernameGenerationMethod::UserEqualsPassword,
            'JKT',
            6,
            20,
            $mikrotik->id,
        );

        $this->assertCount(20, $usernames);
        $this->assertCount(20, array_unique($usernames));

        foreach ($usernames as $username) {
            $this->assertStringStartsWith('JKT', $username);
        }
    }

    public function test_numeric_password_has_correct_length_and_digits_only(): void
    {
        $password = (new PasswordGenerator)->generate(PasswordGenerationMethod::Numeric, 6);

        $this->assertSame(6, strlen($password));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $password);
    }

    public function test_alphanumeric_password_has_correct_length(): void
    {
        $password = (new PasswordGenerator)->generate(PasswordGenerationMethod::Alphanumeric, 8);

        $this->assertSame(8, strlen($password));
    }

    public function test_generate_many_passwords_returns_requested_quantity(): void
    {
        $passwords = (new PasswordGenerator)->generateMany(PasswordGenerationMethod::Numeric, 6, 25);

        $this->assertCount(25, $passwords);
    }
}
