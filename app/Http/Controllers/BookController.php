<?php
namespace App\Http\Controllers;

use App\Models\Book;
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
}