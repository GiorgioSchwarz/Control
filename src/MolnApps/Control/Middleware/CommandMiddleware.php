<?php

namespace MolnApps\Control\Middleware;

use \MolnApps\Control\CommandsMap\CommandsMap;
use \MolnApps\Control\Middleware\Middleware;

class CommandMiddleware
{
	private $commandsMap;
	
	private $middleware = [];

	private $lastResult;

	public function __construct(CommandsMap $commandsMap)
	{
		$this->commandsMap = $commandsMap;
	}

	public function register($key, Middleware $middleware)
	{
		$this->middleware[$key] = $middleware;
	}

	public function authorize($commandName)
	{
		$commandMiddleware = $this->commandsMap->getMiddleware($commandName);

		$this->lastResult = null;

		foreach ((array)$commandMiddleware as $key) {
			$this->lastResult = $this->getMiddleware($key)->authorize();
			if ($this->hasErrors()) {
				return $this->lastResult;
			}
		}
	}

	public function hasErrors()
	{
		return $this->lastResult != $this->expectedResult();
	}

	private function expectedResult()
	{
		return 0;
	}

	public function getMiddleware($key)
	{
		if ( ! isset($this->middleware[$key])) {
			throw new \Exception('Unknown middleware ['.$key.']');
		}

		return $this->middleware[$key];
	}
}