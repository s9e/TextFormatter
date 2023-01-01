<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\NodeLocator;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;
use s9e\TextFormatter\Configurator\Helpers\TemplateModifier;
use s9e\TextFormatter\Configurator\TemplateNormalizer;

class Template
{
	/**
	* @var TemplateInspector Instance of TemplateInspector based on the content of this template
	*/
	protected $inspector;

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
	*/
	public function __construct($template)
	{
		$this->template = $template;
	}

	/**
	* Handle calls to undefined methods
	*
	* Forwards calls to this template's TemplateInspector instance
	*
	* @return mixed
	*/
	public function __call($methodName, $args)
	{
		return call_user_func_array([$this->getInspector(), $methodName], $args);
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

		$dom = new TemplateDocument($this);
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
		return NodeLocator::getCSSNodes($this->asDOM());
	}

	/**
	* Return an instance of TemplateInspector based on this template's content
	*
	* @return TemplateInspector
	*/
	public function getInspector()
	{
		if (!isset($this->inspector))
		{
			$this->inspector = new TemplateInspector($this->__toString());
		}

		return $this->inspector;
	}

	/**
	* Return all the nodes in this template whose content type is JavaScript
	*
	* @return array
	*/
	public function getJSNodes()
	{
		return NodeLocator::getJSNodes($this->asDOM());
	}

	/**
	* Return all the nodes in this template whose value is an URL
	*
	* @return array
	*/
	public function getURLNodes()
	{
		return NodeLocator::getURLNodes($this->asDOM());
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
		$this->inspector    = null;
		$this->template     = $templateNormalizer->normalizeTemplate($this->template);
		$this->isNormalized = true;
	}

	/**
	* Replace parts of this template that match given regexp
	*
	* @param  string   $regexp Regexp for matching parts that need replacement
	* @param  callable $fn     Callback used to get the replacement
	* @return void
	*/
	public function replaceTokens($regexp, $fn)
	{
		$this->inspector    = null;
		$this->template     = TemplateModifier::replaceTokens($this->template, $regexp, $fn);
		$this->isNormalized = false;
	}

	/**
	* Replace this template's content
	*
	* @param  string $template New content
	* @return void
	*/
	public function setContent($template)
	{
		$this->inspector    = null;
		$this->template     = (string) $template;
		$this->isNormalized = false;
	}
}