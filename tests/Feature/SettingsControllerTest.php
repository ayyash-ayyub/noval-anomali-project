<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('settings.index'))->assertOk();
    }

    public function test_operator_cannot_view_settings_even_via_direct_url(): void
    {
        $operator = User::factory()->create();

        $this->actingAs($operator)->get(route('settings.index'))->assertForbidden();
    }
}
