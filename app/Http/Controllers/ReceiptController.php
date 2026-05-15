<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\TableMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ReceiptController extends Controller
{
    protected static $valid_withs = ['category', 'units'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $with = [];

        if ($request->with) {
            $with = array_intersect(explode(',', strtolower($request->with)), self::$valid_withs);
        }
        return Receipt::with($with)->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'serno' => 'string|required',
            'guest_id' => 'integer|required',
            'issued_at' => 'date|required',
            'paid_for' => 'integer|required',
            'paid_at' => 'date|required',
            'payment_method' => ['string', 'required', Rule::in(Receipt::PAYMENT_METHODS)],
            'table' => 'string|sometimes|nullable',
        ]);
        $receipt = new Receipt();
        $receipt->fill($valid)->save();
        return $receipt;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $with = [];

        if ($request->with) {
            $with = array_intersect(explode(',', strtolower($request->with)), self::$valid_withs);
        }
        return Receipt::with($with)->findOrFail($id);
    }

    public function guestShow(Request $request, Receipt $receipt)
    {
        $guest = $request->user();

        if ($receipt->guest_id !== $guest->id && !$this->guestCanAccessTableReceipt($guest->id, $receipt)) {
            abort(403);
        }

        return $receipt->load([
            'details.order',
            'details.drinkUnit.drink',
            'tableSession.table',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Receipt $receipt)
    {
        $valid = $request->validate([
            'serno' => 'string|required',
            'guest_id' => 'integer|required',
            'issued_at' => 'date|required',
            'paid_for' => 'integer|required',
            'paid_at' => 'date|required',
            'payment_method' => ['string', 'required', Rule::in(Receipt::PAYMENT_METHODS)],
            'table' => 'string|sometimes|nullable',
        ]);

        $receipt->fill($valid)->save();
        return $receipt;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Receipt $receipt)
    {
        if ($receipt->delete()) {
            return response()->noContent();
        }
    }

    public function scheme()
    {
        $receipt = Receipt::firstOrNew();

        // if an existing record was found
        if ($receipt->exists) {
            $receipt = $receipt->attributesToArray();
        } else { // otherwise a new model instance was instantiated
            // get the column names for the table
            $columns = Schema::getColumnListing($receipt->getTable());

            // create array where column names are keys, and values are null
            $columns = array_fill_keys($columns, null);

            // merge the populated values into the base array
            $receipt = array_merge($columns, $receipt->attributesToArray());
        }

        return $receipt;
    }

    private function guestCanAccessTableReceipt(int $guestId, Receipt $receipt): bool
    {
        if ($receipt->table_session_id === null) {
            return false;
        }

        $receipt->loadMissing('tableSession');

        if ($receipt->tableSession?->owner_guest_id === $guestId) {
            return true;
        }

        return TableMember::where('table_session_id', $receipt->table_session_id)
            ->where('guest_id', $guestId)
            ->where('status', TableMember::STATUS_APPROVED)
            ->exists();
    }
}
