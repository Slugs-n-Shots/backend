<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\GuestRecentDrink;
use App\Models\GdprAuditEvent;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuestAnonymizationService
{
    public function check(Guest $guest): array
    {
        $blockingReasons = $this->blockingReasons($guest);

        return [
            'can_anonymize' => $blockingReasons === [],
            'blocking_reasons' => $blockingReasons,
        ];
    }

    public function anonymize(
        Guest $guest,
        string $reason = 'guest_request',
        ?Guest $actorGuest = null,
        bool $useGuestAsActor = true
    ): array
    {
        if ($useGuestAsActor && $actorGuest === null) {
            $actorGuest = $guest;
        }

        return DB::transaction(function () use ($guest, $reason, $actorGuest) {
            $lockedGuest = Guest::whereKey($guest->id)->lockForUpdate()->firstOrFail();
            $check = $this->check($lockedGuest);

            if (!$check['can_anonymize']) {
                $this->recordAuditEvent(
                    GdprAuditEvent::TYPE_ANONYMIZATION_BLOCKED,
                    $lockedGuest,
                    'blocked',
                    $check['blocking_reasons'],
                    $actorGuest
                );

                return $check;
            }

            $oldPicture = $lockedGuest->picture;
            $data = $lockedGuest->data ?? [];
            unset(
                $data['confirm_token'],
                $data['confirm_exp'],
                $data['pw_reset_token'],
                $data['pw_reset_exp']
            );
            $data['anonymization'] = [
                'status' => 'completed',
                'completed_at' => now()->toISOString(),
            ];

            $lockedGuest->forceFill([
                'first_name' => 'Anonimizált',
                'middle_name' => null,
                'last_name' => 'Vendég',
                'email' => "deleted-guest-{$lockedGuest->id}@anonymized.local",
                'picture' => null,
                'active' => false,
                'email_verified_at' => null,
                'age_verified_at' => null,
                'birth_date' => null,
                'phone' => null,
                'address' => null,
                'password' => Hash::make(Str::random(64)),
                'anonymized_at' => now(),
                'anonymization_reason' => $reason,
                'data' => $data,
            ])->save();

            Receipt::where('guest_id', $lockedGuest->id)->update([
                'guest_id' => null,
            ]);
            Order::where('guest_id', $lockedGuest->id)->update([
                'guest_id' => null,
            ]);
            PaymentAttempt::where('guest_id', $lockedGuest->id)->update([
                'guest_id' => null,
            ]);
            PaymentEvent::where('actor_guest_id', $lockedGuest->id)->update([
                'actor_guest_id' => null,
            ]);
            GuestRecentDrink::where('guest_id', $lockedGuest->id)->delete();
            DB::afterCommit(function () use ($oldPicture) {
                $this->deleteStoredGuestPicture($oldPicture);
            });

            $this->recordAuditEvent(
                GdprAuditEvent::TYPE_ANONYMIZATION_COMPLETED,
                $lockedGuest,
                'completed',
                [],
                $actorGuest
            );

            return [
                'can_anonymize' => true,
                'blocking_reasons' => [],
            ];
        });
    }

    private function deleteStoredGuestPicture(?string $path): void
    {
        if ($path !== null && str_starts_with($path, 'guest-pictures/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function blockingReasons(Guest $guest): array
    {
        $reasons = [];

        if ($guest->anonymized_at !== null) {
            $reasons[] = [
                'code' => 'already_anonymized',
                'message' => __('The account has already been anonymized.'),
            ];
        }

        if ($this->hasOpenOwnedTable($guest)) {
            $reasons[] = [
                'code' => 'open_table_owner',
                'message' => __('You have an open table as owner.'),
            ];
        }

        if ($this->hasOpenTableMembership($guest)) {
            $reasons[] = [
                'code' => 'open_table_membership',
                'message' => __('You have an active or pending table membership.'),
            ];
        }

        if ($this->hasPendingPayment($guest)) {
            $reasons[] = [
                'code' => 'pending_payment',
                'message' => __('You have order items waiting for payment.'),
            ];
        }

        if ($this->hasActiveOrder($guest)) {
            $reasons[] = [
                'code' => 'active_order',
                'message' => __('You have an order in progress.'),
            ];
        }

        return $reasons;
    }

    private function hasOpenOwnedTable(Guest $guest): bool
    {
        return TableSession::query()
            ->where('owner_guest_id', $guest->id)
            ->where('status', TableSession::STATUS_OPEN)
            ->exists();
    }

    private function hasOpenTableMembership(Guest $guest): bool
    {
        return TableMember::query()
            ->where('guest_id', $guest->id)
            ->whereIn('status', [
                TableMember::STATUS_PENDING,
                TableMember::STATUS_APPROVED,
            ])
            ->whereHas('tableSession', function ($query) {
                $query->where('status', TableSession::STATUS_OPEN);
            })
            ->exists();
    }

    private function hasPendingPayment(Guest $guest): bool
    {
        return OrderDetail::query()
            ->where('payment_status', OrderDetail::PAYMENT_STATUS_PENDING)
            ->whereHas('order', function ($query) use ($guest) {
                $query->where('guest_id', $guest->id);
            })
            ->exists();
    }

    private function hasActiveOrder(Guest $guest): bool
    {
        return Order::query()
            ->where('guest_id', $guest->id)
            ->whereIn('status', [
                Order::STATUS_OPEN,
                Order::STATUS_PREPARING,
                Order::STATUS_READY,
            ])
            ->exists();
    }

    private function recordAuditEvent(
        string $eventType,
        Guest $guest,
        string $status,
        array $blockingReasons = [],
        ?Guest $actorGuest = null
    ): void {
        GdprAuditEvent::create([
            'event_type' => $eventType,
            'guest_id' => $guest->id,
            'actor_guest_id' => $actorGuest?->id,
            'status' => $status,
            'masked_email' => $this->auditMaskedEmail($guest),
            'blocking_reason_count' => count($blockingReasons),
            'blocking_reason_codes' => array_values(array_map(
                static fn (array $reason) => $reason['code'] ?? 'unknown',
                $blockingReasons
            )),
            'created_at' => now(),
        ]);
    }

    private function auditMaskedEmail(Guest $guest): string
    {
        return $guest->anonymized_at === null
            ? "guest-{$guest->id}@masked.local"
            : $guest->email;
    }
}
