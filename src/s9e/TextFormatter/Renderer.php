<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;
use Serializable;
use XSLTProcessor;

class Renderer implements Serializable
{
	/**
	* @var bool Whether the stylesheet used by this renderer output HTML
	*/
	protected $htmlOutput;

	/**
	* @var XSLTProcessor The lazy-loaded XSLTProcessor instance used by this renderer
	*/
	protected $proc;

	/**
	* @var string The stylesheet used by this renderer
	*/
	protected $stylesheet;

	/**
	* Constructor
	*
	* @param  string $stylesheet The stylesheet used to render intermediate representations
	* @return void
	*/
	public function __construct($stylesheet)
	{
		$this->stylesheet = $stylesheet;
	}

	/**
	* Serializer
	*
	* @return string This renderer's stylesheet
	*/
	public function serialize()
	{
		return $this->stylesheet;
	}

	/**
	* Unserializer
	*
	* @param  string $data Serialized data
	* @return void
	*/
	public function unserialize($data)
	{
		$this->__construct($data);
	}

	/**
	* Set the value of a parameter from the stylesheet
	*
	* @param  string $paramName  Parameter name
	* @param  mixed  $paramValue Parameter's value
	* @return void
	*/
	public function setParameter($paramName, $paramValue)
	{
		$this->load();
		$this->proc->setParameter('', $paramName, $paramValue);
	}

	/**
	* Set the values of several parameters from the stylesheet
	*
	* @param  string $params Associative array of [parameter name => parameter value]
	* @return void
	*/
	public function setParameters(array $params)
	{
		foreach ($params as $paramName => $paramValue)
		{
			$this->setParameter($paramName, $paramValue);
		}
	}

	/**
	* Render an intermediate representation
	*
	* @param  string $xml Intermediate representation
	* @return string      Rendered result
	*/
	public function render($xml)
	{
		// Fast path for plain text
		if (substr($xml, 0, 4) === '<pt>')
		{
			return $this->renderPlainText($xml);
		}

		// Load the intermediate representation
		$dom  = new DOMDocument;
		$dom->loadXML($xml);

		// Load the stylesheet
		$this->load();

		// Perform the transformation and cast it as a string because it may return NULL if the
		// transformation didn't output anything
		$output = (string) $this->proc->transformToXml($dom);

		// Remove the \n that XSL adds at the end of the output, if applicable
		if (substr($output, -1) === "\n")
		{
			$output = substr($output, 0, -1);
		}

		return $output;
	}

	/**
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
		foreach ($arr as $k => &$xml)
		{
			if (substr($xml, 0, 4) === '<pt>')
			{
				$xml = $this->renderPlainText($xml);
			}
			else
			{
				// Collect the keys and content of intermediate representations of rich text
				$keys[]   = $k;
				$render[] = $xml;
			}
		}
		unset($xml);

		// Render the rich text representations, if any
		if (!empty($render))
		{
			$uid = sha1(uniqid(mt_rand(), true));
			$xml = '<m>' . implode($uid, $render) . '</m>';

			foreach (explode($uid, $this->render($xml)) as $i => $html)
			{
				// Replace the IR with its rendering
				$arr[$keys[$i]] = $html;
			}
		}

		return $arr;
	}

	/**
	* Create an XSLTProcessor and load the stylesheet
	*
	* @return void
	*/
	protected function load()
	{
		if (!isset($this->proc))
		{
			$xsl = new DOMDocument;
			$xsl->loadXML($this->stylesheet);

			$this->proc = new XSLTProcessor;
			$this->proc->importStylesheet($xsl);
		}
	}

	/**
	* Render an intermediate representation of plain text
	*
	* @param  string $xml Intermediate representation
	* @return string      Rendered result
	*/
	protected function renderPlainText($xml)
	{
		if (!isset($this->htmlOutput))
		{
			// Test whether we output HTML or XML
			$this->htmlOutput = (strpos($this->stylesheet, '<xsl:output method="html') !== false);
		}

		// Remove the <pt> and </pt> tags
		$html = substr($xml, 4, -5);

		// Replace all <br/> with <br> if we output HTML
		if ($this->htmlOutput)
		{
			$html = str_replace('<br/>', '<br>', $html);
		}

		return $html;
	}
}