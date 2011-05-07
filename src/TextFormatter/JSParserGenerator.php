<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

class JSParserGenerator
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	protected $src;

	/**
	* 
	*
	* @return void
	*/
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;

		$this->src = file_get_contents(__DIR__ . '/Parser.js');
	}

	/**
	* 
	*
	* @return void
	*/
	public function get(array $options = array())
	{
		$options += array(
			'compression'    => 'none',
			'enableDebugLog' => true,
			'removeDeadCode' => true
		);

		$parserConfig = $this->cb->getParserConfig();
		$js = $this->src;

		$js = preg_replace(
			'#(DEBUG\\s*=\\s*).*?,#',
			'DEBUG=' . (int) $options['enableDebugLog'] . ',',
			$js
		);

		if ($options['removeDeadCode'])
		{
			$this->removeDeadCode($js, $parserConfig);
		}

		return $js;
	}

	/**
	* Remove JS code that is not going to be used
	*
	* @param string &$js           JS source
	* @param arrray &$parserConfig Parser config
	*/
	protected function removeDeadCode(&$js, &$parserConfig)
	{
		$this->removeDeadRules($js, $parserConfig);
		$this->removeAttributesProcessing($js, $parserConfig);
		$this->removeDeadFilters($js, $parserConfig);
	}

	/**
	* Remove JS code related to rules that are not used
	*
	* @param string &$js           JS source
	* @param arrray &$parserConfig Parser config
	*/
	protected function removeDeadRules(&$js, &$parserConfig)
	{
		$rules = array(
			'closeParent',
			'closeAscendant',
			'requireParent',
			'requireAscendant'
		);

		$remove = array();

		foreach ($rules as $rule)
		{
			foreach ($parserConfig['tags'] as $tag)
			{
				if (!empty($tag['rules'][$rule]))
				{
					continue 2;
				}
			}

			$remove[] = $rule;
		}

		if ($remove)
		{
			$this->removeFunctions($js, implode('|', $remove));
		}
	}

	/**
	* Remove JS code related to attributes if no attributes exist
	*
	* @param string &$js           JS source
	* @param arrray &$parserConfig Parser config
	*/
	protected function removeAttributesProcessing(&$js, &$parserConfig)
	{
		foreach ($parserConfig['tags'] as $tag)
		{
			if (!empty($tag['attrs']))
			{
				return;
			}
		}

		$this->removeFunctions($js, 'processCurrentTagAttributes');
	}

	/**
	* Remove JS code related to filters that are not used
	*
	* @param string &$js           JS source
	* @param arrray &$parserConfig Parser config
	*/
	protected function removeDeadFilters(&$js, &$parserConfig)
	{
	}

	/**
	* Remove the content of some JS functions from the source
	*
	* @param string &$js     JS source
	* @param string  $regexp Regexp used to match the function's name
	*/
	protected function removeFunctions(&$js, $regexp)
	{
		$js = preg_replace(
			'#(\\n\\t+function\\s+(?:' . $regexp . ')\\(.*?\\)(\\n\\t+)\\{).*?(\\2\\})#s',
			'$1$3',
			$js
		);
	}
}