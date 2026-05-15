<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use App\Services\GuestRecentDrinkService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GdprRetentionPrune extends Command
{
    protected $signature = 'gdpr:retention-prune
        {--days= : Override configured order personal-data retention days}
        {--before= : Prune records before this date/time instead of calculating from days}
        {--dry-run : Show the affected counts without changing data}';

    protected $description = 'Detach personal guest links from old settled operational records while preserving minimal recent-drink history.';

    public function handle(GuestRecentDrinkService $recentDrinkService): int
    {
        $cutoff = $this->cutoff();
        $dryRun = (bool) $this->option('dry-run');

        $orderIds = $this->eligibleOrdersQuery($cutoff)->pluck('id');
        $receiptIds = $this->eligibleReceiptsQuery($cutoff)->pluck('id');
        $paymentAttemptIds = $this->eligiblePaymentAttemptsQuery($cutoff)->pluck('id');
        $paymentEventIds = $this->eligiblePaymentEventsQuery($cutoff)->pluck('id');

        $this->info('GDPR retention prune');
        $this->line('Cutoff: ' . $cutoff->toJSON());
        $this->line('Dry run: ' . ($dryRun ? 'yes' : 'no'));
        $this->line('Orders to detach: ' . $orderIds->count());
        $this->line('Receipts to detach: ' . $receiptIds->count());
        $this->line('Payment attempts to detach: ' . $paymentAttemptIds->count());
        $this->line('Payment events to detach: ' . $paymentEventIds->count());

        if ($dryRun) {
            return self::SUCCESS;
        }

        DB::transaction(function () use (
            $orderIds,
            $receiptIds,
            $paymentAttemptIds,
            $paymentEventIds,
            $recentDrinkService
        ) {
            Order::with('details.drinkUnit')
                ->whereIn('id', $orderIds)
                ->get()
                ->each(fn (Order $order) => $recentDrinkService->recordOrder($order));

            Order::whereIn('id', $orderIds)->update(['guest_id' => null]);
            Receipt::whereIn('id', $receiptIds)->update(['guest_id' => null]);
            PaymentAttempt::whereIn('id', $paymentAttemptIds)->update(['guest_id' => null]);
            PaymentEvent::whereIn('id', $paymentEventIds)->update(['actor_guest_id' => null]);
        });

        $this->info('GDPR retention prune completed.');

        return self::SUCCESS;
    }

    private function cutoff(): Carbon
    {
        if ($this->option('before') !== null) {
            return Carbon::parse($this->option('before'));
        }

        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('gdpr.order_personal_data_retention_days', 30);

        return now()->subDays(max($days, 0));
    }

    private function eligibleOrdersQuery(Carbon $cutoff)
    {
        return Order::query()
            ->whereNotNull('guest_id')
            ->whereIn('status', [
                Order::STATUS_SERVED,
                Order::STATUS_CANCELLED,
            ])
            ->where(function ($query) use ($cutoff) {
                $query->where('recorded_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff) {
                        $query->whereNull('recorded_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->whereDoesntHave('details', function ($query) {
                $query->where('payment_status', OrderDetail::PAYMENT_STATUS_PENDING);
            });
    }

    private function eligibleReceiptsQuery(Carbon $cutoff)
    {
        return Receipt::query()
            ->whereNotNull('guest_id')
            ->where(function ($query) use ($cutoff) {
                $query->where('paid_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff) {
                        $query->whereNull('paid_at')
                            ->where('issued_at', '<', $cutoff);
                    });
            });
    }

    private function eligiblePaymentAttemptsQuery(Carbon $cutoff)
    {
        return PaymentAttempt::query()
            ->whereNotNull('guest_id')
            ->where('status', '!=', PaymentAttempt::STATUS_PENDING)
            ->where(function ($query) use ($cutoff) {
                $query->where('finished_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff) {
                        $query->whereNull('finished_at')
                            ->where('created_at', '<', $cutoff);
                    });
            });
    }

    private function eligiblePaymentEventsQuery(Carbon $cutoff)
    {
        return PaymentEvent::query()
            ->whereNotNull('actor_guest_id')
            ->where('created_at', '<', $cutoff)
            ->whereHas('paymentAttempt', function ($query) {
                $query->where('status', '!=', PaymentAttempt::STATUS_PENDING);
            });
    }
}
