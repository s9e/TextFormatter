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
	* @var XSLTProcessor
	*/
	protected $proc;

	/**
	* @var string
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

		$xsl = new DOMDocument;
		$xsl->loadXML($stylesheet);

		$this->proc = new XSLTProcessor;
		$this->proc->importStylesheet($xsl);
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
		$this->proc->setParameter('', $paramName, $paramValue);
	}

	/**
	* Set thes value of several parameters from the stylesheet
	*
	* @param  string $params Associative array of [parameter name => parameter value]
	* @return void
	*/
	public function setParameters(array $params)
	{
		$this->proc->setParameter('', $params);
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
			return substr($xml, 4, -5);
		}

		// Load the intermediate representation
		$dom  = new DOMDocument;
		$dom->loadXML($xml);

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
		$uid = sha1(uniqid(mt_rand(), true));
		$xml = '<m>' . implode($uid, $arr) . '</m>';

		return array_combine(
			array_keys($arr),
			explode($uid, $this->render($xml))
		);
	}
}