<?php

namespace MolnApps\Control\AppController;

use \MolnApps\Control\CommandsMap\CommandsMap;
use \MolnApps\Control\Middleware\CommandMiddleware;

class MiddlewareController
{
	private $commandsMap;
	private $commandsMiddleware;

	private $middlewareStatus;

	private $commandName;

	public function __construct(CommandsMap $commandsMap, CommandMiddleware $commandsMiddleware = null)
	{
		$this->commandsMap = $commandsMap;
		$this->commandsMiddleware = $commandsMiddleware;
	}

	public function authorize($commandName)
	{
		$commandMiddleware = $this->commandsMap->getMiddleware($commandName);
		if ($commandMiddleware && ! $this->commandsMiddleware) {
			throw new \Exception('The command ['.$commandName.'] has ['.implode(', ', $commandMiddleware).'] middleware but no CommandMiddleware has been passed.');
		}

		if ( ! $this->commandsMiddleware) {
			return true;
		}

		$this->commandName = $commandName;

		$this->middlewareStatus = $this->commandsMiddleware->authorize($commandName);
		
		return ( ! $this->hasErrors());
	}

	public function hasErrors()
	{
		return $this->commandsMiddleware && $this->commandsMiddleware->hasErrors();
	}

	public function getForward()
	{
		return $this->getResource('getForward');
	}

	public function getViews()
	{
		return $this->getResource('getViews');
	}

	private function getResource($methodName)
	{
		$qualifiedResource = $this->commandsMap->$methodName($this->commandName, $this->middlewareStatus);

		if ($qualifiedResource) {
			return $qualifiedResource;
		}

		return $this->commandsMap->$methodName('default', $this->middlewareStatus);
	}
}