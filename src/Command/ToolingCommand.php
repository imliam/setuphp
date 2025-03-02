<?php

namespace Setuphp\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\outro;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'tooling',
    description: 'Installs test frameworks, static analysis tools and code style tools',
)]
class ToolingCommand extends Command
{
    use Utilities;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        intro('Setting up common tooling');

        $isLaravel = file_exists('artisan');
        if ($isLaravel) {
            info('Laravel application detected.');
        }

        if (!$this->packageIsInstalled('phpunit/phpunit') && !$this->packageIsInstalled('pestphp/pest')) {
            $testFramework = select(
                label: 'Select your preferred test framework',
                options: [
                    'phpunit' => 'PHPUnit',
                    'pest' => 'Pest',
                    'none' => 'None'
                ],
                default: 'phpunit',
            );

            if ($testFramework === 'pest') {
                info('Installing Pest...');

                if (!$this->exec(['composer', 'config', '--no-plugins', 'allow-plugins.pestphp/pest-plugin', 'true', '--no-interaction'], true)->isSuccessful()) {
                    error('Failed to configure Pest plugin installation');
                    return Command::FAILURE;
                }

                if (!$this->exec(['composer', 'require', 'pestphp/pest', '--dev', '--no-interaction'], true)->isSuccessful()) {
                    error('Failed to install Pest');
                    return Command::FAILURE;
                }

                if (!$this->exec(['./vendor/bin/pest', '--init'], true)->isSuccessful()) {
                    error('Failed to install Pest');
                    return Command::FAILURE;
                }
            } elseif ($testFramework === 'phpunit') {
                info('Installing PHPUnit...');

                if (!$this->exec(['composer', 'require', 'phpunit/phpunit', '--dev', '--no-interaction'])->isSuccessful()) {
                    error('Failed to install PHPUnit');
                    return Command::FAILURE;
                }
            } else {
                info('No test framework will be installed.');
            }
        }

        $composerJson = json_decode(file_get_contents('composer.json'), true);
        $isDuskInstalled = isset($composerJson['require-dev']['laravel/dusk']) || isset($composerJson['require']['laravel/dusk']);

        if ($isLaravel && !$isDuskInstalled) {
            $browserTestingTool = select(
                label: 'Select your preferred browser testing tool',
                options: [
                    'none' => 'None',
                    'dusk' => 'Dusk',
                    'cypress' => 'Laracasts Cypress'
                ],
                default: 'none',
            );

            if ($browserTestingTool === 'dusk') {
                info('Installing Laravel Dusk...');

                if (!$this->exec(['composer', 'require', 'laravel/dusk', '--dev', '--no-interaction'])->isSuccessful()) {
                    error('Failed to install Laravel Dusk');
                    return Command::FAILURE;
                }

                if (!$this->exec(['php', 'artisan', 'dusk:install'])->isSuccessful()) {
                    error('Failed to set up Laravel Dusk');
                    return Command::FAILURE;
                }
            } elseif ($browserTestingTool === 'cypress') {
                info('Installing Laracasts Cypress...');

                if (!$this->exec(['composer', 'require', 'laracasts/cypress', '--dev', '--no-interaction'])->isSuccessful()) {
                    error('Failed to install Laracasts Cypress');
                    return Command::FAILURE;
                }

                if (!$this->exec(['php', 'artisan', 'cypress:boilerplate'])->isSuccessful()) {
                    error('Failed to set up Cypress boilerplate');
                    return Command::FAILURE;
                }

                if (!$this->exec(['npm', 'install', 'cypress', '--save-dev'])->isSuccessful()) {
                    error('Failed to install Cypress as an NPM dependency');
                    return Command::FAILURE;
                }
            } else {
                info('No browser testing tool will be installed.');
            }
        }

        if (!$this->packageIsInstalled('larastan/larastan') && !$this->packageIsInstalled('phpstan/phpstan') && !$this->packageIsInstalled('vimeo/psalm')) {
            $staticAnalysis = select(
                label: 'Select your preferred static analysis tool',
                options: [
                    'none' => 'None',
                    'phpstan' => 'PHPStan' . ($isLaravel ? ' (Larastan)' : ''),
                    'psalm' => 'Psalm' . ($isLaravel ? ' (with Psalm Laravel plugin)' : ''),
                ],
                default: 'phpstan',
            );

            if ($staticAnalysis === 'phpstan') {
                if ($isLaravel) {
                    info('Installing Larastan (PHPStan for Laravel)...');

                    if (!$this->exec(['composer', 'require', 'larastan/larastan', '--dev', '--no-interaction'])->isSuccessful()) {
                        error('Failed to install Larastan');
                        return Command::FAILURE;
                    }

                    $neon = <<<NEON
    includes:
        - vendor/larastan/larastan/extension.neon
        - vendor/nesbot/carbon/extension.neon

    parameters:

        paths:
            - app/

        # Level 10 is the highest level
        level: 5

    #    ignoreErrors:
    #        - '#PHPDoc tag @var#'
    #
    #    excludePaths:
    #        - ./*/*/FileToBeExcluded.php
    NEON;

                    info('Creating phpstan.neon config file');

                    if (!$this->createFile('phpstan.neon', $neon)) {
                        error('Failed to create the phpstan.neon file');
                        return Command::FAILURE;
                    }
                } else {
                    info('Installing PHPStan...');

                    if (!$this->exec(['composer', 'require', 'phpstan/phpstan', '--dev', '--no-interaction'])->isSuccessful()) {
                        error('Failed to install PHPStan');
                        return Command::FAILURE;
                    }
                }
            } elseif ($staticAnalysis === 'psalm') {
                info('Installing Psalm...');

                if (!$this->exec(['composer', 'require', 'vimeo/psalm', '--dev', '--no-interaction'])->isSuccessful()) {
                    error('Failed to install Psalm');
                    return Command::FAILURE;
                }

                if (!$this->exec(['./vendor/bin/psalm', '--init'])->isSuccessful()) {
                    error('Failed to set up Psalm');
                    return Command::FAILURE;
                }

                if ($isLaravel) {
                    info('Installing Psalm Laravel plugin...');

                    if (!$this->exec(['composer', 'require', 'psalm/laravel-plugin', '--dev', '--no-interaction'])->isSuccessful()) {
                        error('Failed to install Psalm Laravel plugin');
                        return Command::FAILURE;
                    }

                    if (!$this->exec(['./vendor/bin/psalm-plugin', 'enable', 'psalm/plugin-laravel', '--no-interaction'])->isSuccessful()) {
                        error('Failed to enable Psalm Laravel plugin');
                        return Command::FAILURE;
                    }
                }
            }
        }

        if (!$this->packageIsInstalled('friendsofphp/php-cs-fixer') && !$this->packageIsInstalled('laravel/pint')) {
            $codeStyleTool = select(
                label: 'Select your preferred code style tool',
                options: [
                    'none' => 'None',
                    'php-cs-fixer' => 'PHP CS Fixer',
                    'pint' => 'Laravel Pint',
                ],
                default: 'php-cs-fixer',
            );

            if ($codeStyleTool === 'php-cs-fixer') {
                info('Installing php-cs-fixer...');

                if (!$this->exec(['composer', 'require', 'friendsofphp/php-cs-fixer', '--dev', '--no-interaction'])->isSuccessful()) {
                    error('Failed to install php-cs-fixer');
                    return Command::FAILURE;
                }
            } else {
                info('Installing Laravel Pint...');

                if (!$this->exec(['composer', 'require', 'laravel/pint', '--dev', '--no-interaction'])->isSuccessful()) {
                    error('Failed to install Laravel Pint');
                    return Command::FAILURE;
                }
            }
        }

        outro('Common tooling setup completed successfully');

        return Command::SUCCESS;
    }
}
