<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Resources\App\Entity;

use Choks\PasswordPolicy\Atrribute\Listen;
use Doctrine\ORM\Mapping as ORM;

#[Listen]
#[ORM\Entity]
final class ListenedSubject extends AbstractSubject
{
    #[ORM\Id]
    #[ORM\Column]
    public int|string $id;
}