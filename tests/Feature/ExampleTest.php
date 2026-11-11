<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test unauthenticated root redirects to laboratory login gateway
     */
    public function test_root_redirects_to_login_gateway(): void
    {
        $response = $this->get('/');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test embryologist login page renders successfully
     */
    public function test_embryologist_login_screen_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Embryologist Authentication');
        $response->assertSee('Unique Special Key');
    }

    /**
     * Test administrator login page renders successfully
     */
    public function test_admin_login_screen_loads(): void
    {
        $response = $this->get('/login/admin');
        $response->assertStatus(200);
        $response->assertSee('Administrator Access');
    }
}
