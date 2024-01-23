<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Service;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\PolicyInterface;
use Choks\PasswordPolicy\Contract\PolicyProviderInterface;
use Choks\PasswordPolicy\Enum\PeriodUnit;
use Choks\PasswordPolicy\Model\CharacterPolicy;
use Choks\PasswordPolicy\Model\HistoryPolicy;
use Choks\PasswordPolicy\Model\Policy;

/**
 * @psalm-type HistoryConfig = array{
 *           not_used_in_past_n_passwords: integer|null,
 *           period: array{
 *               unit: value-of<PeriodUnit>|null,
 *               value: integer|null
 *           }
 *       }
 *
 * @psalm-type CharacterConfig = array{
 *           min_length: integer|null,
 *           numbers: integer|null,
 *           lowercase: integer|null,
 *           uppercase: integer|null,
 *           special: integer|null
 *       }
 *
 * @psalm-type Config = array{
 *      character: CharacterConfig,
 *      history: HistoryConfig
 * }
 */
final class ConfigurationPolicyProvider implements PolicyProviderInterface
{
    /**
     * @param  Config  $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function getPolicy(PasswordPolicySubjectInterface $subject): PolicyInterface
    {
        $historyConfig   = $this->config['history'];
        $characterConfig = $this->config['character'];

        return new Policy(
            $this->hasCharacterPolicy($characterConfig) ? new CharacterPolicy(
                $characterConfig['min_length'],
                $characterConfig['numbers'],
                $characterConfig['lowercase'],
                $characterConfig['uppercase'],
                $characterConfig['special'],
            ) : null,
            $this->hasHistoryPolicy($historyConfig) ? new HistoryPolicy(
                $historyConfig['not_used_in_past_n_passwords'],
                $historyConfig['period']['unit'],
                $historyConfig['period']['value'],
            ) : null
        );
    }

    /**
     * @param  CharacterConfig  $characterConfig
     */
    private function hasCharacterPolicy(array $characterConfig): bool
    {
        return null !== $characterConfig['min_length'] ||
               null !== $characterConfig['numbers'] ||
               null !== $characterConfig['lowercase'] ||
               null !== $characterConfig['uppercase'] ||
               null !== $characterConfig['special'];
    }

    /**
     * @param  HistoryConfig  $historyConfig
     */
    private function hasHistoryPolicy(array $historyConfig): bool
    {
        return null !== $historyConfig['not_used_in_past_n_passwords'] ||
               (null !== $historyConfig['period']['unit'] && null !== $historyConfig['period']['value']);
    }
}