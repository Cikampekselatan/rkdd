<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProgramAssetController extends Controller
{
    public function __invoke(Program $program, string $kind): Response
    {
        abort_unless(in_array($kind, ['logo', 'banner'], true), 404);

        $path = $kind === 'logo' ? $program->logo_path : $program->banner_path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
