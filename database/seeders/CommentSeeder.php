<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommentSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Comment::query()->delete();
        DB::statement('ALTER TABLE comments AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        User::all()->each(function ($user) {
            $numComments = rand(3, 7);
            for ($i = 0; $i < $numComments; $i++) {
                $comment = Comment::create([
                    'user_id' => $user->id,
                    'text' => fake()->sentence(20),
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);

                $numReplies = rand(5, 32);
                for ($j = 0; $j < $numReplies; $j++) {
                    Comment::create([
                        'user_id' => User::inRandomOrder()->first()->id,
                        'parent_id' => $comment->id,
                        'text' => fake()->sentence(28),
                        'created_at' => now()->subDays(rand(1, 30)),
                    ]);
                }
            }
        });
    }
}
