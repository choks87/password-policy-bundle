<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Contract;

/**
 * @extends \IteratorAggregate<ViolationInterface>
 */
interface ViolationListInterface extends \IteratorAggregate
{
    public function add(ViolationInterface $violation): ViolationListInterface;

    /**
     * @return iterable<ViolationInterface>
     */
    public function getViolations(): iterable;

    public function getIterator(): \Traversable;
}