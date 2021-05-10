<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use JWTAuth;
use JWTAuthException;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth:api', ['except' => ['login']]);
        $this->user = new User;
        $this->admin = new Admin;
        
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function adminLogin(Request $request)
    {

        // auth()->setDefaultDriver('admin');
        // auth()->shouldUse('admin'); // Set the auth gaurd to be used

        $credentials = $request->only('email', 'password');
        $token = null;
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'response' => 'error',
                    'message' => 'invalid_email_or_password',
                ]);
            }
        } catch (JWTAuthException $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'failed_to_create_token',
            ]);
        }
        return $this->respondWithToken($token);
    }

    public function testPassword(Request $request){
        $password = Hash::make($request->password);
        return response()->json([
            'password' => $password,
        ]);
    }

    public function adminRegister(Request $request)
    {

        // auth()->setDefaultDriver('admin');
        // auth()->shouldUse('admin'); // Set the auth gaurd to be used

        $admin = new Admin;

        $password = Hash::make($request->password);

        $admin->unique_id = Str::random(20);
        $admin->first_name = $request->first_name;
        $admin->last_name = $request->last_name;
        $admin->email = $request->email;
        $admin->password = $password;

        $admin->save();

        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'admin' => $admin,
                    'message' => 'Your admin registration was successful',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'Could not make registration',
            ]);
        }
        
        
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    public function adminUser(Request $request)
    {
        // auth()->shouldUse('admin');
        return response()->json([
            "user" => auth()->user(),
            "request" => $request
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
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
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function guard() {
        return Auth::guard();
    } 
}
