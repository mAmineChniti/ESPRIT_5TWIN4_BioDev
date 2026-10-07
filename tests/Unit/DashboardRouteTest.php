<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Navigation resolves the destination dashboard from the role, so a link can
 * never point at an area the reader cannot reach.
 */
class DashboardRouteTest extends TestCase
{
    #[DataProvider('roleProvider')]
    public function test_each_role_resolves_to_its_own_dashboard(string $role, string $expected): void
    {
        $user = User::factory()->make(['role' => $role]);

        $this->assertSame($expected, $user->dashboardRouteName());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function roleProvider(): array
    {
        return [
            'admin' => ['admin', 'admin.dashboard'],
            'producer' => ['producer', 'producer.dashboard'],
            'processor' => ['processor', 'processor.dashboard'],
            'distributor' => ['distributor', 'distributor.dashboard'],
            'consumer' => ['consumer', 'consumer.dashboard'],
        ];
    }

    public function test_an_unknown_role_falls_back_to_the_consumer_space(): void
    {
        $user = User::factory()->make(['role' => 'something-else']);

        $this->assertSame('consumer.dashboard', $user->dashboardRouteName());
    }

    public function test_the_dashboard_url_resolves(): void
    {
        $user = User::factory()->make(['role' => 'producer']);

        $this->assertSame(route('producer.dashboard'), $user->dashboardUrl());
    }

    public function test_the_professional_roles_are_the_supply_chain_roles(): void
    {
        $this->assertSame(['producer', 'processor', 'distributor'], User::PROFESSIONAL_ROLES);

        foreach (User::PROFESSIONAL_ROLES as $role) {
            $this->assertTrue(User::factory()->make(['role' => $role])->isProfessional());
        }

        // An admin supervises but does not take part in the chain, and a
        // consumer has no chain role at all.
        $this->assertFalse(User::factory()->make(['role' => 'admin'])->isProfessional());
        $this->assertFalse(User::factory()->make(['role' => 'consumer'])->isProfessional());
    }
}
