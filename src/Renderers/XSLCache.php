<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;

use XSLTCache;
use s9e\TextFormatter\Renderer;

/**
* This renderer uses the xslcache PECL extension
*
* @link http://pecl.php.net/package/xslcache
* @link http://michaelsanford.com/compiling-xslcache-0-7-1-for-php-5-4/
*/
class XSLCache extends Renderer
{
	/**
	* @var string Path to the stylesheet used by this renderer
	*/
	protected $filepath;

	/**
	* @var XSLTCache The lazy-loaded XSLCache instance used by this renderer
	*/
	protected $proc;

	/**
	* @var bool Whether parameters need to be reloaded
	*/
	protected $reloadParams = false;

	/**
	* Constructor
	*
	* @param  string $filepath Path to the stylesheet used by this renderer
	* @return void
	*/
	public function __construct($filepath)
	{
		$this->filepath = $filepath;

		// Load the stylesheet for inspection
		$stylesheet = file_get_contents($this->filepath);

		// Test whether we output HTML or XML
		$this->htmlOutput = (strpos($stylesheet, '<xsl:output method="html') !== false);

		// Capture the parameters' values from the stylesheet
		preg_match_all('#<xsl:param name="([^"]+)"(?>/>|>([^<]+))#', $stylesheet, $matches);
		foreach ($matches[1] as $k => $paramName)
		{
			$this->params[$paramName] = (isset($matches[2][$k]))
			                          ? htmlspecialchars_decode($matches[2][$k])
			                          : '';
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
	* Return the path to the stylesheet used by this renderer
	*
	* @return string
	*/
	public function getFilepath()
	{
		return $this->filepath;
	}

	/**
	* {@inheritdoc}
	*/
	public function setParameter($paramName, $paramValue)
	{
		/**
		* @link https://bugs.php.net/64137
		*/
		if (strpos($paramValue, '"') !== false
		 && strpos($paramValue, "'") !== false)
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
		$output = (string) $this->proc->transformToXml($dom);

		// XSLTProcessor does not correctly identify <embed> as a void element. We fix it by
		// removing </embed> end tags
		if ($this->htmlOutput)
		{
			$output = str_replace('</embed>', '', $output);
		}

		// Remove the \n that XSL adds at the end of the output, if applicable
		if (substr($output, -1) === "\n")
		{
			$output = substr($output, 0, -1);
		}

		return $output;
	}

	/**
	* Cache the XSLTCache instance used by this renderer if it does not exist
	*
	* @return void
	*/
	protected function load()
	{
		if (!isset($this->proc))
		{
			$this->proc = new XSLTCache;
			$this->proc->importStylesheet($this->filepath);
		}
	}
}