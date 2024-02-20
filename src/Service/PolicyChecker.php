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
use Symfony\Contracts\Translation\TranslatorInterface;

final class PolicyChecker implements PolicyCheckerInterface
{
    public function __construct(
        private readonly PolicyProviderInterface  $policyProvider,
        private readonly PasswordHistoryInterface $passwordHistory,
        private readonly TranslatorInterface      $translator,
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
            $msg   = [];
            $msg[] = $this->translator->trans('violation.password_is_already_used', [], 'password_policy');

            if ((int)$policy->getBackTrackCount() > 0) {
                $msg[] = $this->translator->trans(
                    'violation.in_last_n_passwords',
                    ['%number%' => $policy->getBackTrackCount()],
                    'password_policy'
                );
            }

            if ($policy->hasPeriod()) {
                $unitTranslation = $this->translator->trans(
                    'enum.'.$policy->getBackTrackTimeUnit(),
                    [],
                    'password_policy'
                );

                $msg[] = $this->translator->trans(
                    'violation.in_past_x_y',
                    ['%number%' => $policy->getBackTrackTimeValue(), '%unit%' => $unitTranslation],
                    'password_policy'
                );
            }

            $violations->add(new Violation(\implode(' ', $msg)));
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
                'violation.at_least_chars',
            ],
            [
                \preg_match_all('/\d/', $plainPassword),
                $policy->getMinNumerics(),
                'violation.at_least_numerics',
            ],
            [
                \preg_match_all('/\p{Ll}/u', $plainPassword),
                $policy->getMinLowercase(),
                'violation.at_least_lowercase',
            ],
            [
                \preg_match_all('/\p{Lu}/u', $plainPassword),
                $policy->getMinUppercase(),
                'violation.at_least_uppercase',
            ],
            [
                $this->countSpecialChars($plainPassword),
                $policy->getMinSpecials(),
                'violation.at_least_special',
            ],
        ];

        foreach ($matrix as $item) {
            [$count, $minRequired, $message] = $item;
            if (null !== $minRequired && $count < $minRequired) {
                $violations->add(
                    new Violation($this->translator->trans($message, ['%number%' => $minRequired], 'password_policy')
                    )
                );
            }
        }
    }

    private function countSpecialChars(string $plainPassword): int
    {
        $cnt = 0;

        $specialChars = \str_split($this->specialChars);

        foreach ($specialChars as $specialChar) {
            $cnt += \substr_count($plainPassword, $specialChar);
        }

        return $cnt;
    }
}