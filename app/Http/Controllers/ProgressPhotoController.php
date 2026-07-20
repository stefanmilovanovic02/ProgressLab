<?php

namespace App\Http\Controllers;

use App\Models\ProgressPhotoSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use RuntimeException;
use Throwable;

class ProgressPhotoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'front_photo' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'])->max('2mb'),
            ],
            'side_photo' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'])->max('2mb'),
            ],
            'back_photo' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'])->max('2mb'),
            ],
        ]);

        $directory = 'progress-photos/' . $request->user()->id;
        $storedPaths = [];

        try {
            foreach (['front_photo', 'side_photo', 'back_photo'] as $field) {
                $path = $validated[$field]->store($directory, 'local');
                if (!$path) {
                    throw new RuntimeException('The progress photo could not be stored.');
                }
                $storedPaths[$field] = $path;
            }

            DB::transaction(function () use ($request, $storedPaths) {
                ProgressPhotoSet::query()->create([
                    'user_id' => $request->user()->id,
                    'front_path' => $storedPaths['front_photo'],
                    'side_path' => $storedPaths['side_photo'],
                    'back_path' => $storedPaths['back_photo'],
                    'captured_on' => now()->toDateString(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_values($storedPaths));
            throw $exception;
        }

        return redirect()
            ->to(route('add-today') . '#progress-photos')
            ->with('progress_photo_status', 'Your front, side, and back progress photos were saved privately.');
    }

    public function show(Request $request, ProgressPhotoSet $progressPhoto, string $view)
    {
        abort_unless($progressPhoto->user_id === $request->user()->id, 403);
        abort_unless(in_array($view, ['front', 'side', 'back'], true), 404);

        $path = $progressPhoto->{$view . '_path'};
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
