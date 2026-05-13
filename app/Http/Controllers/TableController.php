<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\TableMember;
use App\Models\TableSession;
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
        ];
    }
}
