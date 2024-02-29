<?php

declare(strict_types=1);

namespace Choks\PasswordPolicy\Command;

use Choks\PasswordPolicy\Service\PasswordHistory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ClearHistoryCommand extends Command
{
    public function __construct(private readonly PasswordHistory $passwordHistory,) {
        parent::__construct('password-policy:history:clear');
    }

    protected function configure(): void
    {
        $this->setDescription('Clears password history.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->passwordHistory->clear();

        $io->success('Cleared all passwords from history.');

        return Command::SUCCESS;
    }
}
