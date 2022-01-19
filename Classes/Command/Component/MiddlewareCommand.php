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
use B13\Make\Component\Middleware;
use B13\Make\Exception\AbortCommandException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for creating a new middleware component
 */
class MiddlewareCommand extends SimpleComponentCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Create a PSR-15 middleware');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->initializeArrayConfiguration('RequestMiddlewares.php');
    }

    protected function createComponent(): ComponentInterface
    {
        $middleware = new Middleware($this->psr4Prefix);
        return $middleware
            ->setName(
                (string)$this->io->ask(
                    'Enter the name of the middleware (e.g. "PostProcessContent")',
                    null,
                    [$this, 'answerRequired']
                )
            )
            ->setDirectory(
                (string)$this->io->ask(
                    'Enter the directory, the middleware should be placed in',
                    $this->getProposalFromEnvironment('MIDDLEWARE_DIR', 'Middleware')
                )
            )
            ->setIdentifier(
                (string)$this->io->ask(
                    'Enter an identifier for the middleware',
                    $middleware->getIdentifierProposal($this->getProposalFromEnvironment('MIDDLEWARE_IDENTIFIER_PREFIX'))
                )
            )
            ->setType(
                (string)$this->io->choice(
                    'Choose the type (context) for the middleware',
                    ['frontend', 'backend'],
                    $this->getProposalFromEnvironment('MIDDLEWARE_TYPE', 'frontend')
                )
            )
            ->setBefore(
                GeneralUtility::trimExplode(
                    ',',
                    (string)$this->io->ask('Enter a comma separated list of identifiers the new middleware should be executed beforehand'),
                    true
                )
            )
            ->setAfter(
                GeneralUtility::trimExplode(
                    ',',
                    (string)$this->io->ask('Enter a comma separated list of identifiers after which the new middleware should be executed'),
                    true
                )
            );
    }

    /**
     * @param Middleware $component
     * @throws AbortCommandException
     */
    protected function publishComponentConfiguration(ComponentInterface $component): bool
    {
        $middlewareConfiguration = $this->arrayConfiguration->getConfiguration();
        if (isset($middlewareConfiguration[$component->getType()][$component->getIdentifier()])
            && !$this->io->confirm('The identifier ' . $component->getIdentifier() . ' already exists for type ' . $component->getType() . '. Do you want to override it?', true)
        ) {
            throw new AbortCommandException('Aborting middleware generation.', 1639664755);
        }

        $middlewareConfiguration[$component->getType()][$component->getIdentifier()] = $component->getArrayConfiguration();
        $this->arrayConfiguration->setConfiguration($middlewareConfiguration);
        if (!$this->writeArrayConfiguration()) {
            $this->io->error('Updating middleware configuration failed.');
            return false;
        }

        $this->io->success('Successfully created the middleware ' . $component->getName() . ' (' . $component->getIdentifier() . ').');
        return true;
    }
}
