<?php

namespace Setuphp\Command;

use Composer\Semver\VersionParser;
use Symfony\Component\Process\Process;

/** @mixin \Symfony\Component\Console\Command\Command */
trait Utilities
{
    /** Run a process. */
    protected function exec(array $command, bool $interactive = false): Process
    {
        $process = new Process(array_filter($command));

        if ($interactive) {
            $process->setTty(true);
        }

        $process->run();

        return $process;
    }

    protected function getProjectName(): ?string
    {
        return basename(getcwd());

        // if (file_exists('composer.json')) {
        //     $composer = json_decode(file_get_contents('composer.json'), true);

        //     if (isset($composer['name'])) {
        //         if (str_contains($composer['name'], '/')) {
        //             return array_reverse(explode('/', $composer['name']))[0];
        //         }

        //         return $composer['name'];
        //     }
        // }

        // return null;
    }

    /** Creates a file and all directories needed to make it. */
    protected function createFile(string $filePath, string $contents = ''): bool
    {
        if (file_exists($filePath)) {
            return (file_put_contents($filePath, $contents) !== false);
        }

        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                return false;
            }
        }

        if (!touch($filePath)) {
            return false;
        }

        return file_put_contents($filePath, $contents) !== false;;
    }

    protected function packageIsInstalled(string $packageName, bool $includeDevDependencies = true): bool
    {
        if (file_exists('composer.lock')) {
            $composerLock = json_decode(file_get_contents('composer.lock'), true);

            foreach ($composerLock['packages'] as $package) {
                if ($package['name'] === $packageName) {
                    return true;
                }

                foreach ($package['require'] as $require => $version) {
                    if ($require === $packageName) {
                        return true;
                    }
                }
            }
        }

        if (!file_exists('composer.json')) {
            return false;
        }

        $composerJson = json_decode(file_get_contents('composer.json'), true);

        if (isset($composerJson['require'][$packageName])) {
            return true;
        }

        if ($includeDevDependencies && isset($composerJson['require-dev'][$packageName])) {
            return true;
        }

        return false;
    }

    /** Get the "major.minor" formatted PHP version */
    protected function getPhpVersion(): string
    {
        if (file_exists('composer.json')) {
            $composer = json_decode(file_get_contents('composer.json'), true);

            $parser = new VersionParser();

            if (isset($composer['config']['platform']['php'])) {
                $version = $parser->parseConstraints($composer['config']['platform']['php'])->getLowerBound()->getVersion();
                $split = explode('.', $version, 3);
                return $split[0] . '.' . $split[1];
            }

            if (isset($composer['require']['php'])) {
                $version = $parser->parseConstraints($composer['require']['php'])->getLowerBound()->getVersion();
                $split = explode('.', $version, 3);
                return $split[0] . '.' . $split[1];
            }
        }

        $version = PHP_VERSION;
        $split = explode('.', $version, 3);

        return $split[0] . '.' . $split[1];
    }
}
