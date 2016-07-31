<?php

namespace MolnApps\Control\Command;

use \Countable;
use \MolnApps\Control\Command\CommandsChain;
use \MolnApps\Control\Command\CommandsChainTrait;

class SimpleCommandsChain implements CommandsChain, Countable
{
	use CommandsChainTrait;

	public function __construct($commandName = null)
	{
		$this->setCommandName($commandName);
	}

	public function count()
	{
		return $this->getExecutedCommandsCount();
	}
}