<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\DependencyInjection;

use Choks\PasswordPolicy\Enum\PeriodUnit;
use Choks\PasswordPolicy\Service\ConfigurationPolicyProvider;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('password_policy');

        /**
         * @phpstan-ignore-next-line
         */
        $treeBuilder
            ->getRootNode()
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')
                        ->info('Setting bundle off. Probably for debugging purposes.')
                        ->defaultTrue()
                    ->end()
                    ->scalarNode('policy_provider')
                        ->defaultValue(ConfigurationPolicyProvider::class)
                        ->info("Policy Provider. Default is configuration provider.")
                    ->end()
                    ->scalarNode('special_chars')
                        ->defaultValue("\"'!@#$%^&*()_+=-`~.,;:<>[]{}\\|")
                        ->info('What is considered special character.')
                    ->end()
                    ->booleanNode('trim')
                        ->defaultValue(true)
                        ->info('Trim whitespaces when checking password.')
                    ->end()
                    ->scalarNode('salt')
                        ->defaultValue('%env(APP_SECRET)%')
                        ->info('Salt used for OpenSSL encryption of passwords into history.')
                    ->end()
                    ->scalarNode('cipher_method')
                        ->defaultValue('aes-128-ctr')
                        ->info('Cipher method used for OpenSSL encryption of passwords into history.')
                    ->end()
                    ->append($this->getPolicyConfiguration())
                    ->append($this->getStorageConfiguration())
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function getPolicyConfiguration(): NodeDefinition
    {
        $node = new ArrayNodeDefinition('policy');
        /**
         * @phpstan-ignore-next-line
         */
        $node
            ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('character')
                        ->children()
                            ->integerNode('min_length')->defaultValue(null)->info('Total minimum character(s).')->end()
                            ->integerNode('numbers')->defaultValue(null)->info('At least numeric character(s).')->end()
                            ->integerNode('lowercase')->defaultValue(null)->info('At least lowercase character(s).')->end()
                            ->integerNode('uppercase')->defaultValue(null)->info('At least uppercase character(s).')->end()
                            ->integerNode('special')->defaultValue(null)->info('At least special character(s).')->end()
                        ->end()
                    ->end()
                    ->arrayNode('history')
                    ->children()
                        ->scalarNode('not_used_in_past_n_passwords')
                            ->defaultValue(null)
                            ->info('Not used in last N passwords.')
                        ->end()
                        ->arrayNode('period')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('unit')
                                    ->defaultValue(null)
                                    ->info(
                                        sprintf("Unit of time, possible values: %s .", \implode(', ', PeriodUnit::values()))
                                    )
                                    ->values(PeriodUnit::values())
                                ->end()
                                ->scalarNode('value')
                                    ->defaultValue(null)
                                    ->info("Value of unit.")
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    private function getStorageConfiguration(): NodeDefinition
    {
        $node = new ArrayNodeDefinition('storage');
        /**
         * @phpstan-ignore-next-line
         */
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('dbal')
                    ->children()
                        ->scalarNode('table')
                            ->defaultValue('password_history')
                            ->info('Table name that will be created and used.')
                        ->end()
                        ->scalarNode('connection')
                            ->defaultValue('default')
                            ->info("DBAL Connection used for storage. If not set 'default' is used.")
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}