<?php
/**
 * User: nemishkor
 * Date: 14.05.20
 */

declare(strict_types=1);


namespace Nemishkor\TwilioBundle;


use Exception;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Twilio\Rest\Verify\V2\Service\VerificationCheckInstance;
use Twilio\Rest\Verify\V2\Service\VerificationInstance;
use Twilio\Rest\Verify\V2\ServiceInstance as VerifyService;
use UnexpectedValueException;

final class Twilio {

    private $verifyServicesConfiguration;
    private $client;
    private $nameConverter;

    public function __construct(array $verifyServicesConfiguration, Client $client) {
        $this->verifyServicesConfiguration = $verifyServicesConfiguration;
        $this->client = $client;
        $this->nameConverter = new CamelCaseToSnakeCaseNameConverter(null, true);
    }

    /**
     * @param array $config
     * @return VerifyService
     * @throws Exception
     */
    private function createVerifyServiceFromConfig(array $config): VerifyService {

        if (strlen($config['friendly_name']) > 30) {
            throw new Exception('Friendly name must contains 30 or less characters');
        }

        return $this->client->verify->v2->services->create(
            str_replace(' ', '_', $config['friendly_name']),
            $this->denormalizeConfig($config)
        );
    }

    private function denormalizeConfig(array $config): array {
        return array_combine(
            array_map(
                function ($k) {
                    return $this->nameConverter->denormalize((string)$k);
                },
                array_keys($config)
            ),
            array_map(
                function ($v) {
                    switch (true) {
                        case is_string($v):
                            return $this->nameConverter->denormalize($v);
                            break;
                        case is_array($v):
                            return $this->denormalizeConfig($v);
                            break;
                    }

                    return $v;
                },
                array_values($config)
            )
        );
    }

    /**
     * @return VerifyService
     * @throws Exception
     */
    private function getFirstService(): VerifyService {

        $services = $this->client->verify->v2->services->read();

        if (count($services) === 1) {
            return array_values($services)[0];
        }

        if (count($this->verifyServicesConfiguration) === 0) {
            throw new UnexpectedValueException(
                sprintf(
                    'Not found any verify services in configuration. Add service declaration to file "config/packages/twilio.yaml"'
                )
            );
        }

        return $this->createVerifyServiceFromConfig(array_values($this->verifyServicesConfiguration)[0]);
    }

    /**
     * @param string $name
     * @return VerifyService
     * @throws Exception
     */
    private function createVerifyServiceByName(string $name): VerifyService {

        $configurations = array_filter(
            $this->verifyServicesConfiguration,
            static function (array $config) use ($name): bool {
                return $config['friendly_name'] === $name;
            }
        );

        if (count($configurations) !== 1) {
            throw new UnexpectedValueException(
                sprintf(
                    'Expected 1 verify service configurations with friendly name "%s" but found %s',
                    $name,
                    count($configurations)
                )
            );
        }

        return $this->createVerifyServiceFromConfig(array_values($configurations)[0]);
    }

    /**
     * @param string $serviceId
     * @return VerifyService
     * @throws Exception
     */
    private function getVerifyService(string $serviceId) {

        $services = $this->client->verify->v2->services->read();

        $services = array_filter(
            $services,
            static function (VerifyService $service) use ($serviceId): bool {
                return $serviceId === $service->friendlyName || $serviceId === $service->sid;
            }
        );
        if (count($services) > 1) {
            throw new UnexpectedValueException(
                sprintf(
                    'Expected less then 2 verify services with name "%s" but found %s',
                    $serviceId,
                    count($services)
                )
            );
        }

        if (count($services) === 1) {
            return array_values($services)[0];
        }

        return $this->createVerifyServiceByName($serviceId);
    }

    /**
     * @param string $to
     * @param string $channel
     * @param array $options
     * @param string|null $service - friendly name or sid
     * @return VerificationInstance
     * @throws Exception
     */
    public function verify(
        string $to,
        string $channel,
        array $options = [],
        ?string $service = null
    ): VerificationInstance {
        return $this->client->verify->v2
            ->services($service === null ? $this->getFirstService()->sid : $this->getVerifyService($service)->sid)
            ->verifications
            ->create($to, $channel, $options);
    }

    /**
     * @param string $code
     * @param string $number
     * @param array $options
     * @param string|null $service - friendly name or sid
     * @throws TwilioException
     */
    public function checkVerify(
        string $code,
        string $number,
        array $options = [],
        ?string $service = null
    ): VerificationCheckInstance {
        return $this->client->verify->v2
            ->services($service === null ? $this->getFirstService()->sid : $this->getVerifyService($service)->sid)
            ->verificationChecks
            ->create($code, array_merge($options, ["to" => $number]));
    }

}
