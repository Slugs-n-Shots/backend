<?php

namespace App\Http\Controllers;

use App\Models\DrinkUnit;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    /**
     * Fields
     *
     * guest_id: integer
     * recorded_by: ?integer
     * recorded_at: ?datetime
     * made_by: ?integer
     * made_at: ?datetime
     * served_by: ?integer
     * served_at: ?datetime
     * table: ?string
     *
     * Relations
     *
     * guest_id => guest.id
     * recorded_by => employee.id
     * made_by => employee.id
     * served_by => employee.id
     */


    protected static $valid_withs = ['order_details'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $with = [];

        if ($request->with) {
            $with = array_intersect(explode(',', strtolower($request->with)), self::$valid_withs);
        }
        return Order::with($with)->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'guest_id' => 'integer|required',
            'recorded_by' => 'integer|sometimes',
            'recorded_at' => 'datetime|sometimes',
            'made_by' => 'integer|sometimes|nullable',
            'made_at' => 'datetime|sometimes|nullable',
            'served_by' => 'integer|sometimes|nullable',
            'served_at' => 'datetime|sometimes|nullable',
            'table' => 'string|sometimes|nullable',
        ]);
        $order = new Order();
        $order->fill($valid)->save();
        return $order;
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
        return Order::with($with)->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $valid = $request->validate([
            'guest_id' => 'integer|required',
            'recorded_by' => 'integer|sometimes',
            'recorded_at' => 'datetime|sometimes',
            'made_by' => 'integer|sometimes|nullable',
            'made_at' => 'datetime|sometimes|nullable',
            'served_by' => 'integer|sometimes|nullable',
            'served_at' => 'datetime|sometimes|nullable',
            'table' => 'string|sometimes|nullable',
        ]);

        $order->fill($valid)->save();
        return $order;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        return $order->delete();
    }

    public function scheme()
    {
        $order = Order::firstOrNew();

        // if an existing record was found
        if ($order->exists) {
            $order = $order->attributesToArray();
        } else { // otherwise a new model instance was instantiated
            // get the column names for the table
            $columns = Schema::getColumnListing($order->getTable());

            // create array where column names are keys, and values are null
            $columns = array_fill_keys($columns, null);

            // merge the populated values into the base array
            $order = array_merge($columns, $order->attributesToArray());
        }

        return $order;
    }

    public function activeOrders(Request $request)
    {
        if ($request->has('status')) {

        };
        return Order::all();
    }

    public function makeOrder(Request $request)
    {
        $guest = Auth::user();
        $order = Order::create([
            'guest_id' => $guest->id,
            'recorded_at' => now(),
        ]);
        $order->save();
        $total = 0;
        foreach ($request->cart as $item) {
            $drink_unit = DrinkUnit::where('drink_id', $item['drink_id'])
                ->where('quantity', $item['quantity'])
                ->where('unit_en', $item['unit'])->first();
            if ($drink_unit == null) {
                return [
                    'drink_id' => $item['drink_id'],
                    'quantity' => $item['quantity'],
                    'unit_en' => $item['unit'],
                ];
            }
            // return $drink_unit;

            $order_det = OrderDetail::create([
                'order_id' => $order->id,
                'drink_unit_id' => $drink_unit->id,
                'ordered_quantity' => $item['ordered_quantity'],
                'promo_id' => null,
                'unit_price' => $drink_unit->quantity,
                'discount' => 0,
            ]);
            $total += $item['quantity'] * $drink_unit->unit_price;
            $order_det->save();
        }

        $new_order = Order::with(['details', 'details.drinkUnit.drink'])->find($order->id);
        return (object)[
            'message' => __('Your selections are being prepared and will be served shortly. Stay tuned!'),
            'cart' => $request->cart,
            'order' => $new_order,
            'discounts' => [],
            'total' => $total,
        ];
    }
}
