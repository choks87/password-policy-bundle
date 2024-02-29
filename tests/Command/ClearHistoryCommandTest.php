<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Tests\Command;

use Choks\PasswordPolicy\Contract\StorageAdapterInterface;
use Choks\PasswordPolicy\Criteria\SearchCriteria;
use Choks\PasswordPolicy\Service\PasswordHistory;
use Choks\PasswordPolicy\Tests\KernelTestCase;
use Choks\PasswordPolicy\Tests\Resources\App\Entity\Subject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearHistoryCommandTest extends KernelTestCase
{
    private PasswordHistory     $passwordHistory;
    private StorageAdapterInterface $storageAdapter;

    protected function setUp(): void
    {
        $this->storageAdapter  = self::getContainer()->get(StorageAdapterInterface::class);
        $this->passwordHistory = self::getContainer()->get(PasswordHistory::class);
    }

    public function testExecute(): void
    {
        $application = new Application(self::$kernel);

        $command       = $application->find('password-policy:history:clear');
        $commandTester = new CommandTester($command);

        $this->addSomePasswords();
        self::assertNotEquals(0, $this->getNumberOfRecords());

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        self::assertEquals(0, $this->getNumberOfRecords());
    }

    private function getNumberOfRecords(): int
    {
        return \count([...$this->storageAdapter->get(new SearchCriteria())]);
    }

    private function addSomePasswords(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->passwordHistory->add(new Subject(1, 'foo_'.$i));
        }
    }
}