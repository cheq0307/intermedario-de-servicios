<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Models\User;

final class DemoMode
{
    public function enabled(): bool
    {
        return (bool) config('demo.enabled') && app()->environment(['local', 'testing']);
    }

    public function ensureEnabled(): void
    {
        abort_unless($this->enabled(), 404);
    }

    public function isDemoIdentity(User|AdminUser $identity): bool
    {
        return str_ends_with(strtolower($identity->email), '.demo@example.test');
    }

    public function enforcePaymentFreeRelease(): void
    {
        if (! $this->enabled()) {
            return;
        }

        config([
            'marketplace.payments_enabled' => false,
            'marketplace.allow_fake_payments' => false,
        ]);
    }
}
