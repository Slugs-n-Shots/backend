<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Services\GuestAnonymizationService;
use App\Services\GuestDataExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class GuestController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Guest::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'first_name' => 'string|required',
            'middle_name' => 'string|nullable|sometimes',
            'last_name' => 'string|required',
            'email' => 'string|required|unique:guests,email',
            'password' => ['string', PasswordRule::min(10)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'picture' => 'string|nullable',
            'active' => 'boolean|required',
        ]);
        $guest = new Guest();
        $guest->fill($valid)->save();
        return $guest;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        return Guest::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Guest $guest)
    {
        $valid = $request->validate([
            'first_name' => 'string|sometimes|min:2',
            'middle_name' => 'string|nullable|sometimes',
            'last_name' => 'string|sometimes|min:2',
            'email' => 'string|sometimes|required|unique:guests,email,' . $guest->id,
            'password' => ['sometimes', PasswordRule::min(10)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'picture' => 'string|nullable',
            'active' => ['boolean','sometimes','required',Rule::in([Guest::INACTIVE, Guest::ACTIVE])],
        ]);

        $guest->fill($valid)->save();
        return $guest;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Guest $guest, GuestAnonymizationService $anonymizationService)
    {
        if ($guest->anonymized_at !== null) {
            return response()->noContent();
        }

        $result = $anonymizationService->anonymize($guest, 'staff_delete', null, false);

        if (!$result['can_anonymize']) {
            return response()->json($result, 409);
        }

        return response()->noContent();
    }

    public function scheme()
    {
        $guest = Guest::firstOrNew();

        // if an existing record was found
        if ($guest->exists) {
            $guest = $guest->attributesToArray();
        } else { // otherwise a new model instance was instantiated
            // get the column names for the table
            $columns = Schema::getColumnListing($guest->getTable());

            // create array where column names are keys, and values are null
            $columns = array_fill_keys($columns, null);

            // merge the populated values into the base array
            $guest = array_merge($columns, $guest->attributesToArray());
        }

        return $guest;
    }

    public function me(Request $request) {
        return Auth::user()->makeVisible(['created_at']);
    }

    public function updateSelf(Request $request)
    {
        $guest = Auth::user();
        $valid = $request->validate([
            'first_name' => 'string|sometimes|required',
            'middle_name' => 'string|sometimes|nullable',
            'last_name' => 'string|sometimes|required',
            'email' => 'prohibited',
            'active' => 'prohibited',
            'password' => 'prohibited',
            'picture' => 'string|nullable',
        ]);

        $guest->fill($valid)->save();
        return $guest;
    }

    public function uploadPicture(Request $request)
    {
        $valid = $request->validate([
            'picture' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:' . config('guests.profile_picture_max_kilobytes', 2048),
            ],
        ]);

        $guest = Auth::user();
        $oldPicture = $guest->picture;
        $file = $valid['picture'];
        $path = $file->storeAs(
            "guest-pictures/{$guest->id}",
            Str::random(24) . '.' . $file->extension(),
            'public'
        );

        $guest->picture = $path;
        $guest->save();

        $this->deleteStoredGuestPicture($oldPicture);

        return $guest;
    }

    public function deletePicture(Request $request)
    {
        $guest = Auth::user();
        $oldPicture = $guest->picture;

        $guest->picture = null;
        $guest->save();

        $this->deleteStoredGuestPicture($oldPicture);

        return $guest;
    }

    public function updatePassword(Request $request)
    {
        // $guest = Employee::find(Auth::user()->id);
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            $validator->errors()->add('current_password', __('Your current password is incorrect.'));
        }

        if ($validator->errors()->isNotEmpty()) {
            throw new ValidationException($validator);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return $user;
    }

    public function anonymizeCheck(Request $request, GuestAnonymizationService $anonymizationService)
    {
        return response()->json($anonymizationService->check(Auth::user()));
    }

    public function export(Request $request, GuestDataExportService $guestDataExportService)
    {
        return response()->json($guestDataExportService->export(Auth::user()));
    }

    public function anonymize(Request $request, GuestAnonymizationService $anonymizationService)
    {
        $request->validate([
            'confirm' => ['required', 'accepted'],
        ]);

        $result = $anonymizationService->anonymize(Auth::user());

        if (!$result['can_anonymize']) {
            return response()->json($result, 409);
        }

        if ($request->bearerToken()) {
            Auth::logout(true);
        }

        return response()->json(['message' => __('The account has been anonymized.')]);
    }

    private function deleteStoredGuestPicture(?string $path): void
    {
        if ($path !== null && str_starts_with($path, 'guest-pictures/')) {
            Storage::disk('public')->delete($path);
        }
    }

}
