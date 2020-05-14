<?php
/**
 * User: nemishkor
 * Date: 13.05.20
 */

declare(strict_types=1);


namespace Nemishkor\TwilioBundle\DependencyInjection;


use Exception;
use Nemishkor\TwilioBundle\Twilio;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Twilio\Rest\Client;

final class TwilioExtension extends Extension implements PrependExtensionInterface {

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container) {

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setAlias(Client::class, 'nemishkor_twilio_client');
        $container
            ->register('nemishkor_twilio_client', Client::class)
            ->addArgument($config['account'])
            ->addArgument($config['auth_token']);

        $container
            ->register('nemishkor_twilio', Twilio::class)
            ->setPublic(true)
            ->setAutowired(true)
            ->addArgument($config['verify']['services'])
            ->addArgument(new Reference('nemishkor_twilio_client'));

        $container->setAlias(Twilio::class, 'nemishkor_twilio');

    }

    /**
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function prepend(ContainerBuilder $container) {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        $loader->load('twilio.yaml');
    }
}
