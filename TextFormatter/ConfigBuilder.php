<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

use DOMDocument,
    DOMXPath,
    InvalidArgumentException,
    RuntimeException,
    UnexpectedValueException;

class ConfigBuilder
{
	const ALLOW_INSECURE_TEMPLATES = 1;
	const PRESERVE_WHITESPACE      = 2;

	protected $passes = array(
		'BBCode' => array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		),
		'Autolink' => array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		),
		'Censor' => array(
			'limit'               => 1000,
			'limit_action'        => 'warn',
			'default_replacement' => '****'
		),
		'Emoticon' => array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		)
	);

	protected $bbcodes = array();
	protected $bbcodeRules = array();
	protected $bbcodeAliases = array();

	protected $censor = array();

	protected $emoticons = array();

	protected $filters = array(
		'url' => array(
			'allowed_schemes' => array('http', 'https')
		)
	);

	/**
	* @var Extra XSL to append to the stylesheet
	*/
	protected $xsl = '';

	//==========================================================================
	// Passes
	//==========================================================================

	public function addPass($name, array $options)
	{
		if (isset($this->passes[$name]))
		{
			throw new InvalidArgumentException('There is already a pass named ' . $name);
		}

		if (!isset($options['parser']))
		{
			throw new InvalidArgumentException('You must specify a parser for pass ' . $name);
		}

		if (!is_callable($options['parser']))
		{
			throw new InvalidArgumentException('The parser for pass ' . $name . ' must be a valid callback');
		}

		$this->passes[$name] = $options + array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		);
	}

	//==========================================================================
	// Autolink
	//==========================================================================

	public function setAutolinkOption($k, $v)
	{
		$this->setOption('Autolink', $k, $v);
	}

	public function getAutolinkConfig()
	{
		$config = $this->passes['Autolink'];

		if (!isset($config['bbcode'], $config['param']))
		{
			return false;
		}

		$config['regexp'] =
			'#' . self::buildRegexpFromList($this->filters['url']['allowed_schemes']) . '://\\S+#iS';

		return $config;
	}

	//==========================================================================
	// BBCode
	//==========================================================================

	public function setBBCodeOption($k, $v)
	{
		$this->setOption('BBCode', $k, $v);
	}

	public function addBBCode($bbcodeId, array $options = array())
	{
		if (!self::isValidId($bbcodeId))
		{
			throw new InvalidArgumentException ("Invalid BBCode name '" . $bbcodeId . "'");
		}

		$bbcodeId = $this->normalizeBBCodeId($bbcodeId);

		if (isset($this->bbcodes[$bbcodeId]))
		{
			throw new InvalidArgumentException('BBCode ' . $bbcodeId . ' already exists');
		}

		$bbcode = $this->getDefaultBBCodeOptions();
		foreach ($options as $k => $v)
		{
			if (isset($bbcode[$k]))
			{
				/**
				* Preserve the PHP type of that option
				*/
				settype($v, gettype($bbcode[$k]));
			}

			$bbcode[$k] = $v;
		}

		$this->bbcodes[$bbcodeId] = $bbcode;
		$this->bbcodeAliases[$bbcodeId] = $bbcodeId;
	}

	public function addBBCodeAlias($bbcodeId, $alias)
	{
		$bbcodeId = $this->normalizeBBCodeId($bbcodeId);
		$alias    = $this->normalizeBBCodeId($alias);

		if (!isset($this->bbcodes[$bbcodeId]))
		{
			throw new InvalidArgumentException("Unknown BBCode '" . $bbcodeId . "'");
		}
		if (isset($this->bbcodes[$alias]))
		{
			throw new InvalidArgumentException("Cannot create alias '" . $alias . "' - a BBCode using that name already exists");
		}

		/**
		* For the time being, restrict aliases to a-z, 0-9, _ and * with no restriction on first
		* char
		*/
		if (!preg_match('#^[A-Z_0-9\\*]+$#D', $alias))
		{
			throw new InvalidArgumentException("Invalid alias name '" . $alias . "'");
		}
		$this->bbcodeAliases[$alias] = $bbcodeId;
	}

	/**
	* Add a param to a BBCode
	*
	* @param string $bbcodeId
	* @param string $paramName
	* @param string $paramType
	* @param array  $conf
	*/
	public function addBBCodeParam($bbcodeId, $paramName, $paramType, array $paramConf = array())
	{
		/**
		* Add default config
		*/
		$paramConf += array(
			'is_required' => true
		);

		$bbcodeId = $this->normalizeBBCodeId($bbcodeId);
		if (!isset($this->bbcodes[$bbcodeId]))
		{
			throw new InvalidArgumentException("Unknown BBCode '" . $bbcodeId . "'");
		}

		if (!self::isValidId($paramName))
		{
			throw new InvalidArgumentException ("Invalid param name '" . $paramName . "'");
		}

		$paramName = strtolower($paramName);

		if (isset($this->bbcodes[$bbcodeId]['params'][$paramName]))
		{
			throw new InvalidArgumentException('Param ' . $paramName . ' already exists');
		}

		$paramConf['type'] = $paramType;
		$this->bbcodes[$bbcodeId]['params'][$paramName] = $paramConf;
	}

	public function addBBCodeRule($bbcodeId, $action, $target)
	{
		if (!in_array($action, array(
			'allow',
			'close_parent',
			'deny',
			'require_parent',
			'require_ascendant'
		), true))
		{
			throw new UnexpectedValueException("Unknown rule action '" . $action . "'");
		}

		$bbcodeId = $this->normalizeBBCodeId($bbcodeId);
		$target   = $this->normalizeBBCodeId($target);

		if ($action === 'require_parent')
		{
			if (isset($this->bbcodeRules[$bbcodeId]['require_parent'])
			 && $this->bbcodeRules[$bbcodeId]['require_parent'] !== $target)
			{
				throw new RuntimeException("BBCode $bbcodeId already has a require_parent rule");
			}
			$this->bbcodeRules[$bbcodeId]['require_parent'] = $target;
		}
		else
		{
			$this->bbcodeRules[$bbcodeId][$action][] = $target;
		}
	}

	public function getBBCodeConfig()
	{
		$config = $this->passes['BBCode'];
		$config['aliases'] = $this->bbcodeAliases;
		$config['bbcodes'] = $this->bbcodes;

		$bbcodeIds = array_keys($this->bbcodes);

		foreach ($config['bbcodes'] as $bbcodeId => &$bbcode)
		{
			$allow = array();

			if (isset($this->bbcodeRules[$bbcodeId]))
			{
				/**
				* Sort the rules so that "deny" overwrite "allow"
				*/
				ksort($this->bbcodeRules[$bbcodeId]);

				foreach ($this->bbcodeRules[$bbcodeId] as $action => $targets)
				{
					switch ($action)
					{
						case 'allow':
							foreach ($targets as $target)
							{
								$allow[$target] = true;
							}
							break;

						case 'deny':
							foreach ($targets as $target)
							{
								$allow[$target] = false;
							}
							break;

						case 'require_parent':
							$bbcode['require_parent'] = $targets;
							break;

						default:
							$bbcode[$action] = array_unique($targets);
					}
				}
			}

			if ($bbcode['default_rule'] === 'allow')
			{
				$allow += array_fill_keys($bbcodeIds, true);
			}

			/**
			* Keep only the BBCodes that are allowed
			*/
			$bbcode['allow'] = array_filter($allow);

			if (isset($bbcode['default_param'])
			 && !isset($bbcode['params'][$bbcode['default_param']]))
			{
				trigger_error("Skipping unknown BBCode param '" . $bbcode['default_param'] . "'", E_USER_NOTICE);
				// @codeCoverageIgnoreStart
			}
			// @codeCoverageIgnoreEnd

			unset($bbcode['tpl']);
		}
		unset($bbcode);

		$aliases = array();
		foreach ($this->bbcodeAliases as $alias => $bbcodeId)
		{
			if (empty($this->bbcodes[$bbcodeId]['internal_use']))
			{
				$aliases[] = $alias;
			}
		}

		$regexp = self::buildRegexpFromList($aliases);
		$config['regexp'] =
			'#\\[/?(' . preg_replace('#^\\(\\?:(.*)\\)$#D', '$1', $regexp) . ')(?=[\\] =:/])#i';

		return $config;
	}

	public function getDefaultBBCodeOptions()
	{
		return array(
			'tag_limit'        => 100,
			'nesting_limit'    => 10,
			'default_rule'     => 'allow',
			'content_as_param' => false
		);
	}

	public function setBBCodeTemplate($bbcodeId, $tpl, $flags = 0)
	{
		$bbcodeId = $this->normalizeBBCodeId($bbcodeId);
		if (!isset($this->bbcodes[$bbcodeId]))
		{
			throw new InvalidArgumentException("Unknown BBCode '" . $bbcodeId . "'");
		}

		if (!($flags & self::PRESERVE_WHITESPACE))
		{
			// Remove whitespace containing newlines from the template
			$tpl = trim(preg_replace('#>\\s*\\n\\s*<#', '><', $tpl));
		}

		$tpl = '<xsl:template match="' . $bbcodeId . '">'
		     . $tpl
		     . '</xsl:template>';

		$xsl = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $tpl
		     . '</xsl:stylesheet>';

		$old = libxml_use_internal_errors(true);
		$dom = new DOMDocument;
		$res = $dom->loadXML($xsl);
		libxml_use_internal_errors($old);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new InvalidArgumentException('Invalid XML - error was: ' . $error->message);
		}

		if (!($flags & self::ALLOW_INSECURE_TEMPLATES))
		{
			$xpath = new DOMXPath($dom);

			if ($xpath->evaluate('count(//script[contains(@src, "{") or .//xsl:value-of or xsl:attribute])'))
			{
				throw new RuntimeException('It seems that your template contains a <script> tag that uses user-supplied information. Those can be insecure and are disabled by default. Please pass ' . __CLASS__ . '::ALLOW_INSECURE_TEMPLATES as a third parameter to setBBCodeTemplate() to enable it');
			}
		}

		/**
		* Strip the whitespace off that template, except in <xsl:text/> elements
		*/
		$this->bbcodes[$bbcodeId]['tpl'] = $tpl;
	}

	public function addBBCodeFromExample($def, $tpl, $flags = 0)
	{
		$p = $this->parseBBCodeDefinition($def);

		if ($p === false)
		{
			throw new InvalidArgumentException('Cannot interpret the BBCode definition');
		}

		/**
		* Generate a random tag name so that the user cannot inject stuff outside of that template.
		* For instance, if the tag was <t>, one could input </t><xsl:evil-stuff/><t>
		*/
		$t = 't' . md5(microtime(true) . mt_rand());

		$old = libxml_use_internal_errors(true);
		$dom = new DOMDocument;
		$res = $dom->loadXML('<' . $t . '>' . $tpl . '</' . $t . '>');
		libxml_use_internal_errors($old);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new InvalidArgumentException('Invalid XML in template - error was: ' . $error->message);
		}

		$bbcodeId     = $p['bbcodeId'];
		$params       = $p['params'];
		$placeholders = $p['placeholders'];
		$options      = $p['options'];

		/**
		* Replace placeholders in attributes
		*/
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attr)
		{
			$attr->value = preg_replace_callback(
				'#\\{[A-Z]+[0-9]*?\\}#',
				function ($m) use ($placeholders, &$params, $flags)
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

					$param = substr($placeholders[$identifier], 1);
					if (isset($params[$param]))
					{
						$params[$param]['is_required'] = true;
					}

					return '{' . $placeholders[$identifier] . '}';
				},
				$attr->value
			);
		}

		/**
		* Replace placeholders everywhere else: the lazy version
		*/
		$tpl = preg_replace_callback(
			'#\\{[A-Z]+[0-9]*\\}#',
			function ($m) use ($placeholders, &$params)
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
			substr(trim($dom->saveXML($dom->documentElement)), 35, -36)
		);

		$this->addBBCode($bbcodeId, $options);
		foreach ($params as $paramName => $paramConf)
		{
			$this->addBBCodeParam(
				$bbcodeId,
				$paramName,
				$paramConf['type'],
				$paramConf
			);
		}
		$this->setBBCodeTemplate($bbcodeId, $tpl, $flags);
	}

	public function parseBBCodeDefinition($def)
	{
		$bbcodeId    = '[a-zA-Z_][a-zA-Z_0-9]*';
		$placeholder = '\\{(?:REGEXP[0-9]*:/[^/]+/i?|[A-Z_]+[0-9]*)\\}';
		$param       = '[a-zA-Z_][a-zA-Z_0-9]*';

		$regexp = '#'
		        // [(BBCODE)(=paramval)?
		        . '\\[(' . $bbcodeId . ')(=' . $placeholder . ')?'
		        // (foo=fooval bar=barval)
		        . '((?:\\s+' . $param . '=' . $placeholder . ')*)'
		        // ]({TEXT})[/BBCODE]
		        . '(?:\\s*/\\]|\\](' . $placeholder . ')?\\[/\\1])'
		        . '$#D';

		if (!preg_match($regexp, trim($def), $m))
		{
			return false;
		}

		$bbcodeId     = $m[1];
		$options      = array();
		$params       = array();
		$placeholders = array();

		/**
		* If we have a default param in $m[2], we prepend the definition to the attribute pairs.
		* e.g. [a href={URL}]           => $attrs = "href={URL}"
		*      [url={URL} title={TEXT}] => $attrs = "url={URL} title={TEXT}"
		*/
		$attrs = ($m[2]) ? $m[1] . $m[2] . $m[3] : $m[3];

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
		if (isset($m[4]))
		{
			// TEXT or TEXT1
			$identifier = substr($m[4], 1, -1);

			if (preg_match('#^TEXT[0-9]*$#D', $identifier))
			{
				/**
				* Use substring() to exclude the <st/> and <et/> children
				*/
				$placeholders[$identifier] =
					'substring(., 1 + string-length(st), string-length() - (string-length(st) + string-length(et)))';
			}
			else
			{
				/**
				* We need to validate the content, means we should probably disable BBCodes,
				* e.g. [email]{EMAIL}[/email]
				*/
				$param = strtolower($bbcodeId);

				$options['default_rule']     = 'deny';
				$options['default_param']    = $param;
				$options['content_as_param'] = true;

				/**
				* We append the placeholder to the attributes, using the BBCode's name as param name
				*/
				$attrs .= ' ' . $param . '={' . $identifier . '}';
			}
		}

		foreach (preg_split('#\\s+#', $attrs, null, \PREG_SPLIT_NO_EMPTY) as $pair)
		{
			list($paramName, $identifier) = explode('=', $pair);

			/**
			* Normalize the param name, remove the braces around the identifier
			*/
			$paramName  = strtolower($paramName);
			$identifier = substr($identifier, 1, -1);

			if (isset($params[$paramName]))
			{
				throw new InvalidArgumentException('Param ' . $paramName . ' is defined twice');
			}

			$paramConf = array(
				'is_required' => false
			);

			if (preg_match('#^(REGEXP[0-9]*):(/[^/]+/i?)$#D', $identifier, $m))
			{
				$identifier = $m[1];

				$paramConf['type']   = 'regexp';
				$paramConf['regexp'] = $m[2];
			}
			else
			{
				$paramConf['type'] = rtrim(strtolower($identifier), '1234567890');
			}

			if (isset($placeholders[$identifier]))
			{
				throw new InvalidArgumentException('Placeholder ' . $identifier . ' is used twice');
			}

			$placeholders[$identifier] = '@' . $paramName;

			$params[$paramName] = $paramConf;
		}

		return array(
			'bbcodeId'     => $bbcodeId,
			'options'      => $options,
			'params'       => $params,
			'placeholders' => $placeholders
		);
	}

	protected function addInternalBBCode($prefix)
	{
		$prefix   = strtoupper($prefix);
		$bbcodeId = $prefix;
		$i        = 0;

		while (isset($this->bbcodes[$bbcodeId]) || isset($this->aliases[$bbcodeId]))
		{
			$bbcodeId = $prefix . $i;
			++$i;
		}

		$this->addBBCode($bbcodeId, array('internal_use' => true));

		return $bbcodeId;
	}

	/**
	* Takes a lowercased BBCode name and return a canonical BBCode ID with aliases resolved
	*
	* @param  string $bbcodeId BBCode name
	* @return string            BBCode ID, uppercased and with with aliases resolved
	*/
	protected function normalizeBBCodeId($bbcodeId)
	{
		$bbcodeId = strtoupper($bbcodeId);

		return (isset($this->bbcodeAliases[$bbcodeId])) ? $this->bbcodeAliases[$bbcodeId] : $bbcodeId;
	}

	//==========================================================================
	// Censor
	//==========================================================================

	public function setCensorOption($k, $v)
	{
		$this->setOption('Censor', $k, $v);
	}

	public function addCensor($word, $replacement = null)
	{
		/**
		* 0 00 word
		* 1 01 word*
		* 2 10 *word
		* 3 11 *word*
		*/
		$k = (($word[0] === '*') << 1) + (substr($word, -1) === '*');

		/**
		* Remove leading and trailing asterisks
		*/
		$word = trim($word, '*');
		$this->censor['words'][$k][] = $word;

		if (isset($replacement))
		{
			$mask = (($k & 2) ? '#' : '#^')
			      . str_replace('\\*', '.*', preg_quote($word, '#'))
			      . (($k & 1) ? '#i' : '$#iD');

			if (preg_match('#[\\x80-\\xFF]#', $word))
			{
				/**
				* Non-ASCII characters get the Unicode treatment
				*/
				$mask .= 'u';
			}

			$this->censor['replacements'][$k][$mask] = $replacement;
		}
	}

	public function getCensorConfig()
	{
		if (empty($this->censor))
		{
			return false;
		}
		
		if (!isset($this->passes['Censor']['bbcode'], $this->passes['Censor']['param']))
		{
			$bbcodeId = $this->addInternalBBCode('C');

			$this->addBBCodeParam($bbcodeId, 'with', 'text', array('is_required' => false));

			$this->setCensorOption('bbcode', $bbcodeId);
			$this->setCensorOption('param', 'with');
		}

		$config   = $this->passes['Censor'];
		$bbcodeId = $config['bbcode'];

		if (!isset($this->bbcodes[$bbcodeId]['tpl']))
		{
			$this->setBBCodeTemplate(
				$bbcodeId,
				'<xsl:choose><xsl:when test="@with"><xsl:value-of select="@with"/></xsl:when><xsl:otherwise>' . htmlspecialchars($config['default_replacement']) . '</xsl:otherwise></xsl:choose>'
			);
		}

		foreach ($this->censor['words'] as $k => $words)
		{
			$regexp = self::buildRegexpFromList($words, array('*' => '\\pL*'));

			$config['regexp'][$k] = (($k & 2) ? '#\\pL*?' : '#\\b')
			                      . $regexp
			                      . (($k & 1) ? '\\pL*#i' : '\\b#i')
			                      . 'u';
		}

		if (isset($this->censor['replacements']))
		{
			$config['replacements'] = $this->censor['replacements'];
		}

		unset($config['default_replacement']);

		return $config;
	}

	//==========================================================================
	// Emoticons
	//==========================================================================

	/**
	* Add an emoticon
	*
	* @param string $code Emoticon code
	* @param string $tpl  Emoticon template, e.g. <img src="emot.png"/> -- must be well-formed XML
	*/
	public function addEmoticon($code, $tpl)
	{
		$this->emoticons[$code] = $tpl;
	}

	public function setEmoticonOption($k, $v)
	{
		$this->setOption('Emoticon', $k, $v);
	}

	public function getEmoticonConfig()
	{
		if (empty($this->emoticons))
		{
			return false;
		}

		if (!isset($this->passes['Emoticon']['bbcode']))
		{
			$this->setEmoticonOption('bbcode', $this->addInternalBBCode('E'));
		}

		$config = $this->passes['Emoticon'];

		/**
		* Create a template for this BBCode.
		* If one already exists, we overwrite it. That's how we roll
		*/
		$tpls = array();
		foreach ($this->emoticons as $code => $_tpl)
		{
			$tpls[$_tpl][] = $code;
		}

		$tpl = '<xsl:choose>';
		foreach ($tpls as $_tpl => $codes)
		{
			$tpl .= '<xsl:when test=".=\'' . implode("' or .='", $codes) . '\'">'
			      . $_tpl
			      . '</xsl:when>';
		}
		$tpl .= '<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise></xsl:choose>';

		$this->setBBCodeTemplate($config['bbcode'], $tpl);

		// Non-anchored pattern, will benefit from the S modifier
		$config['regexp'] =	'#' . self::buildRegexpFromList(array_keys($this->emoticons)) . '#S';

		return $config;
	}

	//==========================================================================
	// Filters
	//==========================================================================

	/**
	* Set the filter used to validate a param type
	*
	* @param string   $type     Param type
	* @param callback $callback Callback
	* @param array    $conf     Optional config, will be appended to the param config and passed
	*                           to the callback
	*/
	public function setFilter($type, $callback, array $conf = null)
	{
		if (!is_callable($callback))
		{
			throw new InvalidArgumentException('The second argument passed to ' . __METHOD__ . ' is expected to be a valid callback');
		}

		$this->filters[$type] = array(
			'callback' => $callback
		);

		if (isset($conf))
		{
			$this->filters[$type]['conf'] = $conf;
		}
	}

	public function allowScheme($scheme)
	{
		$this->filters['url']['allowed_schemes'][] = $scheme;
	}

	public function disallowHost($host)
	{
		/**
		* Transform "*.tld" and ".tld" into the functionally equivalent "tld"
		*
		* As a side-effect, when someone bans *.example.com it also bans example.com (no subdomain)
		* but that's usually what people were trying to achieve.
		*/
		$this->filters['url']['disallowed_hosts'][]
			= preg_replace('#^\\*?\\.#', '', $host);
	}

	public function getFiltersConfig()
	{
		$filters = $this->filters;

		$filters['url']['allowed_schemes']
			= '#' . self::buildRegexpFromList($filters['url']['allowed_schemes']) . '$#ADi';

		if (isset($filters['url']['disallowed_hosts']))
		{
			$filters['url']['disallowed_hosts']
				= '#(?<![^\\.])'
				. self::buildRegexpFromList(
					$filters['url']['disallowed_hosts'],
					array('*' => '.*?')
				  )
				. '#DiS';
		}

		return $filters;
	}

	//==========================================================================
	// Misc
	//==========================================================================

	public function setOption($pass, $k, $v)
	{
		if ($k === 'bbcode' || $k === 'param')
		{
			if (!self::isValidId($v))
			{
				throw new InvalidArgumentException ("Invalid $k name '" . $v . "'");
			}

			if ($k === 'bbcode')
			{
				$v = $this->normalizeBBCodeId($v);

				if (!isset($this->bbcodes[$v]))
				{
					trigger_error('Unknown BBCode ' . $v, E_USER_NOTICE);
					// @codeCoverageIgnoreStart
				}
				// @codeCoverageIgnoreEnd
			}
			else
			{
				$v = strtolower($v);

				if (isset($this->passes[$pass]['bbcode']))
				{
					$bbcode = $this->passes[$pass]['bbcode'];

					if (!isset($this->bbcodes[$bbcode]['params'][$v]))
					{
						trigger_error('Unknown BBCode param ' . $v, E_USER_NOTICE);
						// @codeCoverageIgnoreStart
					}
					// @codeCoverageIgnoreEnd
				}
			}
		}
		$this->passes[$pass][$k] = $v;
	}

	static public function buildRegexpFromList($words, array $esc = array())
	{
		$arr = array();

		foreach ($words as $str)
		{
			if (preg_match_all('#.#us', $str, $matches))
			{
				$cur =& $arr;
				foreach ($matches[0] as $c)
				{
					if (!isset($esc[$c]))
					{
						$esc[$c] = preg_quote($c, '#');
					}

					$cur =& $cur[$esc[$c]];
				}
				$cur[''] = false;
			}
		}
		unset($cur);

		$regexp = self::buildRegexpFromTrie($arr);

		// replace (?:x)? with x?
		$regexp = preg_replace('#\\(\\?:(.)\\)\\?#us', '$1?', $regexp);

		// replace (?:x|y) with [xy]
		$regexp = preg_replace_callback(
			/**
			* Here, we only try to match single letters and numbers because trying to match escaped
			* characters is much more complicated and increases the potential of letting a bug slip
			* by unnoticed, without much gain in return. Just letters and numbers is simply safer
			*/
			'#\\(\\?:([\\pL\\pN](?:\\|[\\pL\\pN])*)\\)#u',
			function($m)
			{
				return '[' . preg_quote(str_replace('|', '', $m[1]), '#') . ']';
			},
			$regexp
		);

		return $regexp;
	}

	static protected function buildRegexpFromTrie($arr)
	{
		if (isset($arr['.*?'])
		 && $arr['.*?'] === array('' => false))
		{
			return '.*?';
		}

		$regexp = '';
		$suffix = '';
		$cnt    = 0;

		if (isset($arr['']))
		{
			unset($arr['']);

			if (empty($arr))
			{
				return '';
			}

			$suffix = '?';
			++$cnt;
		}

		$sep = '';
		foreach ($arr as $c => $sub)
		{
			$regexp .= $sep . $c . self::buildRegexpFromTrie($sub);
			$sep = '|';

			++$cnt;
		}

		if ($cnt > 1)
		{
			return '(?:' . $regexp . ')' . $suffix;
		}

		return $regexp . $suffix;
	}

	public function getParser()
	{
		if (!class_exists('Parser'))
		{
			include_once(__DIR__ . '/Parser.php');
		}
		return new Parser($this->getParserConfig());
	}

	public function getRenderer()
	{
		if (!class_exists('Renderer'))
		{
			include_once(__DIR__ . '/Renderer.php');
		}
		return new Renderer($this->getXSL());
	}

	public function getParserConfig()
	{
		$passes = array('BBCode' => null);

		foreach ($this->passes as $pass => $config)
		{
			if ($pass === 'BBCode')
			{
				// do it later
				continue;
			}

			$method = 'get' . $pass . 'Config';

			if (method_exists($this, $method))
			{
				/**
				* Finalize the config
				*/
				$config = $this->$method();

				if ($config === false)
				{
					continue;
				}
			}

			$passes[$pass] = $config;
		}

		$passes['BBCode'] = $this->getBBCodeConfig();

		return array(
			'passes'  => $passes,
			'filters' => $this->getFiltersConfig()
		);
	}

	public function getJavascriptParserConfig()
	{
		$config = $this->getParserConfig();

		$config['xsl'] = $this->getXSL();

		return json_encode($config);
	}

	static public function isValidId($id)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9]*$#Di', $id);
	}

	public function getXSL()
	{
		/**
		* Force the automatic creation of default BBCodes for Autolink/Censor/Emoticons
		*/
		$this->getParserConfig();

		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . "\n"
			 . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
			 . '<xsl:output method="xml" encoding="utf-8" />'
			 . '<xsl:template match="/m">'
			 . '<xsl:for-each select="*">'
			 . '<xsl:apply-templates />'
			 . '<xsl:if test="following-sibling::*"><xsl:value-of select="/m/@uid" /></xsl:if>'
			 . '</xsl:for-each>'
			 . '</xsl:template>';

		foreach ($this->bbcodes as $bbcodeId => $bbcode)
		{
			if (isset($bbcode['tpl']))
			{
				$xsl .= $bbcode['tpl'];
			}
		}

		$xsl .= $this->xsl;
		$xsl .= '<xsl:template match="st" /><xsl:template match="et" /><xsl:template match="i" /></xsl:stylesheet>';

		return $xsl;
	}

	public function addXSL($xsl)
	{
		$xml = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $xsl
		     . '</xsl:stylesheet>';

		$dom = new DOMDocument;

		$old = libxml_use_internal_errors(true);
		$res = $dom->loadXML($xml);
		libxml_use_internal_errors($old);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new InvalidArgumentException('Malformed XSL - error was: ' . $error->message);
		}

		$this->xsl .= $xsl;
	}
}