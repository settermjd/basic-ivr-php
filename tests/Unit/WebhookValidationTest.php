<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Twilio\Security\RequestValidator;

/**
 * @covers \Twilio\Security\RequestValidator
 */
class WebhookValidationTest extends TestCase
{
    private RequestValidator $validator;
    private string $authToken;
    private const TEST_URL = 'https://example.com/webhook';

    protected function setUp(): void
    {
        parent::setUp();
        $this->authToken = 'test_auth_token_123456789abcdef';
        $this->validator = new RequestValidator($this->authToken);
    }

    public function testValidSignatureIsAccepted(): void
    {
        $url = self::TEST_URL;
        $postData = [
            'CallSid' => 'CA1234567890abcdef',
            'From' => '+14155551234',
            'To' => '+15551234567'
        ];
        $signature = $this->validator->computeSignature($url, $postData);
        $isValid = $this->validator->validate($signature, $url, $postData);
        $this->assertTrue($isValid, 'Valid signature should be accepted');
    }

    public function testInvalidSignatureIsRejected(): void
    {
        $url = self::TEST_URL;
        $postData = [
            'CallSid' => 'CA1234567890abcdef',
            'From' => '+14155551234',
            'To' => '+15551234567'
        ];

        $invalidSignature = 'invalid_signature_123';
        $isValid = $this->validator->validate($invalidSignature, $url, $postData);
        $this->assertFalse($isValid, 'Invalid signature should be rejected');
    }

    public function testTamperedDataIsRejected(): void
    {
        $url = self::TEST_URL;
        $originalData = [
            'CallSid' => 'CA1234567890abcdef',
            'From' => '+14155551234',
            'To' => '+15551234567'
        ];

        $signature = $this->validator->computeSignature($url, $originalData);
        $tamperedData = $originalData;
        $tamperedData['From'] = '+14155559999';
        $isValid = $this->validator->validate($signature, $url, $tamperedData);
        $this->assertFalse($isValid, 'Tampered data should be rejected');
    }

    public function testDifferentUrlIsRejected(): void
    {
        $originalUrl = self::TEST_URL;
        $postData = [
            'CallSid' => 'CA1234567890abcdef',
            'From' => '+14155551234',
            'To' => '+15551234567'
        ];
        $signature = $this->validator->computeSignature($originalUrl, $postData);
        $differentUrl = 'https://example.org/webhook';
        $isValid = $this->validator->validate($signature, $differentUrl, $postData);
        $this->assertFalse($isValid, 'Different URL should be rejected');
    }
}
