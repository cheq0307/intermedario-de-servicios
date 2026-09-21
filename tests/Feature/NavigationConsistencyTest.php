<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_more_menu_exposes_each_employment_flow_explicitly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->get(route('more.index'))
            ->assertOk()
            ->assertSee('Buscar trabajo')
            ->assertSee('Ofrecer trabajo')
            ->assertSee('Mis vacantes y postulaciones')
            ->assertSee('href="'.route('vacancies.index').'"', false)
            ->assertSee('href="'.route('vacancies.create').'"', false)
            ->assertSee('href="'.route('vacancies.mine').'"', false);
    }

    public function test_job_search_is_visible_in_public_and_member_navigation(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('aria-label="Bolsa de trabajo"', false)
            ->assertSee('Buscar trabajo')
            ->assertSee('href="'.route('vacancies.index').'"', false);

        $this->get(route('vacancies.index'))->assertOk()
            ->assertSee('Buscar trabajo')
            ->assertSee('aria-current="page"', false);

        $user = User::factory()->create();
        $this->actingAs($user, 'web')->get(route('dashboard'))->assertOk()
            ->assertSee('aria-label="Bolsa de trabajo"', false)
            ->assertSee('Buscar trabajo');
    }

    public function test_secondary_account_screens_use_a_consistent_back_arrow(): void
    {
        $user = User::factory()->create();

        foreach (['orders.index', 'profile.edit', 'support.index', 'vacancies.index'] as $routeName) {
            $this->actingAs($user, 'web')->get(route($routeName))
                ->assertOk()
                ->assertSee('aria-label="Regresar"', false);
        }

        $this->actingAs($user, 'web')->get(route('orders.index'))
            ->assertSee('href="'.route('more.index').'"', false);
        $this->actingAs($user, 'web')->get(route('profile.edit'))
            ->assertSee('href="'.route('more.index').'"', false)
            ->assertSee('Cancelar');
        $this->actingAs($user, 'web')->get(route('support.index'))
            ->assertSee('href="'.route('more.index').'"', false)
            ->assertDontSee('>Volver<', false);
        $this->actingAs($user, 'web')->get(route('vacancies.index'))
            ->assertSee('href="'.route('more.index').'"', false)
            ->assertSee('Publicar vacante')
            ->assertSee('Mis vacantes y postulaciones');
    }
}
