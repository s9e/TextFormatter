<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

class XmlFileDefinitionCollection extends SiteDefinitionCollection
{
	/**
	* @var array Known config types [<name regexp>, <value regexp>, <type>]
	*/
	protected $configTypes = [
		['(^defaultValue$)', '(^(?:0|[1-9][0-9]+)$)D', 'castToInt'],
		['(height$|width$)', '(^(?:0|[1-9][0-9]+)$)D', 'castToInt'],
		['(^required$)',     '(^(?:fals|tru)e$)Di',    'castToBool']
	];

	/**
	* Constructor
	*
	* @param  string $path Path to site definitions' dir
	*/
	public function __construct($path)
	{
		if (!file_exists($path) || !is_dir($path))
		{
			throw new InvalidArgumentException('Invalid site directory');
		}
		foreach (glob($path . '/*.xml') as $filepath)
		{
			$siteId = basename($filepath, '.xml');
			$this->add($siteId, $this->getConfigFromXmlFile($filepath));
		}
	}

	/**
	* Cast given config value to the appropriate type
	*
	* @param  string $name  Name of the config value
	* @param  string $value Config value in string form
	* @return mixed         Config value in appropriate type
	*/
	protected function castConfigValue($name, $value)
	{
		foreach ($this->configTypes as list($nameRegexp, $valueRegexp, $methodName))
		{
			if (preg_match($nameRegexp, $name) && preg_match($valueRegexp, $value))
			{
				return $this->$methodName($value);
			}
		}

		return $value;
	}

	/**
	* Cast given config value to a boolean
	*
	* @param  string $value
	* @return bool
	*/
	protected function castToBool($value)
	{
		return (strtolower($value) === 'true');
	}

	/**
	* Cast given config value to an integer
	*
	* @param  string  $value
	* @return integer
	*/
	protected function castToInt($value)
	{
		return (int) $value;
	}

	/**
	* Convert known config values to the appropriate type
	*
	* Will cast properties whose name is "defaultValue" or ends in "height" or "width" to integers
	*
	* @param  array $config Original config
	* @return array         Converted config
	*/
	protected function convertValueTypes(array $config)
	{
		foreach ($config as $k => $v)
		{
			if (is_array($v))
			{
				$config[$k] = $this->convertValueTypes($v);
			}
			elseif (is_string($v))
			{
				$config[$k] = $this->castConfigValue($k, $v);
			}
		}

		return $config;
	}

	/**
	* Replace arrays that contain a single element with the element itself
	*
	* @param  array $config
	* @return array
	*/
	protected function flattenConfig(array $config)
	{
		foreach ($config as $k => $v)
		{
			if (is_array($v) && count($v) === 1)
			{
				$config[$k] = end($v);
			}
		}

		return $config;
	}

	/**
	* Extract a site's config from its XML file
	*
	* @param  string $filepath Path to the XML file
	* @return mixed
	*/
	protected function getConfigFromXmlFile($filepath)
	{
		$dom = new DOMDocument;
		$dom->loadXML(file_get_contents($filepath), LIBXML_NOCDATA);

		return $this->getElementConfig($dom->documentElement);
	}

	/**
	* Extract a site's config from its XML representation
	*
	* @param  DOMElement $element Current node
	* @return mixed
	*/
	protected function getElementConfig(DOMElement $element)
	{
		$config = [];
		foreach ($element->attributes as $attribute)
		{
			$config[$attribute->name][] = $attribute->value;
		}
		foreach ($element->childNodes as $childNode)
		{
			if ($childNode instanceof DOMElement)
			{
				$config[$childNode->nodeName][] = $this->getValueFromElement($childNode);
			}
		}

		return $this->flattenConfig($this->convertValueTypes($config));
	}

	/**
	* Extract a value from given element
	*
	* @param  DOMElement $element
	* @return mixed
	*/
	protected function getValueFromElement(DOMElement $element)
	{
		return (!$element->attributes->length && $element->childNodes->length === 1 && $element->firstChild->nodeType === XML_TEXT_NODE)
		     ? $element->nodeValue
		     : $this->getElementConfig($element);
	}
}