<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Exceptions;

use DOMNode;
use RuntimeException;

class UnsafeTemplateException extends InvalidTemplateException
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
	* NOTE: this method may incorrectly highlight parts of the source that are identical to the
	*       node's XML representation
	*
	* @param  string $prepend HTML to prepend
	* @param  string $append  HTML to append
	* @return string          Template's source, as HTML
	*/
	public function highlightNode($prepend = '<span style="background-color:#ff0">', $append = '</span>')
	{
		$dom = $this->node->ownerDocument;
		$dom->formatOutput = true;

		$docXml = $dom->saveXML($dom->documentElement);
		$docXml = preg_replace('#^<(t\\w*)[^>]*>(.*)</\\1>$#s', '$2', $docXml);
		$docXml = trim(str_replace("\n  ", "\n", $docXml));

		$nodeHtml = htmlspecialchars(trim($dom->saveXML($this->node)));
		$docHtml  = htmlspecialchars($docXml);

		return str_replace($nodeHtml, $prepend . $nodeHtml . $append, $docHtml);
	}
}