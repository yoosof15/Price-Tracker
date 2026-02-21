<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_is_disabled(): void
    {
        $this->markTestSkipped('Password reset is disabled in phone-based auth.');
    }
}
