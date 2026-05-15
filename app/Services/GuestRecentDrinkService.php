<?php

namespace App\Services;

use App\Models\GuestRecentDrink;
use App\Models\Order;
use Illuminate\Support\Carbon;

class GuestRecentDrinkService
{
    public function recordOrder(Order $order): void
    {
        if ($order->guest_id === null) {
            return;
        }

        $order->loadMissing('details.drinkUnit');
        $orderedAt = $order->recorded_at ?? $order->created_at ?? now();

        $drinkIds = $order->details
            ->map(fn ($detail) => $detail->drinkUnit?->drink_id)
            ->filter()
            ->unique()
            ->values();

        foreach ($drinkIds as $drinkId) {
            $this->recordDrink($order->guest_id, $drinkId, $orderedAt);
        }

        $this->pruneExcess($order->guest_id);
    }

    private function recordDrink(int $guestId, int $drinkId, Carbon $orderedAt): void
    {
        $recentDrink = GuestRecentDrink::firstOrNew([
            'guest_id' => $guestId,
            'drink_id' => $drinkId,
        ]);

        $recentDrink->order_count = (int) $recentDrink->order_count + 1;

        if ($recentDrink->last_ordered_at === null || $orderedAt->greaterThan($recentDrink->last_ordered_at)) {
            $recentDrink->last_ordered_at = $orderedAt;
        }

        $recentDrink->save();
    }

    private function pruneExcess(int $guestId): void
    {
        $limit = (int) config('gdpr.recent_drinks_per_guest', 10);

        if ($limit < 1) {
            GuestRecentDrink::where('guest_id', $guestId)->delete();
            return;
        }

        $idsToKeep = GuestRecentDrink::where('guest_id', $guestId)
            ->orderByDesc('last_ordered_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('id');

        GuestRecentDrink::where('guest_id', $guestId)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
