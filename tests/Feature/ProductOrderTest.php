<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_creates_order_and_inventory_is_reserved(): void
    {
        [$buyer, , $listing] = $this->scenario(stock: 5);

        $response = $this->actingAs($buyer)->post(route('products.orders.store', $listing), [
            'quantity' => 2,
            'buyer_notes' => 'Paso por el producto esta tarde.',
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame('awaiting_payment', $order->status->value);
        $this->assertSame(3, $listing->fresh()->stock);
        $this->assertSame(30000, $order->subtotal_amount);
        $this->assertSame(2400, $order->commission_amount);
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'quantity' => 2, 'status' => 'active']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'provider' => 'fake', 'status' => 'pending']);
    }

    public function test_product_order_follows_payment_and_delivery_lifecycle(): void
    {
        [$buyer, $provider, $listing] = $this->scenario();
        $this->actingAs($buyer)->post(route('products.orders.store', $listing), ['quantity' => 1]);
        $order = Order::firstOrFail();

        $this->actingAs($provider)->post(route('products.orders.simulate-payment', $order))->assertForbidden();
        $this->actingAs($buyer)->post(route('products.orders.simulate-payment', $order))->assertRedirect();
        $this->assertSame('paid', $order->fresh()->status->value);
        $this->assertSame('paid', $order->payments()->firstOrFail()->status->value);
        $this->assertSame('consumed', $order->inventoryReservation()->firstOrFail()->status);

        $this->actingAs($provider)->patch(route('products.orders.ready', $order))->assertRedirect();
        $this->assertSame('ready', $order->fresh()->status->value);
        $this->actingAs($provider)->patch(route('products.orders.deliver', $order))->assertRedirect();
        $this->assertSame('delivered', $order->fresh()->status->value);
        $this->actingAs($buyer)->patch(route('orders.complete', $order))->assertRedirect();
        $this->assertSame('completed', $order->fresh()->status->value);
    }

    public function test_buyer_can_cancel_before_payment_and_stock_is_restored_once(): void
    {
        [$buyer, , $listing] = $this->scenario(stock: 2);
        $this->actingAs($buyer)->post(route('products.orders.store', $listing), ['quantity' => 2]);
        $order = Order::firstOrFail();

        $this->actingAs($buyer)->patch(route('products.orders.cancel', $order))->assertRedirect();

        $this->assertSame(2, $listing->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status->value);
        $this->assertSame('released', $order->inventoryReservation()->firstOrFail()->status);
        $this->actingAs($buyer)->patch(route('products.orders.cancel', $order))->assertStatus(422);
        $this->assertSame(2, $listing->fresh()->stock);
    }

    public function test_expired_reservation_is_cancelled_and_stock_restored(): void
    {
        [$buyer, , $listing] = $this->scenario(stock: 2);
        $this->actingAs($buyer)->post(route('products.orders.store', $listing), ['quantity' => 1]);
        $order = Order::firstOrFail();
        $order->inventoryReservation()->update(['expires_at' => now()->subMinute()]);

        $this->artisan('plaza:expire-reservations')->assertSuccessful()->expectsOutput('Reservas liberadas: 1');

        $this->assertSame(2, $listing->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status->value);
        $this->assertSame('cancelled', $order->payments()->firstOrFail()->status->value);
    }

    public function test_invalid_or_unsafe_product_purchases_are_rejected(): void
    {
        [$buyer, $provider, $listing] = $this->scenario(stock: 1);

        $this->actingAs($provider)->post(route('products.orders.store', $listing), ['quantity' => 1])->assertForbidden();
        $this->actingAs($buyer)->post(route('products.orders.store', $listing), ['quantity' => 2])->assertStatus(422);

        $listing->vendor->update(['status' => 'suspended']);
        $this->actingAs($buyer)->get(route('products.checkout', $listing))->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
    }

    private function scenario(int $stock = 4): array
    {
        $buyer = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Abarrotes de prueba',
            'slug' => 'abarrotes-'.Str::lower(Str::random(8)),
            'status' => 'active',
            'commission_rate_basis_points' => 800,
        ]);
        $listing = Listing::create([
            'vendor_id' => $vendor->id,
            'type' => 'product',
            'name' => 'Producto local',
            'slug' => 'producto-local',
            'description' => 'Producto preparado por un comercio de la comunidad.',
            'price_type' => 'fixed',
            'price_amount' => 15000,
            'currency' => 'MXN',
            'stock' => $stock,
            'is_active' => true,
        ]);

        return [$buyer, $provider, $listing];
    }
}
