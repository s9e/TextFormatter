<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use DOMDocument;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\TemplateNormalizer;

class Template
{
	/**
	* @var bool Whether this template has been normalized
	*/
	protected $isNormalized = false;

	/**
	* @var string This template's content
	*/
	protected $template;

	/**
	* Constructor
	*
	* @param  string $template This template's content
	* @return void
	*/
	public function __construct($template)
	{
		$this->template = $template;
	}

	/**
	* Return this template's content
	*
	* @return string
	*/
	public function __toString()
	{
		return $this->template;
	}

	/**
	* Return the content of this template as a DOMDocument
	*
	* NOTE: the content is wrapped in an <xsl:template/> node
	*
	* @return DOMDocument
	*/
	public function asDOM()
	{
		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $this->__toString()
		     . '</xsl:template>';

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		return $dom;
	}

	/**
	* Return all the nodes in this template whose content type is CSS
	*
	* @return array
	*/
	public function getCSSNodes()
	{
		return TemplateHelper::getCSSNodes($this->asDOM());
	}

	/**
	* Return all the nodes in this template whose content type is JavaScript
	*
	* @return array
	*/
	public function getJSNodes()
	{
		return TemplateHelper::getJSNodes($this->asDOM());
	}

	/**
	* Return all the nodes in this template whose value is an URL
	*
	* @return array
	*/
	public function getURLNodes()
	{
		return TemplateHelper::getURLNodes($this->asDOM());
	}

	/**
	* Return a list of parameters in use in this template
	*
	* @return array Alphabetically sorted list of unique parameter names
	*/
	public function getParameters()
	{
		return TemplateHelper::getParametersFromXSL($this->__toString());
	}

	/**
	* Set and/or return whether this template has been normalized
	*
	* @param  bool $bool If present, the new value for this template's isNormalized flag
	* @return bool       Whether this template has been normalized
	*/
	public function isNormalized($bool = null)
	{
		if (isset($bool))
		{
			$this->isNormalized = $bool;
		}

		return $this->isNormalized;
	}

	/**
	* Normalize this template's content
	*
	* @param  TemplateNormalizer $templateNormalizer
	* @return void
	*/
	public function normalize(TemplateNormalizer $templateNormalizer)
	{
		$this->template     = $templateNormalizer->normalizeTemplate($this->template);
		$this->isNormalized = true;
	}
}