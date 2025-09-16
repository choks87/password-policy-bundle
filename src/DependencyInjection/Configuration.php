<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\DependencyInjection;

use Choks\PasswordPolicy\Enum\PeriodUnit;
use Choks\PasswordPolicy\Service\ConfigurationPolicyProvider;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @codeCoverageIgnore
 */
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
                    ->booleanNode('produce_json_bad_request')
                        ->info('Send default bad request when JSON request is made and policy fails.')
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
                    ->arrayNode('expiration')
                        ->children()
                            ->append($this->getTimeFrameConfiguration('expires_after'))
                        ->end()
                    ->end()
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
                        ->append($this->getTimeFrameConfiguration('period'))
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    private function getTimeFrameConfiguration(string $nodeName): NodeDefinition
    {
        $node = new ArrayNodeDefinition($nodeName);

        /**
         * @phpstan-ignore-next-line
         */
        $node
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
             ->end();

        return $node;
    }

    private function getStorageConfiguration(): NodeDefinition
    {
        $node = new ArrayNodeDefinition('storage');

        $defaultStorageValue = [
            'dbal' => [
                'table'      => 'password_history',
                'connection' => 'default',
            ],
        ];

        /**
         * @phpstan-ignore-next-line
         */
        $node
            ->treatNullLike($defaultStorageValue)
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

                ->arrayNode('cache')
                    ->children()
                        ->scalarNode('adapter')
                            ->info('Cache Adapter, must be instance of ' . AdapterInterface::class)
                        ->end()
                        ->scalarNode('key_prefix')
                            ->defaultValue('password_history')
                            ->info('Each subject (user) password history is on key. Needs to be prefixed.')
                        ->end()
                    ->end()
                ->end()

            ->end();

        return $node;
    }
}