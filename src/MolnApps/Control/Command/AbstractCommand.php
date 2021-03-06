<?php

namespace MolnApps\Control\Command;

use \MolnApps\Control\Command\CommandInterface;
use \MolnApps\Control\CommandsMap\CommandStatuses;

abstract class AbstractCommand implements CommandInterface
{
	private $commandStatuses;
	
	private $status = 0;

	public function __construct($commandStatuses) {
		$this->commandStatuses = CommandStatuses::create($commandStatuses);
	}

	public function execute()
	{
		$status = $this->doExecute();
		
		$this->setStatus($status);
	}

	abstract protected function doExecute();
	
	public function getStatus()
	{
		return $this->status;
	}
	
	private function setStatus($status)
	{
		$this->status = $status;
	}
	
	protected function statuses($str = 'CMD_DEFAULT')
	{
		if (empty($str)) {
			$str = 'CMD_DEFAULT';
		}

		return $this->commandStatuses->getStatus($str);
	}
}