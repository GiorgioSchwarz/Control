<?php

namespace MolnApps\Control\Command;

interface CommandFactory
{
	public function createCommand($commandName);
}