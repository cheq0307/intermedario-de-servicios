<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Community;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Post;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SeedMarketplaceDemo extends Command
{
    protected $signature = 'plaza:demo-marketplace {--force : Permite crear los datos en un entorno no local}';

    protected $description = 'Crea usuarios y operaciones DEMO visibles para revisar los flujos del marketplace';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            $this->error('Este comando crea datos visibles. Usa --force únicamente en el servidor de prueba.');

            return self::FAILURE;
        }

        $password = Str::password(16, symbols: false);

        DB::transaction(function () use ($password): void {
            $community = Community::updateOrCreate(
                ['name' => 'Comunidad Demostración', 'municipality' => 'Municipio de Prueba'],
                ['state' => 'Puebla', 'postal_code' => '74140', 'default_radius_km' => 8, 'is_active' => true],
            );
            $category = Category::updateOrCreate(
                ['slug' => 'demostracion-plaza-local'],
                ['name' => 'Demostración Plaza Local', 'is_active' => true],
            );

            $users = collect([
                'provider' => ['Proveedor DEMO', 'proveedor.demo@example.test', '2229000101'],
                'buyer' => ['Comprador DEMO', 'comprador.demo@example.test', '2229000102'],
                'employer' => ['Empleador DEMO', 'empleador.demo@example.test', '2229000103'],
                'applicant' => ['Postulante DEMO', 'postulante.demo@example.test', '2229000104'],
            ])->mapWithKeys(function (array $data, string $key) use ($community, $password): array {
                $user = User::updateOrCreate(
                    ['email' => $data[1]],
                    ['name' => $data[0], 'phone' => $data[2], 'community_id' => $community->id, 'password' => $password],
                );
                $user->forceFill(['email_verified_at' => now(), 'account_status' => 'active'])->save();
                $user->syncRoles([Role::findOrCreate('client', 'web')]);

                return [$key => $user];
            });

            $users['provider']->assignRole(Role::findOrCreate('provider', 'web'));
            $vendor = Vendor::updateOrCreate(
                ['user_id' => $users['provider']->id],
                [
                    'display_name' => 'Negocio DEMO',
                    'slug' => 'negocio-demo-plaza-local',
                    'description' => 'Cuenta demostrativa para revisar el flujo completo de Plaza Local.',
                    'specialty' => 'Productos de demostración',
                    'service_area' => 'Comunidad Demostración',
                    'business_hours' => ['days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], 'opens_at' => '09:00', 'closes_at' => '18:00'],
                    'status' => 'active',
                    'submitted_at' => now(),
                    'reviewed_at' => now(),
                ],
            );
            $vendor->categories()->syncWithoutDetaching([$category->id]);

            $listing = Listing::updateOrCreate(
                ['vendor_id' => $vendor->id, 'slug' => 'producto-demo-visible'],
                [
                    'category_id' => $category->id,
                    'type' => 'product',
                    'name' => 'Producto DEMO con imagen',
                    'description' => 'Producto ficticio creado para probar compra, publicación e imágenes.',
                    'price_type' => 'fixed',
                    'price_amount' => 17550,
                    'currency' => 'MXN',
                    'stock' => 4,
                    'is_active' => true,
                ],
            );
            $post = Post::updateOrCreate(
                ['submission_token' => 'demo-marketplace-product'],
                [
                    'user_id' => $users['provider']->id,
                    'vendor_id' => $vendor->id,
                    'listing_id' => $listing->id,
                    'category_id' => $category->id,
                    'type' => 'offer',
                    'body' => 'Publicación DEMO: producto con imagen para recorrer una compra completa sin usar dinero real.',
                    'comments_enabled' => true,
                    'published_at' => now(),
                ],
            );
            Storage::disk('public')->put('demo/producto-plaza-local.svg', $this->demoImage());
            $post->media()->updateOrCreate(
                ['position' => 0],
                ['type' => 'image', 'disk' => 'public', 'path' => 'demo/producto-plaza-local.svg', 'alt_text' => 'Producto demostrativo de Plaza Local'],
            );

            $order = Order::updateOrCreate(
                ['public_id' => 'd0000000-0000-4000-8000-000000000001'],
                [
                    'buyer_id' => $users['buyer']->id,
                    'vendor_id' => $vendor->id,
                    'status' => 'awaiting_payment',
                    'fulfillment_type' => 'pickup',
                    'subtotal_amount' => 35100,
                    'commission_amount' => 2808,
                    'total_amount' => 37908,
                    'currency' => 'MXN',
                    'buyer_notes' => 'Pedido DEMO. No realizar ningún pago real.',
                ],
            );
            $order->items()->updateOrCreate(
                ['listing_id' => $listing->id],
                ['name_snapshot' => $listing->name, 'quantity' => 2, 'unit_price_amount' => 17550, 'line_total_amount' => 35100],
            );

            $vacancy = JobVacancy::updateOrCreate(
                ['public_id' => 'd0000000-0000-4000-8000-000000000002'],
                [
                    'employer_id' => $users['employer']->id,
                    'community_id' => $community->id,
                    'category_id' => $category->id,
                    'title' => 'Auxiliar DEMO de Plaza Local',
                    'description' => 'Vacante ficticia visible para revisar el módulo de empleo.',
                    'requirements' => 'Disponibilidad y trato respetuoso. No es una oferta laboral real.',
                    'salary_min_amount' => 180000,
                    'salary_max_amount' => 220000,
                    'pay_period' => 'weekly',
                    'work_mode' => 'onsite',
                    'contract_type' => 'temporary',
                    'schedule' => 'Lunes a viernes de 09:00 a 14:00',
                    'vacancies_count' => 1,
                    'publication_fee_amount' => 0,
                    'currency' => 'MXN',
                    'status' => 'published',
                    'paid_at' => now(),
                    'published_at' => now(),
                    'expires_at' => now()->addDays(30),
                ],
            );
            JobApplication::updateOrCreate(
                ['job_vacancy_id' => $vacancy->id, 'applicant_id' => $users['applicant']->id],
                ['cover_letter' => 'Postulación DEMO para comprobar el flujo del candidato.', 'status' => 'submitted'],
            );
        });

        $this->newLine();
        $this->info('Escenario DEMO creado o actualizado. Todos usan esta contraseña temporal:');
        $this->line($password);
        $this->table(['Tipo', 'Correo'], [
            ['Proveedor', 'proveedor.demo@example.test'],
            ['Comprador', 'comprador.demo@example.test'],
            ['Empleador', 'empleador.demo@example.test'],
            ['Postulante', 'postulante.demo@example.test'],
        ]);
        $this->warn('Son datos ficticios visibles. No realices pagos ni cargues documentos reales en estas cuentas.');

        return self::SUCCESS;
    }

    private function demoImage(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800" role="img" aria-labelledby="title description">
<title id="title">Producto demostrativo</title><desc id="description">Imagen de prueba de Plaza Local</desc>
<rect width="1200" height="800" fill="#f7f3ea"/><rect x="70" y="70" width="1060" height="660" rx="48" fill="#123b4a"/>
<circle cx="220" cy="220" r="85" fill="#ff6b19"/><text x="600" y="365" fill="#ffffff" font-family="Arial,sans-serif" font-size="88" text-anchor="middle" font-weight="700">PLAZA LOCAL</text>
<text x="600" y="465" fill="#ffffff" font-family="Arial,sans-serif" font-size="42" text-anchor="middle">PRODUCTO DEMO</text>
<text x="600" y="560" fill="#dce9e5" font-family="Arial,sans-serif" font-size="28" text-anchor="middle">No realizar pagos reales</text>
</svg>
SVG;
    }
}
