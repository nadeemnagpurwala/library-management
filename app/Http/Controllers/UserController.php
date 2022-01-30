<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use App\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller {
    public function __construct(){
        $this->rules = [
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'mobile' => 'required|digits_between:10,10|unique:users,mobile',
            'email' => 'required|email|unique:users,email',
            'age' => 'required|numeric',
            'gender' => 'required|in:m,f,o',
            'city' => 'required|string',
            'password' => 'required|string|min:6|max:50'
        ];
    }
    public function register(Request $request) {
        try {
            $validation = $this->formValidations($request);
            if ($validation['status'] == 'true') {
                $requestData = $request->all();
                $requestData['password'] = bcrypt($request->password);
                $user = User::create($requestData);
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => $user
                ], Response::HTTP_OK);
            }
            else {
                return response()->json(['error' => $validation['message']], 200);
            }
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

    public function editUser(Request $request, User $user) {
        try {
            $validation = $this->formValidations($request, $user['id']);
            if ($validation['status'] == 'true') {
                $requestData = $request->all();
                $requestData['password'] = bcrypt($request->password);
                $currentUser = $user->update($requestData);
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'data' => $currentUser
                ], Response::HTTP_OK);
            }
            else {
                return response()->json(['error' => $validation['message']], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not updated',
            ], 500);
        }
    }

    private function formValidations(Request $request, $id = null) {
        $data = $request->only('firstname','lastname','mobile','email','age','gender','city','password');

        $rules = $this->rules;
        if (!empty($id)) {
            $rules['email'] = $rules['email'] . ',' . $id;
            $rules['mobile'] = $rules['mobile'] . ',' . $id;
        }
        
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return ['status' => 'false', 'message' => $validator->messages()];
        }

        return ['status' => 'true', 'message' => 'Validation Passed'];
    }

    public function logout(Request $request){
        $validator = Validator::make($request->only('token'), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }
        try {
            JWTAuth::invalidate($request->token);
            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(User $user) {
        $user->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ], Response::HTTP_OK);
    }
}
