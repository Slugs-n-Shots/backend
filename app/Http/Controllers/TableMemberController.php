<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableMemberController extends Controller
{
    public function join(Request $request)
    {
        $valid = $request->validate([
            'guid' => 'required|uuid',
        ]);

        $membership = DB::transaction(function () use ($valid) {
            $table = Table::where('guid', $valid['guid'])->lockForUpdate()->first();

            if ($table === null) {
                abort(404);
            }

            if (!$table->active) {
                return $this->conflict(__('Inactive tables cannot be joined.'));
            }

            $tableSession = $table->openSession()->lockForUpdate()->first();
            if ($tableSession === null) {
                return $this->conflict(__('This table is not reserved.'));
            }

            $guest = request()->user();
            if ($tableSession->owner_guest_id === $guest->id) {
                return $this->conflict(__('The table owner cannot join as a member.'));
            }

            $membership = TableMember::where('table_session_id', $tableSession->id)
                ->where('guest_id', $guest->id)
                ->lockForUpdate()
                ->first();

            if ($membership !== null) {
                if (in_array($membership->status, [
                    TableMember::STATUS_PENDING,
                    TableMember::STATUS_APPROVED,
                    TableMember::STATUS_DENIED,
                ], true)) {
                    return $this->conflict(__('This guest already has a membership state for this table.'));
                }

                $membership->fill([
                    'status' => TableMember::STATUS_PENDING,
                    'can_order' => true,
                    'approved_by_guest_id' => null,
                    'approved_at' => null,
                    'removed_at' => null,
                ])->save();

                return $membership->fresh();
            }

            return TableMember::create([
                'table_session_id' => $tableSession->id,
                'guest_id' => $guest->id,
                'role' => TableMember::ROLE_MEMBER,
                'status' => TableMember::STATUS_PENDING,
                'can_order' => true,
            ]);
        });

        return [
            'membership' => $this->memberResponse($membership),
        ];
    }

    public function members()
    {
        [$tableSession, $isOwner] = $this->currentTableSessionForUser();

        if ($tableSession === null) {
            abort(404);
        }

        $tableSession->load(['ownerGuest', 'approvedMembers.guest', 'pendingMembers.guest']);

        return [
            'members' => array_merge(
                [$this->ownerResponse($tableSession)],
                $tableSession->approvedMembers->map(fn (TableMember $member) => $this->memberListResponse($member))->all()
            ),
            'pending' => $isOwner
                ? $tableSession->pendingMembers->map(fn (TableMember $member) => $this->memberListResponse($member))->all()
                : [],
        ];
    }

    public function approve(TableMember $member)
    {
        $this->ensureOwner($member);

        if ($member->status !== TableMember::STATUS_PENDING) {
            return $this->conflict(__('Only pending memberships can be approved.'));
        }

        $member->fill([
            'status' => TableMember::STATUS_APPROVED,
            'approved_by_guest_id' => request()->user()->id,
            'approved_at' => now(),
            'can_order' => true,
        ])->save();

        return [
            'membership' => $this->memberResponse($member->fresh()),
        ];
    }

    public function reject(TableMember $member)
    {
        $this->ensureOwner($member);

        if ($member->status !== TableMember::STATUS_PENDING) {
            return $this->conflict(__('Only pending memberships can be rejected.'));
        }

        $member->delete();

        return response()->noContent();
    }

    public function toggleOrdering(Request $request, TableMember $member)
    {
        $valid = $request->validate([
            'can_order' => 'required|boolean',
        ]);

        $this->ensureOwner($member);

        if ($member->status !== TableMember::STATUS_APPROVED) {
            return $this->conflict(__('Only approved memberships can change ordering permission.'));
        }

        $member->can_order = $valid['can_order'];
        $member->save();

        return [
            'membership' => $this->memberResponse($member->fresh()),
        ];
    }

    public function destroy(TableMember $member)
    {
        $this->ensureOwner($member);

        if ($member->role === TableMember::ROLE_OWNER) {
            return $this->conflict(__('The owner cannot be removed with this endpoint.'));
        }

        if ($member->status !== TableMember::STATUS_APPROVED) {
            return $this->conflict(__('Only approved memberships can be removed.'));
        }

        $member->fill([
            'status' => TableMember::STATUS_REMOVED,
            'can_order' => false,
            'removed_at' => now(),
        ])->save();

        return [
            'membership' => $this->memberResponse($member->fresh()),
        ];
    }

    private function currentTableSessionForUser(): array
    {
        $guest = request()->user();

        $ownedSession = TableSession::where('owner_guest_id', $guest->id)
            ->where('status', TableSession::STATUS_OPEN)
            ->whereNull('closed_at')
            ->first();

        if ($ownedSession !== null) {
            return [$ownedSession, true];
        }

        $membership = TableMember::with('tableSession')
            ->where('guest_id', $guest->id)
            ->where('status', TableMember::STATUS_APPROVED)
            ->whereHas('tableSession', function ($query) {
                $query->where('status', TableSession::STATUS_OPEN)
                    ->whereNull('closed_at');
            })
            ->first();

        return [$membership?->tableSession, false];
    }

    private function ensureOwner(TableMember $member): void
    {
        $member->loadMissing('tableSession');

        if ($member->tableSession->owner_guest_id !== request()->user()->id) {
            abort(403);
        }
    }

    private function ownerResponse(TableSession $tableSession): array
    {
        return [
            'id' => null,
            'guest_id' => $tableSession->owner_guest_id,
            'name' => $tableSession->ownerGuest->name,
            'role' => TableMember::ROLE_OWNER,
            'status' => TableMember::STATUS_APPROVED,
            'can_order' => true,
        ];
    }

    private function memberListResponse(TableMember $member): array
    {
        return [
            'id' => $member->id,
            'guest_id' => $member->guest_id,
            'name' => $member->guest->name,
            'role' => $member->role,
            'status' => $member->status,
            'can_order' => $member->can_order,
        ];
    }

    private function memberResponse(TableMember $member): array
    {
        return [
            'id' => $member->id,
            'table_session_id' => $member->table_session_id,
            'guest_id' => $member->guest_id,
            'role' => $member->role,
            'status' => $member->status,
            'can_order' => $member->can_order,
            'approved_by_guest_id' => $member->approved_by_guest_id,
            'approved_at' => $member->approved_at?->toJSON(),
            'removed_at' => $member->removed_at?->toJSON(),
        ];
    }

    private function conflict(string $message)
    {
        abort(response()->json(['message' => $message], 409));
    }
}
