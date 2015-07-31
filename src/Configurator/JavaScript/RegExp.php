<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

class RegExp
{
	/**
	* @var string This regexp's flags
	*/
	public $flags;

	/**
	* @var array Capturing subpatterns' names
	*/
	public $map = array('');

	/**
	* @var string Regexp
	*/
	public $regexp;

	/**
	* Constructor
	*
	* @param  string $regexp Regexp (with no delimiters)
	* @param  string $flags  Regexp's flags
	* @return void
	*/
	public function __construct($regexp, $flags = '')
	{
		$this->regexp = $regexp;
		$this->flags  = $flags;
	}

	/**
	* Return this regexp as a JavaScript regexp literal
	*
	* @return string
	*/
	public function __toString()
	{
		// We cannot return // as it would be interpreted as a comment. We need to put anything
		// between the slashes
		if ($this->regexp === '')
		{
			return '/(?:)/';
		}

		return '/' . $this->regexp . '/' . $this->flags;
	}
}