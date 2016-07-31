<?php

namespace MolnApps\Control\AppController;

use \MolnApps\Control\CommandsMap\CommandsMap;
use \MolnApps\Control\CommandsMap\CommandStatuses;

use \MolnApps\Control\Middleware\CommandMiddleware;
use \MolnApps\Control\Middleware\Middleware;

class MiddlewareControllerTest extends \PHPUnit_Framework_TestCase
{
	private $commandStatuses;
	private $middleware;
	private $commandsMap;
	private $commandMiddleware;

	protected function setUp()
	{
		$this->middleware = $this->createMock(Middleware::class);
		
		$this->commandStatuses = new CommandStatuses([
			'CMD_OK' => 1,
			'CMD_DEFAULT' => 0,
			'CMD_AUTH_ERROR' => -1,
		]);

		$this->commandsMap = new CommandsMap;

		$this->commandMiddleware = new CommandMiddleware($this->commandsMap);
	}

	/** @test */
	public function it_can_be_instantiated_with_a_commands_map()
	{
		$commandsMap = new CommandsMap;

		$middlewareController = new MiddlewareController($commandsMap);
		
		$this->assertNotNull($middlewareController);
	}

	/** @test */
	public function it_will_authorize_if_no_command_middleware_is_passed_and_command_has_no_middleware()
	{
		$this->command('SaveArticle')->hasNoMiddleware();

		$middlewareController = new MiddlewareController($this->commandsMap);
		
		$this->assertTrue($middlewareController->authorize('SaveArticle'));

		$this->assertFalse($middlewareController->hasErrors());
	}

	/** @test */
	public function it_will_throw_if_command_has_middleware_but_no_command_middleware_is_passed()
	{
		$this->command('SaveArticle')->hasMiddleware('auth');
		
		$middlewareController = new MiddlewareController($this->commandsMap);

		$this->setExpectedException(
			'\Exception', 
			'The command [SaveArticle] has [auth] middleware but no CommandMiddleware has been passed'
		);
		
		$middlewareController->authorize('SaveArticle');
	}

	/** @test */
	public function it_will_authorize_if_command_has_no_middleware_and_command_middleware_is_passed()
	{
		$this->command('SaveArticle')->hasNoMiddleware();
		
		$this->middleware('auth')->isNotRegistered();

		$middlewareController = new MiddlewareController($this->commandsMap, $this->commandMiddleware);

		$this->assertTrue($middlewareController->authorize('SaveArticle'));

		$this->assertFalse($middlewareController->hasErrors());
	}

	/** @test */
	public function it_will_throw_if_command_has_middleware_and_middleware_is_not_registered()
	{
		$this->command('SaveArticle')->hasMiddleware('auth');
		
		$this->middleware('auth')->isNotRegistered();

		$middlewareController = new MiddlewareController($this->commandsMap, $this->commandMiddleware);

		$this->setExpectedException('\Exception', 'Unknown middleware [auth]');
		
		$middlewareController->authorize('SaveArticle');
	}

	/** @test */
	public function it_will_authorize_if_command_has_registered_middleware_and_middleware_authorizes()
	{
		$this->command('SaveArticle')->hasMiddleware('auth');
		
		$this->middleware('auth')->isRegistered()->willReturn('CMD_DEFAULT');

		$middlewareController = new MiddlewareController($this->commandsMap, $this->commandMiddleware);

		$this->assertTrue($middlewareController->authorize('SaveArticle'));

		$this->assertFalse($middlewareController->hasErrors());
	}

	/** @test */
	public function it_wont_authorize_if_command_has_registered_middleware_and_middleware_does_not_authorize()
	{
		$this->command('SaveArticle')->hasMiddleware('auth');
		
		$this->middleware('auth')->isRegistered()->willReturn('CMD_AUTH_ERROR');

		$middlewareController = new MiddlewareController($this->commandsMap, $this->commandMiddleware);

		$this->assertFalse($middlewareController->authorize('SaveArticle'));

		$this->assertTrue($middlewareController->hasErrors());
	}

	/** @test */
	public function it_returns_qualified_forward_if_command_is_not_authorized()
	{
		$this->command('SaveArticle')
			->hasMiddleware('auth')
			->hasQualifiedForward('CMD_AUTH_ERROR', 'SignIn');
		
		$this->middleware('auth')->isRegistered()->willReturn('CMD_AUTH_ERROR');
		
		$middlewareController = new MiddlewareController($this->commandsMap, $this->commandMiddleware);
		$middlewareController->authorize('SaveArticle');

		$this->assertEquals('SignIn', $middlewareController->getForward());
	}

	/** @test */
	public function it_returns_qualified_views_if_command_is_not_authorized()
	{
		$this->command('SaveArticle')
			->hasMiddleware('auth')
			->hasQualifiedView('CMD_AUTH_ERROR', 'signin');
		
		$this->middleware('auth')->isRegistered()->willReturn('CMD_AUTH_ERROR');
		
		$middlewareController = new MiddlewareController($this->commandsMap, $this->commandMiddleware);
		$middlewareController->authorize('SaveArticle');

		$this->assertEquals(['signin'], $middlewareController->getViews());
	}

	/** @test */
	public function it_returns_default_forward_if_command_is_not_authorized_and_has_no_qualified_forwards()
	{
		$this->command('SaveArticle')->hasMiddleware('auth');
		
		$this->commandsMap()->hasDefaultForward('CMD_AUTH_ERROR', 'SignIn');
		
		$this->middleware('auth')->isRegistered()->willReturn('CMD_AUTH_ERROR');
		
		$middlewareController = new MiddlewareController($this->commandsMap, $this->commandMiddleware);
		$middlewareController->authorize('SaveArticle');

		$this->assertEquals('SignIn', $middlewareController->getForward());
	}

	/** @test */
	public function it_returns_default_views_if_command_is_not_authorized_and_has_no_qualified_views()
	{
		$this->command('SaveArticle')->hasMiddleware('auth');

		$this->commandsMap()->hasDefaultView('CMD_AUTH_ERROR', 'signin');
		
		$this->middleware('auth')->isRegistered()->willReturn('CMD_AUTH_ERROR');
		
		$middlewareController = new MiddlewareController($this->commandsMap, $this->commandMiddleware);
		$middlewareController->authorize('SaveArticle');

		$this->assertEquals(['signin'], $middlewareController->getViews());
	}

	// ! Middleware methods

	private function middleware($key)
	{
		$this->middlewareKey = $key;
		return $this;
	}

	private function isRegistered()
	{
		$this->commandMiddleware->register($this->middlewareKey, $this->middleware);
		return $this;
	}

	private function isNotRegistered()
	{
		// Do nothing
		return $this;
	}

	private function willReturn($commandStatus)
	{
		$commandStatus = $this->getCommandStatus($commandStatus);
		$this->middleware->method('authorize')->willReturn($commandStatus);
		return $this;
	}

	// ! Command methods

	private function command($commandName)
	{
		$this->commandName = $commandName;
		$this->commandsMap->addCommand($commandName);
		return $this;
	}

	private function hasMiddleware($middleware)
	{
		$this->commandsMap->addMiddleware($this->commandName, $middleware);
		return $this;
	}

	private function hasNoMiddleware()
	{
		return $this;
	}

	private function hasQualifiedView($commandStatus, $view)
	{
		$commandStatus = $this->getCommandStatus($commandStatus);
		$this->commandsMap->addView($this->commandName, $commandStatus, $view);
		return $this;
	}

	private function hasQualifiedForward($commandStatus, $forward)
	{
		$commandStatus = $this->getCommandStatus($commandStatus);
		$this->commandsMap->addForward($this->commandName, $commandStatus, $forward);
		return $this;
	}

	// ! CommandsMap methods

	private function commandsMap()
	{
		return $this->command('default');
	}

	private function hasDefaultForward($commandStatus, $forward)
	{
		return $this->hasQualifiedForward($commandStatus, $forward);
	}

	private function hasDefaultView($commandStatus, $view)
	{
		return $this->hasQualifiedView($commandStatus, $view);
	}

	// ! Utility methods

	private function getCommandStatus($commandStatusString)
	{
		return $this->commandStatuses->getStatus($commandStatusString);
	}
}