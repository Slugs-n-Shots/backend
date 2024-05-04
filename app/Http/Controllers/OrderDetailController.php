<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use Illuminate\Http\Request;

class OrderDetailController extends Controller
{
    /*
     * Fields:
     * order_id: integer
     * drink_unit_id: integer
     * quantity: integer
     * promo_id: ?integer
     * unit_price: integer
     * discount: ?decimal
     * receipt_id: ?integer
     *
     * Relations
     * order_id => order.id
     * promo_id => promo.id
     * receipt_id => receipt.id
    */
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
        return OrderDetail::with($with)->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return response($request->all(), 404);
        $valid = $request->validate([
            'order_id' => 'integer|required',
            'drink_unit_id' => 'integer|required',
            'quantity' => 'integer|required',
            'promo_id' => 'integer|required',
            'unit_price' => 'integer|required',
            'discount' => 'decimal|required',
            'receipt_id' => 'integer|required',
        ]);
        $orderDetail = new OrderDetail();
        $orderDetail->fill($valid)->save();
        return $orderDetail;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $with = [];

        return OrderDetail::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderDetail $orderDetail)
    {
        $valid = $request->validate([
            'order_id' => 'integer',
            'drink_unit_id' => 'integer',
            'quantity' => 'integer',
            'promo_id' => 'integer',
            'unit_price' => 'integer',
            'discount' => 'decimal',
            'receipt_id' => 'integer',
        ]);

        $orderDetail->fill($valid)->save();
        return $orderDetail;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderDetail $orderDetail)
    {
        return $orderDetail->delete();
    }
}
