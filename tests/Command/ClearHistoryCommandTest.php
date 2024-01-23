<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Command;

use Choks\PasswordPolicy\Service\PasswordHistory;
use Choks\PasswordPolicy\Tests\KernelTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearHistoryCommandTest extends KernelTestCase
{
    private Connection      $connection;
    private string          $tableName;
    private PasswordHistory $passwordHistory;

    protected function setUp(): void
    {
        $this->passwordHistory = self::getContainer()->get(PasswordHistory::class);
        $this->connection      = self::getContainer()->get('password_policy.storage.dbal.connection');
        $this->tableName       = self::getContainer()->getParameter('password_policy.storage.dbal.table');
    }

    public function testExecute(): void
    {
        $application = new Application(self::$kernel);

        $command       = $application->find('password-policy:clear:history');
        $commandTester = new CommandTester($command);

        $this->addSomePasswords();
        self::assertNotEquals(0, $this->getNumberOfRecords());

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        self::assertEquals(0, $this->getNumberOfRecords());
    }

    private function getNumberOfRecords(): int
    {
        return (int)$this
                        ->connection
                        ->executeQuery(\sprintf("SELECT COUNT(*) FROM %s", $this->tableName))
                        ->fetchFirstColumn()[0];
    }

    private function addSomePasswords(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->passwordHistory->add(new Subject(1, 'foo_'.$i));
        }
    }
}