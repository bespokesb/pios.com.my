<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Concerns\ResolvesPiosIdLogin;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PiosIdLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Expose the trait's protected resolver for assertion.
     */
    private function resolve(string $login): string
    {
        $resolver = new class
        {
            use ResolvesPiosIdLogin;

            public function call(string $login): string
            {
                return $this->resolvePiosIdToEmail($login);
            }
        };

        return $resolver->call($login);
    }

    public function test_an_email_resolves_to_itself(): void
    {
        $this->assertSame('someone@pios.com.my', $this->resolve('someone@pios.com.my'));
    }

    public function test_a_partner_ic_number_resolves_to_the_account_email(): void
    {
        $user = User::factory()->create(['email' => 'partner@pios.com.my']);
        Partner::factory()->create(['user_id' => $user->id, 'ic_no' => '900101-10-1234']);

        $this->assertSame('partner@pios.com.my', $this->resolve('900101-10-1234'));
    }

    public function test_a_partner_phone_number_resolves_to_the_account_email(): void
    {
        $user = User::factory()->create(['email' => 'phone@pios.com.my']);
        Partner::factory()->create(['user_id' => $user->id, 'hp_no' => '60123456789']);

        // Entered with a local 0-prefix and spacing — normalised to the same number.
        $this->assertSame('phone@pios.com.my', $this->resolve('012-345 6789'));
    }

    public function test_a_business_owner_ic_number_resolves_to_the_account_email(): void
    {
        $user = User::factory()->create(['email' => 'bo@pios.com.my']);
        BusinessOwner::factory()->create(['user_id' => $user->id, 'ic_no' => '880202-08-5678']);

        $this->assertSame('bo@pios.com.my', $this->resolve('880202-08-5678'));
    }

    public function test_an_unknown_identifier_is_returned_unchanged(): void
    {
        $this->assertSame('999999-99-9999', $this->resolve('999999-99-9999'));
    }
}
