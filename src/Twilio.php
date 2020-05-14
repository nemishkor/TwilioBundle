<?php
/**
 * User: nemishkor
 * Date: 14.05.20
 */

declare(strict_types=1);


namespace Nemishkor\TwilioBundle;


use Twilio\Rest\Client;

class Twilio {

    private $servicesConfiguration;
    private $client;

    public function __construct(array $servicesConfiguration, Client $client) {
        $this->servicesConfiguration = $servicesConfiguration;
        $this->client = $client;
    }

}
