<?php

namespace MolnApps\Control\Command;

use \MolnApps\Control\Command\Command;

trait CommandsChainTrait
{
	private $commandName;
	private $executedCommands = [];

	public function setCommandName($commandName = null)
	{
		$this->commandName = $commandName;
	}

	public function getCommandName()
	{
		return $this->commandName;
	}

	public function setLastCommand($commandName, Command $command)
	{
		$this->setCommandName($commandName);
		$this->executedCommands[$commandName] = $command;
	}

	public function getLastCommandName()
	{
		if ($this->hasLastCommand()) {
			return $this->getCommandName();
		}
	}

	public function getLastCommandStatus()
	{
		if ($this->hasLastCommand()) {
			return $this->getLastCommand()->getStatus();
		}
	}

	private function getLastCommand()
	{
		if (isset($this->executedCommands[$this->getCommandName()])) {
			return $this->executedCommands[$this->getCommandName()];
		}
	}

	private function hasLastCommand()
	{
		return $this->getExecutedCommandsCount() > 0;
	}

	public function commandWasExecuted($commandName)
	{
		return (in_array($commandName, array_keys($this->executedCommands)));
	}

	public function getExecutedCommandsCount()
	{
		return count($this->executedCommands);
	}
}