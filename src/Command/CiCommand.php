<?php

namespace Setuphp\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'ci',
    description: 'Sets up CI workflow for the project'
)]
class CiCommand extends Command
{
    use Utilities;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        intro('Setting up CI workflow');

        $remoteHost = parse_url(trim($this->exec(['git', 'remote', 'get-url', 'origin'])->getOutput()), PHP_URL_HOST) ?? '';

        // Determine CI platform based on the remote URL.
        $isGithub = str_contains($remoteHost, 'github');
        $isGitlab = str_contains($remoteHost, 'gitlab');
        $defaultPlatform = $isGithub ? 'github' : ($isGitlab ? 'gitlab' : 'none');

        $selectedPlatform = select('Which CI platform would you like to set up?', [
            'github' => 'GitHub Actions' . ($isGithub ? ' (detected)' : ''),
            'gitlab' => 'GitLab CI' . ($isGitlab ? ' (detected)' : ''),
            'none' => 'None',
        ], $defaultPlatform);

        if ($selectedPlatform === 'github') {
            info('Setting up GitHub Actions workflow.');
            $ciFilePath = getcwd() . '/.github/workflows/ci.yml';
            $ciContent = $this->generateGithubActionsWorkflow();
        } elseif ($selectedPlatform === 'gitlab') {
            info('Setting up GitLab CI configuration.');
            $ciFilePath = getcwd() . '/.gitlab-ci.yml';
            $ciContent = $this->generateGitlabCiConfig();
        } else {
            error('No CI platform selected. Skipping CI setup.');
            return Command::SUCCESS;
        }

        // Check if the CI configuration file already exists.
        if (file_exists($ciFilePath)) {
            warning("CI configuration file already exists at {$ciFilePath}");
            if (!confirm('Do you want to overwrite the existing CI configuration file?', false)) {
                note('Skipping CI configuration setup.');
                return Command::SUCCESS;
            }
        }

        if (!$this->createFile($ciFilePath, $ciContent)) {
            error("Failed to create CI configuration file at {$ciFilePath}");
            return Command::FAILURE;
        }

        info("CI configuration file created at {$ciFilePath}");
        outro('CI setup completed successfully');

        return Command::SUCCESS;
    }

    /**
     * Generate a GitHub Actions workflow YAML string.
     */
    protected function generateGithubActionsWorkflow(): string
    {
        $yaml = <<<YAML
name: CI

on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest
YAML;

        if (file_exists('vendor/bin/pest')) {
            $yaml .= <<<YAML

      - name: Run Pest tests
        run: vendor/bin/pest
YAML;
        } elseif (file_exists('vendor/bin/phpunit')) {
            $yaml .= <<<YAML

      - name: Run PHPUnit tests
        run: vendor/bin/phpunit
YAML;
        }

        if (file_exists('vendor/bin/phpstan')) {
            $yaml .= <<<YAML

      - name: Run PHPStan analysis
        run: vendor/bin/phpstan analyse
YAML;
        } elseif (file_exists('vendor/bin/psalm')) {
            $yaml .= <<<YAML

      - name: Run Psalm analysis
        run: vendor/bin/psalm
YAML;
        }

        if (file_exists('vendor/bin/php-cs-fixer')) {
            $yaml .= <<<YAML

      - name: Check code style
      run: vendor/bin/php-cs-fixer fix --dry-run --diff
YAML;
        } elseif (file_exists('vendor/bin/pint')) {
            $yaml .= <<<YAML

      - name: Check code style
        run: vendor/bin/pint --test
YAML;
        }

        return $yaml;
    }

    /** Generate a GitLab CI configuration YAML string. */
    protected function generateGitlabCiConfig(): string
    {
        $yaml = <<<YAML
stages:
  - test

test:
  image: php:8.0
  script:
    - composer install --prefer-dist --no-progress --no-suggest
YAML;

        if (file_exists('vendor/bin/pest')) {
            $yaml .= <<<YAML

    - vendor/bin/pest
YAML;
        } elseif (file_exists('vendor/bin/phpunit')) {
            $yaml .= <<<YAML

    - vendor/bin/phpunit
YAML;
        }

        if (file_exists('vendor/bin/phpstan')) {
            $yaml .= <<<YAML

    - vendor/bin/phpstan analyse
YAML;
        } elseif (file_exists('vendor/bin/psalm')) {
            $yaml .= <<<YAML

    - vendor/bin/psalm
YAML;
        }

        if (file_exists('vendor/bin/php-cs-fixer')) {
            $yaml .= <<<YAML

            - vendor/bin/php-cs-fixer fix --dry-run --diff
YAML;
        } elseif (file_exists('vendor/bin/pint')) {
            $yaml .= <<<YAML

    - vendor/bin/pint --test
YAML;
        }

        return $yaml;
    }
}
