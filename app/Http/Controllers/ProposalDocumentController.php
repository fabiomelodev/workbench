<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serve o PDF da proposta "inline" para o visualizador da tela de edição.
 * Rota própria (em vez da URL temporária do disco) porque aquela responde com
 * CSP "sandbox", e o Chrome não abre PDF em documento sandboxed.
 */
class ProposalDocumentController extends Controller
{
    public function __invoke(Proposal $proposal): StreamedResponse
    {
        $user = auth()->user();

        abort_unless($user && $user->canAccessPanel(Filament::getPanel('admin')), 403);
        abort_unless($proposal->hasDocument(), 404);

        $extension = pathinfo($proposal->document, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk(Proposal::DOCUMENT_DISK)->response(
            $proposal->document,
            Str::slug($proposal->name) . '.' . $extension,
            ['Cache-Control' => 'private, no-store'],
            request()->boolean('download') ? 'attachment' : 'inline',
        );
    }
}
