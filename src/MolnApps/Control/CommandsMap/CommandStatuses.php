<?php

namespace MolnApps\Control\CommandsMap;

class CommandStatuses {
	
	private $statusStrings;
	
	public function __construct(array $statusStrings)
	{
		$this->statusStrings = $statusStrings;
	}

	public static function create($commandStatuses)
	{
		if (is_array($commandStatuses)) {
			return new static($commandStatuses);
		}

		if ($commandStatuses instanceof static) {
			return $commandStatuses;
		}

		throw new \Exception('Command statuses must be either an array or a CommaStatuses object!');
	}
	
	public function getStatus($string)
	{
		if ( ! isset($this->statusStrings[$string])) {
			throw new \Exception("Undefined status [{$string}]");
		}

		return $this->statusStrings[$string];
	}

	public function getCommandStatuses()
	{
		return $this->statusStrings;
	}
}