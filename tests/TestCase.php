<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test environment variables
        $_ENV['TWILIO_ACCOUNT_SID'] = 'ACtest123456789abcdef123456789abcdef';
        $_ENV['TWILIO_AUTH_TOKEN'] = 'test_auth_token_123456789abcdef';
        $_ENV['TWILIO_PHONE_NUMBER'] = '+15551234567';
        $_ENV['SKIP_WEBHOOK_VALIDATION'] = 'true';
    }
}
