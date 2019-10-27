<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
class EnforceContentModels implements BooleanRulesGenerator, TargetedRulesGenerator
{
	protected $br;
	protected $span;
	public function __construct()
	{
		$this->br   = new TemplateInspector('<br/>');
		$this->span = new TemplateInspector('<span><xsl:apply-templates/></span>');
	}
	public function generateBooleanRules(TemplateInspector $src)
	{
		$rules = array();
		if ($src->isTransparent())
			$rules['isTransparent'] = \true;
		if (!$src->allowsChild($this->br))
		{
			$rules['preventLineBreaks'] = \true;
			$rules['suspendAutoLineBreaks'] = \true;
		}
		if (!$src->allowsDescendant($this->br))
		{
			$rules['disableAutoLineBreaks'] = \true;
			$rules['preventLineBreaks'] = \true;
		}
		return $rules;
	}
	public function generateTargetedRules(TemplateInspector $src, TemplateInspector $trg)
	{
		$rules = array();
		if ($src->allowsChild($trg))
			$rules[] = 'allowChild';
		if ($src->allowsDescendant($trg))
			$rules[] = 'allowDescendant';
		return $rules;
	}
}