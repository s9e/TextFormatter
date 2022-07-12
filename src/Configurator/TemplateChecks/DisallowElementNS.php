<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowElementNS extends TemplateCheck
{
	/**
	* @var string Local name of the disallowed element
	*/
	public $elName;

	/**
	* @var string Namespace URI of the disallowed element
	*/
	public $namespaceURI;

	/**
	* Constructor
	*
	* @param  string $namespaceURI Namespace URI of the disallowed element
	* @param  string $elName       Local name of the disallowed element
	*/
	public function __construct($namespaceURI, $elName)
	{
		$this->namespaceURI  = $namespaceURI;
		$this->elName        = $elName;
	}

	/**
	* Test for the presence of an element of given name in given namespace
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$node = $template->getElementsByTagNameNS($this->namespaceURI, $this->elName)->item(0);

		if ($node)
		{
			throw new UnsafeTemplateException("Element '" . $node->nodeName . "' is disallowed", $node);
		}
	}
}