<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Resources\App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Subject extends AbstractSubject
{
    #[ORM\Id]
    #[ORM\Column]
    public int|string $id;
}