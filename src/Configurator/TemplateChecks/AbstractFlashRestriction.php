<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
abstract class AbstractFlashRestriction extends TemplateCheck
{
	public $defaultSetting;
	public $maxSetting;
	public $onlyIfDynamic;
	protected $settingName;
	protected $settings;
	protected $template;
	public function __construct($maxSetting, $onlyIfDynamic = \false)
	{
		$this->maxSetting    = $maxSetting;
		$this->onlyIfDynamic = $onlyIfDynamic;
	}
	public function check(DOMElement $template, Tag $tag)
	{
		$this->template = $template;
		$this->checkEmbeds();
		$this->checkObjects();
	}
	protected function checkAttributes(DOMElement $embed)
	{
		$settingName = \strtolower($this->settingName);
		$useDefault  = \true;
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
	}
	protected function checkDynamicAttributes(DOMElement $embed)
	{
		$settingName = \strtolower($this->settingName);
		foreach ($embed->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute') as $attribute)
		{
			$attrName = \strtolower($attribute->getAttribute('name'));
			if ($attrName === $settingName)
				throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
		}
	}
	protected function checkDynamicParams(DOMElement $object)
	{
		foreach ($this->getObjectParams($object) as $param)
			foreach ($param->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute') as $attribute)
				if (\strtolower($attribute->getAttribute('name')) === 'value')
					throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
	}
	protected function checkEmbeds()
	{
		foreach ($this->getElements('embed') as $embed)
		{
			$this->checkDynamicAttributes($embed);
			$this->checkAttributes($embed);
		}
	}
	protected function checkObjects()
	{
		foreach ($this->getElements('object') as $object)
		{
			$this->checkDynamicParams($object);
			$params = $this->getObjectParams($object);
			foreach ($params as $param)
				$this->checkSetting($param, $param->getAttribute('value'));
			if (empty($params))
				$this->checkSetting($object, $this->defaultSetting);
		}
	}
	protected function checkSetting(DOMNode $node, $setting)
	{
		if (!isset($this->settings[\strtolower($setting)]))
		{
			if (\preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $setting))
				throw new UnsafeTemplateException('Cannot assess ' . $this->settingName . " setting '" . $setting . "'", $node);
			throw new UnsafeTemplateException('Unknown ' . $this->settingName . " value '" . $setting . "'", $node);
		}
		$value    = $this->settings[\strtolower($setting)];
		$maxValue = $this->settings[\strtolower($this->maxSetting)];
		if ($value > $maxValue)
			throw new UnsafeTemplateException($this->settingName . " setting '" . $setting . "' exceeds restricted value '" . $this->maxSetting . "'", $node);
	}
	protected function isDynamic(DOMElement $node)
	{
		if ($node->getElementsByTagNameNS(self::XMLNS_XSL, '*')->length)
			return \true;
		$xpath = new DOMXPath($node->ownerDocument);
		$query = './/@*[contains(., "{")]';
		foreach ($xpath->query($query, $node) as $attribute)
			if (\preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $attribute->value))
				return \true;
		return \false;
	}
	protected function getElements($tagName)
	{
		$nodes = array();
		foreach ($this->template->ownerDocument->getElementsByTagName($tagName) as $node)
			if (!$this->onlyIfDynamic || $this->isDynamic($node))
				$nodes[] = $node;
		return $nodes;
	}
	protected function getObjectParams(DOMElement $object)
	{
		$params      = array();
		$settingName = \strtolower($this->settingName);
		foreach ($object->getElementsByTagName('param') as $param)
		{
			$paramName = \strtolower($param->getAttribute('name'));
			if ($paramName === $settingName && $param->parentNode->isSameNode($object))
				$params[] = $param;
		}
		return $params;
	}
}