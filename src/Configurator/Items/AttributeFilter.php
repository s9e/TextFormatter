<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;
class AttributeFilter extends Filter
{
	protected $markedSafe = array();
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
		$this->markedSafe = array();
		return $this;
	}
	public function __construct($callback)
	{
		parent::__construct($callback);
		$this->resetParameters();
		$this->addParameterByName('attrValue');
	}
	public function isSafeInJS()
	{
		$safeCallbacks = array(
			'urlencode',
			'strtotime',
			'rawurlencode'
		);
		if (\in_array($this->callback, $safeCallbacks, \true))
			return \true;
		return $this->isSafe('InJS');
	}
}