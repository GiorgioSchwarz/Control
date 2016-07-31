<?php

namespace MolnApps\Control\Command;

use \MolnApps\Control\Command\Command;

interface CommandsChain
{
	public function setCommandName($commandName = null);
	public function getCommandName();

	public function setLastCommand($commandName, Command $command);
	
	public function getLastCommandName();
	public function getLastCommandStatus();
	
	public function commandWasExecuted($commandName);
	public function getExecutedCommandsCount();
}