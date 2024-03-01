<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Resources\App\Entity;

use Choks\PasswordPolicy\Atrribute\PasswordPolicy;
use Doctrine\ORM\Mapping as ORM;

#[PasswordPolicy]
#[ORM\Entity]
final class ListenedSubject extends AbstractSubject
{
    #[ORM\Id]
    #[ORM\Column]
    public int|string $id;
}