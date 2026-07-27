<?php

namespace App\Http\Controllers;

use App\Models\ProgramBatch;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProgramContextController extends Controller
{
    public function update(Request $request, ProgramContextService $context): RedirectResponse
    {
        $data = $request->validate([
            'program_batch_id' => ['required', 'integer', 'exists:program_batches,id'],
        ]);

        $batch = ProgramBatch::query()->findOrFail($data['program_batch_id']);

        abort_unless($context->setActiveBatch($request->user(), $batch), 403);

        return back()->with('success', 'Konteks program aktif berhasil diganti.');
    }
}
