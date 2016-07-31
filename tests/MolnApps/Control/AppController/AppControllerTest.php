<?php

namespace MolnApps\Control\AppController;

use \MolnApps\Control\CommandsMap\CommandStatuses;
use \MolnApps\Control\CommandsMap\CommandsMapBuilder;

use \MolnApps\Control\Command\CommandFactory;
use \MolnApps\Control\Command\Command;

use \MolnApps\Control\Middleware\Middleware;
use \MolnApps\Control\Middleware\CommandMiddleware;

class AppControllerTest extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		$this->commandStatuses = $this->createCommandStatuses();
		$this->commandsMap = $this->createCommandsMap();
		$this->commandObjects = $this->createCommandStubs();
		$this->commandsFactory = $this->createCommandsFactoryStub();

		$this->commandResolver = new CommandResolver($this->commandsMap, $this->commandsFactory);
		
		$this->middleware = $this->createMock(Middleware::class);
		$this->commandMiddleware = new CommandMiddleware($this->commandsMap);
		$this->commandMiddleware->register('auth', $this->middleware);
		
		$this->middlewareController = new MiddlewareController($this->commandsMap, $this->commandMiddleware);
	}

	private function createCommandStatuses()
	{
		return new CommandStatuses([
			'CMD_AUTH_ERROR' => -2,
			'CMD_ERROR' => -1, 
			'CMD_DEFAULT' => 0, 
			'CMD_OK' => 1
		]);
	}

	private function createCommandsMap()
	{
		$builder = new CommandsMapBuilder($this->commandStatuses);

		$builder->setGroupMiddleware('web', ['auth']);

		$builder
			->setDefaultBehaviour([
				'CMD_AUTH_ERROR' => 'default.signin',
				'CMD_ERROR' => 'default.error',
				'CMD_DEFAULT' => 'default.index',
			])
			->command('CommandWithDefaultForward')
				->on('CMD_DEFAULT', '@CommandWithDefaultView')
			->command('CommandWithQualifiedForward')
				->on('CMD_OK', '@CommandWithDefaultView')
			->command('CommandWithDefaultView')
				->on('CMD_DEFAULT', 'default.view')
			->command('CommandWithQualifiedView')
				->on('CMD_ERROR', 'qualified.view')
			->command('CommandWithAlias')
				->alias('CommandWithQualifiedView')
				->on('CMD_DEFAULT', 'alias.view')
			->command('CommandWithoutBehaviour')
			->command('CommandWithMiddleware')
				->middleware('auth')
				->on('CMD_OK', 'qualified.view');

		$builder->openGroup('web');

		$builder
			->command('CommandWithGroupedMiddleware')
				->on('CMD_DEFAULT', 'grouped.view');

		return $builder->getCommandsMap();
	}

	private function createCommandStubs()
	{
		return [
			'CommandWithDefaultForward' => $this->createMock(Command::class),
			'CommandWithQualifiedForward' => $this->createMock(Command::class),
			'CommandWithDefaultView' => $this->createMock(Command::class),
			'CommandWithQualifiedView' => $this->createMock(Command::class),
			'CommandWithAlias' => $this->createMock(Command::class),
			'CommandWithoutBehaviour' => $this->createMock(Command::class),
			'CommandWithMiddleware' => $this->createMock(Command::class),
			'CommandWithGroupedMiddleware' => $this->createMock(Command::class),
		];
	}

	private function createCommandsFactoryStub()
	{
		$commandsFactory = $this->createMock(CommandFactory::class);
		
        $map = [
            ['CommandWithDefaultForward', $this->getCommandStub('CommandWithDefaultForward')],
            ['CommandWithQualifiedForward', $this->getCommandStub('CommandWithQualifiedForward')],
            ['CommandWithDefaultView', $this->getCommandStub('CommandWithDefaultView')],
            ['CommandWithQualifiedView', $this->getCommandStub('CommandWithQualifiedView')],
            ['CommandWithAlias', $this->getCommandStub('CommandWithAlias')],
            ['CommandWithoutBehaviour', $this->getCommandStub('CommandWithoutBehaviour')],
            ['CommandWithMiddleware', $this->getCommandStub('CommandWithMiddleware')],
            ['CommandWithGroupedMiddleware', $this->getCommandStub('CommandWithGroupedMiddleware')],
        ];
		
		$commandsFactory->method('createCommand')->will($this->returnValueMap($map));

		return $commandsFactory;
	}

	// ! Begin tests

	/** @test */
	public function it_can_be_instantiated_with_command_resolver_and_middleware_controller()
	{
		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$this->assertNotNull($appController);
	}

	/** @test */
	public function it_throws_if_command_is_not_defined()
	{
		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$this->setExpectedException('\Exception', 'Command Foobar is not defined');
		
		$appController->executeCommandsChain('Foobar');
	}

	/** @test */
	public function it_executes_a_command_and_returns_command_default_views()
	{
		$this->commandWillReturn('CommandWithDefaultView', 'CMD_DEFAULT');

		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithDefaultView');

		$this->assertEquals(1, $appController->getExecutedCommandsCount());
		$this->assertEquals(['default.view'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_executes_a_command_and_returns_command_qualified_views()
	{
		$this->commandWillReturn('CommandWithQualifiedView', 'CMD_ERROR');

		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithQualifiedView');

		$this->assertEquals(1, $appController->getExecutedCommandsCount());
		$this->assertEquals(['qualified.view'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_executes_a_command_and_its_default_forward()
	{
		$this->commandWillReturn('CommandWithDefaultForward', 'CMD_DEFAULT');
		$this->commandWillReturn('CommandWithDefaultView', 'CMD_DEFAULT');

		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithDefaultForward');

		$this->assertEquals(2, $appController->getExecutedCommandsCount());
		$this->assertEquals(['default.view'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_executes_a_command_and_its_qualified_forward()
	{
		$this->commandWillReturn('CommandWithQualifiedForward', 'CMD_OK');
		$this->commandWillReturn('CommandWithDefaultView', 'CMD_DEFAULT');

		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithQualifiedForward');

		$this->assertEquals(2, $appController->getExecutedCommandsCount());
		$this->assertEquals(['default.view'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_executes_a_command_without_behaviour_and_displays_qualified_default_view()
	{
		$this->commandWillReturn('CommandWithoutBehaviour', 'CMD_ERROR');
		
		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithoutBehaviour');

		$this->assertEquals(1, $appController->getExecutedCommandsCount());
		$this->assertEquals(['default.error'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_executes_a_command_without_behaviour_and_displays_default_view()
	{
		$this->commandWillReturn('CommandWithoutBehaviour', 'CMD_OK');
		
		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithoutBehaviour');

		$this->assertEquals(1, $appController->getExecutedCommandsCount());
		$this->assertEquals(['default.index'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_executes_a_command_with_middleware_and_displays_qualified_view()
	{
		$this->middlewareWillReturn('CMD_DEFAULT');
		$this->commandWillReturn('CommandWithMiddleware', 'CMD_OK');
		
		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithMiddleware');

		$this->assertEquals(1, $appController->getExecutedCommandsCount());
		$this->assertEquals(['qualified.view'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_wont_execute_a_command_with_middleware_if_middleware_does_not_authorize()
	{
		$this->middlewareWillReturn('CMD_AUTH_ERROR');
		$this->commandWillReturn('CommandWithMiddleware', 'CMD_OK');
		
		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithMiddleware');

		$this->assertEquals(0, $appController->getExecutedCommandsCount());
		$this->assertEquals(['default.signin'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_executes_a_command_with_grouped_middleware_and_displays_qualified_view()
	{
		$this->middlewareWillReturn('CMD_DEFAULT');
		$this->commandWillReturn('CommandWithGroupedMiddleware', 'CMD_OK');
		
		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithGroupedMiddleware');

		$this->assertEquals(1, $appController->getExecutedCommandsCount());
		$this->assertEquals(['grouped.view'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_wont_execute_a_command_with_grouped_middleware_if_middleware_does_not_authorize()
	{
		$this->middlewareWillReturn('CMD_AUTH_ERROR');
		$this->commandWillReturn('CommandWithGroupedMiddleware', 'CMD_OK');
		
		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithGroupedMiddleware');

		$this->assertEquals(0, $appController->getExecutedCommandsCount());
		$this->assertEquals(['default.signin'], $appController->getQualifiedViews());
	}

	/** @test */
	public function it_executes_a_command_with_alias_and_displays_qualified_view()
	{
		$this->commandWillReturn('CommandWithAlias', 'CMD_OK');
		
		$appController = new AppController($this->commandResolver, $this->middlewareController);
		
		$appController->executeCommandsChain('CommandWithAlias');

		$this->assertEquals(1, $appController->getExecutedCommandsCount());
		$this->assertEquals(['alias.view'], $appController->getQualifiedViews());
	}

	// ! Utility methods

	private function middlewareWillReturn($commandStatus)
	{
		$commandStatus = $this->getCommandStatus($commandStatus);
		$this->middleware->method('authorize')->willReturn($commandStatus);
	}

	private function commandWillReturn($commandName, $commandStatus)
	{
		$commandStatus = $this->getCommandStatus($commandStatus);
		$this->getCommandStub($commandName)->method('getStatus')->willReturn($commandStatus);
	}

	private function getCommandStub($commandName)
	{
		return $this->commandObjects[$commandName];
	}

	private function getCommandStatus($commandStatusString)
	{
		return $this->commandStatuses->getStatus($commandStatusString);
	}
}