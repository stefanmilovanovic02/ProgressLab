<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgressPhotoSet;
use Illuminate\Support\Facades\Storage;

class OwnerProgressPhotoController extends Controller
{
    public function show(ProgressPhotoSet $progressPhoto, string $view)
    {
        abort_unless(in_array($view, ['front', 'side', 'back'], true), 404);

        $path = $progressPhoto->{$view . '_path'};
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
