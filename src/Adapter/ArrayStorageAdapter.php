<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Adapter;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Contract\StorageAdapterInterface;

/**
 * @psalm-type Item = array{
 *     subject_id: string|int,
 *     password: string,
 *     created_at: \DateTimeImmutable
 * }
 */
final class ArrayStorageAdapter implements StorageAdapterInterface
{
    /**
     * @var Item[]
     */
    private array $list = [];

    public function add(PasswordPolicySubjectInterface $subject, string $hashedPassword): void
    {
        $this->list[] = [
            'subject_id' => $subject->getIdentifier(),
            'password'   => $hashedPassword,
            'created_at' => new \DateTimeImmutable(),
        ];
    }

    public function remove(PasswordPolicySubjectInterface $subject): void
    {
        foreach ($this->list as $key => $item) {
            if ($item['subject_id'] === $subject->getIdentifier()) {
                unset($this->list[$key]);
            }
        }

        $this->list = \array_values($this->list);
    }

    public function clear(): void
    {
        $this->list = [];
    }

    /**
     * @return iterable<string>
     */
    public function getPastPasswords(
        PasswordPolicySubjectInterface $subject,
        ?int                           $lastN = null,
        ?\DateTimeImmutable            $startingFrom = null,
    ): iterable {
        $list  = \array_filter(\array_reverse($this->list), static function (array $item) use ($subject){
            return $item['subject_id'] === $subject->getIdentifier();
        });

        foreach ($list as $index => $item) {
            if (null !== $lastN && ($index + 1) > $lastN) {
                break;
            }

            if (null !== $startingFrom && $item['created_at'] < $startingFrom) {
                break;
            }

            yield $item['password'];
        }
    }

    /**
     * @return Item[]
     */
    public function &getListByReference(): array
    {
        return $this->list;
    }
}