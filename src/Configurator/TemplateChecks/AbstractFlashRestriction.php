<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
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

	protected function checkEmbeds()
	{
		$settingName = \strtolower($this->settingName);
		foreach ($this->getElements('embed') as $embed)
		{
			$nodes = $embed->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute');
			foreach ($nodes as $attribute)
			{
				$attrName = \strtolower($attribute->getAttribute('name'));
				if ($attrName === $settingName)
					throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
			}

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
	}

	protected function checkObjects()
	{
		$settingName = \strtolower($this->settingName);
		foreach ($this->getElements('object') as $object)
		{
			if ($this->onlyIfDynamic && !$this->isDynamic($object))
				continue;

			$useDefault = \true;
			foreach ($object->getElementsByTagName('param') as $param)
			{
				$paramName = \strtolower($param->getAttribute('name'));
				if ($paramName === $settingName)
				{
					$this->checkSetting($param, $param->getAttribute('value'));

					$nodes = $param->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute');
					foreach ($nodes as $attribute)
						if (\strtolower($attribute->getAttribute('name')) === 'value')
							throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);

					if ($param->parentNode->isSameNode($object))
						$useDefault = \false;
				}
			}
			if ($useDefault)
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
}