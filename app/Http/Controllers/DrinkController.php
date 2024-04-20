<?php

namespace App\Http\Controllers;

use App\Models\Drink;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DrinkController extends Controller
{
    protected static $valid_withs = ['category', 'units'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $with = [];
        $visible = [];
        $hidden = [];

        if ($request->nolang) {
            $visible = [
                'name_en',
                'name_hu',
                'description_en',
                'description_hu',
            ];
            $hidden = [
                'name',
                'description'
            ];
        }
        if ($request->with) {
            $with = array_intersect(explode(',', strtolower($request->with)), self::$valid_withs);
        }
        return Drink::with($with)->get()->makeVisible($visible)->makeHidden($hidden);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return response($request->all(), 404);
        $valid = $request->validate([
            'name_en' => 'string|required|unique:drinks,name_en',
            'name_hu' => 'string|required|unique:drinks,name_hu',
            'category_id' => 'integer|required',
            'description_en' => 'string|sometimes|nullable',
            'description_hu' => 'string|sometimes|nullable',
            'active' => 'boolean|sometimes',
        ]);
        $drink = new Drink();
        $drink->fill($valid)->save();
        event(new \App\Events\DrinkCreated($drink));
        return $drink;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $with = [];
        $visible = [];
        $hidden = [];

        if ($request->nolang) {
            $visible = [
                'name_en',
                'name_hu',
                'description_en',
                'description_hu',
            ];
            $hidden = [
                'name',
                'description'
            ];
        }

        if ($request->with) {
            $with = array_intersect(explode(',', strtolower($request->with)), self::$valid_withs);
        }
        return Drink::with($with)->findOrFail($id)->makeVisible($visible)->makeHidden($hidden);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Drink $drink)
    {
        $valid = $request->validate([
            'name_en' => 'string|sometimes|unique:drinks,name_en,'.$drink->id,
            'name_hu' => 'string|sometimes|unique:drinks,name_hu,'.$drink->id,
            'category_id' => 'integer|sometimes',
            'description_en' => 'string|sometimes|nullable',
            'description_hu' => 'string|sometimes|nullable',
            'active' => 'boolean|sometimes',
        ]);

        $drink->fill($valid)->save();
        event(new \App\Events\DrinkUpdated($drink));
        return $this->show($request, $drink->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Drink $drink)
    {
        $id = $drink->id;
        if ($drink->delete()) {
            event(new \App\Events\DrinkDeleted(Drink::class, $id));
            return response()->noContent();
        }
    }

    public function scheme()
    {
        $drink = Drink::firstOrNew();

        // if an existing record was found
        if ($drink->exists) {
            $drink = $drink->attributesToArray();
        } else { // otherwise a new model instance was instantiated
            // get the column names for the table
            $columns = Schema::getColumnListing($drink->getTable());

            // create array where column names are keys, and values are null
            $columns = array_fill_keys($columns, null);

            // merge the populated values into the base array
            $drink = array_merge($columns, $drink->attributesToArray());
        }

        return $drink;
    }

    public function menu(Request $request)
    {
        return Drink::where('active', true)->get()->append('category_name')->append('units')->makeHidden(['category', 'active']);
    }

    public function menuTree(Request $request)
    {
        $locale = app()->getLocale();
        $cached = Cache::get("drink-menu-tree-{$locale}");

        if ($cached) {
            $response = $cached;
        } else {
            $categories = \App\Models\DrinkCategory::all();
            $drinks = Drink::where('active', true)->get()->append('category_name')->append('units')->makeHidden(['category', 'active']);

            $tree = [];

            foreach ($categories as $category) {
                $cat = (object)($category->toArray());

                $cat->drinks = array_values(array_filter($drinks->toArray(), function ($d) use ($cat) {
                    return $d['category_id'] == $cat->id;
                }));

                if ($cat->parent_id === null) {
                    $tree[$cat->id] = $cat;
                    $cat->subcategory = [];
                } else {
                    $tree[$cat->parent_id]->subcategory[$cat->id] = $cat;
                }
                unset($cat->parent_id);
                $response = $tree;
            }
            Cache::put("drink-menu-tree-{$locale}", $response, 3600);
        }

        return $response;
    }
}
