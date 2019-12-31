<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

/**
* NOTE: when this check is enabled, DisallowObjectParamsWithGeneratedName should be enabled too.
*       Otherwise, <param/> elements with a dynamic 'name' attribute could be used to bypass this
*       restriction. For the same reason, DisallowCopy, DisallowDisableOutputEscaping,
*       DisallowDynamicAttributeNames, DisallowDynamicElementNames and DisallowUnsafeCopyOf should
*       all be enabled too
*/
abstract class AbstractFlashRestriction extends TemplateCheck
{
	/**
	* @var string Name of the default setting
	*/
	public $defaultSetting;

	/**
	* @var string Name of the highest setting allowed
	*/
	public $maxSetting;

	/**
	* @var bool Whether this restriction applies only to elements using any kind of dynamic markup:
	*           XSL elements or attribute value templates
	*/
	public $onlyIfDynamic;

	/**
	* @var string Name of the restricted setting
	*/
	protected $settingName;

	/**
	* @var array Valid settings
	*/
	protected $settings;

	/**
	* @var DOMElement <xsl:template/> node
	*/
	protected $template;

	/**
	* Constructor
	*
	* @param  string $maxSetting    Max setting allowed
	* @param  bool   $onlyIfDynamic Whether this restriction applies only to elements using any kind
	*                               of dynamic markup: XSL elements or attribute value templates
	*/
	public function __construct($maxSetting, $onlyIfDynamic = false)
	{
		$this->maxSetting    = $maxSetting;
		$this->onlyIfDynamic = $onlyIfDynamic;
	}

	/**
	* Test for the set Flash restriction
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$this->template = $template;
		$this->checkEmbeds();
		$this->checkObjects();
	}

	/**
	* Test given element's attributes
	*
	* @param  DOMElement $embed Context element
	* @return void
	*/
	protected function checkAttributes(DOMElement $embed)
	{
		$settingName = strtolower($this->settingName);
		$useDefault  = true;
		foreach ($embed->attributes as $attribute)
		{
			$attrName = strtolower($attribute->name);
			if ($attrName === $settingName)
			{
				$this->checkSetting($attribute, $attribute->value);
				$useDefault = false;
			}
		}
		if ($useDefault)
		{
			$this->checkSetting($embed, $this->defaultSetting);
		}
	}

	/**
	* Test whether given element has dynamic attributes that match the setting's name
	*
	* @param  DOMElement $embed Context element
	* @return void
	*/
	protected function checkDynamicAttributes(DOMElement $embed)
	{
		$settingName = strtolower($this->settingName);
		foreach ($embed->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute') as $attribute)
		{
			$attrName = strtolower($attribute->getAttribute('name'));
			if ($attrName === $settingName)
			{
				throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
			}
		}
	}

	/**
	* Test the presence of dynamic params in given object
	*
	* @param  DOMElement $object Context element
	* @return void
	*/
	protected function checkDynamicParams(DOMElement $object)
	{
		foreach ($this->getObjectParams($object) as $param)
		{
			foreach ($param->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute') as $attribute)
			{
				// Test for a dynamic "value" attribute
				if (strtolower($attribute->getAttribute('name')) === 'value')
				{
					throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
				}
			}
		}
	}

	/**
	* Check embed elements in given template
	*
	* @return void
	*/
	protected function checkEmbeds()
	{
		foreach ($this->getElements('embed') as $embed)
		{
			// Test <xsl:attribute/> descendants
			$this->checkDynamicAttributes($embed);

			// Test the element's attributes
			$this->checkAttributes($embed);
		}
	}

	/**
	* Check object elements in given template
	*
	* @return void
	*/
	protected function checkObjects()
	{
		foreach ($this->getElements('object') as $object)
		{
			// Make sure this object doesn't have dynamic params
			$this->checkDynamicParams($object);

			// Test the element's <param/> descendants
			$params = $this->getObjectParams($object);
			foreach ($params as $param)
			{
				$this->checkSetting($param, $param->getAttribute('value'));
			}
			if (empty($params))
			{
				$this->checkSetting($object, $this->defaultSetting);
			}
		}
	}

	/**
	* Test whether given setting is allowed
	*
	* @param  DOMNode $node    Target node
	* @param  string  $setting Setting
	* @return void
	*/
	protected function checkSetting(DOMNode $node, $setting)
	{
		if (!isset($this->settings[strtolower($setting)]))
		{
			// Test whether the value contains an odd number of {
			if (preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $setting))
			{
				throw new UnsafeTemplateException('Cannot assess ' . $this->settingName . " setting '" . $setting . "'", $node);
			}

			throw new UnsafeTemplateException('Unknown ' . $this->settingName . " value '" . $setting . "'", $node);
		}

		$value    = $this->settings[strtolower($setting)];
		$maxValue = $this->settings[strtolower($this->maxSetting)];

		if ($value > $maxValue)
		{
			throw new UnsafeTemplateException($this->settingName . " setting '" . $setting . "' exceeds restricted value '" . $this->maxSetting . "'", $node);
		}
	}

	/**
	* Test whether given node contains dynamic content (XSL elements or attribute value template)
	*
	* @param  DOMElement $node Node
	* @return bool
	*/
	protected function isDynamic(DOMElement $node)
	{
		if ($node->getElementsByTagNameNS(self::XMLNS_XSL, '*')->length)
		{
			return true;
		}

		// Look for any attributes containing "{" in this element or its descendants
		$xpath = new DOMXPath($node->ownerDocument);
		$query = './/@*[contains(., "{")]';

		foreach ($xpath->query($query, $node) as $attribute)
		{
			// Test whether the value contains an odd number of {
			if (preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $attribute->value))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Get all elements the restriction applies to
	*
	* @param  string       $tagName Element's name
	* @return DOMElement[]
	*/
	protected function getElements($tagName)
	{
		$nodes = [];
		foreach ($this->template->ownerDocument->getElementsByTagName($tagName) as $node)
		{
			if (!$this->onlyIfDynamic || $this->isDynamic($node))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Get all param elements attached to given object
	*
	* @param  DOMElement   $object Context element
	* @return DOMElement[]
	*/
	protected function getObjectParams(DOMElement $object)
	{
		$params      = [];
		$settingName = strtolower($this->settingName);
		foreach ($object->getElementsByTagName('param') as $param)
		{
			$paramName = strtolower($param->getAttribute('name'));
			if ($paramName === $settingName && $param->parentNode->isSameNode($object))
			{
				$params[] = $param;
			}
		}

		return $params;
	}
}