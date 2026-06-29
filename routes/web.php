<?php

use Illuminate\Support\Facades\Route;

// A raiz do site leva direto para o login do painel.
Route::redirect('/', '/admin/login');
