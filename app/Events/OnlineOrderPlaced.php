<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis lorsqu'un client passe une commande en ligne depuis la boutique.
 * Permet au point de vente d'être notifié en temps réel (Pusher).
 */
class OnlineOrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public array $order;

    public function __construct(array $order)
    {
        $this->order = $order;
    }

    /**
     * Canal PUBLIC écouté par le tableau de bord.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('online-orders');
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }

    public function broadcastWith(): array
    {
        return ['order' => $this->order];
    }
}
