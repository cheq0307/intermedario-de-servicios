<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\PostalCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PostalCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_postal_code_lookup_returns_all_matching_settlements(): void
    {
        PostalCode::create([
            'postal_code' => '72000',
            'settlement' => 'Centro',
            'settlement_type' => 'Colonia',
            'municipality' => 'Puebla',
            'state' => 'Puebla',
        ]);
        PostalCode::create([
            'postal_code' => '72000',
            'settlement' => 'San Pablo de los Frailes',
            'settlement_type' => 'Barrio',
            'municipality' => 'Puebla',
            'state' => 'Puebla',
        ]);

        $this->getJson(route('postal-codes.show', '72000'))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('catalog_available', true)
            ->assertJsonCount(2, 'places')
            ->assertJsonPath('places.0.settlement', 'Centro')
            ->assertJsonPath('places.0.municipality', 'Puebla');
    }

    public function test_lookup_returns_only_active_communities_matching_the_postal_code(): void
    {
        PostalCode::create([
            'postal_code' => '74140',
            'settlement' => 'San Matías Tlalancaleca',
            'municipality' => 'San Matías Tlalancaleca',
            'state' => 'Puebla',
        ]);
        $active = Community::create([
            'name' => 'Centro',
            'municipality' => 'San Matías Tlalancaleca',
            'state' => 'Puebla',
            'postal_code' => '74140',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);
        Community::create([
            'name' => 'Comunidad inactiva',
            'municipality' => 'San Matías Tlalancaleca',
            'state' => 'Puebla',
            'postal_code' => '74140',
            'default_radius_km' => 8,
            'is_active' => false,
        ]);

        $this->getJson(route('postal-codes.show', '74140'))
            ->assertOk()
            ->assertJsonCount(1, 'communities')
            ->assertJsonPath('communities.0.id', $active->id)
            ->assertJsonPath('communities.0.name', 'Centro');
    }

    public function test_unknown_postal_code_returns_an_empty_successful_lookup(): void
    {
        $this->getJson(route('postal-codes.show', '99999'))
            ->assertOk()
            ->assertJson(['found' => false, 'catalog_available' => false, 'postal_code' => '99999', 'places' => []]);
    }

    public function test_official_pipe_delimited_catalog_can_be_imported(): void
    {
        $path = storage_path('framework/testing/postal-codes-test.txt');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode("\n", [
            'El Catálogo Nacional de Códigos Postales es proporcionado para uso particular.',
            'd_codigo|d_asenta|d_tipo_asenta|D_mnpio|d_estado|d_ciudad|d_CP|c_estado|c_oficina|c_CP|c_tipo_asenta|c_mnpio|id_asenta_cpcons|d_zona|c_cve_ciudad',
            '72000|Centro|Colonia|Puebla|Puebla|Puebla|72001|21|72001||09|114|0001|Urbano|01',
        ]));

        try {
            $this->artisan('plaza:import-postal-codes', ['file' => $path])
                ->expectsOutputToContain('1 asentamientos procesados')
                ->assertSuccessful();

            $this->assertDatabaseHas('postal_codes', [
                'postal_code' => '72000',
                'settlement' => 'Centro',
                'municipality' => 'Puebla',
                'state_code' => '21',
                'municipality_code' => '114',
            ]);
        } finally {
            File::delete($path);
        }
    }
}
