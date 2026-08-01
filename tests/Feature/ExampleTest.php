<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_beranda_dialihkan_ke_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }
}
