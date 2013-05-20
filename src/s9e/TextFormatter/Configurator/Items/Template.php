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

class Template
{
	/**
	* @var callback
	*/
	protected $callback;

	/**
	* @var callback
	*/
	protected $template;

	/**
	* Constructor
	*
	* @param  callback|string $arg Either a template or a callback that returns the template
	* @return void
	*/
	public function __construct($arg)
	{
		if (is_string($arg))
		{
			$this->template = TemplateHelper::normalize($arg);
		}
		elseif (is_callable($arg))
		{
			$this->callback = $arg;
		}
		else
		{
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a string or a valid callback');
		}
	}

	/**
	* Return this template in string form, executing the stored callback if applicable
	*
	* @return string
	*/
	public function __toString()
	{
		return (isset($this->callback))
		     ? TemplateHelper::normalize(call_user_func($this->callback))
		     : $this->template;
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
}