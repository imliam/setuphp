<?php

namespace Setuphp\Command;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;
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

        if (!file_exists('composer.json')) {
            warning('SetuPHP works best in an existing PHP project with a composer.json file.');

            if (confirm('Do you want to create a composer.json file?', true)) {
                $projectName = text(
                    label: 'Project name',
                    placeholder: 'vendor/project',
                    required: true,
                    validate: fn ($name) => preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/', $name) ? null : 'Invalid project name (lowercase letters, numbers, underscores and hyphens only)',
                    hint: 'The project name should be in the format vendor/project'
                );

                $this->exec(['composer', 'init', '--no-interaction', '--name', $projectName]);
            }
        }

        if (file_exists('composer.json') && !file_exists('composer.lock') && confirm('Do you want to install Composer dependencies?', hint: 'Having dependencies available will assist SetuPHP')) {
            $this->exec(['composer', 'install', '--no-interaction']);
        }

        $this->getApplication()->find('tooling')->run($input, $output);

        if ($this->exec(['which', 'git'])->isSuccessful() && confirm('Do you want to set up a git repository?')) {
            $this->getApplication()->find('git')->run($input, $output);
        }

        if (confirm('Do you want to set up a CI workflow?')) {
            $this->getApplication()->find('ci')->run($input, $output);
        }

        return Command::SUCCESS;
    }
}
