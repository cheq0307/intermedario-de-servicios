<?php

namespace Tests\Unit;

use App\Domain\Marketplace\Enums\OrderStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    #[Test]
    public function an_order_follows_the_expected_marketplace_lifecycle(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Accepted));
        $this->assertTrue(OrderStatus::Accepted->canTransitionTo(OrderStatus::Paid));
        $this->assertFalse(OrderStatus::Paid->canTransitionTo(OrderStatus::Delivered));
        $this->assertTrue(OrderStatus::Delivered->canTransitionTo(OrderStatus::Completed));
    }

    #[Test]
    public function a_completed_order_is_terminal(): void
    {
        $this->assertFalse(OrderStatus::Completed->canTransitionTo(OrderStatus::Refunded));
    }
}
