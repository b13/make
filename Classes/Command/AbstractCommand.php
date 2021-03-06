<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Command;

use B13\Make\Environment\Variables;
use B13\Make\Exception\EmptyAnswerException;
use B13\Make\Exception\InvalidPackageNameException;
use B13\Make\PackageResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract command with basic functionalities
 */
abstract class AbstractCommand extends Command
{
    /** @var SymfonyStyle */
    protected $io;

    /** @var PackageResolver */
    protected $packageResolver;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->packageResolver = GeneralUtility::makeInstance(PackageResolver::class);
    }

    protected function getProposalFromEnvironment(string $key, string $default = ''): string
    {
        return Variables::has($key) ? Variables::get($key) : $default;
    }

    /**
     * @param mixed|string $answer
     */
    public function answerRequired($answer): string
    {
        $answer = (string)$answer;

        if (trim($answer) === '') {
            throw new EmptyAnswerException('Answer can not be empty.', 1639664759);
        }

        return $answer;
    }

    /**
     * @param mixed|string $answer
     *
     * @see https://getcomposer.org/doc/04-schema.md#name
     */
    public function validatePackageKey($answer): string
    {
        $answer = $this->answerRequired($answer);

        if (!preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$/', $answer)) {
            throw new InvalidPackageNameException(
                'Package key does not match the allowed pattern. More information are available on https://getcomposer.org/doc/04-schema.md#name.',
                1639664760
            );
        }

        return $answer;
    }

    /**
     * Resolve package using the extension key from either input argument, environment variable or CLI
     */
    protected function getPackage(InputInterface $input): ?PackageInterface
    {
        if ($input->hasArgument('extensionKey')
            && ($key = ($input->getArgument('extensionKey') ?? '')) !== ''
        ) {
            return $this->packageResolver->resolvePackage($key);
        }

        if (($key = $this->getProposalFromEnvironment('EXTENSION_KEY')) !== '') {
            return $this->packageResolver->resolvePackage($key);
        }

        if (($key = $this->askForExtensionKey()) !== '') {
            $this->io->note('You can also always set the extension key as argument or by using an environment variable.');
            return $this->packageResolver->resolvePackage($key);
        }

        return null;
    }

    /**
     * Let user select an extension to work with.
     */
    protected function askForExtensionKey(): string
    {
        $packages = $this->packageResolver->getAvailablePackages();
        $choices = array_reduce($packages, static function ($result, PackageInterface $package) {
            if ($package->getValueFromComposerManifest('type') === 'typo3-cms-extension' && $package->getPackageKey() !== 'make') {
                $extensionKey = $package->getPackageKey();
                $result[$extensionKey] = $extensionKey;
            }
            return $result;
        }, []);

        if (!$choices) {
            throw new \LogicException('No available extension found. You may want to execute "bin/typo3 make:extension".');
        }

        return (string)$this->io->choice('Select a extension to work on', $choices);
    }
}
