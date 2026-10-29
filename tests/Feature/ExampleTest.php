<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
    }

    public function test_guest_can_access_invitation_root_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Reach Nagoya with Jesus');
        $response->assertSee('Until There is No Place Left');
    }

    public function test_welcome_page_returns_a_successful_response(): void
    {
        $response = $this->get('/welcome');

        $response->assertOk();
    }
}
