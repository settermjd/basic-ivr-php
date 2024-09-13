# Basic Twilio IVR with PHP

This app shows how to build a basic [IVR (Interactive voice response)][twilio_ivr_url] system with PHP and Twilio.

## Overview

- A user calls their Twilio phone number
- The user is then presented with 3 options: 1) Talk to sales, 2) The company's hours of operation, or 3) The company's address
- If the user chooses one of the first two options, they get a voice response on the call with more information
- If they choose the third option, they will receive an SMS with the company's address information

## Prerequisites/Requirements

To run the code, you will need the following:

- PHP 8.3
- [Composer][composer_url] installed globally
- A network testing tool such as [curl][curl_url] or [Postman][postman_url]
- [ngrok][ngrok_url] and a free ngrok account
- A Twilio account (free or paid) with an active phone number that can send SMS.
  If you are new to Twilio, [create a free account][try_twilio_url].

[composer_url]: https://getcomposer.org
[ngrok_url]: https://ngrok.com/
[try_twilio_url]: https://www.twilio.com/try-twilio
[curl_url]: https://curl.se/
[postman_url]: https://www.postman.com/
[twilio_ivr_url]: https://www.twilio.com/en-us/use-cases/ivr