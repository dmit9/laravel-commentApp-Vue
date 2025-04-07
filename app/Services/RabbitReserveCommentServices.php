<?php

namespace App\Services;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use HTMLPurifier;
use HTMLPurifier_Config;

class RabbitReserveCommentServices
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'a[href|title],code,strong,i');
        $this->purifier = new HTMLPurifier($config);
    }

    public function createComment( $data)
    {
        $name = $data["name"];
        $email = $data["email"];
        $cleanText = $this->purifier->purify($data['text']);
        $filePath = null;
        $filePath = !empty($data["filename"]) ? $this->saveUploadedFile($data) : null;    
            
        $users = User::where('email', $email)
                 ->orWhere('name', $name)
                 ->get();

        $userByEmail = $users->firstWhere('email', $email);
        $userByName = $users->firstWhere('name', $name);

        if ($userByEmail && $userByName) {
            if ($userByEmail->id !== $userByName->id) {
                throw new \Exception('Имя уже используется другим пользователем.');
            }
            $user = $userByEmail;
        } elseif ($userByEmail) {
            // Проверим, не занят ли $name другим пользователем
            $conflict = User::where('name', $name)->where('id', '!=', $userByEmail->id)->exists();
            if ($conflict) {
                dump("Exception name ".$name);
                throw new \Exception('Имя уже используется другим пользователем.');
            }
    
            $userByEmail->name = $name;
            $userByEmail->save();
            $user = $userByEmail;
        } elseif ($userByName) {
            // Проверим, не занят ли $email другим пользователем
            $conflict = User::where('email', $email)->where('id', '!=', $userByName->id)->exists();
            if ($conflict) {
                dump("Exception ByEmail ".$email);
                throw new \Exception('Email уже зарегистрирован другим пользователем.');
            }
    
            $userByName->email = $email;
            $userByName->save();
            $user = $userByName;
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
            ]);
        }

        $comment = Comment::create([
            'user_id' => $user->id,
            'text' => $cleanText,
            'image_path' => $filePath ,
            'parent_id' => null
        ]);
        dump("comment  ".$user->id);

        return [
            'comment' => $comment,
            'user' => $user
        ];
    }

    protected function saveUploadedFile(array $fileData)
    {
        try {
            $fileContent = base64_decode($fileData["content"]);
            $fileName = 'uploads/'.uniqid().'_'.$fileData["filename"];
            Storage::disk('public')->put($fileName, $fileContent);
            return $fileName;
        } catch (\Exception $e) {
            Log::error('Ошибка сохранения файла: '.$e->getMessage());
            return null;
        }
    }
}
