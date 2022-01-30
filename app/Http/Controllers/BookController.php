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
        $books = Book::all();
        if (!$books) {
            return response()->json([
                'success' => false,
                'message' => 'No books found.'
            ], 400);
        }
        return $books;
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
            elseif (isset($existingRecord['message'])) {
                return response()->json([
                    'success' => false,
                    'message' => $existingRecord['message']
                ], 400);
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
            elseif (isset($existingRecord['message'])) {
                return response()->json([
                    'success' => false,
                    'message' => $existingRecord['message']
                ], 400);
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

    public function userBooks($userId) {
        try {
            $existingRecord = $this->rentBookValidation($userId);
            if (!$existingRecord) {
                return response()->json([
                    'success' => true,
                    'message' => 'No book rented by the user'
                ], Response::HTTP_OK);
            }
            elseif (isset($existingRecord['message'])) {
                return response()->json([
                    'success' => false,
                    'message' => $existingRecord['message']
                ], 400);
            }
            else {
                return response()->json([
                    'success' => true,
                    'message' => $existingRecord
                ], Response::HTTP_OK);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Book list rented by the user not found. Please try again later',
            ], 500);
        }
    }

    private function rentBookValidation($userId, $bookId = null) {
        $user = User::find($userId);
        if (!$user) {
            return ['message' => 'Sorry, user not found'];
        }
        if (!empty($bookId)) {
            $book = Book::find($bookId);
            if (!$book) {
                return ['message' => 'Sorry, book not found'];
            }

            $existingRecord = UserBooks::where('user_id', $userId)->where('book_id', $bookId)->first();
            return $existingRecord;
        }
        
        else {
            $existingRecord = UserBooks::where('user_id', $userId);
            if ($existingRecord->count() > 0) {
                return $existingRecord->get();
            }
        }
        return false;
    }
}