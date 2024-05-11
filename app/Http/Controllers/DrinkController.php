<?php

namespace App\Http\Controllers;

use App\Models\Drink;
use App\Models\DrinkUnit;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
        } else {
            $with = 'units';
        }
        return Drink::with($with)
            // ->limit(10)
            ->get()
            ->makeVisible($visible)
            ->makeHidden($hidden);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $drink = new Drink();
        DB::transaction(function () use ($request, $drink) {
            $validator = Validator::make($request->all(), [
                'name_en' => 'string|required|unique:drinks,name_en',
                'name_hu' => 'string|required|unique:drinks,name_hu',
                'category_id' => 'integer|required',
                'description_en' => 'string|sometimes|nullable',
                'description_hu' => 'string|sometimes|nullable',
                'active' => 'boolean|sometimes',
            ]);

            $drink->fill($validator->valid())->save();

            foreach ($request->units as $idx => $unit) {
                if ($unit['quantity'] <= 0) {
                    $validator->errors()->add("units.{$idx}.quantity", __("Quantity should be larger than 0."));
                }
                $drink_unit = DrinkUnit::create([
                    'quantity' => $unit['quantity'],
                ]);
                $locales = config('app.available_locales');
                foreach ($locales as $code) {
                    if ($code != 'en') {
                        $drink_unit->{"unit_{$code}"} = __($unit->unit, [], $code);
                    }
                }

                $drink_unit->save();
            }
            event(new \App\Events\DrinkCreated($drink));
            return $this->show($request, $drink->id);
        });
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
        } else {
            $with = 'units';
        }
        return Drink::with($with)->findOrFail($id)->makeVisible($visible)->makeHidden($hidden);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Drink $drink)
    {
        return DB::transaction(function () use ($request, $drink) {
            $validator = Validator::make($request->all(), [
                'name_en' => 'string|sometimes|unique:drinks,name_en,' . $drink->id,
                'name_hu' => 'string|sometimes|unique:drinks,name_hu,' . $drink->id,
                'category_id' => 'integer|sometimes',
                'description_en' => 'string|sometimes|nullable',
                'description_hu' => 'string|sometimes|nullable',
                'active' => 'boolean|sometimes',
            ]);

            $drink->fill($validator->valid())->save();

            $locales = config('app.available_locales');
            foreach ($request->units as $idx => $unit) {
                if ($unit['quantity'] <= 0) {
                    $validator->errors()->add("units.{$idx}.quantity", __("Quantity should be larger than 0."));
                }

                $drink_units = DrinkUnit::where('drink_id', $drink->id)->get();

                foreach ($drink_units as $key => $unit) {
                    // $unit->quantity = 1000;
                    // $unit->save();

                    $filtered = array_filter($request->units, function ($req_unit) use ($unit) {
                        return ($req_unit['drink_id'] == $unit->drink_id);
                    });
                    if (count($filtered)) {
                        $req_unit = (object)($filtered[0]);
                        // echo json_encode($unit) . "\n";

                        $values = [
                            'quantity' => $req_unit->quantity,
                            'unit_price' => $req_unit->unit_price,
                            'unit_en' => $req_unit->unit,
                            'unit_hu' => __($req_unit->unit, [], 'hu'),
                        ];
                        $unit->fill($values);
                        // echo json_encode($unit) . "\n";
                        // return $unit;
//                        echo "unit saved\n";
                        $unit->save();
                    } else {
                        $unit->delete();
                    }
                }

                if ($validator->errors()->isNotEmpty()) {
                    throw new ValidationException($validator);
                }

                /*
{
  "drink_units": [
    {
      "id": 1,
      "drink_id": 1,
      "quantity": 1,
      "unit_price": 450,
      "unit": null,
      "unit_code": null
    }
  ],
  "req_units": [
    {
      "id": 1,
      "drink_id": 1,
      "quantity": 1,
      "unit_price": "99900",
      "unit": "bottle",
      "unit_code": null
    }
  ]
}
*/

                // $drink_unit = DrinkUnit::create([
                //     'quantity' => $unit['quantity'],
                //     'unit_en' => $unit['unit_code'],
                // ]);
                // $locales = config('app.available_locales');
                // foreach ($locales as $language => $code) {
                //     if ($code != 'en') {
                //         $drink_unit->{"unit_{$code}"} = __($unit->unit_code, [], $code);
                //     }
                // }
                // $drink_unit->save();
            }

            // event(new \App\Events\DrinkUpdated($drink));
            // return $this->show($request, $drink->id);
            return $this->show($request, $drink->id);
        });
        // return $request->all();
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

    public function card(Request $request, $id)
    {
        $with = [];

        $visible = [];
        $hidden = [
            'active',
            'category',
            'category_id'
        ];

        if ($request->with) {
            $with = array_intersect(explode(',', strtolower($request->with)), self::$valid_withs);
        }
        return Drink::findOrFail($id)->active()->with($with)->findOrFail($id)->append('category_name')->makeVisible($visible)->makeHidden($hidden);
    }
}
