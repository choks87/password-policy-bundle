<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Resources\App\Entity;

use Choks\PasswordPolicy\Atrribute\Listen;
use Doctrine\ORM\Mapping as ORM;

#[Listen]
#[ORM\Entity]
final class ListenedSubject extends Subject
{

}