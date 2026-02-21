<?php

namespace Tests\Feature\Auth;

<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_is_disabled(): void
    {
        $this->markTestSkipped('Verification is disabled in phone-based auth.');
    }
}
    }
}
