<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\CommentService;
use Illuminate\Support\Facades\Cache;

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
        $searchField = $request->query('searchField', '');
        $page = $request->input('page', 1);

        $searchField = preg_replace("#([%_?+])#", "\\$1", $searchField);
        $cacheKey = "comments_index:{$sortField}:{$sortOrder}:{$searchField}:page:{$page}";
        $comments = Cache::rememberForever($cacheKey,  function () use (
            $sortField, $sortOrder, $searchField
        ) {
            $query = Comment::with('user')->whereNull('parent_id');

            if (!empty($searchField)) {
                $query->where(function ($q) use ($searchField) {
                    $q->where('text', 'LIKE', '%' . $searchField . '%')
                        ->orWhereHas('user', function ($qUser) use ($searchField) {
                            $qUser->where('name', 'LIKE', '%' . $searchField . '%')
                                ->orWhere('email', 'LIKE', '%' . $searchField . '%')
                                ->orWhere('created_at', 'LIKE', '%' . $searchField . '%');
                        });
                });
            }

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

            return $query->paginate(15);
        });
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
        // $query = Comment::where('parent_id', $id)
        //     ->with('user');

        // $replies = $query->paginate(10);
        $page = $request->input('page', 1);
        $cacheKey = "replies:{$id}:page:{$page}";

        $replies = Cache::rememberForever($cacheKey, function () use ($id) {
            return Comment::where('parent_id', $id)
                ->with('user')
                ->paginate(10);
        });
        
        return response()->json($replies);
    }
}
