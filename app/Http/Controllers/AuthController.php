<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function createNewToken($token)
    {
        return response()->json([
            'access_token'  => $token,
            'token_type'    => 'bearer',
            'expires_in'    => Auth::factory()->getTTL() * 60,
            'user'          => auth()->user(),
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' =>  ['required', 'email'],
            'password'  =>  ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!$token = Auth::attempt($validator->validated())) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }

        return $this->createNewToken($token);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  =>  ['required', 'string'],
            'email' =>  ['required', 'email', 'unique:users,email'],
            'password'  =>  ['required', 'string', 'confirmed', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            [
                'password' =>   Hash::make($validator->validated()['password'])
            ]
        ));

        return response()->json([
            'message'   => 'User successfully registered',
            'user'      => $user,
        ], 201);

    }

    public function logout(){
        Auth::logout();

        return response()->json([
            'message'   =>  'User successfully signed out',
        ]);
    }

    public function refresh(){
        return $this->createNewToken(Auth::refresh());
    }

    public function user(){
        return response()->json(Auth::user());
    }
}
