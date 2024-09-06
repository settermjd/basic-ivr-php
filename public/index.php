<?php
declare(strict_types=1);

use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();
$dotenv
    ->required(['TWILIO_ACCOUNT_SID', 'TWILIO_AUTH_TOKEN'])
    ->notEmpty();

$container = new Container();
$container->set(
    Client::class,
    fn () => new Client($_ENV["TWILIO_ACCOUNT_SID"], $_ENV["TWILIO_AUTH_TOKEN"])
);

AppFactory::setContainer($container);
$app = AppFactory::create();

// Receives the initial call and provides the options that the IVR supports
$app->post('/', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
    $voiceResponse = new VoiceResponse();
    $gather = $voiceResponse->gather(array('numDigits' => 1, 'action' => '/gather'));
    $gather->say('To talk to sales, press 1. For our hours of operation, press 2. For our address, press 3.');
    $voiceResponse->redirect('/');

    $response->withHeader('Content-Type', 'application/xml');
    $response->getBody()->write($voiceResponse->asXML());

    return $response;
});

// Respond to the user's input
$app->post('/gather', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $response->withHeader('Content-Type', 'application/xml');
        $voiceResponse = new VoiceResponse();

        if (! array_key_exists('Digits', $_POST)) {
            $voiceResponse->redirect('/');
            $response->getBody()->write($voiceResponse);
            return $response;
        }

        switch ((int) $_POST['Digits']) {
            case 1:
                $voiceResponse->say('You selected sales. You will now be forwarded to our sales department.');
                break;
            case 2:
                $voiceResponse->say('We are open from 9am to 5pm every day but Sunday.');
                break;
            case 3:
                $voiceResponse->say('We will send you a text message with our address in a minute.');
                /** @var Client $twilio */
                $twilio = $this->get(Client::class);
                $twilio
                    ->messages
                    ->create(
                        $request->getParsedBody()['From'],
                        [
                            'body' => 'Here is our address: 375 Beale St #300, San Francisco, CA 94105, USA',
                            'from' => $_ENV['TWILIO_PHONE_NUMBER'],
                        ]
                    );
                break;
            default:
                $voiceResponse->say('Sorry, I don\'t understand that choice.');
                $voiceResponse->redirect('/');
        }
        $response->getBody()->write($voiceResponse->asXML());

        return $response;
    }
);

$app->run();