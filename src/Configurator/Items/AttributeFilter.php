<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;
class AttributeFilter extends Filter
{
	use TemplateSafeness;
	public function __construct($callback)
	{
		parent::__construct($callback);
		$this->resetParameters();
		$this->addParameterByName('attrValue');
	}
	public function isSafeInJS()
	{
		$safeCallbacks = [
			'urlencode',
			'strtotime',
			'rawurlencode'
		];
		if (\in_array($this->callback, $safeCallbacks, \true))
			return \true;
		return $this->isSafe('InJS');
	}
}