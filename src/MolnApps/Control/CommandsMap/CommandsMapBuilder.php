<?php

namespace MolnApps\Control\CommandsMap;

class CommandsMapBuilder
{
	private $commandStatuses;
	private $defaultBehaviour;

	private $currentCommandName;

	private $commandsMap;

	private $currentGroups = [];
	private $groupCommandNames = [];

	private $groupMiddleware = [];
	private $commandMiddleware = [];

	public function __construct($commandStatuses, array $defaultBehaviour = [])
	{
		$this->commandsMap = new CommandsMap();	
		
		$this
			->setCommandStatuses($commandStatuses)
			->setDefaultBehaviour($defaultBehaviour);
	}

	private function setCommandStatuses($commandStatuses)
	{
		$this->commandStatuses = CommandStatuses::create($commandStatuses);

		return $this;
	}

	public function setDefaultBehaviour(array $defaultBehaviour)
	{
		$this->defaultBehaviour = $defaultBehaviour;

		$this->setDefaults();

		return $this;
	}

	private function setDefaults()
	{
		foreach ($this->defaultBehaviour as $commandStatusStr => $viewOrForward) {
			$this->command('default')->on($commandStatusStr, $viewOrForward);
		}
		
		$this->currentCommandName = null;
	}

	public function openGroup($groupName)
	{
		$this->currentGroups = [];
		
		return $this->addGroup($groupName);
	}

	public function addGroup($groupName)
	{
		$this->currentGroups[] = $groupName;

		return $this;
	}

	public function closeGroup($groupName)
	{
		$this->currentGroups = array_diff($this->currentGroups, [$groupName]);

		return $this;
	}

	public function command($commandName)
	{
		if ($this->currentGroups) {
			$this->addCommandToGroups($commandName, $this->currentGroups);
		}

		if ( ! $this->commandsMap->verifyCommand($commandName)) {
			$this->commandsMap->addCommand($commandName);
		}
		
		$this->currentCommandName = $commandName;

		return $this;
	}

	public function alias($commandName)
	{
		$this->commandsMap->addClassroot($this->currentCommandName, $commandName);
		
		return $this;
	}

	public function middleware($middleware)
	{
		$this->commandMiddleware[$this->currentCommandName][] = $middleware;

		return $this;
	}

	public function middlewareGroup($groupName)
	{
		$this->addCommandToGroups($this->currentCommandName, $groupName);

		return $this;
	}

	public function setGroupMiddleware($group, $middleware)
	{
		$this->groupMiddleware[$group] = $middleware;
		
		return $this;
	}

	public function on($commandStatusStr, $viewOrForward)
	{
		$commandStatus = $this->getCommandStatus($commandStatusStr);
		
		if ($this->isView($viewOrForward)) {
			$this->commandsMap->setView($this->currentCommandName, $commandStatus, $viewOrForward);
		}
		
		if ($this->isForward($viewOrForward)) {
			$this->commandsMap->setForward($this->currentCommandName, $commandStatus, substr($viewOrForward, 1));
		}
		
		return $this;
	}

	public function defaultBehaviour()
	{
		foreach ($this->defaultBehaviour as $commandStatusStr => $viewOrForward) {
			$this->on($commandStatusStr, $viewOrForward);
		}

		return $this;
	}

	private function isView($viewOrForward)
	{
		return ! $this->isForward($viewOrForward);
	}

	private function isForward($viewOrForward)
	{
		return (stripos($viewOrForward, '@') !== false);
	}

	private function getCommandStatus($commandStatusStr)
	{
		return $this->commandStatuses->getStatus($commandStatusStr);
	}

	public function getCommandsMap()
	{
		$this->registerMiddleware();

		return $this->commandsMap;
	}

	private function registerMiddleware()
	{
		$this->addGroupMiddlewares();
		$this->addCommandMiddlewares();
	}

	private function addGroupMiddlewares()
	{
		foreach ($this->groupMiddleware as $group => $middleware) {
			foreach ($this->getGroupCommandNames($group) as $commandName) {
				$this->commandsMap->addMiddleware($commandName, $middleware);
			}
		}
	}

	private function addCommandMiddlewares()
	{
		foreach ($this->commandMiddleware as $commandName => $middlewares) {
			foreach ($middlewares as $middleware) {
				$this->commandsMap->addMiddleware($commandName, $middleware);
			}
		}
	}

	private function addCommandToGroups($commandName, $groupNames)
	{
		foreach ((array)$groupNames as $groupName) {
			$this->groupCommandNames[$groupName][] = $commandName;
		}
	}

	private function getGroupCommandNames($groupName)
	{
		if (isset($this->groupCommandNames[$groupName])) {
			return $this->groupCommandNames[$groupName];
		}

		return [];
	}
}