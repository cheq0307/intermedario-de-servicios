<?php

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Community;
use App\Models\Conversation;
use App\Models\Post;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/environment.php';
require $root.'/vendor/autoload.php';
if (! is_dir($root.'/storage/framework/testing')) {
    mkdir($root.'/storage/framework/testing', 0755, true);
}
touch(getenv('DB_DATABASE'));
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $root.'/storage/framework/testing/chat-browser.sqlite') {
    throw new RuntimeException('Refusing to initialize a non-test database.');
}
Artisan::call('migrate:fresh', ['--force' => true]);
$community = Community::create([
    'name' => 'Comunidad E2E',
    'municipality' => 'Municipio de Prueba',
    'state' => 'Puebla',
    'postal_code' => '74140',
    'default_radius_km' => 8,
    'is_active' => true,
]);
$category = Category::create([
    'name' => 'Servicios E2E',
    'slug' => 'servicios-e2e',
    'is_active' => true,
]);
$users = collect(['ana', 'luis'])->map(fn ($name, $index) => User::factory()->create([
    'name' => ucfirst($name).' Prueba', 'email' => $name.'@chat.example.test', 'phone' => '222111111'.($index + 1),
    'community_id' => $community->id, 'password' => 'ChatPrueba123',
]));
$post = Post::create(['user_id' => $users[0]->id, 'type' => 'offer', 'body' => 'Carrito de tacos para tu fiesta', 'published_at' => now()]);
$chat = Conversation::create(['public_id' => '11111111-1111-4111-8111-111111111111', 'post_id' => $post->id, 'type' => 'negotiation', 'expires_at' => now()->addDays(7)]);
$chat->participants()->attach($users->pluck('id')->all());
for ($i = 1; $i <= 65; $i++) {
    $chat->messages()->create(['sender_id' => $users[0]->id, 'body' => 'Mensaje anterior '.str_pad((string) $i, 3, '0', STR_PAD_LEFT)]);
}
$chat->update(['last_message_at' => now()]);
$marketplaceUsers = collect([
    ['provider', 'Proveedor E2E', 'provider-e2e@example.test', '2222000001'],
    ['buyer', 'Comprador E2E', 'buyer-e2e@example.test', '2222000002'],
    ['employer', 'Empleador E2E', 'employer-e2e@example.test', '2222000003'],
    ['applicant', 'Postulante E2E', 'applicant-e2e@example.test', '2222000004'],
])->mapWithKeys(function (array $record) use ($community): array {
    [$key, $name, $email, $phone] = $record;

    return [$key => User::factory()->create([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'community_id' => $community->id,
        'password' => 'FlujoE2E123',
    ])];
});
$vendor = Vendor::create([
    'user_id' => $marketplaceUsers['provider']->id,
    'display_name' => 'Negocio E2E',
    'slug' => 'negocio-e2e',
    'description' => 'Negocio utilizado exclusivamente para pruebas integrales.',
    'specialty' => 'Productos y servicios de prueba',
    'service_area' => 'Comunidad E2E',
    'business_hours' => ['days' => ['monday'], 'opens_at' => '09:00', 'closes_at' => '18:00'],
    'status' => 'active',
]);
$vendor->categories()->attach($category->id);
AdminUser::factory()->superadmin()->create([
    'name' => 'Administración E2E',
    'email' => 'admin-e2e@example.test',
    'phone' => '2222000005',
    'password' => 'FlujoE2E123',
]);
echo "Browser fixtures ready in isolated SQLite database.\n";
