<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductScanned implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public array $cartItem;
    public int $userId;

    public function __construct(array $cartItem, int $userId)
    {
        $this->cartItem = $cartItem;
        $this->userId   = $userId;
    }

    /**
     * Canal PUBLIC (accessible à tous)
     */
    public function broadcastOn(): Channel
    {
        return new Channel("cart.{$this->userId}");
    }

    /**
     * Nom de l’événement côté frontend
     */
    public function broadcastAs(): string
    {
        return 'product.scanned';
    }

    /**
     * Données envoyées
     */
    public function broadcastWith(): array
    {
        return [
            'cart_item' => $this->cartItem,
            'user_id'   => $this->userId,
        ];
    }
}
