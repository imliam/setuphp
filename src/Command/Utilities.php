<?php

namespace Setuphp\Command;

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
        if (file_exists('composer.json')) {
            $composer = json_decode(file_get_contents('composer.json'), true);

            if (isset($composer['name'])) {
                if (str_contains($composer['name'], '/')) {
                    return array_reverse(explode('/', $composer['name']))[0];
                }

                return $composer['name'];
            }
        }

        return null;
    }
}
