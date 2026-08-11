<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_succeeds_when_dependencies_are_available(): void
    {
        $this->artisan('plaza:health-check')->assertSuccessful()->expectsOutputToContain('[OK] database')->expectsOutputToContain('[OK] storage');
    }
}
