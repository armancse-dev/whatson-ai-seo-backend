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
        return response()->json($user,201);
    }

    public function login(Request $r) {
        $r->validate(['email'=>'required|email','password'=>'required']);
        $user = User::where('email',$r->email)->first();
        if (! $user || ! Hash::check($r->password,$user->password)) {
            throw ValidationException::withMessages(['email'=>['The provided credentials are incorrect.']]);
        }
        auth()->login($user);
        return response()->json($user);
    }

    public function logout(Request $r){
        auth()->logout();
        return response()->json(['message'=>'logged out']);
    }

    // csrf cookie endpoint:
    public function csrf() {
        return response()->noContent();
    }


    
}
