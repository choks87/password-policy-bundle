<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Service;

use Choks\PasswordPolicy\Contract\CharacterPolicyInterface;
use Choks\PasswordPolicy\Contract\HistoryPolicyInterface;
use Choks\PasswordPolicy\Contract\PasswordHistoryInterface;
use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\PolicyCheckerInterface;
use Choks\PasswordPolicy\Contract\PolicyProviderInterface;
use Choks\PasswordPolicy\Violation\Violation;
use Choks\PasswordPolicy\Violation\ViolationList;

final class PolicyChecker implements PolicyCheckerInterface
{
    public function __construct(
        private readonly PolicyProviderInterface  $policyProvider,
        private readonly PasswordHistoryInterface $passwordHistory,
        private readonly string                   $specialChars,
        private readonly bool                     $trim,
    ) {
    }

    public function validate(PasswordPolicySubjectInterface $subject): ViolationList
    {
        $policy     = $this->policyProvider->getPolicy($subject);
        $violations = new ViolationList($policy);

        if (null === $subject->getPlainPassword()) {
            return $violations;
        }

        if (null !== $policy->getCharacterPolicy()) {
            $this->charactersCheck($violations, $subject, $policy->getCharacterPolicy());
        }

        if (null !== $policy->getHistoryPolicy()) {
            $this->passwordHistoryCheck($violations, $subject, $policy->getHistoryPolicy());
        }

        return $violations;
    }

    private function passwordHistoryCheck(
        ViolationList                  $violations,
        PasswordPolicySubjectInterface $subject,
        HistoryPolicyInterface         $policy,
    ): void {
        $isUsed = $this->passwordHistory->isUsed($subject, $policy);

        if ($isUsed) {
            $msg = '';

            if ((int)$policy->getBackTrackCount() > 0) {
                $msg .= \sprintf("Last %s passwords.",
                                 $policy->getBackTrackCount());
            }

            if ($policy->hasPeriod()) {
                $msg .= \sprintf("In past %d %s(s).", $policy->getBackTrackTimeValue(),
                                 $policy->getBackTrackTimeValue());
            }

            $violations->add(
                new Violation(\sprintf('Password is used in past (%s)', $msg))
            );
        }
    }

    private function charactersCheck(
        ViolationList                  $violations,
        PasswordPolicySubjectInterface $subject,
        CharacterPolicyInterface       $policy,
    ): void {

        /** @var string $plainPassword */
        $plainPassword = $subject->getPlainPassword();

        if (true === $this->trim) {
            $plainPassword = \trim($plainPassword);
        }

        $matrix = [
            [
                \strlen($plainPassword),
                $policy->getMinLength(),
                'Has to be at least %d characters.',
            ],
            [
                \preg_match_all('/\d/', $plainPassword),
                $policy->getMinNumerics(),
                'Has to be at least %d numeric character(s).',
            ],
            [
                \preg_match_all('/\p{Ll}/u', $plainPassword),
                $policy->getMinLowercase(),
                'Has to be at least %d lowercase character(s).',
            ],
            [
                \preg_match_all('/\p{Lu}/u', $plainPassword),
                $policy->getMinUppercase(),
                'Has to be at least %d uppercase character(s).',
            ],
            [
                $this->countSpecialChars($plainPassword),
                $policy->getMinSpecials(),
                'Has to be at least %d special character(s).',
            ],
        ];

        foreach ($matrix as $item) {
            [$count, $minRequired, $message] = $item;
            if (null !== $minRequired && $count < $minRequired) {
                $violations->add(new Violation(\sprintf($message, $minRequired)));
            }
        }
    }

    private
    function countSpecialChars(
        string $plainPassword,
    ): int {
        $cnt = 0;

        $specialChars = \str_split($this->specialChars);

        foreach ($specialChars as $specialChar) {
            $cnt += \substr_count($plainPassword, $specialChar);
        }

        return $cnt;
    }
}