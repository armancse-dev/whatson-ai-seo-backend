<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function register(Request $r) {
        $r->validate(['name'=>'required','email'=>'required|email|unique:users','password'=>'required|min:6']);
        $user = User::create(['name'=>$r->name,'email'=>$r->email,'password'=>Hash::make($r->password)]);
        auth()->login($user);
        $token = $user->createToken('api')->plainTextToken;
        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $r) {
        $r->validate(['email'=>'required|email','password'=>'required']);
        $user = User::where('email',$r->email)->first();
        if (! $user || ! Hash::check($r->password,$user->password)) {
            throw ValidationException::withMessages(['email'=>['The provided credentials are incorrect.']]);
        }
        auth()->login($user);
        $token = $user->createToken('api')->plainTextToken;
        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $r){
        if ($r->user() && $r->user()->currentAccessToken()) {
            $r->user()->currentAccessToken()->delete();
        }
        return response()->json(['message'=>'logged out']);
    }

    // csrf cookie endpoint:
    public function csrf() {
        // For cookie-based SPA auth: set XSRF-TOKEN cookie
        return response()->noContent()->cookie('XSRF-TOKEN', csrf_token(), 120, '/', null, false, false);
    }
}
