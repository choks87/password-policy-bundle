<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Resources\App;

use Choks\PasswordPolicy\Enum\PeriodUnit;
use Choks\PasswordPolicy\PasswordPolicy;
use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class PasswordPolicyTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new SecurityBundle(),
            new DAMADoctrineTestBundle(),
            new PasswordPolicy(),
        ];
    }

    /**
     * @phpstan-ignore-next-line
     */
    private function configureContainer(
        ContainerConfigurator $container,
        LoaderInterface       $loader,
        ContainerBuilder      $builder,
    ): void {

        $container->extension(
            'password_policy',
            [
                'storage' => $this->getStorageConfig(),
                'salt'    => 'foo',
                'policy'  => [
                    'expiration' => [
                        'expires_after' => [
                            'unit'  => PeriodUnit::DAY->value,
                            'value' => 1,
                        ],
                    ],
                    'character'  => [
                        'min_length' => 8,
                        'numbers'    => 1,
                        'lowercase'  => 1,
                        'uppercase'  => 1,
                        'special'    => 1,
                    ],
                    'history'    => [
                        'not_used_in_past_n_passwords' => 3,
                        'period'                       => [
                            'unit'  => PeriodUnit::DAY->value,
                            'value' => 1,
                        ],
                    ],
                ],
            ],
        );

        $container->extension(
            'security',
            [
                'password_hashers' => [
                    PasswordAuthenticatedUserInterface::class => 'auto',
                ],
                'firewalls'        => [
                    'test' => [
                        'security' => false,
                    ],
                ],
            ]
        );

        $container->extension(
            'framework',
            [
                'test'                  => true,
                'http_method_override'  => false,
                'handle_all_throwables' => true,
                'php_errors'            => [
                    'log' => true,
                ],
                'cache'                 => [
                    'app' => 'cache.adapter.array',
                ],
            ]
        );

        $container->extension('doctrine', [
            'dbal' => [
                'driver'         => 'pdo_mysql',
                'url'            => 'mysql://db:db@database/db',
                'use_savepoints' => true,
            ],
            'orm'  => [
                'auto_generate_proxy_classes' => true,
                'naming_strategy'             => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'auto_mapping'                => true,
                'enable_lazy_ghost_objects'   => true,
                'mappings'                    => [
                    'Tests' => [
                        'is_bundle' => false,
                        'type'      => 'attribute',
                        'dir'       => __DIR__.'/Entity',
                        'prefix'    => 'Choks\PasswordPolicy\Tests\Resources\App',
                    ],
                ],
            ],
        ]);

        $container->services()->set('logger', NullLogger::class);
    }

    protected function getStorageConfig(): array
    {
        return [
            'array' => null,
        ];
    }
}