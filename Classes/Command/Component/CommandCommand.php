<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Command\Component;

use B13\Make\Component\Command;
use B13\Make\Component\ComponentInterface;
use B13\Make\Exception\AbortCommandException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for creating a new command component
 */
class CommandCommand extends SimpleComponentCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Create a console command');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->initializeServiceConfiguration();
    }

    protected function createComponent(): ComponentInterface
    {
        $command = new Command($this->psr4Prefix);
        return $command
            ->setName(
                (string)$this->io->ask(
                    'Enter the name of the command (e.g. "AwesomeCommand")?',
                    null,
                    [$this, 'answerRequired']
                )
            )
            ->setDirectory(
                (string)$this->io->ask(
                    'Enter the directory, the command should be placed in',
                    $this->getProposalFromEnvironment('COMMAND_DIR', 'Command')
                )
            )
            ->setCommandName(
                (string)$this->io->ask(
                    'Enter the command name to execute on CLI',
                    $command->getCommandNameProposal($this->getProposalFromEnvironment('COMMAND_NAME_PREFIX', $this->extensionKey))
                )
            )
            ->setDescription(
                (string)$this->io->ask(
                    'Enter a short description for the command',
                    null,
                    [$this, 'answerRequired']
                )
            )
            ->setSchedulable(
                (bool)$this->io->confirm('Should the command be schedulable?', false)
            );
    }

    /**
     * @param Command $component
     * @throws AbortCommandException
     */
    protected function publishComponentConfiguration(ComponentInterface $component): bool
    {
        if (!$this->writeServiceConfiguration($component)) {
            $this->io->error('Updating the service configuration failed.');
            return false;
        }

        $this->io->success('Sucessfully created the command ' . $component->getName() . ' (' . $component->getCommandName() . ').');
        return true;
    }
}
