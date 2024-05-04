<?php

namespace App\Http\Controllers;

use App\Models\DrinkUnit;
use Illuminate\Http\Request;

class DrinkUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return DrinkUnit::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'quantity' => 'float|required',
            'unit_en' => 'string|sometimes|nullable',
            'unit_hu' => 'string|sometimes|nullable',
            'active' => 'boolean|sometimes'
        ]);
        $drink = new DrinkUnit();
        $drink->fill($valid)->save();
        return $drink;
    }

    /**
     * Display the specified resource.
     */
    public function show(DrinkUnit $drinkUnit)
    {
        return $drinkUnit;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DrinkUnit $drinkUnit)
    {
        $valid = $request->validate([
            'quantity' => 'float|required',
            'unit_en' => 'string|sometimes|nullable',
            'unit_hu' => 'string|sometimes|nullable',
            'active' => 'boolean|required',
        ]);

        $drinkUnit->fill($valid)->save();
        return $drinkUnit;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DrinkUnit $drinkUnit)
    {
        return $drinkUnit->delete();
    }
}
