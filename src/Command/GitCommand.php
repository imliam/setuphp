<?php

namespace Setuphp\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'git',
    description: 'Initializes a git repository and optionally sets up a remote repository',
)]
class GitCommand extends Command
{
    use Utilities;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        intro('Setting up git repository');

        if (!$this->exec(['which', 'git'])->isSuccessful()) {
            error('Git is not available on your system');

            return Command::FAILURE;
        }

        if (file_exists('.git')) {
            warning('A git repository already exists in this directory');

            if (!confirm('Do you want to remove the existing git repository?', false)) {
                error('Stopping git setup as there is already a git repository');

                return Command::SUCCESS;
            }

            if (!$this->exec(['rm', '-rf', '.git'])->isSuccessful()) {
                error('Failed to remove the existing git repository');

                return Command::FAILURE;
            }
        }

        info('Initialising git repository');

        if (!$this->exec(['git', 'init'])->isSuccessful()) {
            error('Failed to initialize the git repository');
            return Command::FAILURE;
        }

        info('Adding all files to the repository');

        if (!$this->exec(['git', 'add', '-A'])->isSuccessful()) {
            error('Failed to add all files to the repository');
            return Command::FAILURE;
        }

        info('Committing all files');

        if (!$this->exec(['git', 'commit', '-m', 'Initial commit'])->isSuccessful()) {
            error('Failed to create the initial commit');
            return Command::FAILURE;
        }

        if (!confirm('Do you want to set up a remote repository?')) {
            return Command::SUCCESS;
        }

        $remoteCreated = false;

        if ($this->exec(['which', 'gh'])->isSuccessful()) {
            if (confirm('It looks like you have the GitHub CLI, do you want to use it to create the remote repository?', true)) {
                info('Creating remote repository using gh');

                $visibility = select(
                    label: 'Please select the visibility of the repository',
                    options: ['public', 'private'],
                    default: 'public',
                );

                $process = $this->exec(['gh', 'repo', 'create', $this->getProjectName(), "--{$visibility}"]);

                if (!$process->isSuccessful()) {
                    error('Failed to create remote repository using GitHub CLI, does a repository with the same name already exist?');
                    return Command::FAILURE;
                }

                $remoteUrl = trim(preg_replace('/.*\n(.*)/', '$1', trim($process->getOutput()))) . '.git';

                info('Remote repository created successfully using GitHub CLI at ' . $remoteUrl);

                $remoteCreated = true;
            }
        }

        if (!$remoteCreated) {
            note('You create a remote repository on GitHub, GitLab, Bitbucket, etc. and add the URL here');

            $githubUrl = 'https://github.com/new';

            if ($projectName = $this->getProjectName()) {
                $githubUrl .= '?name=' . $projectName;
            }

            $remoteUrl = text(
                label: 'Please enter the remote repository URL',
                required: true,
                validate: fn ($url) => filter_var($url, FILTER_VALIDATE_URL) !== false ? null : 'Invalid URL',
                hint: 'Create a GitHub repository: ' . $githubUrl,
            );
        }

        if (!empty($remoteUrl)) {
            if (!$this->exec(['git', 'remote', 'add', 'origin', $remoteUrl])->isSuccessful()) {
                error('Failed to add remote repository');
                return Command::FAILURE;
            }

            info('Remote repository set to: ' . $remoteUrl);
        }

        if (confirm('Do you want to push the code to the remote repository?', true)) {
            $process = $this->exec(['git', 'branch', '--show-current']);

            if ($process->isSuccessful()) {
                $branch = trim($process->getOutput());
            } else {
                $branch = 'main';
            }

            if (!$this->exec(['git', 'push', '-u', 'origin', $branch])->isSuccessful()) {
                error('Failed to push the code to the remote repository');
                return Command::FAILURE;
            }

            info('Code pushed to the remote repository');
        }

        outro('Git setup completed successfully');

        return Command::SUCCESS;
    }
}
