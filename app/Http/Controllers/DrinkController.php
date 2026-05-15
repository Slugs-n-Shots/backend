<?php

namespace App\Http\Controllers;

use App\Models\Drink;
use App\Models\DrinkUnit;
use App\Models\GuestRecentDrink;
use App\Models\OrderDetail;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

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

        return Drink::with(['units' => function ($query) {
            $query->each(function ($child) {
                $child->makeVisible(['unit_en', 'unit_hu']);
            });

        }])->with($with)
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
        return DB::transaction(function () use ($request, $drink) {
            $validator = Validator::make($request->all(), [
                'name_en' => 'string|required|unique:drinks,name_en',
                'name_hu' => 'string|required|unique:drinks,name_hu',
                'category_id' => 'integer|required|exists:drink_categories,id',
                'description_en' => 'string|sometimes|nullable',
                'description_hu' => 'string|sometimes|nullable',
                'active' => 'boolean|sometimes',
                'units' => 'array|required|min:1',
                'units.*.quantity' => 'numeric|required|gt:0',
                'units.*.unit_price' => 'numeric|required|gt:0',
                'units.*.unit_en' => 'string|required',
                'units.*.unit_hu' => 'string|required',
            ]);

            $validated = $validator->validate();
            $drink->fill($validated)->save();

            foreach ($request->units as $unit) {
                $drink_unit = DrinkUnit::create([
                    'drink_id' => $drink->id,
                    'unit_price' => $unit['unit_price'],
                    'unit_en' => $unit['unit_en'],
                    'unit_hu' => $unit['unit_hu'],
                    'quantity' => $unit['quantity'],
                ])->save();
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
                'category_id' => 'integer|sometimes|exists:drink_categories,id',
                'description_en' => 'string|sometimes|nullable',
                'description_hu' => 'string|sometimes|nullable',
                'active' => 'boolean|sometimes',
                'units' => 'array|sometimes',
                'units.*.id' => [
                    'integer',
                    'sometimes',
                    Rule::exists('drink_units', 'id')->where('drink_id', $drink->id),
                ],
                'units.*.quantity' => 'numeric|required_with:units|gt:0',
                'units.*.unit_price' => 'numeric|required_with:units|gt:0',
                'units.*.unit_en' => 'string|required_with:units',
                'units.*.unit_hu' => 'string|required_with:units',
            ]);

            $validated = $validator->validate();
            $drink->fill($validated)->save();

            if ($request->has('units')) {
                $unitIds = collect($request->units)->pluck('id')->filter()->all();
                DrinkUnit::where('drink_id', $drink->id)
                    ->whereNotIn('id', $unitIds)
                    ->delete();

                foreach ($request->units as $unit) {
                    $values = [
                        'unit_price' => $unit['unit_price'],
                        'unit_en' => $unit['unit_en'],
                        'unit_hu' => $unit['unit_hu'],
                        'quantity' => $unit['quantity'],
                    ];

                    if (!empty($unit['id'])) {
                        DrinkUnit::where('drink_id', $drink->id)
                            ->where('id', $unit['id'])
                            ->update($values);
                    } else {
                        DrinkUnit::create([
                            'drink_id' => $drink->id,
                            ...$values,
                        ]);
                    }
                }
            }

            event(new \App\Events\DrinkUpdated($drink));
            return $this->show($request, $drink->id);
        });
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

    public function recentForGuest(Request $request)
    {
        $valid = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        $limit = $valid['limit'] ?? 10;

        $recentDrinkIds = GuestRecentDrink::query()
            ->join('drinks', 'drinks.id', '=', 'guest_recent_drinks.drink_id')
            ->where('guest_recent_drinks.guest_id', $request->user()->id)
            ->where('drinks.active', true)
            ->whereNull('drinks.deleted_at')
            ->orderByDesc('guest_recent_drinks.last_ordered_at')
            ->orderByDesc('guest_recent_drinks.id')
            ->limit($limit)
            ->pluck('guest_recent_drinks.drink_id')
            ->all();

        if ($recentDrinkIds === []) {
            $recentDrinkIds = OrderDetail::query()
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->join('drink_units', 'drink_units.id', '=', 'order_details.drink_unit_id')
                ->join('drinks', 'drinks.id', '=', 'drink_units.drink_id')
                ->where('orders.guest_id', $request->user()->id)
                ->where('drinks.active', true)
                ->whereNull('drinks.deleted_at')
                ->groupBy('drink_units.drink_id')
                ->orderByDesc(DB::raw('MAX(COALESCE(orders.recorded_at, orders.created_at))'))
                ->orderByDesc(DB::raw('MAX(order_details.id)'))
                ->limit($limit)
                ->pluck('drink_units.drink_id')
                ->all();
        }

        $drinks = Drink::whereIn('id', $recentDrinkIds)
            ->get()
            ->sortBy(fn (Drink $drink) => array_search($drink->id, $recentDrinkIds, true))
            ->values()
            ->append('category_name')
            ->append('units')
            ->makeHidden(['category', 'active']);

        return ['drinks' => $drinks];
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
