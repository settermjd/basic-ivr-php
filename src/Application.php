<?php

declare(strict_types=1);

namespace App;

use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\App;
use Slim\Psr7\Response;
use Twilio\Rest\Client;
use Twilio\TwiML\Voice\Gather;
use Twilio\TwiML\VoiceResponse;
use Twilio\Security\RequestValidator;

class Application
{
    private App $app;
    private Container $container;

    private const VOICE = 'Google.en-US-Chirp3-HD-Aoede';

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? new Container();
        $this->setupContainer();
        AppFactory::setContainer($this->container);
        $this->app = AppFactory::create();
        $this->setupRoutes();
    }

    private function setupContainer(): void
    {
        if (!$this->container->has(Client::class)) {
            $this->container->set(Client::class, function () {
                return new Client($_ENV["TWILIO_ACCOUNT_SID"], $_ENV["TWILIO_AUTH_TOKEN"]);
            });
        }
    }

    private function setupRoutes(): void
    {
        $webhookValidation = $this->createWebhookValidationMiddleware();

        $this->app->post('/', [$this, 'handleInitialCall'])->add($webhookValidation);

        $this->app->post('/gather', [$this, 'handleGatherInput'])->add($webhookValidation);
    }

    private function createWebhookValidationMiddleware(): callable
    {
        return function (ServerRequestInterface $request, RequestHandlerInterface $handler) {

            if (isset($_ENV['SKIP_WEBHOOK_VALIDATION']) && $_ENV['SKIP_WEBHOOK_VALIDATION'] === 'true') {
                return $handler->handle($request);
            }

            $validator = new RequestValidator($_ENV['TWILIO_AUTH_TOKEN']);

            // Check for X-Forwarded-Proto header (ngrok sets this)
            $url = (string) $request->getUri();
            $forwardedProto = $request->getHeaderLine('X-Forwarded-Proto');

            if (!empty($forwardedProto) && $forwardedProto === 'https') {
                $uri = $request->getUri();
                $url = 'https://' . $uri->getHost() . $uri->getPath();
            }

            $parsedBody = $request->getParsedBody();
            $postData = is_array($parsedBody) ? $parsedBody : [];

            $signature = $request->getHeaderLine('X-Twilio-Signature');

            if (!$validator->validate($signature, $url, $postData)) {
                $response = new Response();
                return $response->withStatus(403, 'Forbidden');
            }

            return $handler->handle($request);
        };
    }

    public function handleInitialCall(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $voiceResponse = new VoiceResponse();
        $gather = $voiceResponse->gather(['numDigits' => 1, 'action' => '/gather']);
        $prompt = 'To talk to sales, press 1. For our hours of operation, press 2. '
            . 'For our address, press 3.';
        $this->say($gather, $prompt);
        $voiceResponse->redirect('/');

        return $this->respond($response, $voiceResponse);
    }

    public function handleGatherInput(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $voiceResponse = new VoiceResponse();
        $data = $request->getParsedBody();

        if (!isset($data['Digits'])) {
            $voiceResponse->redirect('/');
            return $this->respond($response, $voiceResponse);
        }

        $digit = (int) $data['Digits'];
        $messages = [
            1 => 'You selected sales. You will now be forwarded to our sales '
                . 'department.',
            2 => 'We are open from 9am to 5pm every day but Sunday.',
            3 => 'We\'ll send you a text message with our address in a minute.',
        ];

        if (!isset($messages[$digit])) {
            $this->say($voiceResponse, 'Sorry, I don\'t understand that choice.');
            $voiceResponse->redirect('/');
            return $this->respond($response, $voiceResponse);
        }

        $this->say($voiceResponse, $messages[$digit]);

        if ($digit === 3 && isset($data['From'])) {
            $this->sendAddressSms($data['From']);
        }

        return $this->respond($response, $voiceResponse);
    }

    private function say(VoiceResponse|Gather $twiml, string $message): void
    {
        $twiml->say($message, ['voice' => self::VOICE]);
    }

    private function respond(ResponseInterface $response, VoiceResponse $voiceResponse): ResponseInterface
    {
        $response = $response->withHeader('Content-Type', 'application/xml');
        $response->getBody()->write($voiceResponse->asXML());

        return $response;
    }

    private function sendAddressSms(string $toNumber): void
    {
        /** @var Client $twilio */
        $twilio = $this->container->get(Client::class);
        $twilio->messages->create(
            $toNumber,
            [
                'body' => 'Here is our address: 8 Rue du Nom Fictif, 341, Paris',
                'from' => $_ENV['TWILIO_PHONE_NUMBER'],
            ]
        );
    }

    public function getApp(): App
    {
        return $this->app;
    }

    public function run(): void
    {
        $this->app->run();
    }
}
