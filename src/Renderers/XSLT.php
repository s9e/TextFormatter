<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;

use s9e\TextFormatter\Renderer;
use XSLTProcessor;

class XSLT extends Renderer
{
	/**
	* @var XSLTProcessor The lazy-loaded XSLTProcessor instance used by this renderer
	*/
	protected $proc;

	/**
	* @var bool Whether parameters need to be reloaded
	*/
	protected $reloadParams = false;

	/**
	* @var string The stylesheet used by this renderer
	*/
	protected $stylesheet;

	/**
	* Constructor
	*
	* @param  string $stylesheet The stylesheet used to render intermediate representations
	*/
	public function __construct($stylesheet)
	{
		$this->stylesheet = $stylesheet;

		// Capture the parameters' values from the stylesheet
		preg_match_all('#<xsl:param name="([^"]+)"(?>/>|>([^<]+))#', $stylesheet, $matches);
		foreach ($matches[1] as $k => $paramName)
		{
			$this->params[$paramName] = htmlspecialchars_decode($matches[2][$k]);
		}
	}

	/**
	* Serializer
	*
	* @return string[] List of properties to serialize
	*/
	public function __sleep()
	{
		$props = get_object_vars($this);
		unset($props['proc']);

		if (empty($props['reloadParams']))
		{
			unset($props['reloadParams']);
		}

		return array_keys($props);
	}

	/**
	* Unserialize helper
	*
	* Will reload parameters if they were changed between generation and serialization
	*
	* @return void
	*/
	public function __wakeup()
	{
		if (!empty($this->reloadParams))
		{
			$this->setParameters($this->params);
			$this->reloadParams = false;
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function setParameter($paramName, $paramValue)
	{
		/**
		* @link https://bugs.php.net/64137
		*/
		if (strpos($paramValue, '"') !== false && strpos($paramValue, "'") !== false)
		{
			$paramValue = str_replace('"', "\xEF\xBC\x82", $paramValue);
		}
		else
		{
			$paramValue = (string) $paramValue;
		}

		if (!isset($this->params[$paramName]) || $this->params[$paramName] !== $paramValue)
		{
			$this->load();
			$this->proc->setParameter('', $paramName, $paramValue);
			$this->params[$paramName] = $paramValue;
			$this->reloadParams = true;
		}
	}

	/**
	* {@inheritdoc}
	*/
	protected function renderRichText($xml)
	{
		// Load the intermediate representation
		$dom = $this->loadXML($xml);

		// Load the stylesheet
		$this->load();

		// Perform the transformation and cast it as a string because it may return NULL if the
		// transformation didn't output anything
		$this->setLocale();
		$output = (string) $this->proc->transformToXml($dom);
		$this->restoreLocale();

		// XSLTProcessor does not correctly identify <embed> as a void element. We fix it by
		// removing </embed> end tags
		$output = str_replace('</embed>', '', $output);

		// Remove the \n that XSL adds at the end of the output, if applicable
		if (substr($output, -1) === "\n")
		{
			$output = substr($output, 0, -1);
		}

		// Force HTML attributes to use double quotes to be consistent with the PHP renderer
		if (strpos($output, "='") !== false)
		{
			$output = $this->normalizeAttributes($output);
		}

		return $output;
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
			$xsl = $this->loadXML($this->stylesheet);

			$this->proc = new XSLTProcessor;
			$this->proc->importStylesheet($xsl);
		}
	}

	/**
	* Normalize given attribute's value to use double quotes
	*
	* @param  string[] $m
	* @return string
	*/
	protected function normalizeAttribute(array $m)
	{
		if ($m[0][0] === '"')
		{
			return $m[0];
		}

		return '"' . str_replace('"', '&quot;', substr($m[0], 1, -1)) . '"';
	}

	/**
	* Normalize all attributes in given HTML to use double quotes
	*
	* @param  string $html
	* @return string
	*/
	protected function normalizeAttributes($html)
	{
		return preg_replace_callback('(<\\S++ [^>]++>)', [$this, 'normalizeElement'], $html);
	}

	/**
	* Normalize attributes in given element to use double quotes
	*
	* @param  string[] $m
	* @return string
	*/
	protected function normalizeElement(array $m)
	{
		if (strpos($m[0], "='") === false)
		{
			return $m[0];
		}

		return preg_replace_callback('((?:"[^"]*"|\'[^\']*\'))S', [$this, 'normalizeAttribute'], $m[0]);
	}
}