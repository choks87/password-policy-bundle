<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Exception;

use Choks\PasswordPolicy\Contract\ExceptionInterface;

final class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{

}