<?php

namespace MolnApps\Control\AppController;

use \MolnApps\Control\CommandsMap\CommandsMap;
use \MolnApps\Control\Command\CommandsChain;

class ResourcesFinder
{
	private $commandsMap;

	public function __construct(CommandsMap $commandsMap)
	{
		$this->commandsMap = $commandsMap;
	}

	public function getViews(CommandsChain $commandsChain = null)
	{
		return $this->getResource($commandsChain, 'getViews');
	}

	public function getForward(CommandsChain $commandsChain = null)
	{
		return $this->getResource($commandsChain, 'getForward');
	}

	private function getResource(CommandsChain $commandsChain, $methodName)
	{
		$commandName = (string)$commandsChain->getLastCommandName();
		$commandStatus = (int)$commandsChain->getLastCommandStatus();

		$resource = $this->commandsMap->$methodName($commandName, $commandStatus);

		if ( ! $resource) {
			$resource = $this->commandsMap->$methodName($commandName, 0);
		}

		if ( ! $resource) {
			$resource = $this->commandsMap->$methodName('default', $commandStatus);
		}

		if ( ! $resource) {
			$resource = $this->commandsMap->$methodName('default', 0);
		}

		if ( ! $resource || $resource == $commandName) {
			return;
		}

		return $resource;
	}
}