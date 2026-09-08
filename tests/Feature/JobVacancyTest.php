<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Community;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class JobVacancyTest extends TestCase
{
    use RefreshDatabase;

    private function community(): Community
    {
        return Community::create([
            'name' => 'San Felipe Teotlalcingo',
            'municipality' => 'San Felipe Teotlalcingo',
            'state' => 'Puebla',
            'postal_code' => '74140',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);
    }

    public function test_verified_user_can_pay_to_publish_and_another_user_can_apply_once(): void
    {
        $community = $this->community();
        $employer = User::factory()->create(['community_id' => $community->id]);
        $applicant = User::factory()->create(['community_id' => $community->id]);

        $response = $this->actingAs($employer, 'web')->post(route('vacancies.store'), [
            'title' => 'Ayudante para taquería',
            'description' => 'Buscamos una persona responsable para apoyar en atención y preparación de alimentos.',
            'requirements' => 'Disponibilidad por las tardes y trato amable con clientes.',
            'community_id' => $community->id,
            'salary_min' => 1800,
            'salary_max' => 2200,
            'pay_period' => 'weekly',
            'work_mode' => 'onsite',
            'contract_type' => 'permanent',
            'schedule' => 'Martes a domingo de 16:00 a 23:00',
            'vacancies_count' => 1,
        ]);

        $vacancy = JobVacancy::firstOrFail();
        $response->assertRedirect(route('vacancies.show', $vacancy));
        $this->assertSame('pending_payment', $vacancy->status);
        $this->get(route('vacancies.index'))->assertDontSee('Ayudante para taquería');

        $this->actingAs($employer, 'web')->post(route('vacancies.pay', $vacancy))->assertRedirect();
        $vacancy->refresh();
        $this->assertSame('published', $vacancy->status);
        $this->assertNotNull($vacancy->paid_at);
        $this->get(route('vacancies.index'))->assertSee('Ayudante para taquería');

        $application = ['cover_letter' => 'Tengo experiencia atendiendo clientes y disponibilidad en el horario indicado.'];
        $this->actingAs($applicant, 'web')->post(route('vacancies.apply', $vacancy), $application)->assertRedirect();
        $this->actingAs($applicant, 'web')->post(route('vacancies.apply', $vacancy), $application)->assertRedirect();
        $this->assertSame(1, JobApplication::where('job_vacancy_id', $vacancy->id)->count());
    }

    public function test_employer_cannot_apply_to_own_vacancy_and_staff_only_account_cannot_publish(): void
    {
        $community = $this->community();
        $employer = User::factory()->create(['community_id' => $community->id]);
        $vacancy = JobVacancy::create([
            'public_id' => fake()->uuid(),
            'employer_id' => $employer->id,
            'community_id' => $community->id,
            'title' => 'Auxiliar de mostrador',
            'description' => 'Trabajo de atención al público con horario de medio tiempo.',
            'pay_period' => 'weekly',
            'work_mode' => 'onsite',
            'contract_type' => 'temporary',
            'vacancies_count' => 1,
            'publication_fee_amount' => 9900,
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($employer, 'web')->post(route('vacancies.apply', $vacancy), [
            'cover_letter' => 'Esta postulación no debería quedar registrada en el sistema.',
        ])->assertStatus(422);

        Auth::guard('web')->logout();
        $staff = AdminUser::factory()->create();
        $this->actingAs($staff, 'admin')->get(route('vacancies.create'))->assertRedirect(route('login'));
    }
}
