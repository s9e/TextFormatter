<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
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
	protected $inspector;
	protected $isNormalized = \false;
	protected $template;
	public function __construct($template)
	{
		$this->template = $template;
	}
	public function __call($methodName, $args)
	{
		return \call_user_func_array(array($this->getInspector(), $methodName), $args);
	}
	public function __toString()
	{
		return $this->template;
	}
	public function asDOM()
	{
		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $this->__toString()
		     . '</xsl:template>';
		$dom = new TemplateDocument($this);
		$dom->loadXML($xml);
		return $dom;
	}
	public function getCSSNodes()
	{
		return NodeLocator::getCSSNodes($this->asDOM());
	}
	public function getInspector()
	{
		if (!isset($this->inspector))
			$this->inspector = new TemplateInspector($this->__toString());
		return $this->inspector;
	}
	public function getJSNodes()
	{
		return NodeLocator::getJSNodes($this->asDOM());
	}
	public function getURLNodes()
	{
		return NodeLocator::getURLNodes($this->asDOM());
	}
	public function getParameters()
	{
		return TemplateHelper::getParametersFromXSL($this->__toString());
	}
	public function isNormalized($bool = \null)
	{
		if (isset($bool))
			$this->isNormalized = $bool;
		return $this->isNormalized;
	}
	public function normalize(TemplateNormalizer $templateNormalizer)
	{
		$this->inspector    = \null;
		$this->template     = $templateNormalizer->normalizeTemplate($this->template);
		$this->isNormalized = \true;
	}
	public function replaceTokens($regexp, $fn)
	{
		$this->inspector    = \null;
		$this->template     = TemplateModifier::replaceTokens($this->template, $regexp, $fn);
		$this->isNormalized = \false;
	}
	public function setContent($template)
	{
		$this->inspector    = \null;
		$this->template     = (string) $template;
		$this->isNormalized = \false;
	}
}