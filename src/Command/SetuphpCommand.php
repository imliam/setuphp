<?php

namespace Setuphp\Command;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\confirm;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'setuphp',
    description: 'Run through all SetuPHP commands',
)]
class SetuphpCommand extends Command
{
    use Utilities;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        intro('Setting up PHP project');

        if ($this->exec(['which', 'git'])->isSuccessful() && confirm('Do you want to set up a git repository?', true)) {
            $this->getApplication()->find('git')->run($input, $output);
        }

        return Command::SUCCESS;
    }
}
