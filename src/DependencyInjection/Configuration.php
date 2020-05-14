<?php
/**
 * User: nemishkor
 * Date: 13.05.20
 */

declare(strict_types=1);


namespace Nemishkor\TwilioBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface {

    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder('twilio');

        $treeBuilder
            ->getRootNode()
            ->children()
            ->scalarNode('account')
            ->isRequired()
            ->end()
            ->scalarNode('auth_token')
            ->isRequired()
            ->end()
            ->arrayNode('verify')
            ->children()
                ->arrayNode('services')
                ->isRequired()
                ->arrayPrototype()
                ->children()
                    ->scalarNode('friendly_name')->isRequired()->end()
                    ->integerNode('code_length')->min(4)->max(10)->end()
                    ->booleanNode('lookup_enabled')->end()
                    ->booleanNode('skip_sms_to_landlines')->end()
                    ->booleanNode('dtmf_input_required')->end()
                    ->scalarNode('tts_name')->end()
                    ->booleanNode('psd2_enabled')->end()
                    ->booleanNode('do_not_share_warning_enabled')->end()
                    ->booleanNode('custom_code_enabled')->end()
                ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
