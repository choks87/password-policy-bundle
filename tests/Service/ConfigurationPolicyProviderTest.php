<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Service;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Service\ConfigurationPolicyProvider;
use Choks\PasswordPolicy\Tests\KernelTestCase;

/**
 * @psalm-import-type Config from \Choks\PasswordPolicy\Service\ConfigurationPolicyProvider
 */
final class ConfigurationPolicyProviderTest extends KernelTestCase
{
    /**
     * @dataProvider providerForGetPolicyTest
     *
     * @param  Config  $config
     */
    public function testGetPolicy(array $config, bool $haveHistoryPolicySet): void
    {
        $provider = new ConfigurationPolicyProvider($config);
        $policy   = $provider->getPolicy($this->createMock(PasswordPolicySubjectInterface::class));

        self::assertEquals($config['character']['min_length'], $policy->getCharacterPolicy()->getMinLength());
        self::assertEquals($config['character']['numbers'], $policy->getCharacterPolicy()->getMinNumerics());
        self::assertEquals($config['character']['lowercase'], $policy->getCharacterPolicy()->getMinLowercase());
        self::assertEquals($config['character']['uppercase'], $policy->getCharacterPolicy()->getMinUppercase());
        self::assertEquals($config['character']['special'], $policy->getCharacterPolicy()->getMinSpecials());

        self::assertEquals($haveHistoryPolicySet, null !== $policy->getHistoryPolicy());

        if ($haveHistoryPolicySet) {
            $history = $config['history'];
            self::assertEquals($history['period']['unit'], $policy->getHistoryPolicy()?->getUnit());
            self::assertEquals($history['period']['value'], $policy->getHistoryPolicy()?->getPeriod());
            self::assertEquals(
                $history['not_used_in_past_n_passwords'],
                $policy->getHistoryPolicy()?->getLast()
            );
        }
    }

    public static function providerForGetPolicyTest(): iterable
    {
        yield 'Full populated config' => [
            [
                'character' => [
                    'min_length' => 9,
                    'numbers'    => 8,
                    'lowercase'  => 7,
                    'uppercase'  => 6,
                    'special'    => 5,
                ],
                'history'   => [
                    'not_used_in_past_n_passwords' => 4,
                    'period'                       => [
                        'unit'  => 'foo',
                        'value' => 3,
                    ],
                ],
            ],
            true,
        ];

        yield 'Config without history information' => [
            [
                'character' => [
                    'min_length' => 9,
                    'numbers'    => 8,
                    'lowercase'  => 7,
                    'uppercase'  => 6,
                    'special'    => 5,
                ],
                'history'   => [
                    'not_used_in_past_n_passwords' => null,
                    'period'                       => [
                        'unit'  => null,
                        'value' => null,
                    ],
                ],
            ],
            false,
        ];

        yield 'Config with history, only for not used in past information' => [
            [
                'character' => [
                    'min_length' => 9,
                    'numbers'    => 8,
                    'lowercase'  => 7,
                    'uppercase'  => 6,
                    'special'    => 5,
                ],
                'history'   => [
                    'not_used_in_past_n_passwords' => 4,
                    'period'                       => [
                        'unit'  => null,
                        'value' => null,
                    ],
                ],
            ],
            true,
        ];

        yield 'Config with history, but only for last period track' => [
            [
                'character' => [
                    'min_length' => 9,
                    'numbers'    => 8,
                    'lowercase'  => 7,
                    'uppercase'  => 6,
                    'special'    => 5,
                ],
                'history'   => [
                    'not_used_in_past_n_passwords' => null,
                    'period'                       => [
                        'unit'  => 'foo',
                        'value' => 1,
                    ],
                ],
            ],
            true,
        ];

        yield 'Config with history, but without period unit' => [
            [
                'character' => [
                    'min_length' => 9,
                    'numbers'    => 8,
                    'lowercase'  => 7,
                    'uppercase'  => 6,
                    'special'    => 5,
                ],
                'history'   => [
                    'not_used_in_past_n_passwords' => 4,
                    'period'                       => [
                        'unit'  => null,
                        'value' => 1,
                    ],
                ],
            ],
            true,
        ];

        yield 'Config with history, but without period value' => [
            [
                'character' => [
                    'min_length' => 9,
                    'numbers'    => 8,
                    'lowercase'  => 7,
                    'uppercase'  => 6,
                    'special'    => 5,
                ],
                'history'   => [
                    'not_used_in_past_n_passwords' => 4,
                    'period'                       => [
                        'unit'  => 'foo',
                        'value' => null,
                    ],
                ],
            ],
            true,
        ];

        yield 'Config with only period history, but without period unit' => [
            [
                'character' => [
                    'min_length' => 9,
                    'numbers'    => 8,
                    'lowercase'  => 7,
                    'uppercase'  => 6,
                    'special'    => 5,
                ],
                'history'   => [
                    'not_used_in_past_n_passwords' => null,
                    'period'                       => [
                        'unit'  => null,
                        'value' => 1,
                    ],
                ],
            ],
            false,
        ];

        yield 'Config with only period history, but without period value' => [
            [
                'character' => [
                    'min_length' => 9,
                    'numbers'    => 8,
                    'lowercase'  => 7,
                    'uppercase'  => 6,
                    'special'    => 5,
                ],
                'history'   => [
                    'not_used_in_past_n_passwords' => null,
                    'period'                       => [
                        'unit'  => 'foo',
                        'value' => null,
                    ],
                ],
            ],
            false,
        ];
    }
}