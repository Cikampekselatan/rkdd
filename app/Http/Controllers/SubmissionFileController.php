<?php

namespace App\Http\Controllers;

use App\Models\SubmissionFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionFileController extends Controller
{
    public function __invoke(SubmissionFile $submissionFile): StreamedResponse
    {
        $submissionFile->load('version.submission');
        $this->authorize('view', $submissionFile);
        abort_unless(Storage::disk('local')->exists($submissionFile->stored_path), 404);

        return Storage::disk('local')->download($submissionFile->stored_path, $submissionFile->original_name, ['Content-Type' => $submissionFile->mime_type]);
    }
}
