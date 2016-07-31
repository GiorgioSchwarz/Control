<?php

namespace MolnApps\Control\Middleware;

use \MolnApps\Control\CommandsMap\CommandsMap;
use \MolnApps\Control\CommandsMap\CommandStatuses;

class CommandMiddlewareTest extends \PHPUnit_Framework_TestCase
{
	private $commandsMap;
	private $commandStatuses;
	private $middleware;

	protected function setUp()
	{
		$this->commandsMap = new CommandsMap;

		$this->commandStatuses = new CommandStatuses([
			'CMD_DEFAULT' => 0,
			'CMD_AUTH_ERROR' => -1,
		]);

		$this->middleware = $this->createMock(Middleware::class);
	}

	/** @test */
	public function it_can_be_instantiated_with_a_command_map()
	{
		$commandMiddleware = new CommandMiddleware(new CommandsMap);
		
		$this->assertNotNull($commandMiddleware);
	}

	/** @test */
	public function it_registers_a_middleware()
	{
		$commandMiddleware = new CommandMiddleware($this->commandsMap);
		
		$commandMiddleware->register('auth', $this->middleware);

		$this->assertEquals($this->middleware, $commandMiddleware->getMiddleware('auth'));
	}

	/** @test */
	public function it_throws_if_cannot_find_middleware()
	{
		$commandMiddleware = new CommandMiddleware(new CommandsMap);
		
		$this->setExpectedException('\Exception', 'Unknown middleware [auth]');
		
		$commandMiddleware->getMiddleware('auth');
	}

	/** @test */
	public function it_reports_no_errors_if_middleware_authorizes_a_command()
	{
		$this->commandHasMiddleware('SaveArticle', 'auth');
		$this->middlewareWillReturn('CMD_DEFAULT');
		
		$commandMiddleware = new CommandMiddleware($this->commandsMap);
		
		// Register middleware
		$commandMiddleware->register('auth', $this->middleware);

		// Attempt to authorize command
		$result = $commandMiddleware->authorize('SaveArticle');

		$this->assertEquals($this->getCommandStatus('CMD_DEFAULT'), $result);

		// Check if errors occourred
		$hasErrors = $commandMiddleware->hasErrors();

		$this->assertFalse($hasErrors);
	}

	/** @test */
	public function it_reports_errors_if_middleware_does_not_authorize_a_command()
	{
		$this->commandHasMiddleware('SaveArticle', 'auth');
		$this->middlewareWillReturn('CMD_AUTH_ERROR');

		$commandMiddleware = new CommandMiddleware($this->commandsMap);
		
		// Register middleware
		$commandMiddleware->register('auth', $this->middleware);

		// Attempt to authorize command
		$result = $commandMiddleware->authorize('SaveArticle');

		$this->assertEquals($this->getCommandStatus('CMD_AUTH_ERROR'), $result);

		// Check if errors occourred
		$hasErrors = $commandMiddleware->hasErrors();

		$this->assertTrue($hasErrors);
	}

	private function commandHasMiddleware($command, $middleware)
	{
		$this->commandsMap->addCommand($command);
		$this->commandsMap->addMiddleware($command, $middleware);
	}

	private function middlewareWillReturn($returnStatusString)
	{
		$this->middleware->method('authorize')->willReturn($this->getCommandStatus($returnStatusString));
	}

	private function getCommandStatus($statusString)
	{
		return $this->commandStatuses->getStatus($statusString);
	}
}