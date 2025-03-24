<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Captcha;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\Font;

class CaptchaController extends Controller
{
    /**
     * @throws \Exception
     */
    public function generate()
    {
        $manager = new ImageManager(new Driver());
        $img = $manager->create(150, 50);
        $img->fill('#f9fafb');
        $font = new Font(public_path('fonts/light-arial.ttf'), 28);
        $font->color('#343a40');
        $captchaText = Str::upper(Str::random(1));
        $token = Str::uuid();
        Captcha::create([
            'token' => $token,
            'text' => $captchaText,
            'expires_at' => now()->addMinutes(5),
        ]);
        Log::info("Капча сохранена text: " . $captchaText);
        Log::info("Токен капчи CaptchaController: " . $token);
        $img->text($captchaText, 75, 25, $font);
        $encoder = new PngEncoder();
        return response($img->encode($encoder))
            ->header('Content-Type', 'image/png')
            ->header('X-Captcha-Token', $token);
    }
}
