<?php

namespace MolnApps\Control\CommandsMap;

class CommandStatusesTest extends \PHPUnit_Framework_TestCase
{
	/** @test */
	public function it_can_be_instantiated()
	{
		$commandsMap = new CommandStatuses([
			'CMD_DEFAULT' => 0, 
			'CMD_OK' => 1, 
			'CMD_ERROR' => -1
		]);

		$this->assertInstanceOf(CommandStatuses::class, $commandsMap);
	}

	/** @test */
	public function it_can_be_created_through_factory_method()
	{
		$commandsMap = CommandStatuses::create([
			'CMD_DEFAULT' => 0, 
			'CMD_OK' => 1, 
			'CMD_ERROR' => -1
		]);

		$this->assertInstanceOf(CommandStatuses::class, $commandsMap);
	}

	/** @test */
	public function it_can_be_enforced_through_a_factory_method()
	{
		$commandsMap = new CommandStatuses([
			'CMD_DEFAULT' => 0, 
			'CMD_OK' => 1, 
			'CMD_ERROR' => -1
		]);

		$newCommandsMap = CommandStatuses::create($commandsMap);

		$this->assertInstanceOf(CommandStatuses::class, $newCommandsMap);
		$this->assertEquals($commandsMap, $newCommandsMap);
	}

	/** @test */
	public function it_throws_if_cannot_be_created()
	{
		$this->setExpectedException(
			'\Exception', 'Command statuses must be either an array or a CommaStatuses object'
		);

		$commandsMap = CommandStatuses::create('foobar');
	}

	/** @test */
	public function it_returns_a_command_status_string_value()
	{
		$commandsMap = CommandStatuses::create([
			'CMD_DEFAULT' => 0, 
			'CMD_OK' => 1, 
			'CMD_ERROR' => -1
		]);

		$this->assertEquals(-1, $commandsMap->getStatus('CMD_ERROR'));
		$this->assertEquals(0, $commandsMap->getStatus('CMD_DEFAULT'));
		$this->assertEquals(1, $commandsMap->getStatus('CMD_OK'));
	}

	/** @test */
	public function it_throws_if_cannot_determine_a_command_status_string_value()
	{
		$commandsMap = CommandStatuses::create([
			'CMD_DEFAULT' => 0, 
			'CMD_OK' => 1, 
			'CMD_ERROR' => -1
		]);

		$this->setExpectedException('\Exception', 'Undefined status [CMD_INVALID_REQUEST]');

		$commandsMap->getStatus('CMD_INVALID_REQUEST');
	}

	/** @test */
	public function it_returns_an_array_of_command_statuses()
	{
		$commandsMap = CommandStatuses::create([
			'CMD_DEFAULT' => 0, 
			'CMD_OK' => 1, 
			'CMD_ERROR' => -1
		]);

		$this->assertEquals([
			'CMD_DEFAULT' => 0, 
			'CMD_OK' => 1, 
			'CMD_ERROR' => -1
		], $commandsMap->getCommandStatuses());
	}
}