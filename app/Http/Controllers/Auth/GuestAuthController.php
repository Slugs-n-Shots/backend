<?php

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;


class GuestAuthController extends Controller
{
    protected $guard = 'guard_guest';

    protected $token_length = 20;

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        Auth::shouldUse($this->guard);
        $credentials = request(['email', 'password']);

        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['message' => __('Whoops! It seems something didn\'t go as planned.')], 401);
        }
        $user = Auth::user();

        if (!$user->active) {
            return response()->json(['message' => __('Your account is inactive and may not log in.')], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => __('You may need to confirm your email address before log in.')], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::logout(true); # This is just logout function that will destroy access token of current user
        return response()->json(['message' => __('Successfully logged out')]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        # When access token will be expired, we are going to generate a new one wit this function
        # and return it here in response
        return $this->respondWithToken(Auth::refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        # This function is used to make JSON response with new
        # access token of current user

        return response()->json([
            'user' => auth()->user(),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // fiók kezelés - jelszó alap #1
    public function forgotPassword(Request $request) //: JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.

        $guest = Guest::where(['email', $request->email])->get();

        if ($guest === null) {
            return response()->json(['message' => __('Invalid data #3')], 401);
        }

        ResetPassword::$createUrlCallback = function ($notifiable) {
            $data = $notifiable->data;
            $token = Str::random($this->token_length);
            $exp_at = time() + Config::get('auth.passwords.guests.expire') * 60;
            $data['pw_reset_token'] = $token;
            $data['pw_reset_exp'] = $exp_at;
            $notifiable->data = $data;
            $notifiable->save();

            return implode('/', [Config::get('app.frontend_url'), 'reset-password', $notifiable->id, $token]);
        };

        // $this->notify(new ResetPasswordNotification($url));
        // $user->sendPasswordResetNotification('');
        // return static::RESET_LINK_SENT;

        $status = Password::broker('guests')->sendResetLink(
            $request->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => __($status)]);
    }

    /**
     * Handle an incoming reset password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // fiók kezelés - jelszó alap #2
    public function resetPassword(Request $request) // : JsonResponse
    {
        $request->validate([
            'id' => ['required'],
            'token' => ['string', "min:{$this->token_length}", "max:{$this->token_length}", 'required'],
            'password' => ['required',  Rules\Password::defaults()],
        ]);

        $guest = Guest::findOrFail($request->id);
        $data = $guest->data;
        error_log(json_encode($data, JSON_PRETTY_PRINT));
        if (empty($data['pw_reset_token']) || empty($data['pw_reset_exp'])) {
            return response()->json(['message' => __('Invalid user or token')], 401);
        }

        if ($data['pw_reset_exp'] < time()) {
            return response()->json(['message' => __('The password reset link is invalid or it has expired, please request a new one.')], 401);
        }

        if ($data['pw_reset_token'] !== $request->token) {
            return response()->json(['message' => __('The password reset link is invalid or it has expired, please request a new one..')], 401);
        }

        unset($data['pw_reset_token']);
        unset($data['pw_reset_exp']);

        $guest->forceFill([
            'password' => Hash::make($request->password),
            'data' => $data,
        ])->save();

        event(new PasswordReset($guest));

        return response()->json(['message' => __('Your password has been updated')]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // fiók kezelés - regisztráció #1
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . Guest::class],
            'password' => ['required',  Rules\Password::defaults()],
        ]);

        $guest = Guest::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        VerifyEmail::$createUrlCallback = function ($notifiable) {
            $data = $notifiable->data;
            $token = Str::random($this->token_length);
            $exp_at = time() + Config::get('auth.password_timeout') * 60;
            $data['confirm_token'] = $token;
            $data['confirm_exp'] = $exp_at;
            $notifiable->data = $data;
            $notifiable->save();

            return implode('/', [Config::get('app.frontend_url'), 'confirm-registration', $notifiable->id, $token]);
        };

        event(new Registered($guest));

        // Auth::login($guest);

        return response()->json(['message' => __('Thank you for registering for our service. An email has been sent to the email address you provided on registration.')]);
    }

    // fiók kezelés - regisztráció #2
    public function confirmRegistration(Request $request)
    {
        $request->validate([
            'id' => ['required'],
            'token' => ['string', "min:{$this->token_length}", "max:{$this->token_length}", 'required'],
        ]);

        $guest = Guest::findOrFail($request->id);
        $data = $guest->data;

        if (empty($data['confirm_token']) || empty($data['confirm_exp'])) {
            return response()->json(['message' => __('Invalid user or token')], 401);
        }

        if (!empty($guest->hasVerifiedEmail())) {
            return response()->json(['message' => __('Your email is already verified')], 200);
        }

        if ($data['confirm_exp'] < time()) {
            return response()->json(['message' => __('The confirmation link is invalid or it has expired, please request a new one.')], 401);
        }

        if ($data['confirm_token'] !== $request->token) {
            return response()->json(['message' => __('The confirmation link is invalid or it has expired, please request a new one..')], 401);
        }

        unset($data['confirm_token']);
        unset($data['confirm_exp']);

        $guest->forceFill([
            'data' => $data,
            'email_verified_at' => \Date::now(),
        ])->save();

        event(new PasswordReset($guest));

        return response()->json(['message' => __('Your account has been activated')]);

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }
    }

    // fiók kezelés - regisztráció #3
    public function resendEmailVerificationMail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ]);
        $guest = Guest::where('email', $request->email)
            ->whereNull('email_verified_at')->first();
        if ($guest) {
            VerifyEmail::$createUrlCallback = function ($notifiable) {
                $data = $notifiable->data;
                $token = Str::random($this->token_length);
                $exp_at = time() + Config::get('auth.password_timeout') * 60;
                $data['confirm_token'] = $token;
                $data['confirm_exp'] = $exp_at;
                $notifiable->data = $data;
                $notifiable->save();

                return implode('/', [Config::get('app.frontend_url'), 'confirm-registration', $notifiable->id, $token]);
            };
            $guest->sendEmailVerificationNotification();
        }
        return ['message' => __('We have received your request. ' .
            'If there is an unconfirmed registration associated with ' .
            'the provided email address, we have sent a confirmation email. ' .
            'Please check your inbox, including the spam or promotions folder.')];
    }

}
