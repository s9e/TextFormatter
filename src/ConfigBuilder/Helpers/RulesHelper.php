<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Helpers;

use s9e\TextFormatter\ConfigBuilder\TagCollection;

abstract class RulesHelper
{
	public function getParserConfig($keepJs = false)
	{
		/**
		* Generate the root context to be used by the Parser
		*/
		$rootContext = array(
			'allowedChildren'    => str_repeat("\x00", ceil(count($config['tags']) / 8)),
			'allowedDescendants' => str_repeat("\x00", ceil(count($config['tags']) / 8))
		);

		foreach ($tags as $tagName => $tag)
		{
			$n = $tagConfig['n'];

			// We set the bit only if the tag is allowed at the root of document
			if (empty($tagConfig['disallowAsRoot']))
			{
				$config['rootContext']['allowedChildren'][$n >> 3]
					= $config['rootContext']['allowedChildren'][$n >> 3] | chr(1 << ($n & 7));
			}

			$config['rootContext']['allowedDescendants'][$n >> 3]
				= $config['rootContext']['allowedDescendants'][$n >> 3] | chr(1 << ($n & 7));

			// We don't need this anymore
			unset($tagConfig['disallowAsRoot']);
		}
		unset($tagConfig);

		return $config;
	}

	/**
	* Return the tags' config, normalized and sorted, minus the tags' templates
	*
	* @param  bool  $reduce If true, remove unnecessary/empty entries and build the list of allowed
	*                       decendants for each tag
	* @return array
	*/
	public function getTagsConfig($reduce = false)
	{
		$tagsConfig = $this->tags;
		ksort($tagsConfig);

		$n = -1;

		foreach ($tagsConfig as $tagName => &$tagConfig)
		{
			if ($reduce)
			{
				if ($tagConfig['disable'])
				{
					// This tag is disabled, remove it
					unset($tagsConfig[$tagName]);
					continue;
				}

				$tagConfig['n'] = ++$n;

				/**
				* Build the list of allowed children and descendants.
				* Note: $tagsConfig is already sorted, so we don't have to sort the list
				*/
				$tagConfig['allowedChildren'] = array_fill_keys(
					array_keys($tagsConfig),
					($tagConfig['defaultChildRule'] === 'allow') ? '1' : '0'
				);
				$tagConfig['allowedDescendants'] = array_fill_keys(
					array_keys($tagsConfig),
					($tagConfig['defaultDescendantRule'] === 'allow') ? '1' : '0'
				);

				if (isset($tagConfig['rules']))
				{
					/**
					* Sort the rules so that "deny" overwrites "allow"
					*/
					ksort($tagConfig['rules']);

					foreach ($tagConfig['rules'] as $action => &$targets)
					{
						switch ($action)
						{
							case 'allowChild':
							case 'allowDescendant':
							case 'denyChild':
							case 'denyDescendant':
								/**
								* Those rules are converted into the allowedChildren and
								* allowedDescendants bitmaps
								*/
								$k = (substr($action, -5) === 'Child')
								   ? 'allowedChildren'
								   : 'allowedDescendants';

								$v = (substr($action, 0, 4) === 'deny') ? '0' : '1';

								foreach ($targets as $target)
								{
									// make sure the target really exists
									if (isset($tagConfig[$k][$target]))
									{
										$tagConfig[$k][$target] = $v;
									}
								}

								// We don't need those anymore
								unset($tagConfig['rules'][$action]);
								break;

							case 'requireParent':
							case 'requireAncestor':
								/**
								* Nothing to do here. If the target tag does not exist, this tag
								* will never be valid but we still leave it in the configuration
								*/
								break;

							default:
								// keep only the rules that target existing tags
								$targets = array_intersect_key($targets, $tagsConfig);
						}
					}
					unset($targets);

					/**
					* Remove rules with no targets
					*/
					$tagConfig['rules'] = array_filter($tagConfig['rules']);

					if (empty($tagConfig['rules']))
					{
						unset($tagConfig['rules']);
					}

					if (!empty($tagConfig['attrs']))
					{
						foreach ($tagConfig['attrs'] as &$attrConf)
						{
							/**
							* Remove the filterChain if it's empty
							*/
							if (empty($attrConf['filterChain']))
							{
								unset($attrConf['filterChain']);
							}
						}
						unset($attrConf);
					}
				}

				unset($tagConfig['defaultChildRule']);
				unset($tagConfig['defaultDescendantRule']);
				unset($tagConfig['disable']);

				/**
				* We only need to store this option if it's true
				*/
				if (!$tagConfig['disallowAsRoot'])
				{
					unset($tagConfig['disallowAsRoot']);
				}

				/**
				* We don't need the tag's template
				*/
				unset($tagConfig['xsl']);

				/**
				* Generate a proper (binary) bitfield
				*/
				$tagConfig['allowedChildren'] = self::bin2raw($tagConfig['allowedChildren']);
				$tagConfig['allowedDescendants'] = self::bin2raw($tagConfig['allowedDescendants']);

				/**
				* Children are descendants of current node, so we apply denyDescendant rules to them
				* as well.
				*/
				$tagConfig['allowedChildren'] &= $tagConfig['allowedDescendants'];
			}

			ksort($tagConfig);
		}
		unset($tagConfig);

		return $tagsConfig;
	}

	protected static function bin2raw($values)
	{
		$bin = implode('', $values) . str_repeat('0', (((count($values) + 7) & 7) ^ 7));

		return implode('', array_map('chr', array_map('bindec', array_map('strrev', str_split($bin, 8)))));
	}

	//==========================================================================
	// XSL stuff
	//==========================================================================

	/**
	* Return the XSL used for rendering
	*
	* @param  string $prefix Prefix to use for XSL elements (defaults to "xsl")
	* @return string
	*/
	public function getXSL($prefix = 'xsl')
	{
		return TemplateHelper::getXSL($this);
	}

	//==========================================================================
	// Javascript parser stuff
	//==========================================================================

	/**
	* Return the Javascript parser that corresponds to this configuration
	*
	* @param  array  $options Options to be passed to the JSParser generator
	* @return string
	*/
	public function getJSParser(array $options = array())
	{
		$jspg = new JSParserGenerator($this);

		return $jspg->get($options);
	}

	/**
	* Return JS parsers and their config
	*
	* @return array
	*/
	public function getJSPlugins()
	{
		$plugins = array();

		foreach ($this->getPluginsConfig('getJSConfig') as $pluginName => $pluginConfig)
		{
			$js = $this->$pluginName->getJSParser();

			if (!$js)
			{
				continue;
			}

			$plugins[$pluginName] = array(
				'parser' => $js,
				'config' => $pluginConfig,
				'meta'   => $this->$pluginName->getJSConfigMeta()
			);
		}

		return $plugins;
	}
}