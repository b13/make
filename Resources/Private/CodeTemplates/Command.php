<?php

declare(strict_types=1);

namespace {{NAMESPACE}};

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class {{NAME}} extends Command
{
    protected function configure(): void
    {
        $this->setDescription('{{DESCRIPTION}}');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Do awesome stuff
        return 0;
    }
}
