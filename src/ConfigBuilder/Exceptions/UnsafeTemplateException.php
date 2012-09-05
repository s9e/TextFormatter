<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Exceptions;

use DOMNode;
use RuntimeException;

class UnsafeTemplateException extends RuntimeException
{
	/**
	* @var DOMNode The node that is responsible for this exception
	*/
	protected $node;

	/**
	* @param string  $msg  Exception message
	* @param DOMNode $node The node that is responsible for this exception
	*/
	public function __construct($msg, DOMNode $node)
	{
		parent::__construct($msg);
		$this->node = $node;
	}

	/**
	* @return DOMNode
	*/
	public function getNode()
	{
		return $this->node;
	}
}