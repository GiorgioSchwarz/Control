<?php

namespace MolnApps\Control\Command;

interface Command
{
	public function execute();

	public function setStatus($status);
	public function getStatus();
	
	public function statuses($str = 'CMD_DEFAULT');
	public function getCommandStatuses();
}