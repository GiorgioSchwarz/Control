<?php

namespace MolnApps\Control\CommandsMap;

class CommandsMapBuilderTest extends \PHPUnit_Framework_TestCase
{
    private $commandStatuses = [
        'CMD_ERROR'           => -1,
        'CMD_INVALID_REQUEST' => -2,
        'CMD_GET_PLUS'        => -3,
        'CMD_OK'              => 1,
        'CMD_DEFAULT'         => 0,
    ];

    private $defaultBehaviour = [
        'CMD_DEFAULT'         => 'default.index',
        'CMD_OK'              => 'default.index',
        'CMD_ERROR'           => 'default.error',
        'CMD_INVALID_REQUEST' => 'default.invalid',
        'CMD_GET_PLUS'        => 'default.getplus'
    ];
    
    private function getBuilder()
    {
        return new CommandsMapBuilder($this->commandStatuses, $this->defaultBehaviour);
    }

    /** @test */
    public function it_can_define_default_behaviour()
    {
        $builder = new CommandsMapBuilder($this->commandStatuses);

        $builder
            ->setDefaultBehaviour([
                'CMD_DEFAULT'         => 'default.index',
                'CMD_OK'              => 'default.index',
                'CMD_ERROR'           => 'default.error',
                'CMD_INVALID_REQUEST' => 'default.invalid',
                'CMD_GET_PLUS'        => 'default.getplus'
            ]);

        $map = $builder->getCommandsMap();

        $this->assertEquals(['default.index'], $map->getViews('default', 0));
        $this->assertEquals(['default.index'], $map->getViews('default', 1));
        $this->assertEquals(['default.error'], $map->getViews('default', -1));
        $this->assertEquals(['default.invalid'], $map->getViews('default', -2));
        $this->assertEquals(['default.getplus'], $map->getViews('default', -3));
    }
    
    /** @test */
    public function it_can_build_commands_map()
    {
        $builder = $this->getBuilder();
        
        $builder
            ->command('CommandWithForward')
                ->on('CMD_ERROR',   '@AnotherCommandWithView')
                ->on('CMD_OK',      '@CommandWithView')
                ->on('CMD_DEFAULT', 'view.test');
       
        $builder
            ->command('AnotherCommand')
                ->on('CMD_ERROR',   '@ErrorCommand')
                ->on('CMD_OK',      '@SuccessCommand')
                ->on('CMD_DEFAULT', 'another.view');

        $builder
            ->command('CommandAlias')
                ->alias('AnotherCommand')
                ->on('CMD_ERROR',   'test.error.view')
                ->on('CMD_OK',      'test.view')
                ->on('CMD_DEFAULT', 'test.view');

        $map = $builder->getCommandsMap();
        
        $this->assertTrue($map instanceof CommandsMap);

        $this->assertEquals($map->getViews('CommandWithForward', 0), ['view.test']);
        $this->assertEquals($map->getForward('CommandWithForward', 1), 'CommandWithView');
        $this->assertEquals($map->getForward('CommandWithForward', -1), 'AnotherCommandWithView');

        $this->assertEquals($map->getViews('AnotherCommand', 0), ['another.view']);
        $this->assertEquals($map->getForward('AnotherCommand', 1), 'SuccessCommand');
        $this->assertEquals($map->getForward('AnotherCommand', -1), 'ErrorCommand');

        $this->assertEquals($map->getViews('CommandAlias', 0), ['test.view']);
        $this->assertEquals($map->getViews('CommandAlias', 1), ['test.view']);
        $this->assertEquals($map->getViews('CommandAlias', -1), ['test.error.view']);
    }
        
    /** @test */
    public function it_can_build_commands_map_with_default_behaviour()
    {
        $builder = $this->getBuilder();

        $builder
            ->command('CommandWithForward')
                ->defaultBehaviour()
                ->on('CMD_OK',      '@CommandWithView')
                ->on('CMD_DEFAULT', 'view.test');

        $map = $builder->getCommandsMap();
        
        $this->assertTrue($map instanceof CommandsMap);

        $this->assertEquals($map->getViews('CommandWithForward', 0), ['view.test']);
        $this->assertEquals($map->getForward('CommandWithForward', 1), 'CommandWithView');
        $this->assertEquals($map->getViews('CommandWithForward', -1), ['default.error']);
        $this->assertEquals($map->getViews('CommandWithForward', -2), ['default.invalid']);
        $this->assertEquals($map->getViews('CommandWithForward', -3), ['default.getplus']);
    }
    
    /** @test */
    public function it_adds_default_views_or_forwards()
    {
        $builder = $this->getBuilder();

        $map = $builder->getCommandsMap();

        $this->assertEquals($map->getViews('default', 0), ['default.index']);
        $this->assertEquals($map->getViews('default', 1), ['default.index']);
        $this->assertEquals($map->getViews('default', -1), ['default.error']);
        $this->assertEquals($map->getViews('default', -2), ['default.invalid']);
        $this->assertEquals($map->getViews('default', -3), ['default.getplus']);
    }

    /** @test */
    public function it_adds_a_single_middleware()
    {
        $builder = $this->getBuilder();

        $builder
            ->command('MyCommand')
                ->middleware('auth');

        $commandsMap = $builder->getCommandsMap();

        $this->assertEquals(['auth'], $commandsMap->getMiddleware('MyCommand'));
    }

     /** @test */
    public function it_adds_multiple_middlewares()
    {
        $builder = $this->getBuilder();

        $builder
            ->command('MyCommand')
                ->middleware('auth')
                ->middleware('csrf');

        $commandsMap = $builder->getCommandsMap();

        $this->assertEquals(['auth', 'csrf'], $commandsMap->getMiddleware('MyCommand'));
    }

    /** @test */
    public function it_adds_an_array_of_middlewares()
    {
        $builder = $this->getBuilder();

        $builder
            ->command('MyCommand')
                ->middleware(['auth', 'plus']);

        $commandsMap = $builder->getCommandsMap();

        $this->assertEquals(['auth', 'plus'], $commandsMap->getMiddleware('MyCommand'));
    }

    /** @test */
    public function it_adds_an_array_of_middlewares_to_a_group_of_commands()
    {
        $builder = $this->getBuilder();

        $builder
            ->openGroup('web')
                ->command('Foo')
                ->command('Bar')
                ->command('Baz');

        $builder
            ->setGroupMiddleware('web', ['auth', 'plus']);

        $commandsMap = $builder->getCommandsMap();

        $this->assertEquals(['auth', 'plus'], $commandsMap->getMiddleware('Foo'));
        $this->assertEquals(['auth', 'plus'], $commandsMap->getMiddleware('Bar'));
        $this->assertEquals(['auth', 'plus'], $commandsMap->getMiddleware('Baz'));
    }

    /** @test */
    public function it_adds_different_arrays_of_middlewares_to_different_groups_of_commands()
    {
        $builder = $this->getBuilder();

        $builder
            ->openGroup('foo')
                ->command('Foo')
                ->command('FooBar')
                ->command('FooBaz')
            ->openGroup('bar')
                ->command('Bar')
                ->command('BarBaz')
            ->openGroup('baz')
                ->command('Baz')
                ->command('BazFoo');

        $builder
            ->setGroupMiddleware('foo', ['auth', 'plus'])
            ->setGroupMiddleware('baz', ['csrf']);

        $commandsMap = $builder->getCommandsMap();

        $this->assertEquals(['auth', 'plus'], $commandsMap->getMiddleware('Foo'));
        $this->assertEquals(['auth', 'plus'], $commandsMap->getMiddleware('FooBar'));
        $this->assertEquals(['auth', 'plus'], $commandsMap->getMiddleware('FooBaz'));

        $this->assertEquals([], $commandsMap->getMiddleware('Bar'));
        $this->assertEquals([], $commandsMap->getMiddleware('BarBaz'));

        $this->assertEquals(['csrf'], $commandsMap->getMiddleware('Baz'));
        $this->assertEquals(['csrf'], $commandsMap->getMiddleware('BazFoo'));
    }

    /** @test */
    public function it_adds_a_group_of_middlewares_to_a_single_command()
    {
        $builder = $this->getBuilder();

        $builder
            ->openGroup('web')
                ->command('EditClient')
                ->command('SaveClient')
                ->command('SaveSpecialClient')
                    ->middlewareGroup('plus');

        $builder
            ->setGroupMiddleware('web', ['auth', 'csrf'])
            ->setGroupMiddleware('plus', ['plus']);

        $commandsMap = $builder->getCommandsMap();

        $this->assertEquals(['auth', 'csrf'], $commandsMap->getMiddleware('EditClient'));
        $this->assertEquals(['auth', 'csrf'], $commandsMap->getMiddleware('SaveClient'));
        $this->assertEquals(['auth', 'csrf', 'plus'], $commandsMap->getMiddleware('SaveSpecialClient'));
    }

     /** @test */
    public function it_does_not_matter_which_order_you_define_groups_and_register_group_middleware()
    {
        $builder = $this->getBuilder();

        $builder
            ->setGroupMiddleware('web', ['auth', 'csrf'])
            ->setGroupMiddleware('plus', ['plus']);
        
        $builder
            ->openGroup('web')
                ->command('EditClient')
                ->command('SaveClient')
                ->command('SaveSpecialClient')
                    ->middlewareGroup('plus');
        
        $commandsMap = $builder->getCommandsMap();

        $this->assertEquals(['auth', 'csrf'], $commandsMap->getMiddleware('EditClient'));
        $this->assertEquals(['auth', 'csrf'], $commandsMap->getMiddleware('SaveClient'));
        $this->assertEquals(['auth', 'csrf', 'plus'], $commandsMap->getMiddleware('SaveSpecialClient'));
    }

    /** @test */
    public function it_leaves_a_group_open_and_closes_a_group()
    {
        $builder = $this->getBuilder();

        $builder
            ->openGroup('web')
                ->command('EditClient')
                ->command('SaveClient')
            ->addGroup('plus')
                ->command('SaveSpecialClient')
            ->closeGroup('plus')
                ->command('ListClient');

        $builder
            ->setGroupMiddleware('web', ['auth', 'csrf'])
            ->setGroupMiddleware('plus', ['plus']);

        $commandsMap = $builder->getCommandsMap();

        $this->assertEquals(['auth', 'csrf'], $commandsMap->getMiddleware('EditClient'));
        $this->assertEquals(['auth', 'csrf'], $commandsMap->getMiddleware('SaveClient'));
        $this->assertEquals(['auth', 'csrf', 'plus'], $commandsMap->getMiddleware('SaveSpecialClient'));
        $this->assertEquals(['auth', 'csrf'], $commandsMap->getMiddleware('ListClient'));
    }

    /** @test */
    public function it_gives_precedence_to_group_middleware()
    {
        $builder = $this->getBuilder();

        $builder
            ->openGroup('web')
                ->command('Command1')
                ->command('Command2')
                    ->middleware('plus');

        $builder
            ->setGroupMiddleware('web', ['auth', 'csrf']);

        $commandsMap = $builder->getCommandsMap();

        $this->assertEquals(['auth', 'csrf', 'plus'], $commandsMap->getMiddleware('Command2'));
    }
}