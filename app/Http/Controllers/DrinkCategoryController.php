<?php

namespace App\Http\Controllers;

use App\Models\DrinkCategory;
use Illuminate\Http\Request;

class DrinkCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $visible = [];
        $hidden = [];

        if ($request->nolang) {
            $visible = [
                'name_en',
                'name_hu',
            ];
            $hidden = [
                'name',
            ];
        }

        return DrinkCategory::get()->makeVisible($visible)->makeHidden($hidden);
    }

    public function parents(Request $request)
    {
        $visible = [];
        $hidden = [];

        if ($request->nolang) {
            $visible = [
                'name_en',
                'name_hu',
            ];
            $hidden = [
                'name',
            ];
        }

        return DrinkCategory::whereNull('parent_id')->get()->makeVisible($visible)->makeHidden($hidden);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'name_en' => 'string|required|unique:drink_categories,name_en',
            'name_hu' => 'string|required|unique:drink_categories,name_hu',
            'parent_id' => 'nullable|int|sometimes'
        ]);

        // nem lehet az adott szülőt beállítani, az nem főkategória
        if ($request->parent_id) {
            $parent = DrinkCategory::find($request->parent_id);
            if ($parent && $parent->parent_id) {
                throw new \Exception(__("Only a main category can be a parent."));
            }
        }

        $category = new DrinkCategory();
        $category->fill($valid);
        $category->save();
        return $category;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $visible = [];
        $hidden = [];

        if ($request->nolang) {
            $visible = [
                'name_en',
                'name_hu',
            ];
            $hidden = [
                'name',
            ];
        }

        return DrinkCategory::findOrFail($id)->makeVisible($visible)->makeHidden($hidden);
    }

    /**
     * Display the specified resource.
     */
    public function drinks(DrinkCategory $category)
    {
        return $category->drinks;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DrinkCategory $category)
    {
        $valid = $request->validate([
            'name_en' => 'string|required|unique:drink_categories,name_en,' . $category->id,
            'name_hu' => 'string|required|unique:drink_categories,name_hu,' . $category->id,
            'parent_id' => 'nullable|int:sometimes'
        ]);

        if ($request->parent_id) {
            // nem lehet önmaga gyereke
            if ($category->id == $category->parent_id) {
                throw new \Exception(__("A category cannot be parent of itself."));
            }
            // nem lehet az adott szülőt beállítani, ha a szülő nem főkategória
            $parent = DrinkCategory::find($request->parent_id);
            if ($parent && $parent->parent_id) {
                throw new \Exception(__("Only a main category can be a parent."));
            }

            // nem lehet szülőt beállítani, ha már más kategóriáknak szülője a kategória
            $children = DrinkCategory::where('parent_id', $category->id)->count();
            if ($children > 0) {
                throw new \Exception(__(":category cannot be subcategory if it has children category already.", ['parent' => $request->name ?? $category->name]));
            }
        }

        $category->fill($valid);
        $category->save();
        return $this->show($request, $category->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) // DrinkCategory $category
    {
        $category = DrinkCategory::findOrFail($id);
        $category->delete();
        return $category;
    }
}
