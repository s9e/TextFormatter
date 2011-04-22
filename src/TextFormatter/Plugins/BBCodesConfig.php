<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use DOMDocument,
    DOMXPath,
    InvalidArgumentException,
    RuntimeException,
    s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig,
    s9e\Toolkit\TextFormatter\PredefinedBBCodes;

class BBCodesConfig extends PluginConfig
{
	/**
	* @var array Pre-filter and post-filter callbacks we allow in BBCode definitions.
	*            We use a whitelist approach because there are so many different risky callbacks
	*            that it would be too easy to let something dangerous slip by, e.g.: unlink,
	*            system, etc...
	*/
	public $bbcodeFiltersAllowedCallbacks = array(
		'strtolower',
		'strtoupper',
		'mb_strtolower',
		'mb_strtoupper',
		'ucfirst',
		'ucwords',
		'ltrim',
		'rtrim',
		'trim',
		'htmlspecialchars',
		'htmlentities',
		'html_entity_decode',
		'addslashes',
		'stripslashes',
		'addcslashes',
		'stripcslashes',
		'intval',
		'strtotime',
		'strrev'
	);

	/**
	* @var PredefinedBBCodes
	*/
	protected $predefinedBBCodes;

	/**
	* @var array Per-BBCode configuration
	*/
	protected $bbcodesConfig = array();

	/**
	* Create a new BBCode and its corresponding tag
	*
	* Will automatically create a tag of the same name, unless a different name is specified in
	* $config['tagName']. Attributes to be created can be passed via using "attributes" as key. The
	* same applies for "rules" and "template" or "xsl".
	*
	* @param string $bbcodeName
	* @param array  $config
	*/
	public function addBBCode($bbcodeName, array $config = array())
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName);

		if (isset($this->bbcodesConfig[$bbcodeName]))
		{
			throw new InvalidArgumentException("BBCode '" . $bbcodeName . "' already exists");
		}

		/**
		* Separate tag options such as "trimBefore" from BBCodes-specific options such as
		* "defaultAttr"
		*/
		$bbcodeSpecificConfig = array(
			'autoClose'   => 1,
			'defaultAttr' => 1,
			'tagName'     => 1,
			'contentAttr' => 1
		);

		$bbcodeConfig = array_intersect_key($config, $bbcodeSpecificConfig);
		$tagConfig    = array_diff_key($config, $bbcodeSpecificConfig);
		$tagName      = (isset($bbcodeConfig['tagName'])) ? $bbcodeConfig['tagName'] : $bbcodeName;

		$this->cb->addTag($tagName, $tagConfig);
		$this->addBBCodeAlias($bbcodeName, $tagName, $bbcodeConfig);
	}

	/**
	* Create a new BBCode that maps to an existing tag
	*
	* @param string $bbcodeName
	* @param string $tagName
	* @param array  $bbcodeConfig
	*/
	public function addBBCodeAlias($bbcodeName, $tagName, array $bbcodeConfig = array())
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName);
		$tagName    = $this->normalizeBBCodeName($tagName);

		if (!$this->cb->tagExists($tagName))
		{
			throw new InvalidArgumentException("Tag '" . $tagName . "' does not exist");
		}

		if (isset($this->bbcodesConfig[$bbcodeName]))
		{
			throw new InvalidArgumentException("BBCode '" . $bbcodeName . "' already exists");
		}

		$bbcodeConfig['tagName'] = $tagName;

		$this->bbcodesConfig[$bbcodeName] = $bbcodeConfig;
	}

	/**
	* Test whether a BBCode of given name exists
	*
	* @param  string $bbcodeName
	* @return bool
	*/
	public function bbcodeExists($bbcodeName)
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName);

		return isset($this->bbcodesConfig[$bbcodeName]);
	}

	public function setBBCodeOptions($bbcodeName, array $bbcodeOptions)
	{
		foreach ($bbcodeOptions as $optionName => $optionValue)
		{
			$this->setBBCodeOption($bbcodeName, $optionName, $optionValue);
		}
	}

	public function setBBCodeOption($bbcodeName, $optionName, $optionValue)
	{
		$this->bbcodesConfig[$bbcodeName][$optionName] = $optionValue;
	}

	public function getConfig()
	{
		/**
		* Build the regexp that matches all the BBCode names, then remove the extraneous
		* non-capturing expression around it
		*/
		$regexp = ConfigBuilder::buildRegexpFromList(array_keys($this->bbcodesConfig));
		$regexp = preg_replace('#^\\(\\?:(.*)\\)$#D', '$1', $regexp);

		return array(
			'bbcodesConfig' => $this->bbcodesConfig,
			'regexp'        => '#\\[/?(' . $regexp . ')(?=[\\] =:/])#i'
		);
	}

	public function addPredefinedBBCode($bbcodeName)
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName);

		if (!isset($this->predefinedBBCodes))
		{
			$className = implode('\\', array_slice(explode('\\', __NAMESPACE__), 0, -1))
					   . '\\PredefinedBBCodes';

			if (!class_exists($className))
			{
				include __DIR__ . '/../PredefinedBBCodes.php';
			}

			$this->predefinedBBCodes = new PredefinedBBCodes($this->cb);
		}

		$callback = array(
			$this->predefinedBBCodes,
			'add' . $bbcodeName
		);

		if (!is_callable($callback))
		{
			throw new InvalidArgumentException('Unknown BBCode ' . $bbcodeName);
		}

		call_user_func_array($callback, array_slice(func_get_args(), 1));
	}

	public function addBBCodeFromExample($def, $tpl, $flags = 0, array $options = array())
	{
		$def = $this->parseBBCodeDefinition($def);

		if ($def === false)
		{
			throw new InvalidArgumentException('Cannot interpret the BBCode definition');
		}

		$tpl = $this->convertTemplate($tpl, $def, $flags);

		// Options set via $options override the ones we have parsed from the definition
		$this->addBBCode($def['bbcodeName'], $options + $def['options']);

		foreach ($def['params'] as $attrName => $attrConf)
		{
			$this->cb->addTagAttribute(
				$def['bbcodeName'],
				$attrName,
				$attrConf['type'],
				$attrConf
			);
		}

		$this->cb->setTagTemplate($def['bbcodeName'], $tpl, $flags);
	}

	protected function convertTemplate($tpl, array $def, $flags)
	{
		/**
		* Generate a random tag name so that the user cannot inject stuff outside of that template.
		* For instance, if the tag was <t>, one could input </t><xsl:evil-stuff/><t>
		*/
		$t = 't' . md5(microtime(true) . mt_rand());

		$useErrors = libxml_use_internal_errors(true);

		$dom = new DOMDocument;
		$dom->formatOutput = false;
		$dom->preserveWhiteSpace = false;

		$res = $dom->loadXML(
			'<?xml version="1.0" encoding="utf-8" ?>
			<' . $t . ' xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . $tpl . '</' . $t . '>'
		);

		libxml_use_internal_errors($useErrors);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new InvalidArgumentException('Invalid XML in template - error was: ' . $error->message);
		}

		$bbcodeName   = $def['bbcodeName'];
		$params       = $def['params'];
		$placeholders = $def['placeholders'];
		$options      = $def['options'];

		/**
		* Replace placeholders in attributes
		*/
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attr)
		{
			$attr->value = htmlspecialchars(preg_replace_callback(
				'#\\{[A-Z]+[0-9]*?\\}#',
				function ($m) use ($placeholders, $flags)
				{
					$identifier = substr($m[0], 1, -1);

					if (!isset($placeholders[$identifier]))
					{
						throw new InvalidArgumentException('Unknown placeholder ' . $identifier . ' found in template');
					}

					if (!($flags & ConfigBuilder::ALLOW_INSECURE_TEMPLATES)
					 && preg_match('#^TEXT[0-9]*$#D', $identifier))
					{
						throw new RuntimeException('Using {TEXT} inside HTML attributes is inherently insecure and has been disabled. Please pass ' . __CLASS__ . '::ALLOW_INSECURE_TEMPLATES as a third parameter to addBBCodeFromExample() to enable it');
					}

					return '{' . $placeholders[$identifier] . '}';
				},
				$attr->value
			));
		}

		/**
		* Replace placeholders everywhere else: the lazy version
		*/
		$tpl = preg_replace_callback(
			'#\\{[A-Z]+[0-9]*\\}#',
			function ($m) use ($placeholders)
			{
				$identifier = substr($m[0], 1, -1);

				if (!isset($placeholders[$identifier]))
				{
					throw new InvalidArgumentException('Unknown placeholder ' . $identifier . ' found in template');
				}

				if ($placeholders[$identifier][0] !== '@')
				{
					return '<xsl:apply-templates/>';
				}

				return '<xsl:value-of select="' . $placeholders[$identifier] . '"/>';
			},
			substr(trim($dom->saveXML($dom->documentElement)), 84, -36)
		);

		return $tpl;
	}

	/**
	* @todo separate contentAttr from defaultAttr
	*/
	public function parseBBCodeDefinition($def)
	{
		/**
		* The various regexps used to parse the definition
		*/
		$r = array(
			'bbcodeName' => '[a-zA-Z_][a-zA-Z_0-9]*',
			'attrName'   => '[a-zA-Z_][a-zA-Z_0-9]*',
			'type' => array(
				'regexp'   => 'REGEXP[0-9]*=(?P<regexp>/.*?/i?)',
				'compound' => 'COMPOUND[0-9]*=(?P<compoundRegexp>/.*?/i?)',
				'range'    => 'RANGE[0-9]*=(?P<min>-?[0-9]+),(?P<max>-?[0-9]+)',
				'choice'   => 'CHOICE[0-9]*=(?P<choices>.+?)',
				'other'    => '[A-Z_]+[0-9]*'
			),
			'attrOptions' => '[A-Z_a-z]+=[^;]+?'
		);
		$r['placeholder'] =
			  '\\{'
			. '(?P<type>' . implode('|', $r['type']) . ')'
			. '(?P<attrOptions>;(?:' . $r['attrOptions'] . '))*;?'
			. '\\}';

		// we remove all named captures from the placeholder for the global regexp to avoid dupes
		$placeholder = preg_replace('#\\?P<[a-zA-Z]+>#', '?:', $r['placeholder']);

		$regexp = '#\\['
		        // (BBCODE)(=paramval)?
		        . '(?P<bbcodeName>' . $r['bbcodeName'] . ')'
		        . '(?P<defaultAttr>=' . $placeholder . ')?'
		        // (foo=fooval bar=barval)
		        . '(?P<attrs>(?:\\s+' . $r['attrName'] . '=' . $placeholder . ')*)'
		        // ]({TEXT})[/BBCODE]
		        . '(?:\\s*/?\\]|\\](?P<content>' . $placeholder . ')?(?P<endTag>\\[/\\1]))'
		        . '$#D';

		if (!preg_match($regexp, trim($def), $m))
		{
			return false;
		}

		$bbcodeName   = $m['bbcodeName'];
		$options      = array();
		$params       = array();
		$placeholders = array();
		$content      = (isset($m['content'])) ? $m['content'] : '';
		$attrs        = $m['attrs'];

		/**
		* Auto-close the BBCode if no end tag is specified
		*/
		if (empty($m['endTag']))
		{
			$options['autoClose'] = true;
		}

		/**
		* If we have a default param in $m[2], we prepend the definition to the attribute pairs.
		* e.g. [a href={URL}]           => $attrs = "href={URL}"
		*      [url={URL} title={TEXT}] => $attrs = "url={URL} title={TEXT}"
		*/
		if ($m['defaultAttr'])
		{
			$attrs = $m['bbcodeName'] . $m['defaultAttr'] . $attrs;

			$options['defaultAttr'] = strtolower($m['bbcodeName']);
		}

		/**
		* Here we process the content's placeholder
		*
		* e.g. [spoiler]{TEXT}[/spoiler] => {TEXT}
		*      [img]{URL}[/img]          => {URL}
		*
		* {TEXT} doesn't require validation, so we don't copy its content into an attribute in order
		* to save space. Instead, templates will rely on the node's textContent, which we adjust to
		* ignore the node's <st/> and <et/> children
		*/
		if ($content !== '')
		{
			preg_match('#^' . $r['placeholder'] . '$#', $content, $m);

			if (preg_match('#^TEXT[0-9]*$#D', $m['type']))
			{
				/**
				* Use substring() to exclude the <st/> and <et/> children
				*/
				$placeholders[$m['type']] =
					'substring(., 1 + string-length(st), string-length() - (string-length(st) + string-length(et)))';
			}
			else
			{
				/**
				* We need to validate the content, means we should probably disable BBCodes,
				* e.g. [email]{EMAIL}[/email]
				*/
				$attrName = strtolower($bbcodeName);

				$options['defaultRule'] = 'deny';
				$options['defaultAttr'] = $attrName;
				$options['contentAttr'] = $attrName;

				/**
				* We append the placeholder to the attributes, using the BBCode's name as param name
				*/
				$attrs .= ' ' . $attrName . '=' . $content;
			}
		}

		preg_match_all(
			'#(' . $r['attrName'] . ')=' . $r['placeholder'] . '#',
			$attrs,
			$matches,
			\PREG_SET_ORDER
		);

		foreach ($matches as $m)
		{
			$attrName  = strtolower($m[1]);
			$identifier = $m['type'];

			if (isset($params[$attrName]))
			{
				throw new InvalidArgumentException('Param ' . $attrName . ' is defined twice');
			}

			$attrConf = array();

			if (isset($m['attrOptions']))
			{
				foreach (explode(';', trim($m['attrOptions'], ';')) as $pair)
				{
					$pos = strpos($pair, '=');

					$optionName  = substr($pair, 0, $pos);
					$optionValue = substr($pair, 1 + $pos);

					switch ($optionName)
					{
						case 'preFilter':
						case 'postFilter':
							foreach (explode(',', $optionValue) as $callback)
							{
								if (!in_array($callback, $this->bbcodeFiltersAllowedCallbacks))
								{
									throw new RuntimeException('Callback ' . $callback . ' is not allowed');
								}

								if (strpos($callback, '::') !== false)
								{
									$callback = explode('::', $callback);
								}

								$attrConf[$optionName][] = array(
									'callback' => $callback
								);
							}
							break;

						default:
							$attrConf[$optionName] = $optionValue;
					}
				}
			}

			/**
			* Make sure the param type cannot be set via param options. I can't think of any way
			* to exploit that but better safe than sorry
			*/
			unset($attrConf['type']);

			foreach ($r['type'] as $type => $regexp)
			{
				if (!preg_match('#^' . $regexp . '$#D', $identifier, $m))
				{
					continue;
				}

				switch ($type)
				{
					case 'regexp':
						$attrConf['type']   = 'regexp';
						$attrConf['regexp'] = $m['regexp'];
						break;

					case 'compound':
						$attrConf['type']   = 'compound';
						$attrConf['regexp'] = $m['compoundRegexp'];

						if (!i)
						{
						}
						break;

					case 'choice':
						$choices = explode(',', $m['choices']);
						$regexp  = '/^' . ConfigBuilder::buildRegexpFromList($choices) . '$/iD';

						if (preg_match('#[\\x80-\\xff]#', $regexp))
						{
							// Unicode mode needed
							$regexp .= 'u';
						}

						$attrConf['type']   = 'regexp';
						$attrConf['regexp'] = $regexp;
						break;

					case 'range':
						$attrConf['type'] = 'range';
						$attrConf['min']  = (int) $m['min'];
						$attrConf['max']  = (int) $m['max'];
						break;

					default:
						$attrConf['type'] = rtrim(strtolower($identifier), '1234567890');
				}

				// exit the loop once we've got a hit
				break;
			}

			// @codeCoverageIgnoreStart
			if (!isset($attrConf['type']))
			{
				throw new RuntimeException('Cannot determine the attribute type of ' . $identifier);
			}
			// @codeCoverageIgnoreEnd

			if ($pos = strpos($identifier, '='))
			{
				$identifier = substr($identifier, 0, $pos);
			}

			if (isset($placeholders[$identifier]))
			{
				throw new InvalidArgumentException('Placeholder ' . $identifier . ' is used twice');
			}

			$placeholders[$identifier] = '@' . $attrName;

			/**
			* Add the attribute to the list
			*/
			$params[$attrName] = $attrConf;
		}

		return array(
			'bbcodeName'   => $bbcodeName,
			'options'      => $options,
			'params'       => $params,
			'placeholders' => $placeholders
		);
	}

	/**
	* Validate and normalize a BBCode name
	*
	* @param  string $bbcodeName Original BBCode name
	* @return string             Normalized BBCode name, in uppercase
	*/
	protected function normalizeBBCodeName($bbcodeName)
	{
		if (!$this->isValidBBCodeName($bbcodeName))
		{
			throw new InvalidArgumentException ("Invalid BBCode name '" . $bbcodeName . "'");
		}

		return strtoupper($bbcodeName);
	}

	/**
	* Return whether a string is a valid BBCode name
	*
	* @param  string $bbcodeName
	* @return bool
	*/
	public function isValidBBCodeName($bbcodeName)
	{
		return (bool) preg_match('#^(?:[a-z][a-z_0-9]*|\\*)$#Di', $bbcodeName);
	}
}