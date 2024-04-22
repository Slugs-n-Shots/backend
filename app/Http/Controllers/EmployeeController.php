<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class EmployeeController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Employee::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return response($request->all(), 404);
        $valid = $request->validate([
            'first_name' => 'string|required',
            'middle_name' => 'string|nullable|sometimes',
            'last_name' => 'string|required',
            'email' => 'string|required|unique:employees,email',
            'password' => ['optional', PasswordRule::min(10)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'role_code' => [
                'integer',
                'required',
                // Rule::in(array_keys(Employee::ROLES))
            ],
            'active' => 'boolean|required',
        ]);
        $employee = new Employee();
        $employee->fill($valid)->save();
        return $employee;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        return Employee::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $valid = $request->validate([
            'first_name' => 'string|sometimes|min:2',
            'middle_name' => 'string|nullable|sometimes',
            'last_name' => 'string|sometimes|min:2',
            'email' => 'string|sometimes|required|unique:employees,email,' . $employee->id,
//            'password' => ['sometimes', PasswordRule::min(10)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'password' => ['sometimes', PasswordRule::min(10)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'role_code' => [
                'integer',
                'required',
                //  Rule::in(array_keys(Employee::ROLES))
            ],
            'active' => 'boolean|sometimes|required',
        ]);

        $employee->fill($valid)->save();
        return $employee;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        if ($employee->delete())
            return response()->noContent();
    }

    public function scheme()
    {
        $employee = Employee::firstOrNew();

        // if an existing record was found
        if ($employee->exists) {
            $employee = $employee->attributesToArray();
        } else { // otherwise a new model instance was instantiated
            // get the column names for the table
            $columns = Schema::getColumnListing($employee->getTable());

            // create array where column names are keys, and values are null
            $columns = array_fill_keys($columns, null);

            // merge the populated values into the base array
            $employee = array_merge($columns, $employee->attributesToArray());
        }

        return $employee;
    }

    public function me(Request $request)
    {
        // $payload = Auth::payload()->toArray();
        // $payload['refresh_ttl'] = $payload['iat'] + Config::get('jwt.refresh_ttl') * 60;
        // $payload['iat_dt'] = gmdate("Y-m-d\TH:i:s\Z", intval($payload['iat']));
        // $payload['exp_dt'] = gmdate("Y-m-d\TH:i:s\Z", intval($payload['exp']));
        // $payload['refresh_ttl_dt'] = gmdate("Y-m-d\TH:i:s\Z", intval($payload['refresh_ttl']));

        return [Auth::user()->makeVisible(['created_at'])];
    }

    public function updateMe(Request $request)
    {
        $guest = Employee::find(Auth::user()->id);
        $valid = $request->validate([
            'first_name' => 'string|required',
            'middle_name' => 'string|optional|sometimes',
            'last_name' => 'string|required',
            'email' => 'prohibited',
            'password' => 'prohibited',
            'role_code' => 'prohibited',
            'active' => 'prohibited',
        ]);

        $guest->fill($valid)->save();
        return $guest;
    }


    public function updatePassword(Request $request)
    {
        // $guest = Employee::find(Auth::user()->id);
        $user = Auth::user();

        $valid = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }
        $user->password = $valid->password;
        $user->save();

        return $user;
    }

    public function roles() {
        $locales = config('app.available_locales');
        $ret = [];
        foreach(EMPLOYEE::ROLES as $code => $role) {
            $obj = ['id' => $code];
            foreach($locales as $lang) {
                $obj["name_{$lang}"] = __($role, [], $lang);
            }
            $ret[] = $obj;
        }

        return $ret;
    }
}
