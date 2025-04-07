<?php

namespace App\Services;

use App\Models\User;
use App\Models\Comment;
use App\Models\Captcha;
use Illuminate\Http\Request;
use HTMLPurifier;
use HTMLPurifier_Config;

class CommentService
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'a[href|title],code,strong,i');
        $this->purifier = new HTMLPurifier($config);
    }

    public function createComment(Request $request, ?int $parentId = null): array
    {
        $cleanText = $this->purifier->purify($request->input('text'));
        
        // Проверяем и удаляем капчу
        $captcha = Captcha::where('token', $request->header('X-Captcha-Token'))->first();
        $captcha?->delete();

        // Обработка изображения
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('comment-images', 'public');
        }

        try {
            $users = User::where('email', $request->email)
             ->orWhere('name', $request->name)
             ->get();

        if ($users->count()) {
            $userByEmail = $users->firstWhere('email', $request->email);
            $userByName = $users->firstWhere('name', $request->name);

            if ($userByEmail && $userByName) {
                if ($userByEmail->id !== $userByName->id) {
                    throw new \Exception('This name is already in use by another user.');
                }
                $user = $userByEmail;
            } elseif ($userByEmail) {
                throw new \Exception('This email is already registered with another username.');
            } elseif ($userByName) {
                throw new \Exception('This name is already in use by another user.');
            }
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'homepage' => $request->homepage ?? null
            ]);
        }

            // Обновляем homepage, если он изменился
            if ($user->homepage !== $request->homepage) {
                $user->homepage = $request->homepage ?? null;
                $user->save();
            }

            // Создаем комментарий
            $comment = Comment::create([
                'user_id' => $user->id,
                'text' => $cleanText,
                'image_path' => $imagePath,
                'parent_id' => $parentId
            ]);

            return [
                'comment' => $comment,
                'user' => $user
            ];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function validateCaptcha(string $token, string $value): bool
    {
        $captcha = Captcha::where('token', $token)->first();
        return $captcha && $captcha->text === strtoupper($value);
    }
} 