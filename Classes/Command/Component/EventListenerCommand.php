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

use B13\Make\Component\ComponentInterface;
use B13\Make\Component\EventListener;
use B13\Make\Exception\AbortCommandException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for creating a new event listener component
 */
class EventListenerCommand extends SimpleComponentCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Create a PSR-14 event listener');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->initializeServiceConfiguration();
    }

    protected function createComponent(): ComponentInterface
    {
        $eventListener = new EventListener($this->psr4Prefix);
        return $eventListener
            ->setEventName(
                (string)$this->io->ask(
                    'Enter the event to listen for? - Use the FQN',
                    null,
                    [$this, 'answerRequired']
                )
            )
            ->setName(
                (string)$this->io->ask(
                    'Enter the name of the listener (e.g. "AwesomeEventListener")',
                    $eventListener->getNameProposal()
                )
            )
            ->setDirectory(
                (string)$this->io->ask(
                    'Enter the directory, the listener should be placed in',
                    $this->getProposalFromEnvironment('EVENT_LISTENER_DIR', 'EventListener')
                )
            )
            ->setIdentifier(
                (string)$this->io->ask(
                    'Enter an identifier for the listener',
                    $eventListener->getIdentifierProposal($this->getProposalFromEnvironment('EVENT_LISTENER_IDENTIFIER_PREFIX'))
                )
            )
            ->setMethodName(
                (string)$this->io->ask('Enter the method, which should receive the event - LEAVE EMPTY FOR USING __invoke()')
            );
    }

    /**
     * @param EventListener $component
     * @throws AbortCommandException
     */
    protected function publishComponentConfiguration(ComponentInterface $component): bool
    {
        if (!$this->writeServiceConfiguration($component)) {
            $this->io->error('Updating the service configuration failed.');
            return false;
        }

        $this->io->success('Sucessfully created the event listener ' . $component->getName() . ' for event ' . $component->getEventName());
        return true;
    }
}
