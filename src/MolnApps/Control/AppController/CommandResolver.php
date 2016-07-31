<?php

namespace MolnApps\Control\AppController;

use \MolnApps\Control\CommandsMap\CommandsMap;
use \MolnApps\Control\Command\CommandFactory;

class CommandResolver
{
	private $resourceFinder;
	private $commandsMap;
	private $commandsFactory;

	public function __construct(CommandsMap $commandsMap, CommandFactory $commandsFactory)
	{
		$this->resourceFinder = new ResourcesFinder($commandsMap);
		$this->commandsMap = $commandsMap;
		$this->commandsFactory = $commandsFactory;
	}

	public function getViews($cmd)
	{
		return $this->resourceFinder->getViews($cmd);
	}

	public function getForward($cmd)
	{
		return $this->resourceFinder->getForward($cmd);
	}

	public function resolve($cmd)
	{
		$cmd = $this->commandsMap->resolveCommand($cmd);

		return $this->commandsFactory->createCommand($cmd);
	}
}