<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\TableMember;
use App\Models\TableSession;
use App\Models\OrderDetail;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableController extends Controller
{
    public function index()
    {
        return Table::orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:64',
            'active' => 'sometimes|boolean',
        ]);

        return Table::create($valid);
    }

    public function show(Table $table)
    {
        return $table;
    }

    public function update(Request $request, Table $table)
    {
        $valid = $request->validate([
            'name' => 'sometimes|required|string|max:64',
            'active' => 'sometimes|boolean',
        ]);

        $table->fill($valid)->save();

        return $table;
    }

    public function destroy(Table $table)
    {
        if ($table->delete()) {
            return response()->noContent();
        }
    }

    public function regenerateGuid(Table $table)
    {
        if ($table->isReserved()) {
            return response()->json([
                'message' => __('Reserved tables cannot receive a new GUID.'),
            ], 409);
        }

        $table->guid = (string) Str::uuid();
        $table->save();

        return $table;
    }

    public function available()
    {
        return [
            'tables' => Table::where('active', true)
                ->whereDoesntHave('openSession')
                ->orderBy('name')
                ->get()
                ->map(fn (Table $table) => $this->guestTableResponse($table)),
        ];
    }

    public function claim(Request $request)
    {
        $valid = $request->validate([
            'guid' => 'required|uuid',
        ]);

        [$claimedTable, $tableSession] = DB::transaction(function () use ($valid) {
            $table = Table::where('guid', $valid['guid'])->lockForUpdate()->first();

            if ($table === null) {
                abort(404);
            }

            if (!$table->active) {
                abort(response()->json([
                    'message' => __('Inactive tables cannot be reserved.'),
                ], 409));
            }

            if ($table->isReserved()) {
                abort(response()->json([
                    'message' => __('This table is already reserved.'),
                ], 409));
            }

            $guest = request()->user();

            $hasCurrentTable = TableSession::where('owner_guest_id', $guest->id)
                ->where('status', TableSession::STATUS_OPEN)
                ->whereNull('closed_at')
                ->exists();

            if ($hasCurrentTable) {
                abort(response()->json([
                    'message' => __('This guest already owns an active table.'),
                ], 409));
            }

            $tableSession = TableSession::create([
                'table_id' => $table->id,
                'owner_guest_id' => $guest->id,
                'business_date' => now()->toDateString(),
                'opened_at' => now(),
                'closed_at' => null,
                'status' => TableSession::STATUS_OPEN,
            ]);

            return [$table->fresh(), $tableSession];
        });

        return [
            'table' => $this->guestTableResponse($claimedTable, true),
            'table_session' => $this->tableSessionResponse($tableSession),
        ];
    }

    public function current()
    {
        $tableSession = TableSession::with('table')
            ->where('owner_guest_id', request()->user()->id)
            ->where('status', TableSession::STATUS_OPEN)
            ->whereNull('closed_at')
            ->first();

        $isOwner = $tableSession !== null;
        if ($tableSession === null) {
            $membership = TableMember::with('tableSession.table')
                ->where('guest_id', request()->user()->id)
                ->where('status', TableMember::STATUS_APPROVED)
                ->whereHas('tableSession', function ($query) {
                    $query->where('status', TableSession::STATUS_OPEN)
                        ->whereNull('closed_at');
                })
                ->first();

            $tableSession = $membership?->tableSession;
        }

        return [
            'table' => $tableSession ? $this->guestTableResponse($tableSession->table, $isOwner) : null,
            'table_session' => $tableSession ? $this->tableSessionResponse($tableSession) : null,
        ];
    }

    public function updateCurrentSpendingLimit(Request $request)
    {
        $valid = $request->validate([
            'owner_spending_limit' => ['required', 'nullable', 'integer', 'min:0'],
        ]);

        $tableSession = TableSession::with('table')
            ->where('owner_guest_id', request()->user()->id)
            ->where('status', TableSession::STATUS_OPEN)
            ->whereNull('closed_at')
            ->first();

        if ($tableSession === null) {
            abort(403);
        }

        $tableSession->owner_spending_limit = $valid['owner_spending_limit'];
        $tableSession->save();

        return $this->tableSessionLimitResponse($tableSession->fresh());
    }

    public function updateStaffSpendingLimit(Request $request, TableSession $tableSession)
    {
        if (!request()->user()->isAdmin()) {
            abort(403);
        }

        if ($tableSession->status !== TableSession::STATUS_OPEN || $tableSession->closed_at !== null) {
            abort(response()->json([
                'message' => __('This table session is already closed.'),
            ], 409));
        }

        $valid = $request->validate([
            'staff_spending_limit_override' => ['required', 'nullable', 'integer', 'min:0'],
        ]);

        $tableSession->fill([
            'staff_spending_limit_override' => $valid['staff_spending_limit_override'],
            'staff_spending_limit_override_set_by' => request()->user()->id,
            'staff_spending_limit_override_set_at' => now(),
        ])->save();

        return $this->tableSessionLimitResponse($tableSession->fresh());
    }

    public function currentStats()
    {
        $tableSession = TableSession::with('table')
            ->where('owner_guest_id', request()->user()->id)
            ->where('status', TableSession::STATUS_OPEN)
            ->whereNull('closed_at')
            ->first();

        if ($tableSession === null) {
            abort(403);
        }

        $payableTotal = $this->pendingTotalForTableSession($tableSession);
        $effectiveLimit = $tableSession->effectiveSpendingLimit();

        return [
            'table_session' => $this->tableSessionResponse($tableSession),
            'stats' => [
                'payable_total' => $payableTotal,
                'effective_spending_limit' => $effectiveLimit,
                'remaining_spending_limit' => $effectiveLimit === null ? null : max($effectiveLimit - $payableTotal, 0),
                'per_guest_consumption' => $this->perGuestConsumptionForTableSession($tableSession),
            ],
        ];
    }

    public function closeCurrent()
    {
        $tableSession = TableSession::with('table')
            ->where('owner_guest_id', request()->user()->id)
            ->where('status', TableSession::STATUS_OPEN)
            ->whereNull('closed_at')
            ->first();

        if ($tableSession === null) {
            abort(403);
        }

        if ($this->pendingTotalForTableSession($tableSession) > 0) {
            abort(response()->json([
                'message' => __('This table still has pending order details.'),
            ], 409));
        }

        $tableSession->close();
        $tableSession->refresh();

        return [
            'table' => $this->guestTableResponse($tableSession->table, true),
            'table_session' => $this->tableSessionResponse($tableSession),
        ];
    }

    private function guestTableResponse(Table $table, bool $isOwner = false): array
    {
        return [
            'id' => $table->id,
            'name' => $table->name,
            'guid' => $table->guid,
            'status' => $table->status,
            'is_owner' => $isOwner,
        ];
    }

    private function tableSessionResponse(TableSession $tableSession): array
    {
        return [
            'id' => $tableSession->id,
            'table_id' => $tableSession->table_id,
            'owner_guest_id' => $tableSession->owner_guest_id,
            'business_date' => $tableSession->business_date?->toDateString(),
            'opened_at' => $tableSession->opened_at?->toJSON(),
            'closed_at' => $tableSession->closed_at?->toJSON(),
            'status' => $tableSession->status,
            'owner_spending_limit' => $tableSession->owner_spending_limit,
            'staff_spending_limit_override' => $tableSession->staff_spending_limit_override,
            'effective_spending_limit' => $tableSession->effectiveSpendingLimit(),
        ];
    }

    private function tableSessionLimitResponse(TableSession $tableSession): array
    {
        return [
            'table_session' => $this->tableSessionResponse($tableSession),
            'limits' => [
                'owner_spending_limit' => $tableSession->owner_spending_limit,
                'default_staff_spending_limit' => config('tables.default_staff_spending_limit'),
                'staff_spending_limit_override' => $tableSession->staff_spending_limit_override,
                'staff_spending_limit' => $tableSession->staffSpendingLimit(),
                'effective_spending_limit' => $tableSession->effectiveSpendingLimit(),
                'pending_total' => $this->pendingTotalForTableSession($tableSession),
            ],
        ];
    }

    private function pendingTotalForTableSession(TableSession $tableSession): int
    {
        return (int) OrderDetail::where('payment_status', OrderDetail::PAYMENT_STATUS_PENDING)
            ->whereHas('order', function ($query) use ($tableSession) {
                $query->where('table_session_id', $tableSession->id);
            })
            ->selectRaw('COALESCE(SUM(unit_price * ordered_quantity), 0) as total')
            ->value('total');
    }

    private function perGuestConsumptionForTableSession(TableSession $tableSession): array
    {
        $orders = Order::with(['guest', 'details'])
            ->where('table_session_id', $tableSession->id)
            ->orderBy('guest_id')
            ->get();

        $consumption = [];

        foreach ($orders as $order) {
            $guestId = $order->guest_id;

            if (!isset($consumption[$guestId])) {
                $consumption[$guestId] = [
                    'guest_id' => $guestId,
                    'name' => $order->guest?->name ?? __('Anonymized guest'),
                    'total' => 0,
                    'payable_total' => 0,
                    'paid_total' => 0,
                ];
            }

            foreach ($order->details as $detail) {
                $lineTotal = $detail->unit_price * $detail->ordered_quantity;
                $consumption[$guestId]['total'] += $lineTotal;

                if ($detail->payment_status === OrderDetail::PAYMENT_STATUS_PAID) {
                    $consumption[$guestId]['paid_total'] += $lineTotal;
                } else {
                    $consumption[$guestId]['payable_total'] += $lineTotal;
                }
            }
        }

        return array_values($consumption);
    }
}
