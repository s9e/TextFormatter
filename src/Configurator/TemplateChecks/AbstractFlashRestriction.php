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

abstract class AbstractFlashRestriction extends TemplateCheck
{
	public $defaultSetting;

	public $maxSetting;

	public $onlyIfDynamic;

	protected $settingName;

	protected $settings;

	public function __construct($maxSetting, $onlyIfDynamic = \false)
	{
		$this->maxSetting    = $maxSetting;
		$this->onlyIfDynamic = $onlyIfDynamic;
	}

	public function check(DOMElement $template, Tag $tag)
	{
		$dom         = $template->ownerDocument;
		$settingName = \strtolower($this->settingName);

		foreach ($dom->getElementsByTagName('embed') as $embed)
		{
			if ($this->onlyIfDynamic && !$this->isDynamic($embed))
				continue;

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

		foreach ($template->getElementsByTagName('object') as $object)
		{
			if ($this->onlyIfDynamic && !$this->isDynamic($object))
				continue;

			$useDefault = \true;

			foreach ($template->getElementsByTagName('param') as $param)
			{
				$paramName = \strtolower($param->getAttribute('name'));

				if ($paramName === $settingName)
				{
					$this->checkSetting($param, $param->getAttribute('value'));

					$nodes = $param->getElementsByTagNameNS(
						'http://www.w3.org/1999/XSL/Transform',
						'attribute'
					);
					foreach ($nodes as $attribute)
						if (\strtolower($attribute->getAttribute('name')) === 'value')
							throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);

					if ($param->parentNode->isSameNode($object))
						$useDefault = \false;
				}
			}

			if ($useDefault)
				if (!$this->onlyIfDynamic || $this->isDynamic($object))
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
		if ($node->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', '*')->length)
			return \true;

		$xpath = new DOMXPath($node->ownerDocument);
		$query = './/@*[contains(., "{")]';

		foreach ($xpath->query($query, $node) as $attribute)
			if (\preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $attribute->value))
				return \true;

		return \false;
	}
}