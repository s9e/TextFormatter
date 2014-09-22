<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;

abstract class Renderer
{
	/*
	* @var bool Whether the output must be HTML (otherwise it is assumed it is XHTML)
	*/
	protected $htmlOutput = \true;

	/*
	* @var string Regexp that matches meta elements to be removed
	*/
	public $metaElementsRegexp = '(<[eis]>[^<]*</[eis]>)';

	/*
	* @var array Associative array of [paramName => paramValue]
	*/
	protected $params = [];

	/*
	* Create a return a new DOMDocument loaded with given XML
	*
	* @param  string      $xml Source XML
	* @return DOMDocument
	*/
	protected function loadXML($xml)
	{
		// Activate small nodes allocation and relax LibXML's hardcoded limits if applicable. Limits
		// on tags can be set during configuration
		$flags = (\LIBXML_VERSION >= 20700) ? \LIBXML_COMPACT | \LIBXML_PARSEHUGE : 0;

		$dom = new DOMDocument;
		$dom->loadXML($xml, $flags);

		return $dom;
	}

	/*
	* Render an intermediate representation
	*
	* @param  string $xml Intermediate representation
	* @return string      Rendered result
	*/
	public function render($xml)
	{
		if (\substr($xml, 0, 3) === '<t>')
			return $this->renderPlainText($xml);
		else
			return $this->renderRichText(\preg_replace($this->metaElementsRegexp, '', $xml));
	}

	/*
	* Render an array of intermediate representations
	*
	* @param  array $arr Array of XML strings
	* @return array      Array of render results (same keys, same order)
	*/
	public function renderMulti(array $arr)
	{
		$keys   = [];
		$render = [];

		// First replace intermediate representations of plain text
		foreach ($arr as $k => $xml)
			if (\substr($xml, 0, 3) === '<t>')
				$arr[$k] = $this->renderPlainText($xml);
			else
			{
				// Collect the keys and content of intermediate representations of rich text
				$keys[]   = $k;
				$render[] = $xml;
			}

		// Render the rich text representations, if any
		if (!empty($render))
		{
			$uid = \sha1(\uniqid(\mt_rand(), \true));
			$xml = '<m>' . \implode($uid, $render) . '</m>';

			foreach (\explode($uid, $this->renderRichText($xml)) as $i => $html)
				// Replace the IR with its rendering
				$arr[$keys[$i]] = $html;
		}

		return $arr;
	}

	/*
	* Render an intermediate representation of plain text
	*
	* @param  string $xml Intermediate representation
	* @return string      Rendered result
	*/
	protected function renderPlainText($xml)
	{
		// Remove the <t> and </t> tags
		$html = \substr($xml, 3, -4);

		// Replace all <br/> with <br> if we output HTML
		if ($this->htmlOutput)
			$html = \str_replace('<br/>', '<br>', $html);

		return $html;
	}

	/*
	* Render an intermediate representation of rich text
	*
	* @param  string $xml Intermediate representation
	* @return string      Rendered result
	*/
	abstract protected function renderRichText($xml);

	/*
	* Get the value of a parameter
	*
	* @param  string $paramName
	* @return string
	*/
	public function getParameter($paramName)
	{
		return (isset($this->params[$paramName])) ? $this->params[$paramName] : '';
	}

	/*
	* Get the values of all parameters
	*
	* @return array Associative array of parameter names and values
	*/
	public function getParameters()
	{
		return $this->params;
	}

	/*
	* Set the value of a parameter from the stylesheet
	*
	* @param  string $paramName  Parameter name
	* @param  mixed  $paramValue Parameter's value
	* @return void
	*/
	public function setParameter($paramName, $paramValue)
	{
		$this->params[$paramName] = (string) $paramValue;
	}

	/*
	* Set the values of several parameters from the stylesheet
	*
	* @param  string $params Associative array of [parameter name => parameter value]
	* @return void
	*/
	public function setParameters(array $params)
	{
		foreach ($params as $paramName => $paramValue)
			$this->setParameter($paramName, $paramValue);
	}
}