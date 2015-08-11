<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use ReflectionClass;

class HintGenerator
{
	/**
	* @var array Config on which hints are based
	*/
	protected $config;

	/**
	* @var array Generated hints
	*/
	protected $hints;

	/**
	* @var string XSL stylesheet on which hints are based
	*/
	protected $xsl;

	/**
	* Generate a HINT object that contains informations about the configuration
	*
	* @return string JavaScript Code
	*/
	public function getHints()
	{
		$this->hints = [];
		$this->setRenderingHints();
		$this->setRulesHints();
		$this->setTagsHints();

		// Build the source. Note that Closure Compiler seems to require that each of HINT's
		// properties be declared as a const
		$js = "/** @const */ var HINT={};\n";
		ksort($this->hints);
		foreach ($this->hints as $hintName => $hintValue)
		{
			$js .= '/** @const */ HINT.' . $hintName . '=' . $hintValue . ";\n";
		}

		return $js;
	}

	/**
	* Set the config on which hints are based
	*
	* @param  array $config
	* @return void
	*/
	public function setConfig(array $config)
	{
		$this->config = $config;
	}

	/**
	* Set the XSL on which hints are based
	*
	* @param  string $xsl
	* @return void
	*/
	public function setXSL($xsl)
	{
		$this->xsl = $xsl;
	}

	/**
	* Set hints related to rendering
	*
	* @return void
	*/
	protected function setRenderingHints()
	{
		// Test for post-processing in templates. Theorically allows for false positives and
		// false negatives, but not in any realistic setting
		$this->hints['postProcessing'] = (int) (strpos($this->xsl, 'data-s9e-livepreview-postprocess') !== false);
	}

	/**
	* Set hints related to rules
	*
	* @return void
	*/
	protected function setRulesHints()
	{
		$this->hints['closeAncestor']   = 0;
		$this->hints['closeParent']     = 0;
		$this->hints['fosterParent']    = 0;
		$this->hints['requireAncestor'] = 0;

		$flags = 0;
		foreach ($this->config['tags'] as $tagConfig)
		{
			// Test which rules are in use
			foreach (array_intersect_key($tagConfig['rules'], $this->hints) as $k => $v)
			{
				$this->hints[$k] = 1;
			}
			$flags |= $tagConfig['rules']['flags'];
		}
		$flags |= $this->config['rootContext']['flags'];

		// Iterate over Parser::RULE_* constants and test which flags are set
		$parser = new ReflectionClass('s9e\\TextFormatter\\Parser');
		foreach ($parser->getConstants() as $constName => $constValue)
		{
			if (substr($constName, 0, 5) === 'RULE_')
			{
				// This will set HINT.RULE_AUTO_CLOSE and others
				$this->hints[$constName] = ($flags & $constValue) ? 1 : 0;
			}
		}
	}

	/**
	* Set hints based on given tag's attributes config
	*
	* @param  array $tagConfig
	* @return void
	*/
	protected function setTagAttributesHints(array $tagConfig)
	{
		if (empty($tagConfig['attributes']))
		{
			return;
		}

		foreach ($tagConfig['attributes'] as $attrConfig)
		{
			$this->hints['attributeGenerator']    |= isset($attrConfig['generator']);
			$this->hints['attributeDefaultValue'] |= isset($attrConfig['defaultValue']);
		}
	}

	/**
	* Set hints related to tags config
	*
	* @return void
	*/
	protected function setTagsHints()
	{
		$this->hints['attributeGenerator']    = 0;
		$this->hints['attributeDefaultValue'] = 0;
		$this->hints['namespaces']            = 0;
		foreach ($this->config['tags'] as $tagName => $tagConfig)
		{
			$this->hints['namespaces'] |= (strpos($tagName, ':') !== false);
			$this->setTagAttributesHints($tagConfig);
		}
	}
}