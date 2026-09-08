<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        // actingAs bypasses the login controller. When a test switches staff,
        // discard the old login fingerprint just as a fresh admin login does.
        if ($guard === 'admin' && $this->app['auth']->guard('admin')->id() !== $user->getAuthIdentifier()) {
            $this->app['session']->forget('password_hash_admin');
        }

        return parent::actingAs($user, $guard);
    }
}
