<?php

namespace App\Http\Controllers;

use App\Models\PromoType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PromoTypeController extends Controller
{
    protected static $valid_withs = [];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $with = [];

        if ($request->with) {
            $with = array_intersect(explode(',', strtolower($request->with)), self::$valid_withs);
        }
        return PromoType::with($with)->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return response($request->all(), 404);
        $valid = $request->validate([
            'description_en' => 'string|sometimes|nullable',
            'description_hu' => 'string|sometimes|nullable',
        ]);
        $promoType = new PromoType();
        $promoType->fill($valid)->save();
        return $promoType;
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
        return PromoType::with($with)->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PromoType $promoType)
    {
        $valid = $request->validate([
            'description_en' => 'string|sometimes|nullable',
            'description_hu' => 'string|sometimes|nullable',
        ]);

        $promoType->fill($valid)->save();
        return $promoType;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PromoType $promoType)
    {
        if ($promoType->delete()) {
            return response()->noContent();
        }
    }

    public function scheme()
    {
        $promoType = PromoType::firstOrNew();

        // if an existing record was found
        if ($promoType->exists) {
            $promoType = $promoType->attributesToArray();
        } else { // otherwise a new model instance was instantiated
            // get the column names for the table
            $columns = Schema::getColumnListing($promoType->getTable());

            // create array where column names are keys, and values are null
            $columns = array_fill_keys($columns, null);

            // merge the populated values into the base array
            $promoType = array_merge($columns, $promoType->attributesToArray());
        }

        return $promoType;
    }
}
