<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis quand le statut d'une commande en ligne change (back-office).
 * Permet à la cloche de notification de recompter en temps réel.
 */
class OnlineOrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $orderId;
    public string $status;

    public function __construct(int $orderId, string $status)
    {
        $this->orderId = $orderId;
        $this->status  = $status;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('online-orders');
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    public function broadcastWith(): array
    {
        return ['order_id' => $this->orderId, 'status' => $this->status];
    }
}
