<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use DOMXPath;
use InvalidArgumentException;
use RuntimeException;

class BBCodesConfig extends PluginConfig
{
	/**
	* @var RepositoryCollection BBCode repositories
	*/
	protected $repositories;

	/**
	* Plugin setup
	*
	* @return void
	*/
	protected function setUp()
	{
		$this->repositories = new RepositoryCollection;
		$this->repositories->add('default', __DIR__ . '/repository.xml');
	}

	/**
	* Add a BBCode from a repository
	*
	* @param  string $bbcodeName Name of the BBCode to add
	* @param  string $repository Name of the repository to use as source
	* @param  array  $vars       Variables that will replace default values in the tag definition
	* @return BBCode             Newly-created BBCode
	*/
	public function addFromRepository($bbcodeName, $repository = 'default', array $vars = array())
	{
		if (!BBCode::isValid($bbcodeName))
		{
			throw new InvalidArgumentException('Invalid BBCode name');
		}

		if (!$this->repositories->exists($repository))
		{
			throw new InvalidArgumentException("Repository '" . $repository . "' does not exist");
		}

		$dom = $this->repositories->get($repository);

		$xpath = new DOMXPath($dom);
		$node  = $xpath->query('//bbcode[@name="' . $bbcodeName . '"]')->item(0);

		if (!$node)
		{
			throw new RuntimeException("Could not find BBCode '" . $bbcodeName . "' in repository '" . $repository . "'");
		}
	}









	/**
	* @var array Pre-filter and post-filter callbacks we allow in BBCode definitions.
	*            We use a whitelist approach because there are so many different risky callbacks
	*            that it would be too easy to let something dangerous slip by, e.g.: unlink,
	*            system, etc...
	*/
	protected $allowedFilterCallbacks = array(
		'addslashes',
		'intval',
		'json_encode',
		'ltrim',
		'mb_strtolower',
		'mb_strtoupper',
		'rawurlencode',
		'rtrim',
		'str_rot13',
		'stripslashes',
		'strrev',
		'strtolower',
		'strtotime',
		'strtoupper',
		'trim',
		'ucfirst',
		'ucwords',
		'urlencode'
	);

	/**
	* @var PredefinedBBCodes
	*/
	protected $predefinedBBCodes;

	/**
	* @var array Per-BBCode configuration
	*/
	protected $bbcodes = array();

	public function getConfig()
	{
		if (empty($this->bbcodes))
		{
			return false;
		}

		/**
		* Build the regexp that matches all the BBCode names
		*/
		$regexp = $this->cb->getRegexpBuilder()->fromList(
			array_keys($this->bbcodes)
		);

		// Remove the non-capturing subpattern since we place the regexp inside a capturing pattern
		$regexp = preg_replace('#^\\(\\?:(.*)\\)$#D', '$1', $regexp);

		return array(
			'bbcodes' => $this->bbcodes,
			'regexp'  => '#\\[/?(' . $regexp . ')(?=[\\] =:/])#iS'
		);
	}

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
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName, false);

		if (isset($this->bbcodes[$bbcodeName]))
		{
			throw new InvalidArgumentException("BBCode '" . $bbcodeName . "' already exists");
		}

		/**
		* Separate tag options such as "trimBefore" from BBCodes-specific options such as
		* "defaultAttr"
		*/
		$bbcodeSpecificConfig = array(
			'autoClose'    => 1,
			'contentAttr'  => 1,
			'contentAttrs' => 1,
			'defaultAttr'  => 1,
			'tagName'      => 1
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
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName, false);

		if (isset($this->bbcodes[$bbcodeName]))
		{
			throw new InvalidArgumentException("BBCode '" . $bbcodeName . "' already exists");
		}

		/**
		* This line of code has two purposes: first, it ensure that the tag name passed as second
		* parameter is not overwritten by the tagName element that may exist in $bbcodeConfig.
		*
		* Additionally, it ensures that tagName appears first in the array, so that it is available
		* when other options are set.
		*/
		$bbcodeConfig = array('tagName' => $tagName) + $bbcodeConfig;

		$this->bbcodes[$bbcodeName] = array();
		$this->setBBCodeOptions($bbcodeName, $bbcodeConfig);
	}

	/**
	* Test whether a BBCode of given name exists
	*
	* @param  string $bbcodeName
	* @return bool
	*/
	public function bbcodeExists($bbcodeName)
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName, false);

		return isset($this->bbcodes[$bbcodeName]);
	}

	/**
	* Return all of a BBCode's options
	*
	* @param  string $bbcodeName
	* @return array
	*/
	public function getBBCodeOptions($bbcodeName)
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName);

		return $this->bbcodes[$bbcodeName];
	}

	/**
	* Return a BBCode's option
	*
	* @param  string $bbcodeName
	* @param  string $optionName
	* @return mixed
	*/
	public function getBBCodeOption($bbcodeName, $optionName)
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName);

		if (!array_key_exists($optionName, $this->bbcodes[$bbcodeName]))
		{
			throw new InvalidArgumentException("Unknown option '" . $optionName . "' from BBCode '" . $bbcodeName . "'");
		}

		return $this->bbcodes[$bbcodeName][$optionName];
	}

	/**
	* Set several options of a BBCode
	*
	* @param string $bbcodeName
	* @param array  $bbcodeOptions Associative array of $optionName => $optionValue
	*/
	public function setBBCodeOptions($bbcodeName, array $bbcodeOptions)
	{
		if (isset($bbcodeOptions['contentAttr']))
		{
			$bbcodeOptions['contentAttrs'][] = $bbcodeOptions['contentAttr'];
			unset($bbcodeOptions['contentAttr']);
		}

		foreach ($bbcodeOptions as $optionName => $optionValue)
		{
			$this->setBBCodeOption($bbcodeName, $optionName, $optionValue);
		}
	}

	/**
	* Set an option of a BBCode
	*
	* @param string $bbcodeName
	* @param string $optionName
	* @param mixed  $optionValue
	*/
	public function setBBCodeOption($bbcodeName, $optionName, $optionValue)
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName);

		switch ($optionName)
		{
			case 'tagName':
				$optionValue = $this->cb->normalizeTagName($optionValue);
				break;

			case 'defaultAttr':
				$optionValue = $this->cb->normalizeAttributeName($optionValue);
				break;

			case 'contentAttr':
				$optionName  = 'contentAttrs';
				$optionValue = (array) $optionValue;
				// no break; here

			case 'contentAttrs':
				foreach ($optionValue as &$attrName)
				{
					$attrName = $this->cb->normalizeAttributeName($attrName);
				}
				unset($attrName);
				break;
		}

		$this->bbcodes[$bbcodeName][$optionName] = $optionValue;
	}

	/**
	* Add a BBCode defined in the PredefinedBBCodes class
	*
	* @param string $bbcodeName
	*/
	public function addPredefinedBBCode($bbcodeName)
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName, false);

		if (!isset($this->predefinedBBCodes))
		{
			$this->predefinedBBCodes = new PredefinedBBCodes($this->cb);
		}

		$callback = array(
			$this->predefinedBBCodes,
			'add' . $bbcodeName
		);

		call_user_func_array($callback, array_slice(func_get_args(), 1));
	}

	/**
	* Create a BBCode defined based on a given example
	*
	* @param string $def     Definition
	* @param string $tpl     Template
	* @param int    $flags
	* @param array  $options Additional BBCode options
	*/
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

		foreach ($def['attrs'] as $attrName => $attrConf)
		{
			$this->cb->addAttribute(
				$def['bbcodeName'],
				$attrName,
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
		$attrs       = $def['attrs'];
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
						throw new InvalidArgumentException('Undefined placeholder {' . $identifier . '} found in template');
					}

					if (!($flags & ConfigBuilder::ALLOW_UNSAFE_TEMPLATES)
					 && preg_match('#^TEXT[0-9]*$#D', $identifier))
					{
						throw new RuntimeException('Using {TEXT} inside HTML attributes is inherently unsafe and has been disabled. Please pass ' . __CLASS__ . '::ALLOW_UNSAFE_TEMPLATES as a third parameter to addBBCodeFromExample() to enable it');
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
					throw new InvalidArgumentException('Undefined placeholder {' . $identifier . '} found in template');
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
	* Parse a BBCode definition
	*
	* @param  string $def BBCode definition, e.g. [B]{TEXT}[/b]
	* @return array
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
				'regexp' => 'REGEXP[0-9]*=(?P<regexp>/.*?/i?)',
				'parse'  => 'PARSE=(?P<parser>/.*?/i?)',
				'range'  => 'RANGE[0-9]*=(?P<min>-?[0-9]+),(?P<max>-?[0-9]+)',
				'choice' => 'CHOICE[0-9]*=(?P<choices>.+?)',
				'other'  => '[A-Z_]+[0-9]*'
			),
			'attrOptions' => '[A-Z_a-z]+(?:=[^;]+?)?'
		);
		$r['placeholder'] =
			  '\\{'
			. '(?P<type>' . implode('|', $r['type']) . ')'
			. '(?P<attrOptions>(?:;' . $r['attrOptions'] . ')*);?'
			. '\\}';


		$regexp = '#(?J)\\['
		        // (BBCODE)(=paramval)?
		        . '(?P<bbcodeName>' . $r['bbcodeName'] . ')'
		        . '(?P<defaultAttr>=' . $r['placeholder'] . ')?'
		        // (foo=fooval bar=barval)
		        . '(?P<attrs>(?:\\s+' . $r['attrName'] . '=' . $r['placeholder'] . ')*)'
		        // ]({TEXT})[/BBCODE]
		        . '(?:\\s*/?\\]|\\](?P<content>' . $r['placeholder'] . ')?(?P<endTag>\\[/\\1]))'
		        . '$#D';

		if (!preg_match($regexp, trim($def), $m))
		{
			return false;
		}

		$bbcodeName   = $m['bbcodeName'];
		$options      = array();
		$attrs        = array();
		$placeholders = array();
		$content      = (isset($m['content'])) ? $m['content'] : '';
		$attrsDef     = $m['attrs'];

		/**
		* Auto-close the BBCode if no end tag is specified
		*/
		if (empty($m['endTag']))
		{
			$options['autoClose'] = true;
		}

		/**
		* If we have a default attribute in $m[2], we prepend the definition to the attribute pairs.
		* e.g. [a href={URL}]           => $attrsDef = "href={URL}"
		*      [url={URL} title={TEXT}] => $attrsDef = "url={URL} title={TEXT}"
		*/
		if ($m['defaultAttr'])
		{
			$attrsDef = $m['bbcodeName'] . $m['defaultAttr'] . $attrsDef;

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
		* ignore the node's <st/> and <et/> children. This is only applicable if no postprocessing
		* is performed. For instance, no preFilter or postFilter callbacks.
		*/
		if ($content !== '')
		{
			preg_match('#^' . $r['placeholder'] . '$#', $content, $m);

			if (preg_match('#^\\{TEXT[0-9]*\\}$#D', $content))
			{
				/**
				* Use substring() to exclude the <st/> and <et/> children
				*/
				$placeholders[substr($content, 1, -1)] =
					'substring(., 1 + string-length(st), string-length() - (string-length(st) + string-length(et)))';
			}
			else
			{
				/**
				* We need to validate the content, means we should probably disable BBCodes,
				* e.g. [email]{EMAIL}[/email]
				*/
				$options['defaultChildRule'] = 'deny';
				$options['defaultDescendantRule'] = 'deny';

				/**
				* We append the placeholder to the attributes, using "content" as param name, which
				* can be overriden with an attrName option, and setting the "useContent" option
				*/
				$attrsDef .= ' content=' . substr($content, 0, -1) . ';useContent}';
			}
		}

		preg_match_all(
			'#(' . $r['attrName'] . ')=' . $r['placeholder'] . '#',
			$attrsDef,
			$matches,
			PREG_SET_ORDER
		);

		foreach ($matches as $m)
		{
			$attrName   = strtolower($m[1]);
			$identifier = $m['type'];

			$attrConf = array();
			if (!empty($m['attrOptions']))
			{
				foreach (explode(';', trim($m['attrOptions'], ';')) as $pair)
				{
					$pos = strpos($pair, '=');

					if ($pos)
					{
						$optionName  = substr($pair, 0, $pos);
						$optionValue = substr($pair, 1 + $pos);
					}
					else
					{
						// Just the option name, we assume the value is true, e.g. {URL;required}
						$optionName  = $pair;
						$optionValue = true;
					}

					switch ($optionName)
					{
						case 'preFilter':
						case 'postFilter':
							foreach (explode(',', $optionValue) as $callback)
							{
								/**
								* Turn 'stdClass::method()' into array('stdClass', 'method')
								*/
								if (strpos($callback, '::') !== false)
								{
									$callback = explode('::', $callback);
								}

								if (!in_array($callback, $this->allowedFilterCallbacks, true))
								{
									throw new RuntimeException("Callback '" . $callback . "' is not allowed");
								}

								$attrConf[$optionName][] = $callback;
							}
							break;

						case 'attrName':
							if (isset($options['defaultAttr'])
							 && $options['defaultAttr'] === $attrName)
							{
								$options['defaultAttr'] = $optionValue;
							}

							$attrName = strtolower($optionValue);
							break;

						default:
							$attrConf[$optionName] = $optionValue;
					}
				}
			}

			// Not an attribute per-se, just an attribute parser
			if (substr($identifier, 0, 5) === 'PARSE')
			{
				$options['attributeParsers'][$attrName][] = $m['parser'];

				if (!empty($attrConf['useContent']))
				{
					$options['contentAttrs'][] = $attrName;
				}

				continue;
			}

			if (isset($attrs[$attrName]))
			{
				throw new InvalidArgumentException("Attribute '" . $attrName . "' is defined twice");
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

					case 'choice':
						$choices = explode(',', $m['choices']);
						$regexp  = '#^'
						         . $this->cb->getRegexpBuilder()->fromList($choices)
						         . '$#iD';

						if (!preg_match('#^[[:ascii:]]*$#D', $regexp))
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
				throw new InvalidArgumentException('Placeholder {' . $identifier . '} is used twice');
			}

			$placeholders[$identifier] = '@' . $attrName;

			/**
			* Merge the type/preFilter/postFiler values into this attribute's filterChain
			*/
			$attrConf['filterChain'] = array();

			if (isset($attrConf['preFilter']))
			{
				foreach ($attrConf['preFilter'] as $callback)
				{
					$attrConf['filterChain'][] = $callback;
				}
			}

			if ($attrConf['type'] !== 'text')
			{
				$attrConf['filterChain'][] = '#' . $attrConf['type'];
			}

			if (isset($attrConf['postFilter']))
			{
				foreach ($attrConf['postFilter'] as $callback)
				{
					$attrConf['filterChain'][] = $callback;
				}
			}

			unset($attrConf['preFilter'], $attrConf['type'], $attrConf['postFilter']);

			/**
			* Add the attribute to the list
			*/
			$attrs[$attrName] = $attrConf;
		}

		/**
		* Remove the "useContent" option from attributes and set "contentAttrs"
		*/
		foreach ($attrs as $attrName => &$attrConf)
		{
			if (!empty($attrConf['useContent']))
			{
				$options['contentAttrs'][] = $attrName;
			}
			unset($attrConf['useContent']);
		}
		unset($attrConf);

		/**
		* Create the attributes created by attribute parsers if they haven't been defined elsewhere
		*/
		if (isset($options['attributeParsers']))
		{
			$rm = $this->cb->getRegexpBuilder();

			foreach ($options['attributeParsers'] as $attrName => $regexps)
			{
				foreach ($regexps as $regexp)
				{
					$regexpInfo = $rm->parseRegexp($regexp);

					// Ensure that we use the D modifier
					if (strpos($regexpInfo['modifiers'], 'D') === false)
					{
						$regexpInfo['modifiers'] .= 'D';
					}

					foreach ($regexpInfo['tokens'] as $token)
					{
						if ($token['type'] !== 'capturingSubpatternStart'
						 || !isset($token['name']))
						{
							 continue;
						}

						$attrName = $token['name'];

						if (!isset($attrs[$attrName]))
						{
							$regexp = $regexpInfo['delimiter']
							        . '^(?:' . $token['content'] . ')$'
							        . $regexpInfo['delimiter']
							        . $regexpInfo['modifiers'];

							$attrs[$attrName] = array(
								'type'   => 'regexp',
								'regexp' => $regexp
							);
						}
					}
				}
			}
		}

		return array(
			'bbcodeName'   => $bbcodeName,
			'options'      => $options,
			'attrs'        => $attrs,
			'placeholders' => $placeholders
		);
	}

	/**
	* Validate and normalize a BBCode name
	*
	* @param  string $bbcodeName Original BBCode name
	* @param  bool   $mustExist  If TRUE, throw an exception if the BBCode does not exist
	* @return string             Normalized BBCode name, in uppercase
	*/
	protected function normalizeBBCodeName($bbcodeName, $mustExist = true)
	{
		if (!$this->isValidBBCodeName($bbcodeName))
		{
			throw new InvalidArgumentException ("Invalid BBCode name '" . $bbcodeName . "'");
		}

		$bbcodeName = strtoupper($bbcodeName);

		if ($mustExist && !isset($this->bbcodes[$bbcodeName]))
		{
			throw new InvalidArgumentException("BBCode '" . $bbcodeName . "' does not exist");
		}

		return $bbcodeName;
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

	/**
	* Add a callback to the list of allowed callbacks
	*
	* @param callback $callback
	*/
	public function allowFilterCallback($callback)
	{
		$this->allowedFilterCallbacks[] = $callback;
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSConfig()
	{
		$config = $this->getConfig();

		$config['hasAutoCloseHint']    = false;
		$config['hasContentAttrsHint'] = false;
		$config['hasDefaultAttrHint']  = false;

		foreach ($this->bbcodes as $bbcodeConfig)
		{
			if (!empty($bbcodeConfig['autoClose']))
			{
				$config['hasAutoCloseHint'] = true;
			}

			if (!empty($bbcodeConfig['contentAttrs']))
			{
				$config['hasContentAttrsHint'] = true;
			}

			if (!empty($bbcodeConfig['defaultAttr']))
			{
				$config['hasDefaultAttrHint'] = true;
			}
		}

		return $config;
	}

	public function getJSConfigMeta()
	{
		return array(
			'preserveKeys' => array(
				array('bbcodes', true)
			)
		);
	}

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/BBCodesParser.js');
	}
}