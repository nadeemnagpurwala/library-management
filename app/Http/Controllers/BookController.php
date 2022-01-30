<?php
namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Models\UserBooks;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller {
    public function __construct(){
        $this->rules = [
            'book_name' => 'required|string|unique:books,book_name',
            'author' => 'required|string',
            'cover_image' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function index() {
        return Book::all();
    }

    public function store(Request $request){
        try {
            $validation = $this->formValidations($request);
            if ($validation['status'] == 'true') {
                $requestData = $request->all();
                $book = Book::create($requestData);

                return response()->json([
                    'success' => true,
                    'message' => 'Book created successfully',
                    'data' => $book
                ], Response::HTTP_OK);
            }
            else {
                return response()->json(['error' => $validation['message']], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Book not created',
            ], 500);
        }
    }

    public function editBook(Request $request, Book $book) {
        try {
            $validation = $this->formValidations($request, $book['id']);
            if ($validation['status'] == 'true') {
                $requestData = $request->all();
                $currentBook = $book->update($requestData);
                return response()->json([
                    'success' => true,
                    'message' => 'Book updated successfully',
                    'data' => $currentBook
                ], Response::HTTP_OK);
            }
            else {
                return response()->json(['error' => $validation['message']], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Book not updated',
            ], 500);
        }
    }

    public function show($id) {
        $book = Book::find($id);
    
        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, book not found.'
            ], 400);
        }
    
        return $book;
    }

    private function formValidations(Request $request, $id = null) {
        $data = $request->only('book_name', 'author', 'cover_image');

        $rules = $this->rules;
        if (!empty($id)) {
            $rules['book_name'] = $rules['book_name'] . ',' . $id;
        }
        
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return ['status' => 'false', 'message' => $validator->messages()];
        }

        return ['status' => 'true', 'message' => 'Validation Passed'];
    }

    public function destroy(Book $book) {
        $book->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Book deleted successfully'
        ], Response::HTTP_OK);
    }

    public function rentBook($userId, $bookId) {
        try {
            $existingRecord = $this->rentBookValidation($userId, $bookId);
            if (!$existingRecord) {
                $userBooks = UserBooks::create([
                    'user_id' => $userId,
                    'book_id' => $bookId,
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Book rented by user successfully'
                ], Response::HTTP_OK);
            }
            else {
                return response()->json([
                    'success' => true,
                    'message' => 'Book has already been rented by the user'
                ], Response::HTTP_OK);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Book was not rented. Please try again later',
            ], 500);
        }
    }

    public function returnBook($userId, $bookId) {
        try {
            $existingRecord = $this->rentBookValidation($userId, $bookId);
            if (!$existingRecord) {
                return response()->json([
                    'success' => true,
                    'message' => 'Specified book not rented by the user'
                ], Response::HTTP_OK);
            }
            else {
                $existingRecord->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Book has been returned by the user'
                ], Response::HTTP_OK);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Book was not returned. Please try again later',
            ], 500);
        }
    }

    private function rentBookValidation($userId, $bookId) {
        $user = User::find($userId);
        $book = Book::find($bookId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user not found.'
            ], 400);
        }
        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, book not found.'
            ], 400);
        }

        $existingRecord = UserBooks::where('user_id', $userId)->where('book_id', $bookId)->first();

        return $existingRecord;
    }
}