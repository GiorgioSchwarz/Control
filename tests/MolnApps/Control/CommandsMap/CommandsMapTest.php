<?php

namespace MolnApps\Control\CommandsMap;

class CommandsMapTest extends \PHPUnit_Framework_TestCase
{
	/** @test */
	public function it_can_be_instantiated()
	{
		$commandsMap = new CommandsMap;
		$this->assertNotNull($commandsMap);
	}

	/** @test */
	public function it_sets_a_command()
	{
		$commandsMap = new CommandsMap;
		
		$this->assertCount(0, $commandsMap->getCommands());

		$commandsMap->addCommand('SaveNews');
		
		$this->assertCount(1, $commandsMap->getCommands());
	}

	/** @test */
	public function it_asserts_that_a_command_is_not_set()
	{
		$commandsMap = new CommandsMap;
		
		$result = $commandsMap->verifyCommand('SaveNews');

		$this->assertFalse($result);
	}

	/** @test */
	public function it_asserts_that_a_command_is_set()
	{
		$commandsMap = new CommandsMap;
		
		$commandsMap->addCommand('SaveNews');
		
		$result = $commandsMap->verifyCommand('SaveNews');

		$this->assertTrue($result);
	}

	/** @test */
	public function it_adds_an_alias_form_a_command()
	{
		$commandsMap = new CommandsMap;
		
		$commandsMap->addCommand('EditArticle');
		
		$commandsMap->addClassRoot('NewArticle', 'EditArticle');

		$result = $commandsMap->getClassroot('NewArticle');

		$this->assertEquals('EditArticle', $result);
	}

	/** @test */
	public function it_sets_a_view_for_a_command_status()
	{
		$commandsMap = new CommandsMap;

		$commandsMap->setView('SaveArticle', 1, 'success');

		$result = $commandsMap->getViews('SaveArticle', 1);

		$this->assertEquals(['success'], $result);
	}

	/** @test */
	public function it_sets_multiple_views_for_a_command_status()
	{
		$commandsMap = new CommandsMap;

		$commandsMap->setView('SaveArticle', 1, 'success');
		$commandsMap->addView('SaveArticle', 1, 'list');

		$result = $commandsMap->getViews('SaveArticle', 1);

		$this->assertEquals(['success', 'list'], $result);
	}

	/** @test */
	public function it_override_previous_views_for_a_command_status()
	{
		$commandsMap = new CommandsMap;

		$commandsMap->setView('SaveArticle', 1, 'success');
		$commandsMap->addView('SaveArticle', 1, 'list');

		$commandsMap->setView('SaveArticle', 1, 'dashboard');

		$result = $commandsMap->getViews('SaveArticle', 1);

		$this->assertEquals(['dashboard'], $result);
	}

	/** @test */
	public function it_sets_a_forward_for_a_command_status()
	{
		$commandsMap = new CommandsMap;

		$commandsMap->setForward('SaveArticle', 1, 'UploadAttachments');

		$result = $commandsMap->getForward('SaveArticle', 1);

		$this->assertEquals('UploadAttachments', $result);
	}

	/** @test */
	public function it_sets_just_one_forward_for_a_command_status()
	{
		$commandsMap = new CommandsMap;

		$commandsMap->setForward('SaveArticle', 1, 'UploadAttachments');
		$commandsMap->addForward('SaveArticle', 1, 'SendNotificationEmail');

		$result = $commandsMap->getForward('SaveArticle', 1);

		$this->assertEquals('SendNotificationEmail', $result);
	}

	/** @test */
	public function it_adds_multiple_middlewares()
	{
		$commandsMap = new CommandsMap;

		$commandsMap->addMiddleware('SaveArticle', 'auth');
		$commandsMap->addMiddleware('SaveArticle', 'csrf');
		$commandsMap->addMiddleware('SaveArticle', 'admin');
		
		$result = $commandsMap->getMiddleware('SaveArticle');

		$this->assertEquals(['auth', 'csrf', 'admin'], $result);
	}

	/** @test */
	public function it_resolves_a_command_without_alias()
	{
		$commandsMap = new CommandsMap;

		$commandsMap->addCommand('SaveArticle');

		$result = $commandsMap->resolveCommand('SaveArticle');

		$this->assertEquals('SaveArticle', $result);
	}

	/** @test */
	public function it_resolves_a_command_with_alias()
	{
		$commandsMap = new CommandsMap;

		$commandsMap->addCommand('EditArticle');
		$commandsMap->addClassRoot('NewArticle', 'EditArticle');

		$result = $commandsMap->resolveCommand('NewArticle');

		$this->assertEquals('EditArticle', $result);
	}

	/** @test */
	public function it_throws_if_could_not_resolve_a_command()
	{
		$commandsMap = new CommandsMap;

		$this->setExpectedException('\Exception', 'Command SaveArticle is not defined');

		$result = $commandsMap->resolveCommand('SaveArticle');
	}

	/** @test */
	public function it_throws_if_could_not_resolve_a_command_with_undefined_alias()
	{
		$commandsMap = new CommandsMap;

		$commandsMap->addClassRoot('NewArticle', 'EditArticle');

		$this->setExpectedException('\Exception', 'Command EditArticle is not defined');

		$result = $commandsMap->resolveCommand('NewArticle');
	}
}