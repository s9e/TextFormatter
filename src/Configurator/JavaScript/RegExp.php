<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
class RegExp
{
	public $flags;
	public $map = [''];
	public $regexp;
	public function __construct($regexp, $flags = '')
	{
		$this->regexp = $regexp;
		$this->flags  = $flags;
	}
	public function __toString()
	{
		if ($this->regexp === '')
			return '/(?:)/';
		return '/' . $this->regexp . '/' . $this->flags;
	}
}