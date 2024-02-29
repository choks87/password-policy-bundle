<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Event;

use Choks\PasswordPolicy\Atrribute\PasswordPolicy;
use Choks\PasswordPolicy\Contract\PasswordHistoryInterface;
use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\PolicyCheckerInterface;
use Choks\PasswordPolicy\Contract\PolicyProviderInterface;
use Choks\PasswordPolicy\Exception\PolicyCheckException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;

final class PasswordPolicyAttributeListener
{
    public function __construct(
        private readonly PolicyCheckerInterface   $policyChecker,
        private readonly PolicyProviderInterface  $policyProvider,
        private readonly PasswordHistoryInterface $passwordHistory,
    ) {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $subjects = $this->getSubjects($eventArgs->getObjectManager());
        foreach ($subjects as $subject) {

            /** @var PasswordPolicySubjectInterface $subject */
            $violations = $this->policyChecker->validate($subject);

            if ($violations->hasErrors()) {
                throw new PolicyCheckException($violations);
            }

            if ($this->policyProvider->getPolicy($subject)->getHistoryPolicy()) {
                $this->passwordHistory->add($subject);
            }
        }
    }

    public function postRemove(PostRemoveEventArgs $eventArgs): void
    {
        $subject = $eventArgs->getObject();

        if (!$subject instanceof PasswordPolicySubjectInterface) {
            return;
        }

        $this->passwordHistory->remove($subject);
    }

    /**
     * @return iterable<PasswordPolicySubjectInterface>
     */
    private function getSubjects(EntityManagerInterface $entityManager): iterable
    {
        $unitOfWork = $entityManager->getUnitOfWork();

        $entitiesFitActions = \array_merge(
            $unitOfWork->getScheduledEntityUpdates(),
            $unitOfWork->getScheduledEntityInsertions(),
        );

        foreach ($entitiesFitActions as $entity) {
            if (!$entity instanceof PasswordPolicySubjectInterface) {
                continue;
            }

            if (false === $this->hasAttribute($entity)) {
                return;
            }

            yield $entity;
        }
    }

    private function hasAttribute(object $subject): bool
    {
        $reflection = new \ReflectionObject($subject);
        $attributes = $reflection->getAttributes(PasswordPolicy::class);
        /** @var \ReflectionAttribute<object>|null $attribute */
        $attribute = \reset($attributes);

        return false !== $attribute;
    }
}