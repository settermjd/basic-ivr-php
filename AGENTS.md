# AGENTS.md

## Development standards

* Follow PSR-12 coding standards

## Immutable code

Don't change this code:

* Webhook signature validation logic (implemented in $webhookValidation middleware using Twilio\Security\RequestValidator)

## Important commands

* composer test to run tests
* ccomposer run-script serve to start the app

## Common mistakes

* Don't send phone numbers in a format other than [E.164 format](https://en.wikipedia.org/wiki/E.164)

## Task cookbook

### Adding a new IVR option

1. Update the initial call handler to mention the new option
2. Add a new case in the gather handler switch statement
3. Add unit tests for the new option