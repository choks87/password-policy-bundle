<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\DependencyInjection;

use Choks\PasswordPolicy\Adapter\CacheStorageAdapter;
use Choks\PasswordPolicy\Adapter\DbalStorageAdapter;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Contract\PolicyProviderInterface;
use Choks\PasswordPolicy\Enum\PeriodUnit;
use Choks\PasswordPolicy\Service\ConfigurationPolicyProvider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension as SymfonyExtension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @psalm-type StorageConfig = array{}|array{
 *           dbal: array{
 *               connection: string,
 *               table: string
 *           },
*            cache: array{
 *                adapter: string,
 *                key_prefix: string
 *            },
 *            array: null,
 *       }
 *
 * @psalm-type Config = array{
 *     enabled: bool,
 *     policy_provider: class-string|string,
 *     special_chars: string,
 *     trim: bool,
 *     salt: string,
 *     cipher_method: string,
 *     connection: string,
 *     policy: array{
 *        character: array{
 *            min_length: integer|null,
 *            numbers: integer|null,
 *            lowercase: integer|null,
 *            uppercase: integer|null,
 *            special: integer|null
 *        },
 *        history: array{
 *            not_used_in_past_n_passwords: integer|null,
 *            period: array{
 *                unit: value-of<PeriodUnit>|null,
 *                value: integer|null
 *            }
 *        },
 *     },
 *     storage: StorageConfig
 * }
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * TODO: Split extension into more files to avoid `CouplingBetweenObjects`
 */
final class Extension extends SymfonyExtension
{
    public function getAlias(): string
    {
        return 'password_policy';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        if (empty($config['salt'])) {
            throw new InvalidConfigurationException(
                "Salt is required in password policy configuration. Do you have APP_SECRET set?"
            );
        }

        if ($config['policy_provider'] === ConfigurationPolicyProvider::class) {
            $this->registerDefaultPolicyProvider($container, $config['policy']);
        }

        $container->setParameter('password_policy.produce_json_bad_request', $config['produce_json_bad_request']);

        $container->setAlias(PolicyProviderInterface::class, $config['policy_provider']);

        $container->setParameter('password_policy.special_chars', $config['special_chars']);
        $container->setParameter('password_policy.trim', $config['trim']);
        $container->setParameter('password_policy.salt', $config['salt']);
        $container->setParameter('password_policy.cipher_method', $config['cipher_method']);

        $this->registerStorageAdapter($container, $config['storage'] ?? []);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/Config'));
        $loader->load('services.xml');
        $loader->load('commands.xml');
        $loader->load('events.xml');
    }

    /**
     * @param  array<string, mixed>  $policyConfig
     */
    private function registerDefaultPolicyProvider(ContainerBuilder $container, array $policyConfig): void
    {
        $container->setDefinition(
            ConfigurationPolicyProvider::class,
            (new Definition(ConfigurationPolicyProvider::class, [$policyConfig]))
                ->setPublic(false)
        );
    }

    /**
     * @param  StorageConfig  $storageConfig
     */
    private function registerStorageAdapter(ContainerBuilder $container, array $storageConfig): void
    {
        if (\count($storageConfig) === 0) {
            $storageConfig = [
                'dbal' => [
                    'table'      => 'password_history',
                    'connection' => 'default',
                ],
            ];
        }

        if (\count($storageConfig) > 1) {
            throw new InvalidConfigurationException('You can choose only one storage config.');
        }

        if (\array_key_exists('dbal', $storageConfig)) {
            $tableName           = $storageConfig['dbal']['table'];
            $connectionName      = $storageConfig['dbal']['connection'];
            $connectionServiceId = \sprintf('doctrine.dbal.%s_connection', $connectionName);
            $definition          = new Definition(DbalStorageAdapter::class);
            $definition
                ->setArgument('$connection', new Reference($connectionServiceId))
                ->setArgument('$tableName', $tableName)
                ->addTag('doctrine.event_listener', [
                    'event'      => 'postGenerateSchema',
                    'connection' => $connectionName,
                ])
            ;

            $container->setAlias('password_policy.storage.dbal.connection', $connectionServiceId);
            $container->setParameter('password_policy.storage.dbal.table', $tableName);
        }

        if (\array_key_exists('cache', $storageConfig)) {
            $definition          = new Definition(CacheStorageAdapter::class);
            $definition
                ->setArgument('$cache', new Reference($storageConfig['cache']['adapter']))
                ->setArgument('$keyPrefix', $storageConfig['cache']['key_prefix']);
        }

        $definition->setPublic(false);
        $container->setDefinition(StorageAdapterInterface::class, $definition);
    }
}