<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Violation;

use Choks\PasswordPolicy\Contract\PolicyInterface;
use Choks\PasswordPolicy\Contract\ViolationInterface;
use Choks\PasswordPolicy\Contract\ViolationListInterface;

final class ViolationList implements ViolationListInterface
{
    /**
     * @var array<ViolationInterface>
     */
    private array           $list = [];
    private PolicyInterface $policy;

    public function __construct(PolicyInterface $policy)
    {
        $this->policy = $policy;
    }

    public function add(ViolationInterface $violation): ViolationListInterface
    {
        $this->list[] = $violation;

        return $this;
    }

    public function empty(): bool
    {
        return empty($this->list);
    }

    public function hasErrors(): bool
    {
        return !$this->empty();
    }

    public function getViolations(): iterable
    {
        return $this->list;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->list as $violation) {
            yield $violation;
        }
    }

    public function getPolicy(): PolicyInterface
    {
        return $this->policy;
    }
}