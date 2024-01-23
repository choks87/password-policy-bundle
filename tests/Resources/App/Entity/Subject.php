<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Resources\App\Entity;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Subject implements PasswordPolicySubjectInterface
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id;

    public ?string $plainPassword = null;

    public function __construct(int $id, string $plainPassword = null)
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

    public function setPlainPassword(?string $plainPassword): Subject
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }
}