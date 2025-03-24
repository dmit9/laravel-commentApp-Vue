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
            // Проверяем существование пользователя по email или имени
            $userByEmail = User::where('email', $request->email)->first();
            $userByName = User::where('name', $request->name)->first();
            
            if ($userByEmail && $userByName) {
                // Если найдены оба, они должны указывать на одного и того же пользователя
                if ($userByEmail->id !== $userByName->id) {
                    throw new \Exception('Это имя уже используется другим пользователем');
                }
                $user = $userByEmail;
            } elseif ($userByEmail) {
                // Если найден только по email, но имя другое
                throw new \Exception('Этот email уже зарегистрирован с другим именем пользователя');
            } elseif ($userByName) {
                // Если найден только по имени, но email другой
                throw new \Exception('Это имя уже используется другим пользователем');
            } else {
                // Создаем нового пользователя, так как не найден ни по email, ни по имени
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