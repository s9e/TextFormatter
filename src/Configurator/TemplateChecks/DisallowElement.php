<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
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
	*/
	public function __construct($elName)
	{
		// NOTE: the default template normalization rules force elements' names to be lowercase
		$this->elName = strtolower($elName);
	}

	/**
	* Test for the presence of an element of given name
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query
			= '//*[translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "' . $this->elName . '"]'
			. '|'
			. '//xsl:element[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "' . $this->elName . '"]';

		$node = $xpath->query($query)->item(0);
		if ($node)
		{
			throw new UnsafeTemplateException("Element '" . $this->elName . "' is disallowed", $node);
		}
	}
}