<?php

use App\Http\Controllers\ProposalDocumentController;
use Illuminate\Support\Facades\Route;

// A raiz do site leva direto para o login do painel.
Route::redirect('/', '/admin/login');

// PDF da proposta (visualizador da edição); acesso checado no controller.
Route::get('/proposals/{proposal}/document', ProposalDocumentController::class)->name('proposals.document');
