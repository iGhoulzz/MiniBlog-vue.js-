<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Reaction;
use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $users = User::factory(50)->create();

        $reactionTypes = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
        $sourceImage = 'posts/eBeVibCxFVVKeguMJcvKvOqWs0lgqXLDAblt6Ynh.png';
        $hasSourceImage = Storage::disk('public')->exists($sourceImage);

        $users->each(function ($user) use ($users, $reactionTypes, $sourceImage, $hasSourceImage) {
            $posts = \App\Models\Post::factory(20)->create([
                'user_id' => $user->id
            ]);

            $posts->each(function ($post) use ($users, $reactionTypes, $sourceImage, $hasSourceImage) {
                \App\Models\Comment::factory(5)->create([
                    'post_id' => $post->id,
                    'user_id' => $users->random()->id
                ]);

                // Create Reactions
                // Pick a random number of unique users to react (e.g., 0 to 10 users)
                // Ensure we don't pick all users if the pool is small (though 50 is fine)
                $reactorCount = rand(0, 15);
                if ($reactorCount > 0) {
                    $reactors = $users->random($reactorCount);
                    foreach ($reactors as $reactor) {
                        Reaction::create([
                            'user_id' => $reactor->id,
                            'reactionable_id' => $post->id,
                            'reactionable_type' => \App\Models\Post::class,
                            'type' => $reactionTypes[array_rand($reactionTypes)],
                        ]);
                    }
                }

                // Attach Media (Image)
                if ($hasSourceImage) {
                    // Create a copy of the image for this post so deletion doesn't affect others
                    $extension = pathinfo($sourceImage, PATHINFO_EXTENSION);
                    $newFileName = 'posts/' . Str::random(40) . '.' . $extension;
                    Storage::disk('public')->copy($sourceImage, $newFileName);

                    Media::create([
                        'mediable_type' => \App\Models\Post::class,
                        'mediable_id' => $post->id,
                        'file_path' => $newFileName,
                        'file_type' => 'image/png', // Simplified for seeding
                        'disk' => 'public',
                        'collection' => 'default',
                    ]);
                }
            });
        });
    }
}
