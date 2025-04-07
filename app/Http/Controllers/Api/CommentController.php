<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\CommentService;

class CommentController extends Controller
{
    private CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function index(Request $request)
    {
        $sortField = $request->input('sortField', 'created_at');
        $sortOrder = $request->input('sortDirection', 'desc');
        $query = Comment::with('user')->whereNull('parent_id');

        if ($sortField === 'user_name') {
            $query->join('users', 'comments.user_id', '=', 'users.id')
                ->orderBy('users.name', $sortOrder)
                ->select('comments.*');
        } elseif ($sortField === 'email') {
            $query->join('users', 'comments.user_id', '=', 'users.id')
                ->orderBy('users.email', $sortOrder)
                ->select('comments.*');
        } elseif (in_array($sortField, ['created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        }
        $comments = $query->paginate(25);

        return response()->json($comments);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string','min:2','max:60', 'regex:/^[a-zA-Z0-9]+$/'],
            'email' => ['required', 'email'],
            'text' => ['required', 'string','min:2','max:300'],
            'homepage' => ['nullable', 'url'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,gif', 'max:2048'],
            'captcha' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!$this->commentService->validateCaptcha($request->header('X-Captcha-Token'), $value)) {
                    $fail('Неверная капча.');
                }
            }],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->commentService->createComment($request);

            return response()->json([
                'message' => 'Комментарий добавлен',
                'comment' => $result['comment'],
                'user' => $result['user'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    'user' => [$e->getMessage()]
                ]
            ], 422);
        }
    }

    public function reply(Request $request, $id)
    {
        $parent = Comment::find($id);
        if (!$parent) {
            return response()->json(['error' => 'Parent comment not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'regex:/^[a-zA-Z0-9]+$/'],
            'email' => ['required', 'email'],
            'text' => ['required', 'string'],
            'homepage' => ['nullable', 'url'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,gif', 'max:2048'],
            'captcha' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!$this->commentService->validateCaptcha($request->header('X-Captcha-Token'), $value)) {
                    $fail('Invalid captcha.');
    //                Log::info("Токен капчи CommentController: " . $request->header('X-Captcha-Token'));
                }
            }],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->commentService->createComment($request, $id);

            return response()->json([
                'message' => 'Ответ добавлен',
                'reply' => $result['comment'],
                'user' => $result['user'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    'user' => [$e->getMessage()]
                ]
            ], 422);
        }
    }

    public function replies(Request $request, $id)
    {
        $query = Comment::where('parent_id', $id)
            ->with('user');

        $replies = $query->paginate(25);
        return response()->json($replies);
    }
}
