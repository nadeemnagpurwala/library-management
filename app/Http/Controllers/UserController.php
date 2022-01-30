<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use App\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller {
    public function register(Request $request) {
        try {
            $data = $request->only('firstname','lastname','mobile','email','age','gender','city','password'
            );
            $validator = Validator::make($data, [
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'mobile' => 'required|digits_between:10,10|unique:users',
                'email' => 'required|email|unique:users',
                'age' => 'required|numeric',
                'gender' => 'required|in:m,f,o',
                'city' => 'required|string',
                'password' => 'required|string|min:6|max:50'
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages()], 200);
            }
            $user = User::create($request->all());
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not created',
            ], 500);
        }
    }
}
