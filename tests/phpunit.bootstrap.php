<?php
declare(strict_types=1);

use Choks\PasswordPolicy\Tests\Resources\App\PasswordPolicyWithDbalTestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;

require_once(__DIR__.'/../vendor/autoload.php');

const COMMANDS = [
    ['command' => 'doctrine:schema:drop', '--env' => 'test', '--force' => null, '--quiet' => null],
    ['command' => 'doctrine:schema:create', '--env' => 'test', '--quiet' => null],
];

(new Filesystem())->remove(__DIR__.'/../var');

if ((getenv('BOOTSTRAP') ?? 'true') !== 'false') {
    $kernel = new PasswordPolicyWithDbalTestKernel('test', false);
    $kernel->boot();
    $application = new Application($kernel);
    $application->setAutoExit(false);

    $output = new ConsoleOutput();

    foreach (COMMANDS as $command) {
        try {
            if (Command::SUCCESS !== $application->run(new ArrayInput($command), $output)) {
                \printf('Command Failed');
            }
        } catch (\Exception $e) {
            \printf('Exception: %s', $e->getMessage());
        }
    }
}