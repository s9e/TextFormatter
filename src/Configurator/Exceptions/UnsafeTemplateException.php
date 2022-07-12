<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Exceptions;

use DOMNode;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;

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
	* Return the node that has caused this exception
	*
	* @return DOMNode
	*/
	public function getNode()
	{
		return $this->node;
	}

	/**
	* Highlight the source of the template that has caused this exception, with the node highlighted
	*
	* @param  string $prepend HTML to prepend
	* @param  string $append  HTML to append
	* @return string          Template's source, as HTML
	*/
	public function highlightNode($prepend = '<span style="background-color:#ff0">', $append = '</span>')
	{
		return TemplateHelper::highlightNode($this->node, $prepend, $append);
	}

	/**
	* Change the node associated with this exception
	*
	* @param  DOMNode $node
	* @return void
	*/
	public function setNode(DOMNode $node)
	{
		$this->node = $node;
	}
}