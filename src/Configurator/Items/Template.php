<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\TemplateNormalizer;

class Template
{
	protected $forensics;

	protected $isNormalized = \false;

	protected $template;

	public function __construct($template)
	{
		$this->template = $template;
	}

	public function __call($methodName, $args)
	{
		return \call_user_func_array([$this->getForensics(), $methodName], $args);
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

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		return $dom;
	}

	public function getCSSNodes()
	{
		return TemplateHelper::getCSSNodes($this->asDOM());
	}

	public function getForensics()
	{
		if (!isset($this->forensics))
			$this->forensics = new TemplateForensics($this->__toString());

		return $this->forensics;
	}

	public function getJSNodes()
	{
		return TemplateHelper::getJSNodes($this->asDOM());
	}

	public function getURLNodes()
	{
		return TemplateHelper::getURLNodes($this->asDOM());
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
		$this->forensics    = \null;
		$this->template     = $templateNormalizer->normalizeTemplate($this->template);
		$this->isNormalized = \true;
	}

	public function replaceTokens($regexp, $fn)
	{
		$this->forensics    = \null;
		$this->template     = TemplateHelper::replaceTokens($this->template, $regexp, $fn);
		$this->isNormalized = \false;
	}
}