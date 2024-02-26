<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Service;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\PolicyInterface;
use Choks\PasswordPolicy\Contract\PolicyProviderInterface;
use Choks\PasswordPolicy\Enum\PeriodUnit;
use Choks\PasswordPolicy\Model\CharacterPolicy;
use Choks\PasswordPolicy\Model\ExpirationPolicy;
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
 * @psalm-type ExpirationConfig = array{
 *            expires_after: array{
 *                unit: value-of<PeriodUnit>|null,
 *                value: integer|null
 *            }
 *        }
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
 *      character: CharacterConfig|array{}|null,
 *      expiration: ExpirationConfig|array{}|null,
 *      history: HistoryConfig|array{}|null
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
        return new Policy(
            $this->createExpirationPolicy($this->config),
            $this->createCharacterPolicy($this->config),
            $this->createHistoryPolicy($this->config)
        );
    }

    /**
     * @param  Config  $policyConfig
     */
    private function createCharacterPolicy(array $policyConfig): ?CharacterPolicy
    {
        if (empty($policyConfig['character'])) {
            return null;
        }

        $characterConfig = $policyConfig['character'];

        if (!isset($characterConfig['min_length'],
            $characterConfig['numbers'],
            $characterConfig['lowercase'],
            $characterConfig['uppercase'],
            $characterConfig['special'])) {
            return null;
        }

        return new CharacterPolicy(
            $characterConfig['min_length'],
            $characterConfig['numbers'],
            $characterConfig['lowercase'],
            $characterConfig['uppercase'],
            $characterConfig['special'],
        );
    }

    /**
     * @param  Config  $policyConfig
     */
    private function createHistoryPolicy(array $policyConfig): ?HistoryPolicy
    {
        if (empty($policyConfig['history'])) {
            return null;
        }

        $historyConfig = $policyConfig['history'];

        if (empty($historyConfig['not_used_in_past_n_passwords'])
            &&
            (empty($historyConfig['period']['unit']) || empty($historyConfig['period']['value']))
        ) {
            return null;
        }

        return new HistoryPolicy(
            $historyConfig['not_used_in_past_n_passwords'],
            $historyConfig['period']['unit'],
            $historyConfig['period']['value'],
        );
    }

    /**
     * @param  Config  $policyConfig
     */
    private function createExpirationPolicy(array $policyConfig): ?ExpirationPolicy
    {
        if (empty($policyConfig['expiration'])) {
            return null;
        }

        $expirationConfig = $policyConfig['expiration'];

        if (null === $expirationConfig['expires_after']['unit']) {
            return null;
        }

        if (null === $expirationConfig['expires_after']['value']) {
            return null;
        }

        return new ExpirationPolicy(
            $expirationConfig['expires_after']['unit'],
            $expirationConfig['expires_after']['value'],
        );
    }
}