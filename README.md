# Build a phone menu (IVR)

This app shows how to build a basic [phone menu (<abbr>IVR</abbr> Interactive Voice Response)][twilio_ivr_url] system with PHP and Twilio.

## Overview

- A user calls their Twilio phone number
- The user is then presented with three options:
  1. Talk to sales
  2. Hear the company's hours of operation;
  3. Hear the company's address
- If the user chooses one of the first two options, they hear a voice response providing more information
- If they choose the third option, they will receive an SMS with the company's address

## Prerequisites/Requirements

To run the code, you will need the following:

- PHP 8.3 or above
- [Composer][composer_url] installed globally
- A network testing tool such as [curl][curl_url], [Resterm][resterm_url], or [Postman][postman_url]
- [ngrok][ngrok_url] and a free ngrok account
- A Twilio account (free or paid) with an active phone number that can send SMS.
  If you are new to Twilio, [create a free account][try_twilio_url].

[composer_url]: https://getcomposer.org
[curl_url]: https://curl.se/
[ngrok_url]: https://ngrok.com/
[postman_url]: https://www.postman.com/
[resterm_url]: https://github.com/unkn0wn-root/resterm
[try_twilio_url]: https://www.twilio.com/try-twilio
[twilio_ivr_url]: https://www.twilio.com/en-us/use-cases/ivr

