<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

/*
* NOTE: when this check is enabled, DisallowObjectParamsWithGeneratedName should be enabled too.
*       Otherwise, <param/> elements with a dynamic 'name' attribute could be used to bypass this
*       restriction. For the same reason, DisallowCopy, DisallowDisableOutputEscaping,
*       DisallowDynamicAttributeNames, DisallowDynamicElementNames and DisallowUnsafeCopyOf should
*       all be enabled too
*/
abstract class AbstractFlashRestriction extends TemplateCheck
{
	/*
	* @var string Name of the default setting
	*/
	public $defaultSetting;

	/*
	* @var string Name of the highest setting allowed
	*/
	public $maxSetting;

	/*
	* @var bool Whether this restriction applies only to elements using any kind of dynamic markup:
	*           XSL elements or attribute value templates
	*/
	public $onlyIfDynamic;

	/*
	* @var string Name of the restricted setting
	*/
	protected $settingName;

	/*
	* @var array Valid settings
	*/
	protected $settings;

	/*
	* Constructor
	*
	* @param  string $maxSetting    Max setting allowed
	* @param  bool   $onlyIfDynamic Whether this restriction applies only to elements using any kind
	*                               of dynamic markup: XSL elements or attribute value templates
	* @return void
	*/
	public function __construct($maxSetting, $onlyIfDynamic = \false)
	{
		$this->maxSetting    = $maxSetting;
		$this->onlyIfDynamic = $onlyIfDynamic;
	}

	/*
	* Test for the set Flash restriction
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$dom         = $template->ownerDocument;
		$settingName = \strtolower($this->settingName);

		// Test <embed/> elements
		foreach ($dom->getElementsByTagName('embed') as $embed)
		{
			if ($this->onlyIfDynamic && !$this->isDynamic($embed))
				continue;

			$useDefault  = \true;

			// Test the element's attributes
			foreach ($embed->attributes as $attribute)
			{
				$attrName = \strtolower($attribute->name);

				if ($attrName === $settingName)
				{
					$this->checkSetting($attribute, $attribute->value);
					$useDefault = \false;
				}
			}

			if ($useDefault)
				$this->checkSetting($embed, $this->defaultSetting);

			// Test <xsl:attribute/> descendants
			$nodes = $embed->getElementsByTagNameNS(
				'http://www.w3.org/1999/XSL/Transform',
				'attribute'
			);

			foreach ($nodes as $attribute)
			{
				$attrName = \strtolower($attribute->getAttribute('name'));

				if ($attrName === $settingName)
					throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
			}
		}

		// Test <object/> elements
		foreach ($template->getElementsByTagName('object') as $object)
		{
			if ($this->onlyIfDynamic && !$this->isDynamic($object))
				continue;

			$useDefault = \true;

			// Test the element's <param/> descendants
			foreach ($template->getElementsByTagName('param') as $param)
			{
				$paramName = \strtolower($param->getAttribute('name'));

				if ($paramName === $settingName)
				{
					$this->checkSetting($param, $param->getAttribute('value'));

					// Test for a dynamic "value" attribute
					$nodes = $param->getElementsByTagNameNS(
						'http://www.w3.org/1999/XSL/Transform',
						'attribute'
					);
					foreach ($nodes as $attribute)
						if (\strtolower($attribute->getAttribute('name')) === 'value')
							throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);

					// Test whether this <param/> is a child of this object. If it's not, it might
					// actually apply to another <object/> descendant used as fallback, or perhaps
					// it's in an <xsl:if/> condition
					if ($param->parentNode->isSameNode($object))
						$useDefault = \false;
				}
			}

			if ($useDefault)
				if (!$this->onlyIfDynamic || $this->isDynamic($object))
					$this->checkSetting($object, $this->defaultSetting);
		}
	}

	/*
	* Test whether given setting is allowed
	*
	* @param  DOMNode $node    Target node
	* @param  string  $setting Setting
	* @return void
	*/
	protected function checkSetting(DOMNode $node, $setting)
	{
		if (!isset($this->settings[\strtolower($setting)]))
		{
			// Test whether the value contains an odd number of {
			if (\preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $setting))
				throw new UnsafeTemplateException('Cannot assess ' . $this->settingName . " setting '" . $setting . "'", $node);

			throw new UnsafeTemplateException('Unknown ' . $this->settingName . " value '" . $setting . "'", $node);
		}

		$value    = $this->settings[\strtolower($setting)];
		$maxValue = $this->settings[\strtolower($this->maxSetting)];

		if ($value > $maxValue)
			throw new UnsafeTemplateException($this->settingName . " setting '" . $setting . "' exceeds restricted value '" . $this->maxSetting . "'", $node);
	}

	/*
	* Test whether given node contains dynamic content (XSL elements or attribute value template)
	*
	* @param  DOMElement $node Node
	* @return bool
	*/
	protected function isDynamic(DOMElement $node)
	{
		if ($node->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', '*')->length)
			return \true;

		// Look for any attributes containing "{" in this element or its descendants
		$xpath = new DOMXPath($node->ownerDocument);
		$query = './/@*[contains(., "{")]';

		foreach ($xpath->query($query, $node) as $attribute)
			// Test whether the value contains an odd number of {
			if (\preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $attribute->value))
				return \true;

		return \false;
	}
}