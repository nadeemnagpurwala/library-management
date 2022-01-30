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

            $requestData = $request->all();
            $requestData['password'] = bcrypt($request->password);
            $user = User::create($requestData);
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

    public function authenticate(Request $request) {
        $credentials = $request->only('email', 'password');
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6|max:50'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 400);
            }
        }
        catch (JWTException $e) {
            return response()->json([
                    'success' => false,
                    'message' => 'Could not create token.',
                ], 500);
        }
    
        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }

    public function getUser(Request $request) {
        $this->validate($request, [
            'token' => 'required'
        ]);
        $user = JWTAuth::authenticate($request->token);
        return response()->json(['user' => $user]);
    }
}
