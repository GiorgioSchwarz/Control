<?php

namespace MolnApps\Control\AppController;

use \MolnApps\Control\AppController\CommandResolver;
use \MolnApps\Control\AppController\MiddlewareController;

use \MolnApps\Control\Command\CommandsChain;
use \MolnApps\Control\Command\SimpleCommandsChain;

class AppController
{
	private $commandResolver;
	private $middleware;

	private $commandsChain;

	public function __construct(CommandResolver $commandResolver, MiddlewareController $middlewareController)
	{
		$this->commandResolver = $commandResolver;
		$this->middleware = $middlewareController;
	}

	public function executeCommandsChain($commandChain, $defaultCommandName = null)
	{
		$this->commandsChain = $this->normalizeCommandsChain($commandChain, $defaultCommandName);

		while ($commandName = $this->getQualifiedCommand()) {
			if ($this->middleware->authorize($commandName)) {
				$this->executeCommand($commandName);
			}
        }
	}

	private function normalizeCommandsChain($commandChain, $defaultCommandName)
	{
		if ( ! $commandChain instanceof CommandsChain) {
			$commandChain = new SimpleCommandsChain($commandChain);
		}

		if ( ! $commandChain->getCommandName() && $defaultCommandName) {
			$commandChain->setCommandName($defaultCommandName);
		}

		return $commandChain;
	}

	private function executeCommand($commandName)
	{
		$command = $this->createCommand($commandName);
		$this->commandsChain->setLastCommand($commandName, $command);
		$command->execute();
	}

	public function getExecutedCommandsCount()
	{
		return $this->commandsChain->getExecutedCommandsCount();
	}

	public function getQualifiedViews()
	{
		if ($this->middleware->hasErrors()) {
			return (array)$this->middleware->getViews();
		}

		return (array)$this->commandResolver->getViews($this->commandsChain);
	}

	private function getQualifiedCommand()
	{
		if ($this->middleware->hasErrors()) {
			return $this->middleware->getForward();
		}

		if ($this->commandsChain->getExecutedCommandsCount()) {
			return $this->commandResolver->getForward($this->commandsChain);
		}

		return $this->commandsChain->getCommandName();
	}

	private function createCommand($cmd)
	{
		$this->preventCircularForwarding($cmd);

		return $this->commandResolver->resolve($cmd);
	}

	private function preventCircularForwarding($command)
	{
		if ($this->commandsChain->commandWasExecuted($command)) {
			throw new \Exception('Circular forwarding! Command ['.$command.'] already executed.');
		}
	}
}