<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A raiz "/" deve redirecionar para a tela de login do painel.
     */
    public function test_root_redirects_to_admin_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin/login');
    }
}
