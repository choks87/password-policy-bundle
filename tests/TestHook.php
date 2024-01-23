<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests;

use Choks\PasswordPolicy\Tests\Resources\App\PasswordPolicyTestKernel;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;

final class TestHook implements BeforeFirstTestHook, AfterLastTestHook
{
    private const COMMANDS = [
        ['command' => 'doctrine:schema:drop', '--env' => 'test', '--force' => null, '--quiet' => null],
        ['command' => 'doctrine:schema:create', '--env' => 'test', '--quiet' => null],
    ];

    final public function executeBeforeFirstTest(): void
    {
        if ((getenv('BOOTSTRAP') ?? 'true') === 'false') {
            return;
        }

        $this->clear();
        $app = $this->bootApplication();
        $this->executeCommands($app);
    }


    public function executeAfterLastTest(): void
    {
        $this->clear();
    }

    private function bootApplication(): Application
    {
        $kernel = new PasswordPolicyTestKernel('test', false);
        $kernel->boot();
        $application = new Application($kernel);
        $application->setAutoExit(false);

        return $application;
    }

    private function executeCommands(Application $app): void
    {
        $output = new ConsoleOutput();

        foreach (self::COMMANDS as $command) {
            try {
                if (Command::SUCCESS !== $app->run(new ArrayInput($command), $output)) {
                    printf('Command Failed: %s', $output->getErrorOutput());
                }
            } catch (\Exception $e) {
                printf('Exception: %s', $e->getMessage());
            }
        }
    }

    private function clear(): void
    {
        (new Filesystem())->remove(__DIR__.'/../var');
    }
}
