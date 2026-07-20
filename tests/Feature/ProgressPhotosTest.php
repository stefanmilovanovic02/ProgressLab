<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackDailyLogin;
use App\Models\ProgressPhotoSet;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgressPhotosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(TrackDailyLogin::class);
        Carbon::setTestNow('2026-07-20 12:00:00');
        Storage::fake('local');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('progress_photo_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('front_path');
            $table->string('side_path');
            $table->string('back_path');
            $table->date('captured_on');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('progress_photo_sets');
        Schema::dropIfExists('users');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_user_can_save_a_private_three_angle_photo_set(): void
    {
        $user = $this->createUser('photos@example.test');

        $response = $this->actingAs($user)->post(route('progress-photos.store'), [
            'front_photo' => $this->photo('front.png'),
            'side_photo' => $this->photo('side.png'),
            'back_photo' => $this->photo('back.png'),
        ]);

        $response->assertRedirect(route('add-today') . '#progress-photos');
        $this->assertDatabaseHas('progress_photo_sets', [
            'user_id' => $user->id,
        ]);

        $photoSet = ProgressPhotoSet::query()->firstOrFail();
        $this->assertSame('2026-07-20', $photoSet->captured_on->toDateString());
        Storage::disk('local')->assertExists([
            $photoSet->front_path,
            $photoSet->side_path,
            $photoSet->back_path,
        ]);
    }

    public function test_progress_photos_can_only_be_viewed_by_their_owner(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherUser = $this->createUser('other@example.test');
        Storage::disk('local')->put('progress-photos/1/front.png', 'private image');

        $photoSet = ProgressPhotoSet::query()->create([
            'user_id' => $owner->id,
            'front_path' => 'progress-photos/1/front.png',
            'side_path' => 'progress-photos/1/side.png',
            'back_path' => 'progress-photos/1/back.png',
            'captured_on' => '2026-07-20',
        ]);

        $this->actingAs($otherUser)
            ->get(route('progress-photos.show', [$photoSet, 'front']))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('progress-photos.show', [$photoSet, 'front']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=3600, private');
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'name' => 'Progress User',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function photo(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
