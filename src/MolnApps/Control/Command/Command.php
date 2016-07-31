<?php

namespace MolnApps\Control\Command;

interface Command
{
	public function execute();
	public function getStatus();
}