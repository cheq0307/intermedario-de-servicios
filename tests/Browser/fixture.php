<?php

require __DIR__.'/environment.php';
require $root.'/vendor/autoload.php';
if (! is_dir($root.'/storage/framework/testing')) {
    mkdir($root.'/storage/framework/testing', 0755, true);
}
touch(getenv('DB_DATABASE'));
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $root.'/storage/framework/testing/chat-browser.sqlite') {
    throw new RuntimeException('Refusing to initialize a non-test database.');
}
Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
$users = collect(['ana', 'luis'])->map(fn ($name, $index) => App\Models\User::factory()->create([
    'name' => ucfirst($name).' Prueba', 'email' => $name.'@chat.example.test', 'phone' => '222111111'.($index + 1), 'password' => 'ChatPrueba123',
]));
$post = App\Models\Post::create(['user_id' => $users[0]->id, 'type' => 'offer', 'body' => 'Carrito de tacos para tu fiesta', 'published_at' => now()]);
$chat = App\Models\Conversation::create(['public_id' => '11111111-1111-4111-8111-111111111111', 'post_id' => $post->id, 'type' => 'negotiation', 'expires_at' => now()->addDays(7)]);
$chat->participants()->attach($users->pluck('id')->all());
for ($i = 1; $i <= 65; $i++) {
    $chat->messages()->create(['sender_id' => $users[0]->id, 'body' => 'Mensaje anterior '.str_pad((string) $i, 3, '0', STR_PAD_LEFT)]);
}
$chat->update(['last_message_at' => now()]);
echo "Browser fixtures ready in isolated SQLite database.\n";
