<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application;
use DI\Container;
use Tests\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Twilio\Rest\Client;

/**
 * @covers \App\Application
 */
class ApplicationTest extends TestCase
{
    private Application $app;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();

        $mockMessage = $this->createMock(\Twilio\Rest\Api\V2010\Account\MessageInstance::class);

        $mockMessages = $this->createMock(\Twilio\Rest\Api\V2010\Account\MessageList::class);
        $mockMessages->method('create')->willReturn($mockMessage);

        $mockClient = $this->createMock(Client::class);
        $mockClient->messages = $mockMessages;

        $this->container->set(Client::class, $mockClient);
        $this->app = new Application($this->container);
    }

    public function testHandleInitialCallReturnsValidTwiML(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/');
        $response = (new ResponseFactory())->createResponse();

        $result = $this->app->handleInitialCall($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/xml', $result->getHeaderLine('Content-Type'));

        $body = (string) $result->getBody();
        $this->assertStringContainsString('<Response>', $body);
        $this->assertStringContainsString('<Gather', $body);
        $this->assertStringContainsString('To talk to sales, press 1', $body);
        $this->assertStringContainsString('<Redirect>/', $body);
    }

    public function testHandleGatherInputWithValidDigit3(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/gather')
            ->withParsedBody(['Digits' => '3', 'From' => $_ENV['TWILIO_PHONE_NUMBER']]);
        $response = (new ResponseFactory())->createResponse();

        $result = $this->app->handleGatherInput($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $this->assertStringContainsString('We\'ll send you a text message', $body);
    }

    public function testHandleGatherInputWithInvalidDigit(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/gather')
            ->withParsedBody(['Digits' => '9', 'From' => $_ENV['TWILIO_PHONE_NUMBER']]);
        $response = (new ResponseFactory())->createResponse();

        $result = $this->app->handleGatherInput($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $this->assertStringContainsString('Sorry, I don\'t understand', $body);
        $this->assertStringContainsString('<Redirect>/', $body);
    }
}
