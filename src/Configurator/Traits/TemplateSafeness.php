<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;

trait TemplateSafeness
{
	protected $markedSafe = [];

	protected function isSafe($context)
	{
		return !empty($this->markedSafe[$context]);
	}

	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}

	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}

	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}

	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = \true;

		return $this;
	}

	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = \true;

		return $this;
	}

	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = \true;

		return $this;
	}

	public function resetSafeness()
	{
		$this->markedSafe = [];

		return $this;
	}
}