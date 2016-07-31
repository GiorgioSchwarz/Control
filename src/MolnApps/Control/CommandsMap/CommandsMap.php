<?php

namespace MolnApps\Control\CommandsMap;

class CommandsMap
{
	private $commandMap = [];
	private $viewMap = [];
	private $forwardMap = [];
	private $classrootMap = [];
	private $middlewareMap = [];
	
	public function addCommand($command)
	{
		$this->commandMap[$command] = 1;
	}

	public function getCommands()
	{
		return $this->commandMap;
	}
	
	public function verifyCommand($command)
	{
		return (isset($this->commandMap[$command]));
	}
	
	public function addClassroot($command, $classroot)
	{
		if ($classroot) {
			$this->classrootMap[$command] = $classroot;
		}
	}
	
	public function getClassroot($command)
	{
		if (isset($this->classrootMap[$command])) {
			return $this->classrootMap[$command];
		}
		return $command;
	}
	
	public function addView($command = 'default', $status = 0, $view)
	{
		if ($view) {
			$this->viewMap[$command][$status][] = $view;
		}
	}

	public function setView($command, $status, $view)
	{
		$this->viewMap[$command][$status] = [];
		$this->addView($command, $status, $view);
	}
	
	public function getViews($command, $status) 
	{
		if (isset($this->viewMap[$command][$status])) {
			return $this->viewMap[$command][$status];
		}
	}
	
	public function addForward($command, $status = 0, $newCommand)
	{
		if ($newCommand) {
			$this->forwardMap[$command][$status] = $newCommand;
		}
	}

	public function setForward($command, $status, $newCommand)
	{
		return $this->addForward($command, $status, $newCommand);
	}
	
	public function getForward($command, $status)
	{
		if (isset($this->forwardMap[$command][$status])) {
			return $this->forwardMap[$command][$status];
		}
	}

	public function addMiddleware($command, $middleware)
	{
		foreach ((array)$middleware as $m) {
			$this->middlewareMap[$command][] = $m;
		}
	}

	public function getMiddleware($command)
	{
		if (isset($this->middlewareMap[$command])) {
			return $this->middlewareMap[$command];
		}
		return [];
	}

	public function resolveCommand($command)
	{
		$command = $this->getClassroot($command);
		
		if ( ! $this->verifyCommand($command)) {
			throw new \Exception('Command '.$command.' is not defined.');
		}

		return $command;
	}
	
	public function __toString() {
		return implode(', ', array_keys($this->commandMap));
	}
}