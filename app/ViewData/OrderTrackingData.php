<?php

namespace App\ViewData;

use App\Domain\Marketplace\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Review;

final readonly class OrderTrackingData
{
    public function __construct(
        public bool $isBuyer,
        public bool $isProduct,
        public string $pageTitle,
        public string $counterpartyLabel,
        public string $counterpartyName,
        public string $itemTitle,
        public string $statusLabel,
        public array $steps,
        public ?string $action,
        public ?string $actionLabel,
        public ?string $actionRouteName,
        public ?Review $ownReview,
        public bool $canCancel,
        public bool $canDispute,
    ) {}

    public static function from(Order $order, int $viewerId): self
    {
        $isBuyer = $order->buyer_id === $viewerId;
        $isProduct = $order->fulfillment_type === 'pickup';
        $status = $order->status;

        return new self(
            isBuyer: $isBuyer,
            isProduct: $isProduct,
            pageTitle: self::pageTitle($status, $isProduct),
            counterpartyLabel: $isBuyer ? 'Proveedor' : 'Cliente',
            counterpartyName: $isBuyer ? $order->vendor->display_name : $order->buyer->name,
            itemTitle: $order->jobRequest?->title ?? $order->items->first()?->name_snapshot ?? 'Operación de Plaza Local',
            statusLabel: self::statusLabel($status),
            steps: self::steps($order, $isProduct),
            action: self::action($status, $isProduct, $isBuyer),
            actionLabel: self::actionLabel($status, $isProduct, $isBuyer),
            actionRouteName: self::actionRouteName($status, $isProduct, $isBuyer),
            ownReview: $order->reviews->firstWhere('author_id', $viewerId),
            canCancel: $status === OrderStatus::Accepted && (! $isProduct || $isBuyer),
            canDispute: in_array($status, [OrderStatus::Paid, OrderStatus::InProgress, OrderStatus::Ready, OrderStatus::Delivered], true),
        );
    }

    private static function pageTitle(OrderStatus $status, bool $isProduct): string
    {
        return match ($status) {
            OrderStatus::Completed => $isProduct ? 'Pedido completado' : 'Servicio completado',
            OrderStatus::Cancelled => $isProduct ? 'Pedido cancelado' : 'Servicio cancelado',
            OrderStatus::Disputed => 'Operación en revisión',
            default => $isProduct ? 'Pedido en proceso' : 'Servicio en proceso',
        };
    }

    private static function statusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'Pendiente',
            OrderStatus::Accepted => 'Confirmada',
            OrderStatus::AwaitingPayment => 'Acuerdo pendiente',
            OrderStatus::Paid => 'Confirmada',
            OrderStatus::InProgress => 'En servicio',
            OrderStatus::Ready => 'Lista para entregar',
            OrderStatus::Delivered => 'Entregada',
            OrderStatus::Completed => 'Completada',
            OrderStatus::Disputed => 'En revisión',
            OrderStatus::Cancelled => 'Cancelada',
            OrderStatus::Refunded => 'Cerrada',
        };
    }

    private static function steps(Order $order, bool $isProduct): array
    {
        $sequence = $isProduct
            ? [OrderStatus::Accepted, OrderStatus::Ready, OrderStatus::Delivered, OrderStatus::Completed]
            : [OrderStatus::Accepted, OrderStatus::InProgress, OrderStatus::Delivered, OrderStatus::Completed];
        $labels = $isProduct
            ? ['Pedido enviado', 'Pedido listo', 'Pedido entregado', 'Pedido completado']
            : ['Solicitud enviada', 'Proveedor confirmado', 'En servicio', 'Servicio completado'];
        $dates = $isProduct
            ? [$order->accepted_at, null, $order->delivered_at, $order->completed_at]
            : [$order->accepted_at, $order->accepted_at, $order->started_at, $order->completed_at];
        $currentIndex = $isProduct
            ? array_search($order->status, $sequence, true)
            : match ($order->status) {
                OrderStatus::Accepted, OrderStatus::AwaitingPayment, OrderStatus::Paid => 1,
                OrderStatus::InProgress => 2,
                OrderStatus::Delivered => 3,
                OrderStatus::Completed => 4,
                default => -1,
            };
        $currentIndex = $currentIndex === false ? -1 : $currentIndex;

        return collect($labels)->map(fn (string $label, int $index): array => [
            'label' => $label,
            'state' => $index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'current' : 'pending'),
            'date' => $dates[$index]?->translatedFormat('j M Y · H:i'),
        ])->all();
    }

    private static function action(OrderStatus $status, bool $isProduct, bool $isBuyer): ?string
    {
        return match (true) {
            $isProduct && ! $isBuyer && $status === OrderStatus::Accepted => 'product_ready',
            $isProduct && ! $isBuyer && $status === OrderStatus::Ready => 'product_deliver',
            ! $isProduct && ! $isBuyer && $status === OrderStatus::Accepted => 'service_start',
            ! $isProduct && ! $isBuyer && $status === OrderStatus::InProgress => 'service_deliver',
            $isBuyer && $status === OrderStatus::Delivered => 'complete',
            default => null,
        };
    }

    private static function actionLabel(OrderStatus $status, bool $isProduct, bool $isBuyer): ?string
    {
        return match (self::action($status, $isProduct, $isBuyer)) {
            'product_ready' => 'Marcar pedido listo',
            'product_deliver' => 'Registrar entrega',
            'service_start' => 'Iniciar servicio',
            'service_deliver' => 'Marcar como entregado',
            'complete' => 'Marcar como completado',
            default => null,
        };
    }

    private static function actionRouteName(OrderStatus $status, bool $isProduct, bool $isBuyer): ?string
    {
        return match (self::action($status, $isProduct, $isBuyer)) {
            'product_ready' => 'products.orders.ready',
            'product_deliver' => 'products.orders.deliver',
            'service_start' => 'orders.start',
            'service_deliver' => 'orders.deliver',
            'complete' => 'orders.complete',
            default => null,
        };
    }
}
