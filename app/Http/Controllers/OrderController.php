<?php

namespace App\Http\Controllers;

use App\Models\DrinkUnit;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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

    protected static $valid_withs = ['details'];

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
        if ($order->delete()) {
            return response()->noContent();
        }
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

    public function activeOrders(Request $request, ?string $status = null)
    {
        return Order::when(
            $status !== null,
            fn ($query) => $query->where('status', $status),
            fn ($query) => $query->whereIn('status', [Order::STATUS_OPEN, Order::STATUS_PREPARING, Order::STATUS_READY])
        )->get();
    }

    public function makeOrder(Request $request)
    {
        $valid = $request->validate([
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.drink_id' => ['required', 'integer'],
            'cart.*.quantity' => ['required', 'numeric'],
            'cart.*.unit' => ['required', 'string'],
            'cart.*.ordered_quantity' => ['required', 'integer', 'min:1'],
            'table_session_id' => ['sometimes', 'integer', 'exists:table_sessions,id'],
        ]);

        return DB::transaction(function () use ($valid) {
            $guest = Auth::user();
            $tableSession = $this->resolveGuestTableSession($guest, $valid['table_session_id'] ?? null);
            $cartTotal = $this->calculateCartTotal($valid['cart']);
            $this->ensureTableSpendingLimitAllows($tableSession, $cartTotal);

            [$order, $total] = $this->createOrderFromCart($valid['cart'], [
                'guest_id' => $guest->id,
                'recorded_at' => now(),
                'status' => Order::STATUS_OPEN,
                'table_session_id' => $tableSession->id,
            ]);

            $new_order = Order::with(['details', 'details.drinkUnit.drink'])->find($order->id);
            return (object)[
                'message' => __('Your selections are being prepared and will be served shortly. Stay tuned!'),
                'cart' => $valid['cart'],
                'order' => $new_order,
                'discounts' => [],
                'total' => $total,
            ];
        });
    }

    public function staffStore(Request $request)
    {
        $valid = $request->validate([
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.drink_id' => ['required', 'integer'],
            'cart.*.quantity' => ['required', 'numeric'],
            'cart.*.unit' => ['required', 'string'],
            'cart.*.ordered_quantity' => ['required', 'integer', 'min:1'],
            'table_session_id' => ['sometimes', 'integer', 'exists:table_sessions,id'],
        ]);

        return DB::transaction(function () use ($valid) {
            $guest = Guest::findOrFail($valid['guest_id']);
            $tableSessionId = $valid['table_session_id'] ?? null;

            if ($tableSessionId !== null) {
                $this->ensureStaffMayUseTableSession($guest, $tableSessionId);
                $tableSession = TableSession::findOrFail($tableSessionId);
                $cartTotal = $this->calculateCartTotal($valid['cart']);
                $this->ensureTableSpendingLimitAllows($tableSession, $cartTotal);
            }

            [$order, $total] = $this->createOrderFromCart($valid['cart'], [
                'guest_id' => $guest->id,
                'recorded_by' => request()->user()->id,
                'recorded_at' => now(),
                'status' => Order::STATUS_OPEN,
                'table_session_id' => $tableSessionId,
            ]);

            $newOrder = Order::with(['details', 'details.drinkUnit.drink'])->find($order->id);

            return (object)[
                'message' => __('Order recorded successfully.'),
                'cart' => $valid['cart'],
                'order' => $newOrder,
                'discounts' => [],
                'total' => $total,
            ];
        });
    }

    /**
     * for guests
     */
    public function myOrders(Request $request, $status = null)
    {
        $guest = request()->user();
        return Order::with(['details', 'details.drinkUnit.drink'])
            ->where('guest_id', $guest->id)
            ->when($status === 'active', function ($query) use ($request) {
                $query->whereIn('status', [Order::STATUS_OPEN, Order::STATUS_PREPARING, Order::STATUS_READY]);
            })
            ->orderBy('recorded_at', 'desc')->get();
    }

    /**
     * for staff - orders assigned to me
     * I am the last assigned employee, and my assignment is not closed (date is null)
     */
    public function myOpenTasks()
    {
        $employee = request()->user();
        $orders = Order::with(['details', 'guest', 'details.drinkUnit.drink'])
            ->where(function ($query) use ($employee) {
                $query->where('made_by', $employee->id)
                    ->whereNull('made_at');
            })
            ->orWhere(function ($query) use ($employee) {
                $query->where('served_by', $employee->id)
                    ->whereNull('served_at');
            })
            ->orderBy('recorded_at', 'desc')->get();

        return $orders;
    }

    /**
     * Start working on an active orders
     * bartender can made recorded orders
     * waiter can serve serve made orders
     */
    public function waitingOrders()
    {
        $orders = null;
        $employee = request()->user();

        if ($employee->isBartender()) {
            // not made
            $orders = Order::with(['details', 'guest', 'details.drinkUnit.drink'])
                ->where('status', Order::STATUS_OPEN)
                ->whereNull('made_by')
                ->orderBy('recorded_at', 'desc')
                ->get();
        } elseif ($employee->isWaiter()) {
            // made but not served
            $orders = Order::with(['details', 'guest', 'details.drinkUnit.drink'])
                ->where('status', Order::STATUS_READY)
                ->whereNotNull('made_at')
                ->whereNull('served_by')
                ->orderBy('recorded_at', 'desc')
                ->get();
        }
        return $orders;
    }

    public function assignOrder($order_id)
    {
        $employee = request()->user();
        $order = Order::findOrFail($order_id);
        if ($employee->isBartender()) {
            if ($order->status === Order::STATUS_OPEN && $order->made_at == null) {
                $order->fill([
                    'made_by' => $employee->id,
                    'status' => Order::STATUS_PREPARING,
                ]);
                if ($order->save()) {
                    return __("Order assigned successfully");
                } else {
                    return $order->getErrors()->toArray();
                }
            }
        }
        if ($employee->isWaiter()) {
            if ($order->status !== Order::STATUS_READY) {
                return response(__('This order cannot be assigned to you, because it has not completed yet.'), 409);
            } elseif ($order->served_at === null) {
                $order->fill(['served_by' => $employee->id]);
                if ($order->save()) {
                    return __("Order assigned successfully");
                } else {
                    return $order->getErrors()->toArray();
                }
            }
        }

        return $order;
    }

    public function doneOrder($order_id)
    {
        $order = Order::findOrFail($order_id);
        $employee = request()->user();
        if ($order->served_by == $employee->id && $order->served_at === null) {
            $order->fill([
                'served_at' => now(),
                'status' => Order::STATUS_SERVED,
            ]);
            if ($order->save()) {
                return __("Order marked as served.");
            } else {
                return $order->getErrors()->toArray();
            }
        } elseif ($order->made_by == $employee->id && $order->made_at === null) {
            $order->fill([
                'made_at' => now(),
                'status' => Order::STATUS_READY,
            ]);
            if ($order->save()) {
                return __("Order marked as completed.");
            } else {
                return $order->getErrors()->toArray();
            }
        }
    }

    public function lastOrderId()
    {
        return Order::OrderBy('recorded_at', 'desc')->first();
    }

    public function undoAssignOrder($order_id)
    {
        $order = Order::findOrFail($order_id);
        $employee = request()->user();
        if ($order->served_by == $employee->id && $order->served_at === null) {
            $order->fill(['served_by' => null]);
            if ($order->save()) {
                return __("Order assignment was reverted successfully.");
            } else {
                return $order->getErrors()->toArray();
            }
        } elseif ($order->made_by == $employee->id && $order->made_at === null) {
            $order->fill([
                'made_by' => null,
                'status' => Order::STATUS_OPEN,
            ]);
            if ($order->save()) {
                return __("Order assignment was reverted successfully.");
            } else {
                return $order->getErrors()->toArray();
            }
        }
    }

    private function createOrderFromCart(array $cart, array $orderAttributes): array
    {
        $order = Order::create($orderAttributes);

        $total = 0;
        foreach ($cart as $idx => $item) {
            $drinkUnit = DrinkUnit::where('drink_id', $item['drink_id'])
                ->where('quantity', $item['quantity'])
                ->where('unit_en', $item['unit'])
                ->first();

            if ($drinkUnit === null) {
                throw ValidationException::withMessages([
                    "cart.{$idx}" => [__('Invalid drink unit selected.')],
                ]);
            }

            OrderDetail::create([
                'order_id' => $order->id,
                'drink_unit_id' => $drinkUnit->id,
                'ordered_quantity' => $item['ordered_quantity'],
                'promo_id' => null,
                'unit_price' => $drinkUnit->unit_price,
                'discount' => 0,
                'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
            ]);
            $total += $item['ordered_quantity'] * $drinkUnit->unit_price;
        }

        return [$order, $total];
    }

    private function calculateCartTotal(array $cart): int
    {
        $total = 0;

        foreach ($cart as $idx => $item) {
            $drinkUnit = DrinkUnit::where('drink_id', $item['drink_id'])
                ->where('quantity', $item['quantity'])
                ->where('unit_en', $item['unit'])
                ->first();

            if ($drinkUnit === null) {
                throw ValidationException::withMessages([
                    "cart.{$idx}" => [__('Invalid drink unit selected.')],
                ]);
            }

            $total += $item['ordered_quantity'] * $drinkUnit->unit_price;
        }

        return $total;
    }

    private function resolveGuestTableSession(Guest $guest, ?int $tableSessionId): TableSession
    {
        if ($tableSessionId === null) {
            $tableSession = $this->currentGuestTableSession($guest);

            if ($tableSession === null) {
                abort(response()->json([
                    'message' => __('Only table-bound orders can be paid later.'),
                ], 409));
            }

            return $tableSession;
        }

        $tableSession = TableSession::findOrFail($tableSessionId);
        $this->ensureOpenTableSession($tableSession);

        if ($tableSession->owner_guest_id === $guest->id) {
            return $tableSession;
        }

        $membership = TableMember::where('table_session_id', $tableSession->id)
            ->where('guest_id', $guest->id)
            ->first();

        if ($membership?->status !== TableMember::STATUS_APPROVED) {
            abort(403);
        }

        if (!$membership->can_order) {
            abort(response()->json([
                'message' => __('This table member cannot order.'),
            ], 409));
        }

        return $tableSession;
    }

    private function currentGuestTableSession(Guest $guest): ?TableSession
    {
        $ownedSession = TableSession::where('owner_guest_id', $guest->id)
            ->where('status', TableSession::STATUS_OPEN)
            ->whereNull('closed_at')
            ->first();

        if ($ownedSession !== null) {
            return $ownedSession;
        }

        $membership = TableMember::with('tableSession')
            ->where('guest_id', $guest->id)
            ->where('status', TableMember::STATUS_APPROVED)
            ->where('can_order', true)
            ->whereHas('tableSession', function ($query) {
                $query->where('status', TableSession::STATUS_OPEN)
                    ->whereNull('closed_at');
            })
            ->first();

        return $membership?->tableSession;
    }

    private function ensureStaffMayUseTableSession(Guest $guest, int $tableSessionId): void
    {
        $tableSession = TableSession::findOrFail($tableSessionId);
        $this->ensureOpenTableSession($tableSession);

        if ($tableSession->owner_guest_id === $guest->id) {
            return;
        }

        $isApprovedMember = TableMember::where('table_session_id', $tableSession->id)
            ->where('guest_id', $guest->id)
            ->where('status', TableMember::STATUS_APPROVED)
            ->exists();

        if (!$isApprovedMember) {
            abort(403);
        }
    }

    private function ensureOpenTableSession(TableSession $tableSession): void
    {
        if ($tableSession->status !== TableSession::STATUS_OPEN || $tableSession->closed_at !== null) {
            abort(response()->json([
                'message' => __('This table session is already closed.'),
            ], 409));
        }
    }

    private function ensureTableSpendingLimitAllows(TableSession $tableSession, int $newOrderTotal): void
    {
        $limit = $tableSession->effectiveSpendingLimit();

        if ($limit === null) {
            return;
        }

        $pendingTotal = $this->pendingTotalForTableSession($tableSession);

        if ($pendingTotal + $newOrderTotal > $limit) {
            abort(response()->json([
                'message' => __('The table spending limit would be exceeded. Please pay pending items before ordering more.'),
                'limit' => $limit,
                'pending_total' => $pendingTotal,
                'new_order_total' => $newOrderTotal,
            ], 409));
        }
    }

    private function pendingTotalForTableSession(TableSession $tableSession): int
    {
        return (int) OrderDetail::where('payment_status', OrderDetail::PAYMENT_STATUS_PENDING)
            ->whereHas('order', function ($query) use ($tableSession) {
                $query->where('table_session_id', $tableSession->id);
            })
            ->selectRaw('COALESCE(SUM(unit_price * ordered_quantity), 0) as total')
            ->value('total');
    }
}
