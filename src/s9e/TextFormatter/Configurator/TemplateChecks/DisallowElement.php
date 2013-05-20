<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMNode;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowElement extends TemplateCheck
{
	/**
	* @var string Local name of the disallowed element
	*/
	public $elName;

	/**
	* Constructor
	*
	* @param  string $elName Local name of the disallowed element
	* @return void
	*/
	public function __construct($elName)
	{
		// NOTE: TemplateHelper::normalize() forces elements' names to be lowercase
		$this->elName = strtolower($elName);
	}

	/**
	* Test for the presence of an element of given name
	*
	* @param  DOMNode $template <xsl:template/> node
	* @param  Tag     $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMNode $template, Tag $tag)
	{
		$node = $template->getElementsByTagName($this->elName)->item(0);

		if ($node)
		{
			throw new UnsafeTemplateException("Element '" . $node->nodeName . "' is disallowed", $node);
		}
	}
}