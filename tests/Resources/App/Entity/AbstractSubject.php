<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Resources\App\Entity;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
class AbstractSubject implements PasswordPolicySubjectInterface
{
    public ?string $plainPassword = null;

    public function __construct(int|string $id, string $plainPassword = null)
    {
        $this->id            = $id;
        $this->plainPassword = $plainPassword;
    }

    public function getIdentifier(): string
    {
        return (string)$this->id;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): AbstractSubject
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }
}