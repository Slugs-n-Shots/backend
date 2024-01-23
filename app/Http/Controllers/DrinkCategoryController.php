<?php

namespace App\Http\Controllers;

use App\Models\DrinkCategory;
use Illuminate\Http\Request;

class DrinkCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return DrinkCategory::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'name_en' => 'string|required|unique:drink_categories,name_en',
            'name_hu' => 'string|required|unique:drink_categories,name_hu',
            'parent' => 'nullable|int|sometimes'
        ]);

        if ($request->parent) {
            $parent = DrinkCategory::findOrFail($request->parent)->first();
            if ($parent->parent) {
                throw new \Exception(__(":parent is invalid as a parent category.", ['parent' => $parent->name]));
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
    public function show(DrinkCategory $category)
    {
        return $category;
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, $id) // DrinkCategory $category
    public function update(Request $request, DrinkCategory $category)
    {
        // $category = DrinkCategory::findOrFail($id);
        $valid = $request->validate([
            'name' => 'string|required|unique:drink_categories',
            'parent' => 'nullable|int:sometimes'
        ]);

        if ($request->parent) {
            $parent = DrinkCategory::findOrFail($request->parent)->first();
            if ($parent->parent) {
                throw new \Exception(__(":category is invalid as a parent category.", ['parent' => $parent->name]));
            }

            $children = DrinkCategory::where('parent', $category->id)->count();
            echo $children;
            if ($children > 0) {
                throw new \Exception(__(":category cannot be subcategory if it has children category already.", ['parent' => $request->name ?? $category->name]));
            }
            if ($request->parent == $category->id) {
                throw new \Exception(__("Invalid main category: :parent.", ['parent' => $request->parent]));
            }
        }

        // return json_encode([
        //     'id' => $category->id,
        //     'request' => $request->all(),
        //     'category' => $category->toArray(),
        //     'parent' => $parent,
        //     'children' => $children
        // ]);

        $category->fill($valid);

        $category->save();
        return $category;
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
