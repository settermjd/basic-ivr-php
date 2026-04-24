<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application;
use DI\Container;
use Tests\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Twilio\Rest\Client;

/**
 * @covers \App\Application
 */
class WebhookIntegrationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Twilio client for integration tests
        $container = new Container();
        $mockClient = $this->createMock(Client::class);
        $container->set(Client::class, $mockClient);

        $this->app = new Application($container);
    }

    public function testInitialCallWebhookFlow(): void
    {
        $slimApp = $this->app->getApp();

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'CallSid' => 'CA1234567890abcdef',
                'From' => '+14155551234',
                'To' => $_ENV['TWILIO_PHONE_NUMBER']
            ]);

        $response = $slimApp->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/xml', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $body);
        $this->assertStringContainsString('<Response>', $body);
        $this->assertStringContainsString('<Gather', $body);
        $this->assertStringContainsString('numDigits="1"', $body);
        $this->assertStringContainsString('action="/gather"', $body);
    }

    public function testGatherWebhookFlowInvalidOption(): void
    {
        $slimApp = $this->app->getApp();

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/gather')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'CallSid' => 'CA1234567890abcdef',
                'From' => '+14155551234',
                'To' => $_ENV['TWILIO_PHONE_NUMBER'],
                'Digits' => '9'
            ]);

        $response = $slimApp->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Sorry, I don\'t understand', $body);
        $this->assertStringContainsString('<Redirect>/', $body);
    }
}
