<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\BundleGenerator;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterCollection;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RulesHelper;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Configurator\RulesGenerator;
use s9e\TextFormatter\Configurator\TemplateChecker;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
use s9e\TextFormatter\Configurator\UrlConfig;
class Configurator implements ConfigProvider
{
	public $attributeFilters;
	public $bundleGenerator;
	public $javascript;
	public $plugins;
	public $registeredVars;
	public $rendering;
	public $rootRules;
	public $rulesGenerator;
	public $tags;
	public $templateChecker;
	public $templateNormalizer;
	public function __construct()
	{
		$this->attributeFilters   = new AttributeFilterCollection;
		$this->bundleGenerator    = new BundleGenerator($this);
		$this->plugins            = new PluginCollection($this);
		$this->registeredVars     = ['urlConfig' => new UrlConfig];
		$this->rendering          = new Rendering($this);
		$this->rootRules          = new Ruleset;
		$this->rulesGenerator     = new RulesGenerator;
		$this->tags               = new TagCollection;
		$this->templateChecker    = new TemplateChecker;
		$this->templateNormalizer = new TemplateNormalizer;
	}
	public function __get($k)
	{
		if (\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
			return (isset($this->plugins[$k]))
			     ? $this->plugins[$k]
			     : $this->plugins->load($k);
		if (isset($this->registeredVars[$k]))
			return $this->registeredVars[$k];
		throw new RuntimeException("Undefined property '" . __CLASS__ . '::$' . $k . "'");
	}
	public function __isset($k)
	{
		if (\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
			return isset($this->plugins[$k]);
		return isset($this->registeredVars[$k]);
	}
	public function __set($k, $v)
	{
		if (\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
			$this->plugins[$k] = $v;
		else
			$this->registeredVars[$k] = $v;
	}
	public function __unset($k)
	{
		if (\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
			unset($this->plugins[$k]);
		else
			unset($this->registeredVars[$k]);
	}
	public function enableJavaScript()
	{
		if (!isset($this->javascript))
			$this->javascript = new JavaScript($this);
	}
	public function finalize(array $options = [])
	{
		$return = [];
		$options += [
			'addHTML5Rules'  => \true,
			'optimizeConfig' => \true,
			'returnJS'       => isset($this->javascript),
			'returnParser'   => \true,
			'returnRenderer' => \true
		];
		if ($options['addHTML5Rules'])
			$this->addHTML5Rules($options);
		if ($options['returnRenderer'])
		{
			$renderer = $this->getRenderer();
			if (isset($options['finalizeRenderer']))
				$options['finalizeRenderer']($renderer);
			$return['renderer'] = $renderer;
		}
		if ($options['returnJS'] || $options['returnParser'])
		{
			$config = $this->asConfig();
			if ($options['returnJS'])
			{
				$jsConfig = $config;
				ConfigHelper::filterVariants($jsConfig, 'JS');
				$return['js'] = $this->javascript->getParser($jsConfig);
			}
			if ($options['returnParser'])
			{
				ConfigHelper::filterVariants($config);
				if ($options['optimizeConfig'])
					ConfigHelper::optimizeArray($config);
				$parser = new Parser($config);
				if (isset($options['finalizeParser']))
					$options['finalizeParser']($parser);
				$return['parser'] = $parser;
			}
		}
		return $return;
	}
	public function getParser()
	{
		$config = $this->asConfig();
		ConfigHelper::filterVariants($config);
		return new Parser($config);
	}
	public function getRenderer()
	{
		return $this->rendering->getRenderer();
	}
	public function loadBundle($bundleName)
	{
		if (!\preg_match('#^[A-Z][A-Za-z0-9]+$#D', $bundleName))
			throw new InvalidArgumentException("Invalid bundle name '" . $bundleName . "'");
		$className = __CLASS__ . '\\Bundles\\' . $bundleName;
		$bundle = new $className;
		$bundle->configure($this);
	}
	public function saveBundle($className, $filepath, array $options = [])
	{
		$file = "<?php\n\n" . $this->bundleGenerator->generate($className, $options);
		return (\file_put_contents($filepath, $file) !== \false);
	}
	public function addHTML5Rules(array $options = [])
	{
		$options += ['rootRules' => $this->rootRules];
		$this->plugins->finalize();
		foreach ($this->tags as $tag)
			$this->templateNormalizer->normalizeTag($tag);
		$rules = $this->rulesGenerator->getRules($this->tags, $options);
		$this->rootRules->merge($rules['root'], \false);
		foreach ($rules['tags'] as $tagName => $tagRules)
			$this->tags[$tagName]->rules->merge($tagRules, \false);
	}
	public function asConfig()
	{
		$this->plugins->finalize();
		$properties = \get_object_vars($this);
		unset($properties['attributeFilters']);
		unset($properties['bundleGenerator']);
		unset($properties['javascript']);
		unset($properties['rendering']);
		unset($properties['rulesGenerator']);
		unset($properties['registeredVars']);
		unset($properties['templateChecker']);
		unset($properties['templateNormalizer']);
		unset($properties['stylesheet']);
		$config    = ConfigHelper::toArray($properties);
		$bitfields = RulesHelper::getBitfields($this->tags, $this->rootRules);
		$config['rootContext'] = $bitfields['root'];
		$config['rootContext']['flags'] = $config['rootRules']['flags'];
		$config['registeredVars'] = ConfigHelper::toArray($this->registeredVars, \true);
		$config += [
			'plugins' => [],
			'tags'    => []
		];
		$config['tags'] = \array_intersect_key($config['tags'], $bitfields['tags']);
		foreach ($bitfields['tags'] as $tagName => $tagBitfields)
			$config['tags'][$tagName] += $tagBitfields;
		unset($config['rootRules']);
		return $config;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class BundleGenerator
{
	protected $configurator;
	public $serializer = 'serialize';
	public $unserializer = 'unserialize';
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}
	public function generate($className, array $options = [])
	{
		$options += ['autoInclude' => \true];
		$objects  = $this->configurator->finalize($options);
		$parser   = $objects['parser'];
		$renderer = $objects['renderer'];
		$namespace = '';
		if (\preg_match('#(.*)\\\\([^\\\\]+)$#', $className, $m))
		{
			$namespace = $m[1];
			$className = $m[2];
		}
		$php = [];
		$php[] = '/**';
		$php[] = '* @package   s9e\TextFormatter';
		$php[] = '* @copyright Copyright (c) 2010-2015 The s9e Authors';
		$php[] = '* @license   http://www.opensource.org/licenses/mit-license.php The MIT License';
		$php[] = '*/';
		if ($namespace)
		{
			$php[] = 'namespace ' . $namespace . ';';
			$php[] = '';
		}
		$php[] = 'abstract class ' . $className . ' extends \\s9e\\TextFormatter\\Bundle';
		$php[] = '{';
		$php[] = '	/**';
		$php[] = '	* @var s9e\\TextFormatter\\Parser Singleton instance used by parse()';
		$php[] = '	*/';
		$php[] = '	public static $parser;';
		$php[] = '';
		$php[] = '	/**';
		$php[] = '	* @var s9e\\TextFormatter\\Renderer Singleton instance used by render()';
		$php[] = '	*/';
		$php[] = '	public static $renderer;';
		$php[] = '';
		$events = [
			'beforeParse'
				=> 'Callback executed before parse(), receives the original text as argument',
			'afterParse'
				=> 'Callback executed after parse(), receives the parsed text as argument',
			'beforeRender'
				=> 'Callback executed before render(), receives the parsed text as argument',
			'afterRender'
				=> 'Callback executed after render(), receives the output as argument',
			'beforeUnparse'
				=> 'Callback executed before unparse(), receives the parsed text as argument',
			'afterUnparse'
				=> 'Callback executed after unparse(), receives the original text as argument'
		];
		foreach ($events as $eventName => $eventDesc)
			if (isset($options[$eventName]))
			{
				$php[] = '	/**';
				$php[] = '	* @var ' . $eventDesc;
				$php[] = '	*/';
				$php[] = '	public static $' . $eventName . ' = ' . \var_export($options[$eventName], \true) . ';';
				$php[] = '';
			}
		$php[] = '	/**';
		$php[] = '	* Return a new instance of s9e\\TextFormatter\\Parser';
		$php[] = '	*';
		$php[] = '	* @return s9e\\TextFormatter\\Parser';
		$php[] = '	*/';
		$php[] = '	public static function getParser()';
		$php[] = '	{';
		if (isset($options['parserSetup']))
		{
			$php[] = '		$parser = ' . $this->exportObject($parser) . ';';
			$php[] = '		' . $this->exportCallback($namespace, $options['parserSetup'], '$parser') . ';';
			$php[] = '';
			$php[] = '		return $parser;';
		}
		else
			$php[] = '		return ' . $this->exportObject($parser) . ';';
		$php[] = '	}';
		$php[] = '';
		$php[] = '	/**';
		$php[] = '	* Return a new instance of s9e\\TextFormatter\\Renderer';
		$php[] = '	*';
		$php[] = '	* @return s9e\\TextFormatter\\Renderer';
		$php[] = '	*/';
		$php[] = '	public static function getRenderer()';
		$php[] = '	{';
		if (!empty($options['autoInclude'])
		 && $this->configurator->rendering->engine instanceof PHP
		 && isset($this->configurator->rendering->engine->lastFilepath))
		{
			$className = \get_class($renderer);
			$filepath  = \realpath($this->configurator->rendering->engine->lastFilepath);
			$php[] = '		if (!class_exists(' . \var_export($className, \true) . ', false)';
			$php[] = '		 && file_exists(' . \var_export($filepath, \true) . '))';
			$php[] = '		{';
			$php[] = '			include ' . \var_export($filepath, \true) . ';';
			$php[] = '		}';
			$php[] = '';
		}
		if (isset($options['rendererSetup']))
		{
			$php[] = '		$renderer = ' . $this->exportObject($renderer) . ';';
			$php[] = '		' . $this->exportCallback($namespace, $options['rendererSetup'], '$renderer') . ';';
			$php[] = '';
			$php[] = '		return $renderer;';
		}
		else
			$php[] = '		return ' . $this->exportObject($renderer) . ';';
		$php[] = '	}';
		$php[] = '}';
		return \implode("\n", $php);
	}
	protected function exportCallback($namespace, callable $callback, $argument)
	{
		if (\is_array($callback) && \is_string($callback[0]))
			$callback = $callback[0] . '::' . $callback[1];
		if (!\is_string($callback))
			return 'call_user_func(' . \var_export($callback, \true) . ', ' . $argument . ')';
		if ($callback[0] !== '\\')
			$callback = '\\' . $callback;
		if (\substr($callback, 0, 2 + \strlen($namespace)) === '\\' . $namespace . '\\')
			$callback = \substr($callback, 2 + \strlen($namespace));
		return $callback . '(' . $argument . ')';
	}
	protected function exportObject($obj)
	{
		$str = \call_user_func($this->serializer, $obj);
		$str = \var_export($str, \true);
		return $this->unserializer . '(' . $str . ')';
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
interface ConfigProvider
{
	public function asConfig();
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMAttr;
use RuntimeException;
abstract class AVTHelper
{
	public static function parse($attrValue)
	{
		$tokens  = [];
		$attrLen = \strlen($attrValue);
		$pos = 0;
		while ($pos < $attrLen)
		{
			if ($attrValue[$pos] === '{')
			{
				if (\substr($attrValue, $pos, 2) === '{{')
				{
					$tokens[] = ['literal', '{'];
					$pos += 2;
					continue;
				}
				++$pos;
				$expr = '';
				while ($pos < $attrLen)
				{
					$spn = \strcspn($attrValue, '\'"}', $pos);
					if ($spn)
					{
						$expr .= \substr($attrValue, $pos, $spn);
						$pos += $spn;
					}
					if ($pos >= $attrLen)
						throw new RuntimeException('Unterminated XPath expression');
					$c = $attrValue[$pos];
					++$pos;
					if ($c === '}')
						break;
					$quotePos = \strpos($attrValue, $c, $pos);
					if ($quotePos === \false)
						throw new RuntimeException('Unterminated XPath expression');
					$expr .= $c . \substr($attrValue, $pos, $quotePos + 1 - $pos);
					$pos = 1 + $quotePos;
				}
				$tokens[] = ['expression', $expr];
			}
			$spn = \strcspn($attrValue, '{', $pos);
			if ($spn)
			{
				$str = \substr($attrValue, $pos, $spn);
				$str = \str_replace('}}', '}', $str);
				$tokens[] = ['literal', $str];
				$pos += $spn;
			}
		}
		return $tokens;
	}
	public static function replace(DOMAttr $attribute, callable $callback)
	{
		$tokens = self::parse($attribute->value);
		foreach ($tokens as $k => $token)
			$tokens[$k] = $callback($token);
		$attribute->value = \htmlspecialchars(self::serialize($tokens), \ENT_NOQUOTES, 'UTF-8');
	}
	public static function serialize(array $tokens)
	{
		$attrValue = '';
		foreach ($tokens as $token)
			if ($token[0] === 'literal')
				$attrValue .= \preg_replace('([{}])', '$0$0', $token[1]);
			elseif ($token[0] === 'expression')
				$attrValue .= '{' . $token[1] . '}';
			else
				throw new RuntimeException('Unknown token type');
		return $attrValue;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
abstract class ConfigHelper
{
	public static function filterVariants(&$config, $variant = \null)
	{
		foreach ($config as $name => $value)
		{
			while ($value instanceof Variant)
			{
				$value = $value->get($variant);
				if ($value === \null)
				{
					unset($config[$name]);
					continue 2;
				}
			}
			if ($value instanceof Dictionary && $variant !== 'JS')
				$value = (array) $value;
			if (\is_array($value) || $value instanceof Traversable)
				self::filterVariants($value, $variant);
			$config[$name] = $value;
		}
	}
	public static function generateQuickMatchFromList(array $strings)
	{
		foreach ($strings as $string)
		{
			$stringLen  = \strlen($string);
			$substrings = [];
			for ($len = $stringLen; $len; --$len)
			{
				$pos = $stringLen - $len;
				do
				{
					$substrings[\substr($string, $pos, $len)] = 1;
				}
				while (--$pos >= 0);
			}
			if (isset($goodStrings))
			{
				$goodStrings = \array_intersect_key($goodStrings, $substrings);
				if (empty($goodStrings))
					break;
			}
			else
				$goodStrings = $substrings;
		}
		if (empty($goodStrings))
			return \false;
		return \strval(\key($goodStrings));
	}
	public static function optimizeArray(array &$config, array &$cache = [])
	{
		foreach ($config as $k => &$v)
		{
			if (!\is_array($v))
				continue;
			self::optimizeArray($v, $cache);
			$cacheKey = \serialize($v);
			if (!isset($cache[$cacheKey]))
				$cache[$cacheKey] = $v;
			$config[$k] =& $cache[$cacheKey];
		}
		unset($v);
	}
	public static function toArray($value, $keepEmpty = \false, $keepNull = \false)
	{
		$array = [];
		foreach ($value as $k => $v)
		{
			if ($v instanceof ConfigProvider)
				$v = $v->asConfig();
			elseif ($v instanceof Traversable || \is_array($v))
				$v = self::toArray($v, $keepEmpty, $keepNull);
			elseif (\is_scalar($v) || \is_null($v))
				;
			else
			{
				$type = (\is_object($v))
				      ? 'an instance of ' . \get_class($v)
				      : 'a ' . \gettype($v);
				throw new RuntimeException('Cannot convert ' . $type . ' to array');
			}
			if (!isset($v) && !$keepNull)
				continue;
			if (!$keepEmpty && $v === [])
				continue;
			$array[$k] = $v;
		}
		return $array;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use RuntimeException;
abstract class RegexpBuilder
{
	public static function fromList(array $words, array $options = [])
	{
		if (empty($words))
			return '';
		$options += [
			'delimiter'       => '/',
			'caseInsensitive' => \false,
			'specialChars'    => [],
			'useLookahead'    => \false
		];
		if ($options['caseInsensitive'])
		{
			foreach ($words as &$word)
				$word = \strtr(
					$word,
					'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
					'abcdefghijklmnopqrstuvwxyz'
				);
			unset($word);
		}
		$words = \array_unique($words);
		\sort($words);
		$initials = [];
		$esc  = $options['specialChars'];
		$esc += [$options['delimiter'] => '\\' . $options['delimiter']];
		$esc += [
			'!' => '!',
			'-' => '-',
			':' => ':',
			'<' => '<',
			'=' => '=',
			'>' => '>',
			'}' => '}'
		];
		$splitWords = [];
		foreach ($words as $word)
		{
			if (\preg_match_all('#.#us', $word, $matches) === \false)
				throw new RuntimeException("Invalid UTF-8 string '" . $word . "'");
			$splitWord = [];
			foreach ($matches[0] as $pos => $c)
			{
				if (!isset($esc[$c]))
					$esc[$c] = \preg_quote($c);
				if ($pos === 0)
					$initials[] = $esc[$c];
				$splitWord[] = $esc[$c];
			}
			$splitWords[] = $splitWord;
		}
		$regexp = self::assemble([self::mergeChains($splitWords)]);
		if ($options['useLookahead']
		 && \count($initials) > 1
		 && $regexp[0] !== '[')
		{
			$useLookahead = \true;
			foreach ($initials as $initial)
				if (!self::canBeUsedInCharacterClass($initial))
				{
					$useLookahead = \false;
					break;
				}
			if ($useLookahead)
				$regexp = '(?=' . self::generateCharacterClass($initials) . ')' . $regexp;
		}
		return $regexp;
	}
	protected static function mergeChains(array $chains)
	{
		if (!isset($chains[1]))
			return $chains[0];
		$mergedChain = self::removeLongestCommonPrefix($chains);
		if (!isset($chains[0][0])
		 && !\array_filter($chains))
			return $mergedChain;
		$suffix = self::removeLongestCommonSuffix($chains);
		if (isset($chains[1]))
		{
			self::optimizeDotChains($chains);
			self::optimizeCatchallChains($chains);
		}
		$endOfChain = \false;
		$remerge = \false;
		$groups = [];
		foreach ($chains as $chain)
		{
			if (!isset($chain[0]))
			{
				$endOfChain = \true;
				continue;
			}
			$head = $chain[0];
			if (isset($groups[$head]))
				$remerge = \true;
			$groups[$head][] = $chain;
		}
		$characterClass = [];
		foreach ($groups as $head => $groupChains)
		{
			$head = (string) $head;
			if ($groupChains === [[$head]]
			 && self::canBeUsedInCharacterClass($head))
				$characterClass[$head] = $head;
		}
		\sort($characterClass);
		if (isset($characterClass[1]))
		{
			foreach ($characterClass as $char)
				unset($groups[$char]);
			$head = self::generateCharacterClass($characterClass);
			$groups[$head][] = [$head];
			$groups = [$head => $groups[$head]]
			        + $groups;
		}
		if ($remerge)
		{
			$mergedChains = [];
			foreach ($groups as $head => $groupChains)
				$mergedChains[] = self::mergeChains($groupChains);
			self::mergeTails($mergedChains);
			$regexp = \implode('', self::mergeChains($mergedChains));
			if ($endOfChain)
				$regexp = self::makeRegexpOptional($regexp);
			$mergedChain[] = $regexp;
		}
		else
		{
			self::mergeTails($chains);
			$mergedChain[] = self::assemble($chains);
		}
		foreach ($suffix as $atom)
			$mergedChain[] = $atom;
		return $mergedChain;
	}
	protected static function mergeTails(array &$chains)
	{
		self::mergeTailsCC($chains);
		self::mergeTailsAltern($chains);
		$chains = \array_values($chains);
	}
	protected static function mergeTailsCC(array &$chains)
	{
		$groups = [];
		foreach ($chains as $k => $chain)
			if (isset($chain[1])
			 && !isset($chain[2])
			 && self::canBeUsedInCharacterClass($chain[0]))
				$groups[$chain[1]][$k] = $chain;
		foreach ($groups as $groupChains)
		{
			if (\count($groupChains) < 2)
				continue;
			$chains = \array_diff_key($chains, $groupChains);
			$chains[] = self::mergeChains(\array_values($groupChains));
		}
	}
	protected static function mergeTailsAltern(array &$chains)
	{
		$groups = [];
		foreach ($chains as $k => $chain)
			if (!empty($chain))
			{
				$tail = \array_slice($chain, -1);
				$groups[$tail[0]][$k] = $chain;
			}
		foreach ($groups as $tail => $groupChains)
		{
			if (\count($groupChains) < 2)
				continue;
			$mergedChain = self::mergeChains(\array_values($groupChains));
			$oldLen = 0;
			foreach ($groupChains as $groupChain)
				$oldLen += \array_sum(\array_map('strlen', $groupChain));
			if ($oldLen <= \array_sum(\array_map('strlen', $mergedChain)))
				continue;
			$chains = \array_diff_key($chains, $groupChains);
			$chains[] = $mergedChain;
		}
	}
	protected static function removeLongestCommonPrefix(array &$chains)
	{
		$pLen = 0;
		while (1)
		{
			$c = \null;
			foreach ($chains as $chain)
			{
				if (!isset($chain[$pLen]))
					break 2;
				if (!isset($c))
				{
					$c = $chain[$pLen];
					continue;
				}
				if ($chain[$pLen] !== $c)
					break 2;
			}
			++$pLen;
		}
		if (!$pLen)
			return [];
		$prefix = \array_slice($chains[0], 0, $pLen);
		foreach ($chains as &$chain)
			$chain = \array_slice($chain, $pLen);
		unset($chain);
		return $prefix;
	}
	protected static function removeLongestCommonSuffix(array &$chains)
	{
		$chainsLen = \array_map('count', $chains);
		$maxLen = \min($chainsLen);
		if (\max($chainsLen) === $maxLen)
			--$maxLen;
		$sLen = 0;
		while ($sLen < $maxLen)
		{
			$c = \null;
			foreach ($chains as $k => $chain)
			{
				$pos = $chainsLen[$k] - ($sLen + 1);
				if (!isset($c))
				{
					$c = $chain[$pos];
					continue;
				}
				if ($chain[$pos] !== $c)
					break 2;
			}
			++$sLen;
		}
		if (!$sLen)
			return [];
		$suffix = \array_slice($chains[0], -$sLen);
		foreach ($chains as &$chain)
			$chain = \array_slice($chain, 0, -$sLen);
		unset($chain);
		return $suffix;
	}
	protected static function assemble(array $chains)
	{
		$endOfChain = \false;
		$regexps        = [];
		$characterClass = [];
		foreach ($chains as $chain)
		{
			if (empty($chain))
			{
				$endOfChain = \true;
				continue;
			}
			if (!isset($chain[1])
			 && self::canBeUsedInCharacterClass($chain[0]))
				$characterClass[$chain[0]] = $chain[0];
			else
				$regexps[] = \implode('', $chain);
		}
		if (!empty($characterClass))
		{
			\sort($characterClass);
			$regexp = (isset($characterClass[1]))
					? self::generateCharacterClass($characterClass)
					: $characterClass[0];
			\array_unshift($regexps, $regexp);
		}
		if (empty($regexps))
			return '';
		if (isset($regexps[1]))
		{
			$regexp = \implode('|', $regexps);
			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';
		}
		else
			$regexp = $regexps[0];
		if ($endOfChain)
			$regexp = self::makeRegexpOptional($regexp);
		return $regexp;
	}
	protected static function makeRegexpOptional($regexp)
	{
		if (\preg_match('#^\\.\\+\\??$#', $regexp))
			return \str_replace('+', '*', $regexp);
		if (\preg_match('#^(\\\\?.)((?:\\1\\?)+)$#Du', $regexp, $m))
			return $m[1] . '?' . $m[2];
		if (\preg_match('#^(?:[$^]|\\\\[bBAZzGQEK])$#', $regexp))
			return '';
		if (\preg_match('#^\\\\?.$#Dus', $regexp))
			$isAtomic = \true;
		elseif (\preg_match('#^[^[(].#s', $regexp))
			$isAtomic = \false;
		else
		{
			$def    = RegexpParser::parse('#' . $regexp . '#');
			$tokens = $def['tokens'];
			switch (\count($tokens))
			{
				case 1:
					$startPos = $tokens[0]['pos'];
					$len      = $tokens[0]['len'];
					$isAtomic = (bool) ($startPos === 0 && $len === \strlen($regexp));
					if ($isAtomic && $tokens[0]['type'] === 'characterClass')
					{
						$regexp = \rtrim($regexp, '+*?');
						if (!empty($tokens[0]['quantifiers']) && $tokens[0]['quantifiers'] !== '?')
							$regexp .= '*';
					}
					break;
				case 2:
					if ($tokens[0]['type'] === 'nonCapturingSubpatternStart'
					 && $tokens[1]['type'] === 'nonCapturingSubpatternEnd')
					{
						$startPos = $tokens[0]['pos'];
						$len      = $tokens[1]['pos'] + $tokens[1]['len'];
						$isAtomic = (bool) ($startPos === 0 && $len === \strlen($regexp));
						break;
					}
					default:
					$isAtomic = \false;
			}
		}
		if (!$isAtomic)
			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';
		$regexp .= '?';
		return $regexp;
	}
	protected static function generateCharacterClass(array $chars)
	{
		$chars = \array_flip($chars);
		$unescape = \str_split('$()*+.?[{|^', 1);
		foreach ($unescape as $c)
			if (isset($chars['\\' . $c]))
			{
				unset($chars['\\' . $c]);
				$chars[$c] = 1;
			}
		\ksort($chars);
		if (isset($chars['-']))
			$chars = ['-' => 1] + $chars;
		if (isset($chars['^']))
		{
			unset($chars['^']);
			$chars['^'] = 1;
		}
		return '[' . \implode('', \array_keys($chars)) . ']';
	}
	protected static function canBeUsedInCharacterClass($char)
	{
		if (\preg_match('#^\\\\[aefnrtdDhHsSvVwW]$#D', $char))
			return \true;
		if (\preg_match('#^\\\\[^A-Za-z0-9]$#Dus', $char))
			return \true;
		if (\preg_match('#..#Dus', $char))
			return \false;
		if (\preg_quote($char) !== $char
		 && !\preg_match('#^[-!:<=>}]$#D', $char))
			return \false;
		return \true;
	}
	protected static function optimizeDotChains(array &$chains)
	{
		$validAtoms = [
			'\\d' => 1, '\\D' => 1, '\\h' => 1, '\\H' => 1,
			'\\s' => 1, '\\S' => 1, '\\v' => 1, '\\V' => 1,
			'\\w' => 1, '\\W' => 1,
			'\\^' => 1, '\\$' => 1, '\\.' => 1, '\\?' => 1,
			'\\[' => 1, '\\]' => 1, '\\(' => 1, '\\)' => 1,
			'\\+' => 1, '\\*' => 1, '\\\\' => 1
		];
		do
		{
			$hasMoreDots = \false;
			foreach ($chains as $k1 => $dotChain)
			{
				$dotKeys = \array_keys($dotChain, '.?', \true);
				if (!empty($dotKeys))
				{
					$dotChain[$dotKeys[0]] = '.';
					$chains[$k1] = $dotChain;
					\array_splice($dotChain, $dotKeys[0], 1);
					$chains[] = $dotChain;
					if (isset($dotKeys[1]))
						$hasMoreDots = \true;
				}
			}
		}
		while ($hasMoreDots);
		foreach ($chains as $k1 => $dotChain)
		{
			$dotKeys = \array_keys($dotChain, '.', \true);
			if (empty($dotKeys))
				continue;
			foreach ($chains as $k2 => $tmpChain)
			{
				if ($k2 === $k1)
					continue;
				foreach ($dotKeys as $dotKey)
				{
					if (!isset($tmpChain[$dotKey]))
						continue 2;
					if (!\preg_match('#^.$#Du', \preg_quote($tmpChain[$dotKey]))
					 && !isset($validAtoms[$tmpChain[$dotKey]]))
						continue 2;
					$tmpChain[$dotKey] = '.';
				}
				if ($tmpChain === $dotChain)
					unset($chains[$k2]);
			}
		}
	}
	protected static function optimizeCatchallChains(array &$chains)
	{
		$precedence = [
			'.*'  => 3,
			'.*?' => 2,
			'.+'  => 1,
			'.+?' => 0
		];
		$tails = [];
		foreach ($chains as $k => $chain)
		{
			if (!isset($chain[0]))
				continue;
			$head = $chain[0];
			if (!isset($precedence[$head]))
				continue;
			$tail = \implode('', \array_slice($chain, 1));
			if (!isset($tails[$tail])
			 || $precedence[$head] > $tails[$tail]['precedence'])
				$tails[$tail] = [
					'key'        => $k,
					'precedence' => $precedence[$head]
				];
		}
		$catchallChains = [];
		foreach ($tails as $tail => $info)
			$catchallChains[$info['key']] = $chains[$info['key']];
		foreach ($catchallChains as $k1 => $catchallChain)
		{
			$headExpr = $catchallChain[0];
			$tailExpr = \false;
			$match    = \array_slice($catchallChain, 1);
			if (isset($catchallChain[1])
			 && isset($precedence[\end($catchallChain)]))
				$tailExpr = \array_pop($match);
			$matchCnt = \count($match);
			foreach ($chains as $k2 => $chain)
			{
				if ($k2 === $k1)
					continue;
				$start = 0;
				$end = \count($chain);
				if ($headExpr[1] === '+')
				{
					$found = \false;
					foreach ($chain as $start => $atom)
						if (self::matchesAtLeastOneCharacter($atom))
						{
							$found = \true;
							break;
						}
					if (!$found)
						continue;
				}
				if ($tailExpr === \false)
					$end = $start;
				else
				{
					if ($tailExpr[1] === '+')
					{
						$found = \false;
						while (--$end > $start)
							if (self::matchesAtLeastOneCharacter($chain[$end]))
							{
								$found = \true;
								break;
							}
						if (!$found)
							continue;
					}
					$end -= $matchCnt;
				}
				while ($start <= $end)
				{
					if (\array_slice($chain, $start, $matchCnt) === $match)
					{
						unset($chains[$k2]);
						break;
					}
					++$start;
				}
			}
		}
	}
	protected static function matchesAtLeastOneCharacter($expr)
	{
		if (\preg_match('#^[$*?^]$#', $expr))
			return \false;
		if (\preg_match('#^.$#u', $expr))
			return \true;
		if (\preg_match('#^.\\+#u', $expr))
			return \true;
		if (\preg_match('#^\\\\[^bBAZzGQEK1-9](?![*?])#', $expr))
			return \true;
		return \false;
	}
	protected static function canUseAtomicGrouping($expr)
	{
		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\.#', $expr))
			return \false;
		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*[+*]#', $expr))
			return \false;
		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\(?(?<!\\()\\?#', $expr))
			return \false;
		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\\\[a-z0-9]#', $expr))
			return \false;
		return \true;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
abstract class RulesHelper
{
	public static function getBitfields(TagCollection $tags, Ruleset $rootRules)
	{
		$rules = ['*root*' => \iterator_to_array($rootRules)];
		foreach ($tags as $tagName => $tag)
			$rules[$tagName] = \iterator_to_array($tag->rules);
		$matrix = self::unrollRules($rules);
		self::pruneMatrix($matrix);
		$groupedTags = [];
		foreach (\array_keys($matrix) as $tagName)
		{
			if ($tagName === '*root*')
				continue;
			$k = '';
			foreach ($matrix as $tagMatrix)
			{
				$k .= $tagMatrix['allowedChildren'][$tagName];
				$k .= $tagMatrix['allowedDescendants'][$tagName];
			}
			$groupedTags[$k][] = $tagName;
		}
		$bitTag     = [];
		$bitNumber  = 0;
		$tagsConfig = [];
		foreach ($groupedTags as $tagNames)
		{
			foreach ($tagNames as $tagName)
			{
				$tagsConfig[$tagName]['bitNumber'] = $bitNumber;
				$bitTag[$bitNumber] = $tagName;
			}
			++$bitNumber;
		}
		foreach ($matrix as $tagName => $tagMatrix)
		{
			$allowedChildren    = '';
			$allowedDescendants = '';
			foreach ($bitTag as $targetName)
			{
				$allowedChildren    .= $tagMatrix['allowedChildren'][$targetName];
				$allowedDescendants .= $tagMatrix['allowedDescendants'][$targetName];
			}
			$tagsConfig[$tagName]['allowed'] = self::pack($allowedChildren, $allowedDescendants);
		}
		$return = [
			'root' => $tagsConfig['*root*'],
			'tags' => $tagsConfig
		];
		unset($return['tags']['*root*']);
		return $return;
	}
	protected static function initMatrix(array $rules)
	{
		$matrix   = [];
		$tagNames = \array_keys($rules);
		foreach ($rules as $tagName => $tagRules)
		{
			if ($tagRules['defaultDescendantRule'] === 'allow')
			{
				$childValue      = (int) ($tagRules['defaultChildRule'] === 'allow');
				$descendantValue = 1;
			}
			else
			{
				$childValue      = 0;
				$descendantValue = 0;
			}
			$matrix[$tagName]['allowedChildren']    = \array_fill_keys($tagNames, $childValue);
			$matrix[$tagName]['allowedDescendants'] = \array_fill_keys($tagNames, $descendantValue);
		}
		return $matrix;
	}
	protected static function applyTargetedRule(array &$matrix, $rules, $ruleName, $key, $value)
	{
		foreach ($rules as $tagName => $tagRules)
		{
			if (!isset($tagRules[$ruleName]))
				continue;
			foreach ($tagRules[$ruleName] as $targetName)
				$matrix[$tagName][$key][$targetName] = $value;
		}
	}
	protected static function unrollRules(array $rules)
	{
		$matrix = self::initMatrix($rules);
		$tagNames = \array_keys($rules);
		foreach ($rules as $tagName => $tagRules)
		{
			if (!empty($tagRules['ignoreTags']))
				$rules[$tagName]['denyDescendant'] = $tagNames;
			if (!empty($tagRules['requireParent']))
			{
				$denyParents = \array_diff($tagNames, $tagRules['requireParent']);
				foreach ($denyParents as $parentName)
					$rules[$parentName]['denyChild'][] = $tagName;
			}
		}
		self::applyTargetedRule($matrix, $rules, 'allowChild',      'allowedChildren',    1);
		self::applyTargetedRule($matrix, $rules, 'allowDescendant', 'allowedChildren',    1);
		self::applyTargetedRule($matrix, $rules, 'allowDescendant', 'allowedDescendants', 1);
		self::applyTargetedRule($matrix, $rules, 'denyChild',      'allowedChildren',    0);
		self::applyTargetedRule($matrix, $rules, 'denyDescendant', 'allowedChildren',    0);
		self::applyTargetedRule($matrix, $rules, 'denyDescendant', 'allowedDescendants', 0);
		return $matrix;
	}
	protected static function pruneMatrix(array &$matrix)
	{
		$usableTags = ['*root*' => 1];
		$parentTags = $usableTags;
		do
		{
			$nextTags = [];
			foreach (\array_keys($parentTags) as $tagName)
				$nextTags += \array_filter($matrix[$tagName]['allowedChildren']);
			$parentTags  = \array_diff_key($nextTags, $usableTags);
			$parentTags  = \array_intersect_key($parentTags, $matrix);
			$usableTags += $parentTags;
		}
		while (!empty($parentTags));
		$matrix = \array_intersect_key($matrix, $usableTags);
		unset($usableTags['*root*']);
		foreach ($matrix as $tagName => &$tagMatrix)
		{
			$tagMatrix['allowedChildren']
				= \array_intersect_key($tagMatrix['allowedChildren'], $usableTags);
			$tagMatrix['allowedDescendants']
				= \array_intersect_key($tagMatrix['allowedDescendants'], $usableTags);
		}
		unset($tagMatrix);
	}
	protected static function pack($allowedChildren, $allowedDescendants)
	{
		$allowedChildren    = \str_split($allowedChildren,    8);
		$allowedDescendants = \str_split($allowedDescendants, 8);
		$allowed = [];
		foreach (\array_keys($allowedChildren) as $k)
			$allowed[] = \bindec(\sprintf(
				'%1$08s%2$08s',
				\strrev($allowedDescendants[$k]),
				\strrev($allowedChildren[$k])
			));
		return $allowed;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMDocument;
use DOMElement;
use DOMXPath;
class TemplateForensics
{
	protected $allowChildBitfield = "\0";
	protected $allowsChildElements = \true;
	protected $allowsText = \true;
	protected $contentBitfield = "\0";
	protected $denyDescendantBitfield = "\0";
	protected $dom;
	protected $hasElements = \false;
	protected $hasRootText = \false;
	protected $isBlock = \false;
	protected $isEmpty = \true;
	protected $isFormattingElement = \false;
	protected $isPassthrough = \false;
	protected $isTransparent = \false;
	protected $isVoid = \true;
	protected $leafNodes = [];
	protected $preservesNewLines = \false;
	protected $rootBitfields = [];
	protected $rootNodes = [];
	protected $xpath;
	public function __construct($template)
	{
		$this->dom   = TemplateHelper::loadTemplate($template);
		$this->xpath = new DOMXPath($this->dom);
		$this->analyseRootNodes();
		$this->analyseBranches();
		$this->analyseContent();
	}
	public function allowsChild(self $child)
	{
		if (!$this->allowsDescendant($child))
			return \false;
		foreach ($child->rootBitfields as $rootBitfield)
			if (!self::match($rootBitfield, $this->allowChildBitfield))
				return \false;
		if (!$this->allowsText && $child->hasRootText)
			return \false;
		return \true;
	}
	public function allowsDescendant(self $descendant)
	{
		if (self::match($descendant->contentBitfield, $this->denyDescendantBitfield))
			return \false;
		if (!$this->allowsChildElements && $descendant->hasElements)
			return \false;
		return \true;
	}
	public function allowsChildElements()
	{
		return $this->allowsChildElements;
	}
	public function allowsText()
	{
		return $this->allowsText;
	}
	public function closesParent(self $parent)
	{
		foreach ($this->rootNodes as $rootName)
		{
			if (empty(self::$htmlElements[$rootName]['cp']))
				continue;
			foreach ($parent->leafNodes as $leafName)
				if (\in_array($leafName, self::$htmlElements[$rootName]['cp'], \true))
					return \true;
		}
		return \false;
	}
	public function getDOM()
	{
		return $this->dom;
	}
	public function isBlock()
	{
		return $this->isBlock;
	}
	public function isFormattingElement()
	{
		return $this->isFormattingElement;
	}
	public function isEmpty()
	{
		return $this->isEmpty;
	}
	public function isPassthrough()
	{
		return $this->isPassthrough;
	}
	public function isTransparent()
	{
		return $this->isTransparent;
	}
	public function isVoid()
	{
		return $this->isVoid;
	}
	public function preservesNewLines()
	{
		return $this->preservesNewLines;
	}
	protected function analyseContent()
	{
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]';
		foreach ($this->xpath->query($query) as $node)
		{
			$this->contentBitfield |= $this->getBitfield($node->localName, 'c', $node);
			$this->hasElements = \true;
		}
		$this->isPassthrough = (bool) $this->xpath->evaluate('count(//xsl:apply-templates)');
	}
	protected function analyseRootNodes()
	{
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"][not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';
		foreach ($this->xpath->query($query) as $node)
		{
			$elName = $node->localName;
			$this->rootNodes[] = $elName;
			if (!isset(self::$htmlElements[$elName]))
				$elName = 'span';
			if ($this->hasProperty($elName, 'b', $node))
				$this->isBlock = \true;
			$this->rootBitfields[] = $this->getBitfield($elName, 'c', $node);
		}
		$predicate = '[not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';
		$predicate .= '[not(ancestor::xsl:attribute | ancestor::xsl:comment | ancestor::xsl:variable)]';
		$query = '//text()[normalize-space() != ""]' . $predicate
		       . '|//xsl:text[normalize-space() != ""]' . $predicate
		       . '|//xsl:value-of' . $predicate;
		if ($this->evaluate($query, $this->dom->documentElement))
			$this->hasRootText = \true;
	}
	protected function analyseBranches()
	{
		$branchBitfields = [];
		$isFormattingElement = \true;
		$this->isTransparent = \true;
		foreach ($this->getXSLElements('apply-templates') as $applyTemplates)
		{
			$nodes = $this->xpath->query(
				'ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]',
				$applyTemplates
			);
			$allowsChildElements = \true;
			$allowsText = \true;
			$branchBitfield = self::$htmlElements['div']['ac'];
			$isEmpty = \false;
			$isVoid = \false;
			$leafNode = \null;
			$preservesNewLines = \false;
			foreach ($nodes as $node)
			{
				$elName = $leafNode = $node->localName;
				if (!isset(self::$htmlElements[$elName]))
					$elName = 'span';
				if ($this->hasProperty($elName, 'v', $node))
					$isVoid = \true;
				if ($this->hasProperty($elName, 'e', $node))
					$isEmpty = \true;
				if (!$this->hasProperty($elName, 't', $node))
				{
					$branchBitfield = "\0";
					$this->isTransparent = \false;
				}
				if (!$this->hasProperty($elName, 'fe', $node)
				 && !$this->isFormattingSpan($node))
					$isFormattingElement = \false;
				$allowsChildElements = !$this->hasProperty($elName, 'to', $node);
				$allowsText = !$this->hasProperty($elName, 'nt', $node);
				$branchBitfield |= $this->getBitfield($elName, 'ac', $node);
				$this->denyDescendantBitfield |= $this->getBitfield($elName, 'dd', $node);
				$style = '';
				if ($this->hasProperty($elName, 'pre', $node))
					$style .= 'white-space:pre;';
				if ($node->hasAttribute('style'))
					$style .= $node->getAttribute('style') . ';';
				$attributes = $this->xpath->query('.//xsl:attribute[@name="style"]', $node);
				foreach ($attributes as $attribute)
					$style .= $attribute->textContent;
				\preg_match_all(
					'/white-space\\s*:\\s*(no|pre)/i',
					\strtolower($style),
					$matches
				);
				foreach ($matches[1] as $match)
					$preservesNewLines = ($match === 'pre');
			}
			$branchBitfields[] = $branchBitfield;
			if (isset($leafNode))
				$this->leafNodes[] = $leafNode;
			if (!$allowsChildElements)
				$this->allowsChildElements = \false;
			if (!$allowsText)
				$this->allowsText = \false;
			if (!$isEmpty)
				$this->isEmpty = \false;
			if (!$isVoid)
				$this->isVoid = \false;
			if ($preservesNewLines)
				$this->preservesNewLines = \true;
		}
		if (empty($branchBitfields))
			$this->isTransparent = \false;
		else
		{
			$this->allowChildBitfield = $branchBitfields[0];
			foreach ($branchBitfields as $branchBitfield)
				$this->allowChildBitfield &= $branchBitfield;
			if (!empty($this->leafNodes))
				$this->isFormattingElement = $isFormattingElement;
		}
	}
	protected function evaluate($query, DOMElement $node)
	{
		return $this->xpath->evaluate('boolean(' . $query . ')', $node);
	}
	protected function getXSLElements($elName)
	{
		return $this->dom->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', $elName);
	}
	protected function isFormattingSpan(DOMElement $node)
	{
		if ($node->nodeName !== 'span')
			return \false;
		if ($node->getAttribute('class') === ''
		 && $node->getAttribute('style') === '')
			return \false;
		foreach ($node->attributes as $attrName => $attribute)
			if ($attrName !== 'class' && $attrName !== 'style')
				return \false;
		return \true;
	}
	protected static $htmlElements = [
		'a'=>['c'=>"\17",'ac'=>"\0",'dd'=>"\10",'t'=>1,'fe'=>1],
		'abbr'=>['c'=>"\7",'ac'=>"\4"],
		'address'=>['c'=>"\3\10",'ac'=>"\1",'dd'=>"\100\12",'b'=>1,'cp'=>['p']],
		'area'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1],
		'article'=>['c'=>"\3\2",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'aside'=>['c'=>"\3\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1,'cp'=>['p']],
		'audio'=>['c'=>"\57",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\200\4",'ac23'=>'not(@src)','ac26'=>'@src','t'=>1],
		'b'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'base'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'bdi'=>['c'=>"\7",'ac'=>"\4"],
		'bdo'=>['c'=>"\7",'ac'=>"\4"],
		'blockquote'=>['c'=>"\3\1",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'body'=>['c'=>"\0\1\2",'ac'=>"\1",'b'=>1],
		'br'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1],
		'button'=>['c'=>"\17",'ac'=>"\4",'dd'=>"\10"],
		'canvas'=>['c'=>"\47",'ac'=>"\0",'t'=>1],
		'caption'=>['c'=>"\200",'ac'=>"\1",'dd'=>"\0\0\0\10",'b'=>1],
		'cite'=>['c'=>"\7",'ac'=>"\4"],
		'code'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'col'=>['c'=>"\0\0\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'colgroup'=>['c'=>"\200",'ac'=>"\0\0\4",'ac18'=>'not(@span)','nt'=>1,'e'=>1,'e0'=>'@span','b'=>1],
		'data'=>['c'=>"\7",'ac'=>"\4"],
		'datalist'=>['c'=>"\5",'ac'=>"\4\0\0\1"],
		'dd'=>['c'=>"\0\0\20",'ac'=>"\1",'b'=>1,'cp'=>['dd','dt']],
		'del'=>['c'=>"\5",'ac'=>"\0",'t'=>1],
		'dfn'=>['c'=>"\7\0\0\0\2",'ac'=>"\4",'dd'=>"\0\0\0\0\2"],
		'div'=>['c'=>"\3",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'dl'=>['c'=>"\3",'ac'=>"\0\40\20",'nt'=>1,'b'=>1,'cp'=>['p']],
		'dt'=>['c'=>"\0\0\20",'ac'=>"\1",'dd'=>"\100\2\1",'b'=>1,'cp'=>['dd','dt']],
		'em'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'embed'=>['c'=>"\57",'nt'=>1,'e'=>1,'v'=>1],
		'fieldset'=>['c'=>"\3\1",'ac'=>"\1\0\0\2",'b'=>1,'cp'=>['p']],
		'figcaption'=>['c'=>"\0\0\0\0\40",'ac'=>"\1",'b'=>1],
		'figure'=>['c'=>"\3\1",'ac'=>"\1\0\0\0\40",'b'=>1],
		'footer'=>['c'=>"\3\30\1",'ac'=>"\1",'dd'=>"\0\20",'b'=>1,'cp'=>['p']],
		'form'=>['c'=>"\3\0\0\0\1",'ac'=>"\1",'dd'=>"\0\0\0\0\1",'b'=>1,'cp'=>['p']],
		'h1'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h2'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h3'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h4'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h5'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h6'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'head'=>['c'=>"\0\0\2",'ac'=>"\20",'nt'=>1,'b'=>1],
		'header'=>['c'=>"\3\30\1",'ac'=>"\1",'dd'=>"\0\20",'b'=>1,'cp'=>['p']],
		'hr'=>['c'=>"\1",'nt'=>1,'e'=>1,'v'=>1,'b'=>1,'cp'=>['p']],
		'html'=>['c'=>"\0",'ac'=>"\0\0\2",'nt'=>1,'b'=>1],
		'i'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'iframe'=>['c'=>"\57",'nt'=>1,'e'=>1,'to'=>1],
		'img'=>['c'=>"\57",'c3'=>'@usemap','nt'=>1,'e'=>1,'v'=>1],
		'input'=>['c'=>"\17",'c3'=>'@type!="hidden"','c1'=>'@type!="hidden"','nt'=>1,'e'=>1,'v'=>1],
		'ins'=>['c'=>"\7",'ac'=>"\0",'t'=>1],
		'kbd'=>['c'=>"\7",'ac'=>"\4"],
		'keygen'=>['c'=>"\17",'nt'=>1,'e'=>1,'v'=>1],
		'label'=>['c'=>"\17\0\0\100",'ac'=>"\4",'dd'=>"\0\0\0\100"],
		'legend'=>['c'=>"\0\0\0\2",'ac'=>"\4",'b'=>1],
		'li'=>['c'=>"\0\0\0\0\20",'ac'=>"\1",'b'=>1,'cp'=>['li']],
		'link'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'main'=>['c'=>"\3\20\0\200",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'map'=>['c'=>"\7",'ac'=>"\0",'t'=>1],
		'mark'=>['c'=>"\7",'ac'=>"\4"],
		'meta'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'meter'=>['c'=>"\7\100\0\40",'ac'=>"\4",'dd'=>"\0\0\0\40"],
		'nav'=>['c'=>"\3\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1,'cp'=>['p']],
		'noscript'=>['c'=>"\25\0\100",'ac'=>"\0",'dd'=>"\0\0\100",'t'=>1],
		'object'=>['c'=>"\57",'c3'=>'@usemap','ac'=>"\0\0\0\20",'t'=>1],
		'ol'=>['c'=>"\3",'ac'=>"\0\40\0\0\20",'nt'=>1,'b'=>1,'cp'=>['p']],
		'optgroup'=>['c'=>"\0\200",'ac'=>"\0\40\0\1",'nt'=>1,'b'=>1,'cp'=>['optgroup','option']],
		'option'=>['c'=>"\0\200\0\1",'e'=>1,'e0'=>'@label and @value','to'=>1,'b'=>1,'cp'=>['option']],
		'output'=>['c'=>"\7",'ac'=>"\4"],
		'p'=>['c'=>"\3",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'param'=>['c'=>"\0\0\0\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'pre'=>['c'=>"\3",'ac'=>"\4",'pre'=>1,'b'=>1,'cp'=>['p']],
		'progress'=>['c'=>"\7\100\40",'ac'=>"\4",'dd'=>"\0\0\40"],
		'q'=>['c'=>"\7",'ac'=>"\4"],
		'rb'=>['c'=>"\0\4",'ac'=>"\4",'b'=>1,'cp'=>['rb','rp','rt','rtc']],
		'rp'=>['c'=>"\0\4",'ac'=>"\4",'b'=>1,'cp'=>['rb','rp','rtc']],
		'rt'=>['c'=>"\0\4\0\0\10",'ac'=>"\4",'b'=>1,'cp'=>['rb','rp','rt']],
		'rtc'=>['c'=>"\0\4",'ac'=>"\4\0\0\0\10",'b'=>1,'cp'=>['rb','rp','rt','rtc']],
		'ruby'=>['c'=>"\7",'ac'=>"\4\4"],
		's'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'samp'=>['c'=>"\7",'ac'=>"\4"],
		'script'=>['c'=>"\25\40",'e'=>1,'e0'=>'@src','to'=>1],
		'section'=>['c'=>"\3\2",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'select'=>['c'=>"\17",'ac'=>"\0\240",'nt'=>1],
		'small'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'source'=>['c'=>"\0\0\200",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'span'=>['c'=>"\7",'ac'=>"\4"],
		'strong'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'style'=>['c'=>"\20",'to'=>1,'b'=>1],
		'sub'=>['c'=>"\7",'ac'=>"\4"],
		'sup'=>['c'=>"\7",'ac'=>"\4"],
		'table'=>['c'=>"\3\0\0\10",'ac'=>"\200\40",'nt'=>1,'b'=>1,'cp'=>['p']],
		'tbody'=>['c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1,'cp'=>['tbody','tfoot','thead']],
		'td'=>['c'=>"\0\1\10",'ac'=>"\1",'b'=>1,'cp'=>['td','th']],
		'template'=>['c'=>"\25\40\4",'ac'=>"\21"],
		'textarea'=>['c'=>"\17",'pre'=>1],
		'tfoot'=>['c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1,'cp'=>['tbody','thead']],
		'th'=>['c'=>"\0\0\10",'ac'=>"\1",'dd'=>"\100\2\1",'b'=>1,'cp'=>['td','th']],
		'thead'=>['c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1],
		'time'=>['c'=>"\7",'ac'=>"\4"],
		'title'=>['c'=>"\20",'to'=>1,'b'=>1],
		'tr'=>['c'=>"\200\0\0\0\4",'ac'=>"\0\40\10",'nt'=>1,'b'=>1,'cp'=>['tr']],
		'track'=>['c'=>"\0\0\0\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'u'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'ul'=>['c'=>"\3",'ac'=>"\0\40\0\0\20",'nt'=>1,'b'=>1,'cp'=>['p']],
		'var'=>['c'=>"\7",'ac'=>"\4"],
		'video'=>['c'=>"\57",'c3'=>'@controls','ac'=>"\0\0\200\4",'ac23'=>'not(@src)','ac26'=>'@src','t'=>1],
		'wbr'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1]
	];
	protected function getBitfield($elName, $k, DOMElement $node)
	{
		if (!isset(self::$htmlElements[$elName][$k]))
			return "\0";
		$bitfield = self::$htmlElements[$elName][$k];
		foreach (\str_split($bitfield, 1) as $byteNumber => $char)
		{
			$byteValue = \ord($char);
			for ($bitNumber = 0; $bitNumber < 8; ++$bitNumber)
			{
				$bitValue = 1 << $bitNumber;
				if (!($byteValue & $bitValue))
					continue;
				$n = $byteNumber * 8 + $bitNumber;
				if (isset(self::$htmlElements[$elName][$k . $n]))
				{
					$xpath = self::$htmlElements[$elName][$k . $n];
					if (!$this->evaluate($xpath, $node))
					{
						$byteValue ^= $bitValue;
						$bitfield[$byteNumber] = \chr($byteValue);
					}
				}
			}
		}
		return $bitfield;
	}
	protected function hasProperty($elName, $propName, DOMElement $node)
	{
		if (!empty(self::$htmlElements[$elName][$propName]))
			if (!isset(self::$htmlElements[$elName][$propName . '0'])
			 || $this->evaluate(self::$htmlElements[$elName][$propName . '0'], $node))
				return \true;
		return \false;
	}
	protected static function match($bitfield1, $bitfield2)
	{
		return (\trim($bitfield1 & $bitfield2, "\0") !== '');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMAttr;
use DOMCharacterData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Exceptions\InvalidXslException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
abstract class TemplateHelper
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public static function loadTemplate($template)
	{
		$dom = new DOMDocument;
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $template . '</xsl:template>';
		$useErrors = \libxml_use_internal_errors(\true);
		$success   = $dom->loadXML($xml);
		\libxml_use_internal_errors($useErrors);
		if ($success)
			return $dom;
		$tmp = \preg_replace('(&(?![A-Za-z0-9]+;|#\\d+;|#x[A-Fa-f0-9]+;))', '&amp;', $template);
		$tmp = \preg_replace_callback(
			'(&(?!quot;|amp;|apos;|lt;|gt;)\\w+;)',
			function ($m)
			{
				return \html_entity_decode($m[0], \ENT_NOQUOTES, 'UTF-8');
			},
			$tmp
		);
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $tmp . '</xsl:template>';
		$useErrors = \libxml_use_internal_errors(\true);
		$success   = $dom->loadXML($xml);
		\libxml_use_internal_errors($useErrors);
		if ($success)
			return $dom;
		if (\strpos($template, '<xsl:') !== \false)
		{
			$error = \libxml_get_last_error();
			throw new InvalidXslException($error->message);
		}
		$html = '<html><body><div>' . $template . '</div></body></html>';
		$useErrors = \libxml_use_internal_errors(\true);
		$dom->loadHTML($html);
		\libxml_use_internal_errors($useErrors);
		$xml = self::innerXML($dom->documentElement->firstChild->firstChild);
		return self::loadTemplate($xml);
	}
	public static function saveTemplate(DOMDocument $dom)
	{
		return self::innerXML($dom->documentElement);
	}
	protected static function innerXML(DOMElement $element)
	{
		$xml = $element->ownerDocument->saveXML($element);
		$pos = 1 + \strpos($xml, '>');
		$len = \strrpos($xml, '<') - $pos;
		if ($len < 1)
			return '';
		$xml = \substr($xml, $pos, $len);
		return $xml;
	}
	public static function getParametersFromXSL($xsl)
	{
		$paramNames = [];
		$xsl = '<xsl:stylesheet xmlns:xsl="' . self::XMLNS_XSL . '"><xsl:template>'
		     . $xsl
		     . '</xsl:template></xsl:stylesheet>';
		$dom = new DOMDocument;
		$dom->loadXML($xsl);
		$xpath = new DOMXPath($dom);
		$query = '//xsl:*/@match | //xsl:*/@select | //xsl:*/@test';
		foreach ($xpath->query($query) as $attribute)
			foreach (XPathHelper::getVariables($attribute->value) as $varName)
			{
				$varQuery = 'ancestor-or-self::*/preceding-sibling::xsl:variable[@name="' . $varName . '"]';
				if (!$xpath->query($varQuery, $attribute)->length)
					$paramNames[] = $varName;
			}
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$tokens = AVTHelper::parse($attribute->value);
			foreach ($tokens as $token)
			{
				if ($token[0] !== 'expression')
					continue;
				foreach (XPathHelper::getVariables($token[1]) as $varName)
				{
					$varQuery = 'ancestor-or-self::*/preceding-sibling::xsl:variable[@name="' . $varName . '"]';
					if (!$xpath->query($varQuery, $attribute)->length)
						$paramNames[] = $varName;
				}
			}
		}
		$paramNames = \array_unique($paramNames);
		\sort($paramNames);
		return $paramNames;
	}
	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];
		foreach ($xpath->query('//@*') as $attribute)
			if (\preg_match($regexp, $attribute->name))
				$nodes[] = $attribute;
		foreach ($xpath->query('//xsl:attribute') as $attribute)
			if (\preg_match($regexp, $attribute->getAttribute('name')))
				$nodes[] = $attribute;
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');
			if (\preg_match('/^@(\\w+)$/', $expr, $m)
			 && \preg_match($regexp, $m[1]))
				$nodes[] = $node;
		}
		return $nodes;
	}
	public static function getElementsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];
		foreach ($xpath->query('//*') as $element)
			if (\preg_match($regexp, $element->localName))
				$nodes[] = $element;
		foreach ($xpath->query('//xsl:element') as $element)
			if (\preg_match($regexp, $element->getAttribute('name')))
				$nodes[] = $element;
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');
			if (\preg_match('/^\\w+$/', $expr)
			 && \preg_match($regexp, $expr))
				$nodes[] = $node;
		}
		return $nodes;
	}
	public static function getObjectParamsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];
		foreach (self::getAttributesByRegexp($dom, $regexp) as $attribute)
			if ($attribute->nodeType === \XML_ATTRIBUTE_NODE)
			{
				if (\strtolower($attribute->parentNode->localName) === 'embed')
					$nodes[] = $attribute;
			}
			elseif ($xpath->evaluate('ancestor::embed', $attribute))
				$nodes[] = $attribute;
		foreach ($dom->getElementsByTagName('object') as $object)
			foreach ($object->getElementsByTagName('param') as $param)
				if (\preg_match($regexp, $param->getAttribute('name')))
					$nodes[] = $param;
		return $nodes;
	}
	public static function getCSSNodes(DOMDocument $dom)
	{
		$regexp = '/^style$/i';
		$nodes  = \array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^style$/i')
		);
		return $nodes;
	}
	public static function getJSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?>data-s9e-livepreview-postprocess$|on)/i';
		$nodes  = \array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^script$/i')
		);
		return $nodes;
	}
	public static function getURLNodes(DOMDocument $dom)
	{
		$regexp = '/(?:^(?:background|c(?>ite|lassid|odebase)|data|href|i(?>con|tem(?>id|prop|type))|longdesc|manifest|p(?>luginspage|oster|rofile)|usemap|(?>form)?action)|src)$/i';
		$nodes  = self::getAttributesByRegexp($dom, $regexp);
		foreach (self::getObjectParamsByRegexp($dom, '/^(?:dataurl|movie)$/i') as $param)
		{
			$node = $param->getAttributeNode('value');
			if ($node)
				$nodes[] = $node;
		}
		return $nodes;
	}
	public static function replaceTokens($template, $regexp, $fn)
	{
		if ($template === '')
			return $template;
		$dom   = self::loadTemplate($template);
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attribute)
		{
			$attrValue = \preg_replace_callback(
				$regexp,
				function ($m) use ($fn, $attribute)
				{
					$replacement = $fn($m, $attribute);
					if ($replacement[0] === 'expression')
						return '{' . $replacement[1] . '}';
					elseif ($replacement[0] === 'passthrough')
						return '{.}';
					else
						return $replacement[1];
				},
				$attribute->value
			);
			$attribute->value = \htmlspecialchars($attrValue, \ENT_COMPAT, 'UTF-8');
		}
		foreach ($xpath->query('//text()') as $node)
		{
			\preg_match_all(
				$regexp,
				$node->textContent,
				$matches,
				\PREG_SET_ORDER | \PREG_OFFSET_CAPTURE
			);
			if (empty($matches))
				continue;
			$parentNode = $node->parentNode;
			$lastPos = 0;
			foreach ($matches as $m)
			{
				$pos = $m[0][1];
				if ($pos > $lastPos)
					$parentNode->insertBefore(
						$dom->createTextNode(
							\substr($node->textContent, $lastPos, $pos - $lastPos)
						),
						$node
					);
				$lastPos = $pos + \strlen($m[0][0]);
				$_m = [];
				foreach ($m as $capture)
					$_m[] = $capture[0];
				$replacement = $fn($_m, $node);
				if ($replacement[0] === 'expression')
					$parentNode
						->insertBefore(
							$dom->createElementNS(self::XMLNS_XSL, 'xsl:value-of'),
							$node
						)
						->setAttribute('select', $replacement[1]);
				elseif ($replacement[0] === 'passthrough')
					$parentNode->insertBefore(
						$dom->createElementNS(self::XMLNS_XSL, 'xsl:apply-templates'),
						$node
					);
				else
					$parentNode->insertBefore($dom->createTextNode($replacement[1]), $node);
			}
			$text = \substr($node->textContent, $lastPos);
			if ($text > '')
				$parentNode->insertBefore($dom->createTextNode($text), $node);
			$parentNode->removeChild($node);
		}
		return self::saveTemplate($dom);
	}
	public static function highlightNode(DOMNode $node, $prepend, $append)
	{
		$uniqid = \uniqid('_');
		if ($node instanceof DOMAttr)
			$node->value .= $uniqid;
		elseif ($node instanceof DOMElement)
			$node->setAttribute($uniqid, '');
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
			$node->data .= $uniqid;
		$dom = $node->ownerDocument;
		$dom->formatOutput = \true;
		$docXml = self::innerXML($dom->documentElement);
		$docXml = \trim(\str_replace("\n  ", "\n", $docXml));
		$nodeHtml = \htmlspecialchars(\trim($dom->saveXML($node)));
		$docHtml  = \htmlspecialchars($docXml);
		$html = \str_replace($nodeHtml, $prepend . $nodeHtml . $append, $docHtml);
		if ($node instanceof DOMAttr)
		{
			$node->value = \substr($node->value, 0, -\strlen($uniqid));
			$html = \str_replace($uniqid, '', $html);
		}
		elseif ($node instanceof DOMElement)
		{
			$node->removeAttribute($uniqid);
			$html = \str_replace(' ' . $uniqid . '=&quot;&quot;', '', $html);
		}
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
			$html = \str_replace($uniqid, '', $html);
		}
		return $html;
	}
	public static function getMetaElementsRegexp(array $templates)
	{
		$exprs = [];
		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . \implode('', $templates) . '</xsl:template>';
		$dom = new DOMDocument;
		$dom->loadXML($xsl);
		$xpath = new DOMXPath($dom);
		$query = '//xsl:*/@*[contains("matchselectest", name())]';
		foreach ($xpath->query($query) as $attribute)
			$exprs[] = $attribute->value;
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*';
		foreach ($xpath->query($query) as $attribute)
			foreach (AVTHelper::parse($attribute->value) as $token)
				if ($token[0] === 'expression')
					$exprs[] = $token[1];
		$tagNames = [
			'e' => \true,
			'i' => \true,
			's' => \true
		];
		foreach (\array_keys($tagNames) as $tagName)
			if (isset($templates[$tagName]) && $templates[$tagName] !== '')
				unset($tagNames[$tagName]);
		$regexp = '(\\b(?<![$@])(' . \implode('|', \array_keys($tagNames)) . ')(?!-)\\b)';
		\preg_match_all($regexp, \implode("\n", $exprs), $m);
		foreach ($m[0] as $tagName)
			unset($tagNames[$tagName]);
		if (empty($tagNames))
			return '((?!))';
		return '(<' . RegexpBuilder::fromList(\array_keys($tagNames)) . '>[^<]*</[^>]+>)';
	}
	public static function replaceHomogeneousTemplates(array &$templates, $minCount = 3)
	{
		$tagNames = [];
		$expr = 'name()';
		foreach ($templates as $tagName => $template)
		{
			$elName = \strtolower(\preg_replace('/^[^:]+:/', '', $tagName));
			if ($template === '<' . $elName . '><xsl:apply-templates/></' . $elName . '>')
			{
				$tagNames[] = $tagName;
				if (\strpos($tagName, ':') !== \false)
					$expr = 'local-name()';
			}
		}
		if (\count($tagNames) < $minCount)
			return;
		$chars = \preg_replace('/[^A-Z]+/', '', \count_chars(\implode('', $tagNames), 3));
		if (\is_string($chars) && $chars !== '')
			$expr = 'translate(' . $expr . ",'" . $chars . "','" . \strtolower($chars) . "')";
		$template = '<xsl:element name="{' . $expr . '}"><xsl:apply-templates/></xsl:element>';
		foreach ($tagNames as $tagName)
			$templates[$tagName] = $template;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use RuntimeException;
abstract class XPathHelper
{
	public static function export($str)
	{
		if (\strpos($str, "'") === \false)
			return "'" . $str . "'";
		if (\strpos($str, '"') === \false)
			return '"' . $str . '"';
		$toks = [];
		$c = '"';
		$pos = 0;
		while ($pos < \strlen($str))
		{
			$spn = \strcspn($str, $c, $pos);
			if ($spn)
			{
				$toks[] = $c . \substr($str, $pos, $spn) . $c;
				$pos += $spn;
			}
			$c = ($c === '"') ? "'" : '"';
		}
		return 'concat(' . \implode(',', $toks) . ')';
	}
	public static function getVariables($expr)
	{
		$expr = \preg_replace('/(["\']).*?\\1/s', '$1$1', $expr);
		\preg_match_all('/\\$(\\w+)/', $expr, $matches);
		$varNames = \array_unique($matches[1]);
		\sort($varNames);
		return $varNames;
	}
	public static function isExpressionNumeric($expr)
	{
		$expr = \trim($expr);
		$expr = \strrev(\preg_replace('(\\((?!\\s*(?!vid(?!\\w))\\w))', '', \strrev($expr)));
		$expr = \str_replace(')', '', $expr);
		if (\preg_match('(^([$@][-\\w]++|-?\\d++)(?>\\s*(?>[-+*]|div)\\s*(?1))++$)', $expr))
			return \true;
		return \false;
	}
	public static function minify($expr)
	{
		$old     = $expr;
		$strings = [];
		$expr = \preg_replace_callback(
			'/(?:"[^"]*"|\'[^\']*\')/',
			function ($m) use (&$strings)
			{
				$uniqid = '(' . \sha1(\uniqid()) . ')';
				$strings[$uniqid] = $m[0];
				return $uniqid;
			},
			\trim($expr)
		);
		if (\preg_match('/[\'"]/', $expr))
			throw new RuntimeException("Cannot parse XPath expression '" . $old . "'");
		$expr = \preg_replace('/\\s+/', ' ', $expr);
		$expr = \preg_replace('/([-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);
		$expr = \preg_replace('/([^-a-z_0-9]) ([-a-z_0-9])/i', '$1$2', $expr);
		$expr = \preg_replace('/(?!- -)([^-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);
		$expr = \preg_replace('/ - ([a-z_0-9])/i', ' -$1', $expr);
		$expr = \strtr($expr, $strings);
		return $expr;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
class Template
{
	protected $forensics;
	protected $isNormalized = \false;
	protected $template;
	public function __construct($template)
	{
		$this->template = $template;
	}
	public function __call($methodName, $args)
	{
		return \call_user_func_array([$this->getForensics(), $methodName], $args);
	}
	public function __toString()
	{
		return $this->template;
	}
	public function asDOM()
	{
		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $this->__toString()
		     . '</xsl:template>';
		$dom = new TemplateDocument($this);
		$dom->loadXML($xml);
		return $dom;
	}
	public function getCSSNodes()
	{
		return TemplateHelper::getCSSNodes($this->asDOM());
	}
	public function getForensics()
	{
		if (!isset($this->forensics))
			$this->forensics = new TemplateForensics($this->__toString());
		return $this->forensics;
	}
	public function getJSNodes()
	{
		return TemplateHelper::getJSNodes($this->asDOM());
	}
	public function getURLNodes()
	{
		return TemplateHelper::getURLNodes($this->asDOM());
	}
	public function getParameters()
	{
		return TemplateHelper::getParametersFromXSL($this->__toString());
	}
	public function isNormalized($bool = \null)
	{
		if (isset($bool))
			$this->isNormalized = $bool;
		return $this->isNormalized;
	}
	public function normalize(TemplateNormalizer $templateNormalizer)
	{
		$this->forensics    = \null;
		$this->template     = $templateNormalizer->normalizeTemplate($this->template);
		$this->isNormalized = \true;
	}
	public function replaceTokens($regexp, $fn)
	{
		$this->forensics    = \null;
		$this->template     = TemplateHelper::replaceTokens($this->template, $regexp, $fn);
		$this->isNormalized = \false;
	}
	public function setContent($template)
	{
		$this->forensics    = \null;
		$this->template     = (string) $template;
		$this->isNormalized = \false;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
class Variant
{
	protected $defaultValue;
	protected $variants = [];
	public function __construct($value = \null, array $variants = [])
	{
		if ($value instanceof self)
		{
			$this->defaultValue = $value->defaultValue;
			$this->variants     = $value->variants;
		}
		else
			$this->defaultValue = $value;
		foreach ($variants as $k => $v)
			$this->set($k, $v);
	}
	public function __toString()
	{
		return (string) $this->defaultValue;
	}
	public function get($variant = \null)
	{
		if (isset($variant) && isset($this->variants[$variant]))
		{
			list($isDynamic, $value) = $this->variants[$variant];
			return ($isDynamic) ? $value() : $value;
		}
		return $this->defaultValue;
	}
	public function has($variant)
	{
		return isset($this->variants[$variant]);
	}
	public function set($variant, $value)
	{
		$this->variants[$variant] = [\false, $value];
	}
	public function setDynamic($variant, $callback)
	{
		if (!\is_callable($callback))
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback');
		$this->variants[$variant] = [\true, $callback];
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
class Code
{
	public $code;
	public function __construct($code)
	{
		$this->code = $code;
	}
	public function __toString()
	{
		return $this->code;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
use InvalidArgumentException;
class FunctionProvider
{
	static public $cache = [
		'addslashes'=>'function(str)
{
	return str.replace(/["\'\\\\]/g, \'\\\\$&\').replace(/\\u0000/g, \'\\\\0\');
}',
		'dechex'=>'function(str)
{
	return parseInt(str).toString(16);
}',
		'intval'=>'function(str)
{
	return parseInt(str) || 0;
}',
		'ltrim'=>'function(str)
{
	return str.replace(/^[ \\n\\r\\t\\0\\x0B]+/g, \'\');
}',
		'mb_strtolower'=>'function(str)
{
	return str.toLowerCase();
}',
		'mb_strtoupper'=>'function(str)
{
	return str.toUpperCase();
}',
		'mt_rand'=>'function(min, max)
{
	return (min + Math.floor(Math.random() * (max + 1 - min)));
}',
		'rawurlencode'=>'function(str)
{
	return encodeURIComponent(str).replace(
		/[!\'()*]/g,
		/**
		* @param {!string} c
		*/
		function(c)
		{
			return \'%\' + c.charCodeAt(0).toString(16).toUpperCase();
		}
	);
}',
		'rtrim'=>'function(str)
{
	return str.replace(/[ \\n\\r\\t\\0\\x0B]+$/g, \'\');
}',
		'str_rot13'=>'function(str)
{
	return str.replace(
		/[a-z]/gi,
		function(c)
		{
			return String.fromCharCode(c.charCodeAt(0) + ((c.toLowerCase() < \'n\') ? 13 : -13));
		}
	);
}',
		'stripslashes'=>'function(str)
{
	// NOTE: this will not correctly transform \\0 into a NULL byte. I consider this a feature
	//       rather than a bug. There\'s no reason to use NULL bytes in a text.
	return str.replace(/\\\\([\\s\\S]?)/g, \'\\\\1\');
}',
		'strrev'=>'function(str)
{
	return str.split(\'\').reverse().join(\'\');
}',
		'strtolower'=>'function(str)
{
	return str.toLowerCase();
}',
		'strtotime'=>'function(str)
{
	return Date.parse(str) / 1000;
}',
		'strtoupper'=>'function(str)
{
	return str.toUpperCase();
}',
		'trim'=>'function(str)
{
	return str.replace(/^[ \\n\\r\\t\\0\\x0B]+/g, \'\').replace(/[ \\n\\r\\t\\0\\x0B]+$/g, \'\');
}',
		'ucfirst'=>'function(str)
{
	return str.charAt(0).toUpperCase() + str.substr(1);
}',
		'ucwords'=>'function(str)
{
	return str.replace(
		/(?:^|\\s)[a-z]/g,
		function(m)
		{
			return m.toUpperCase()
		}
	);
}',
		'urlencode'=>'function(str)
{
	return encodeURIComponent(str);
}'
	];
	public static function get($funcName)
	{
		if (isset(self::$cache[$funcName]))
			return self::$cache[$funcName];
		if (\preg_match('(^[a-z_0-9]+$)D', $funcName))
		{
			$filepath = __DIR__ . '/Configurator/JavaScript/functions/' . $funcName . '.js';
			if (\file_exists($filepath))
				return \file_get_contents($filepath);
		}
		throw new InvalidArgumentException("Unknown function '" . $funcName . "'");
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
interface RendererGenerator
{
	public function getRenderer(Rendering $rendering);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\XSLT;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
class Optimizer
{
	public $normalizer;
	public function __construct()
	{
		$this->normalizer = new TemplateNormalizer;
		$this->normalizer->clear();
		$this->normalizer->append('MergeIdenticalConditionalBranches');
		$this->normalizer->append('OptimizeNestedConditionals');
	}
	public function optimizeTemplate($template)
	{
		return $this->normalizer->normalizeTemplate($template);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators\Interfaces;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
interface BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators\Interfaces;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
interface TargetedRulesGenerator
{
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use DOMElement;
use s9e\TextFormatter\Configurator\Items\Tag;
abstract class TemplateCheck
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	abstract public function check(DOMElement $template, Tag $tag);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use DOMElement;
abstract class TemplateNormalization
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public $onlyOnce = \false;
	abstract public function normalize(DOMElement $template);
	public static function lowercase($str)
	{
		return \strtr($str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;
trait CollectionProxy
{
	public function __call($methodName, $args)
	{
		return \call_user_func_array([$this->collection, $methodName], $args);
	}
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}
	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}
	public function count()
	{
		return \count($this->collection);
	}
	public function current()
	{
		return $this->collection->current();
	}
	public function key()
	{
		return $this->collection->key();
	}
	public function next()
	{
		return $this->collection->next();
	}
	public function rewind()
	{
		$this->collection->rewind();
	}
	public function valid()
	{
		return $this->collection->valid();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;
use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
trait Configurable
{
	public function __get($propName)
	{
		$methodName = 'get' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		if (!\property_exists($this, $propName))
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		return $this->$propName;
	}
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName($propValue);
			return;
		}
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;
			return;
		}
		if ($this->$propName instanceof NormalizedCollection)
		{
			if (!\is_array($propValue)
			 && !($propValue instanceof Traversable))
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");
			$this->$propName->clear();
			foreach ($propValue as $k => $v)
				$this->$propName->set($k, $v);
			return;
		}
		if (\is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . \get_class($this->$propName) . "' with instance of '" . \get_class($propValue) . "'");
		}
		else
		{
			$oldType = \gettype($this->$propName);
			$newType = \gettype($propValue);
			if ($oldType === 'boolean')
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = \false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = \true;
				}
			if ($oldType !== $newType)
			{
				$tmp = $propValue;
				\settype($tmp, $oldType);
				\settype($tmp, $newType);
				if ($tmp !== $propValue)
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);
				\settype($propValue, $oldType);
			}
		}
		$this->$propName = $propValue;
	}
	public function __isset($propName)
	{
		$methodName = 'isset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		return isset($this->$propName);
	}
	public function __unset($propName)
	{
		$methodName = 'unset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName();
			return;
		}
		if (!isset($this->$propName))
			return;
		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();
			return;
		}
		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;
trait TemplateSafeness
{
	protected $markedSafe = [];
	protected function isSafe($context)
	{
		return !empty($this->markedSafe[$context]);
	}
	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}
	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}
	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}
	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = \true;
		return $this;
	}
	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = \true;
		return $this;
	}
	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = \true;
		return $this;
	}
	public function resetSafeness()
	{
		$this->markedSafe = [];
		return $this;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;
use InvalidArgumentException;
abstract class AttributeName
{
	public static function isValid($name)
	{
		return (bool) \preg_match('#^(?!xmlns$)[a-z_][-a-z_0-9]*$#Di', $name);
	}
	public static function normalize($name)
	{
		if (!static::isValid($name))
			throw new InvalidArgumentException("Invalid attribute name '" . $name . "'");
		return \strtolower($name);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;
use InvalidArgumentException;
abstract class TagName
{
	public static function isValid($name)
	{
		return (bool) \preg_match('#^(?:(?!xmlns|xsl|s9e)[a-z_][a-z_0-9]*:)?[a-z_][-a-z_0-9]*$#Di', $name);
	}
	public static function normalize($name)
	{
		if (!static::isValid($name))
			throw new InvalidArgumentException("Invalid tag name '" . $name . "'");
		if (\strpos($name, ':') === \false)
			$name = \strtoupper($name);
		return $name;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
class Collection implements ConfigProvider, Countable, Iterator
{
	protected $items = [];
	public function clear()
	{
		$this->items = [];
	}
	public function asConfig()
	{
		return ConfigHelper::toArray($this->items, \true);
	}
	public function count()
	{
		return \count($this->items);
	}
	public function current()
	{
		return \current($this->items);
	}
	public function key()
	{
		return \key($this->items);
	}
	public function next()
	{
		return \next($this->items);
	}
	public function rewind()
	{
		\reset($this->items);
	}
	public function valid()
	{
		return (\key($this->items) !== \null);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;
class Attribute implements ConfigProvider
{
	use Configurable;
	use TemplateSafeness;
	protected $defaultValue;
	protected $filterChain;
	protected $generator;
	protected $required = \true;
	public function __construct(array $options = \null)
	{
		$this->filterChain = new AttributeFilterChain;
		if (isset($options))
			foreach ($options as $optionName => $optionValue)
				$this->__set($optionName, $optionValue);
	}
	protected function isSafe($context)
	{
		$methodName = 'isSafe' . $context;
		foreach ($this->filterChain as $filter)
			if ($filter->$methodName())
				return \true;
		return !empty($this->markedSafe[$context]);
	}
	public function setGenerator($callback)
	{
		if (!($callback instanceof ProgrammableCallback))
			$callback = new ProgrammableCallback($callback);
		$this->generator = $callback;
	}
	public function asConfig()
	{
		$vars = \get_object_vars($this);
		unset($vars['markedSafe']);
		return ConfigHelper::toArray($vars);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\FunctionProvider;
class ProgrammableCallback implements ConfigProvider
{
	protected $callback;
	protected $js = \null;
	protected $params = [];
	protected $vars = [];
	public function __construct($callback)
	{
		if (!\is_callable($callback))
			throw new InvalidArgumentException(__METHOD__ . '() expects a callback');
		if (\is_array($callback) && \is_string($callback[0]))
			$callback = $callback[0] . '::' . $callback[1];
		if (\is_string($callback))
			$callback = \ltrim($callback, '\\');
		$this->callback = $callback;
	}
	public function addParameterByValue($paramValue)
	{
		$this->params[] = $paramValue;
		return $this;
	}
	public function addParameterByName($paramName)
	{
		if (\array_key_exists($paramName, $this->params))
			throw new InvalidArgumentException("Parameter '" . $paramName . "' already exists");
		$this->params[$paramName] = \null;
		return $this;
	}
	public function getCallback()
	{
		return $this->callback;
	}
	public function getJS()
	{
		if (!isset($this->js) && \is_string($this->callback))
			try
			{
				return new Code(FunctionProvider::get($this->callback));
			}
			catch (InvalidArgumentException $e)
			{
				}
		return $this->js;
	}
	public function getVars()
	{
		return $this->vars;
	}
	public function resetParameters()
	{
		$this->params = [];
		return $this;
	}
	public function setJS($js)
	{
		if (!($js instanceof Code))
			$js = new Code($js);
		$this->js = $js;
		return $this;
	}
	public function setVar($name, $value)
	{
		$this->vars[$name] = $value;
		return $this;
	}
	public function setVars(array $vars)
	{
		$this->vars = $vars;
		return $this;
	}
	public function asConfig()
	{
		$config = ['callback' => $this->callback];
		foreach ($this->params as $k => $v)
			if (\is_numeric($k))
				$config['params'][] = $v;
			elseif (isset($this->vars[$k]))
				$config['params'][] = $this->vars[$k];
			else
				$config['params'][$k] = \null;
		if (isset($config['params']))
			$config['params'] = ConfigHelper::toArray($config['params'], \true, \true);
		$js = $this->getJS();
		if (isset($js))
		{
			$config['js'] = new Variant;
			$config['js']->set('JS', $js);
		}
		return $config;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
class Regexp implements ConfigProvider
{
	protected $isGlobal;
	protected $regexp;
	public function __construct($regexp, $isGlobal = \false)
	{
		if (@\preg_match($regexp, '') === \false)
			throw new InvalidArgumentException('Invalid regular expression ' . \var_export($regexp, \true));
		$this->regexp   = $regexp;
		$this->isGlobal = $isGlobal;
	}
	public function __toString()
	{
		return $this->regexp;
	}
	public function asConfig()
	{
		$variant = new Variant($this->regexp);
		$variant->setDynamic(
			'JS',
			function ()
			{
				return $this->toJS();
			}
		);
		return $variant;
	}
	public function toJS()
	{
		$obj = RegexpConvertor::toJS($this->regexp);
		if ($this->isGlobal)
			$obj->flags .= 'g';
		return $obj;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Collections\AttributeCollection;
use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Traits\Configurable;
class Tag implements ConfigProvider
{
	use Configurable;
	protected $attributes;
	protected $attributePreprocessors;
	protected $filterChain;
	protected $nestingLimit = 10;
	protected $rules;
	protected $tagLimit = 1000;
	protected $template;
	public function __construct(array $options = \null)
	{
		$this->attributes             = new AttributeCollection;
		$this->attributePreprocessors = new AttributePreprocessorCollection;
		$this->filterChain            = new TagFilterChain;
		$this->rules                  = new Ruleset;
		$this->filterChain->append('s9e\\TextFormatter\\Parser::executeAttributePreprocessors')
		                  ->addParameterByName('tagConfig');
		$this->filterChain->append('s9e\\TextFormatter\\Parser::filterAttributes')
		                  ->addParameterByName('tagConfig')
		                  ->addParameterByName('registeredVars')
		                  ->addParameterByName('logger');
		if (isset($options))
		{
			\ksort($options);
			foreach ($options as $optionName => $optionValue)
				$this->__set($optionName, $optionValue);
		}
	}
	public function asConfig()
	{
		$vars = \get_object_vars($this);
		unset($vars['defaultChildRule']);
		unset($vars['defaultDescendantRule']);
		unset($vars['template']);
		if (!\count($this->attributePreprocessors))
		{
			$callback = 's9e\\TextFormatter\\Parser::executeAttributePreprocessors';
			$filterChain = clone $vars['filterChain'];
			$i = \count($filterChain);
			while (--$i >= 0)
				if ($filterChain[$i]->getCallback() === $callback)
					unset($filterChain[$i]);
			$vars['filterChain'] = $filterChain;
		}
		return ConfigHelper::toArray($vars);
	}
	public function getTemplate()
	{
		return $this->template;
	}
	public function issetTemplate()
	{
		return isset($this->template);
	}
	public function setAttributePreprocessors($attributePreprocessors)
	{
		$this->attributePreprocessors->clear();
		$this->attributePreprocessors->merge($attributePreprocessors);
	}
	public function setNestingLimit($limit)
	{
		$limit = (int) $limit;
		if ($limit < 1)
			throw new InvalidArgumentException('nestingLimit must be a number greater than 0');
		$this->nestingLimit = $limit;
	}
	public function setRules($rules)
	{
		$this->rules->clear();
		$this->rules->merge($rules);
	}
	public function setTagLimit($limit)
	{
		$limit = (int) $limit;
		if ($limit < 1)
			throw new InvalidArgumentException('tagLimit must be a number greater than 0');
		$this->tagLimit = $limit;
	}
	public function setTemplate($template)
	{
		if (!($template instanceof Template))
			$template = new Template($template);
		$this->template = $template;
	}
	public function unsetTemplate()
	{
		unset($this->template);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT\Optimizer;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Renderers\XSLT as XSLTRenderer;
class XSLT implements RendererGenerator
{
	public $optimizer;
	public function __construct()
	{
		$this->optimizer = new Optimizer;
	}
	public function getRenderer(Rendering $rendering)
	{
		return new XSLTRenderer($this->getXSL($rendering));
	}
	public function getXSL(Rendering $rendering)
	{
		$groupedTemplates = [];
		$prefixes         = [];
		$templates        = $rendering->getTemplates();
		TemplateHelper::replaceHomogeneousTemplates($templates, 3);
		foreach ($templates as $tagName => $template)
		{
			$template = $this->optimizer->optimizeTemplate($template);
			$groupedTemplates[$template][] = $tagName;
			$pos = \strpos($tagName, ':');
			if ($pos !== \false)
				$prefixes[\substr($tagName, 0, $pos)] = 1;
		}
		$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';
		$prefixes = \array_keys($prefixes);
		\sort($prefixes);
		foreach ($prefixes as $prefix)
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		if (!empty($prefixes))
			$xsl .= ' exclude-result-prefixes="' . \implode(' ', $prefixes) . '"';
		$xsl .= '><xsl:output method="html" encoding="utf-8" indent="no"';
		$xsl .= '/>';
		foreach ($rendering->getAllParameters() as $paramName => $paramValue)
		{
			$xsl .= '<xsl:param name="' . \htmlspecialchars($paramName) . '"';
			if ($paramValue === '')
				$xsl .= '/>';
			else
				$xsl .= '>' . \htmlspecialchars($paramValue) . '</xsl:param>';
		}
		foreach ($groupedTemplates as $template => $tagNames)
		{
			$xsl .= '<xsl:template match="' . \implode('|', $tagNames) . '"';
			if ($template === '')
				$xsl .= '/>';
			else
				$xsl .= '>' . $template . '</xsl:template>';
		}
		$xsl .= '</xsl:stylesheet>';
		return $xsl;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ReflectionClass;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\TemplateParameterCollection;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Traits\Configurable;
class Rendering
{
	use Configurable;
	protected $configurator;
	protected $engine;
	protected $parameters;
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
		$this->parameters   = new TemplateParameterCollection;
		$this->setEngine('XSLT');
	}
	public function getAllParameters()
	{
		$params = [];
		foreach ($this->configurator->tags as $tag)
			if (isset($tag->template))
				foreach ($tag->template->getParameters() as $paramName)
					$params[$paramName] = '';
		$params = \iterator_to_array($this->parameters) + $params;
		\ksort($params);
		return $params;
	}
	public function getRenderer()
	{
		return $this->engine->getRenderer($this);
	}
	public function getTemplates()
	{
		$templates = [
			'br' => '<br/>',
			'e'  => '',
			'i'  => '',
			'p'  => '<p><xsl:apply-templates/></p>',
			's'  => ''
		];
		foreach ($this->configurator->tags as $tagName => $tag)
			if (isset($tag->template))
				$templates[$tagName] = (string) $tag->template;
		\ksort($templates);
		return $templates;
	}
	public function setEngine($engine)
	{
		if (!($engine instanceof RendererGenerator))
		{
			$className  = 's9e\\TextFormatter\\Configurator\\RendererGenerators\\' . $engine;
			$reflection = new ReflectionClass($className);
			$engine = $reflection->newInstanceArgs(\array_slice(\func_get_args(), 1));
		}
		$this->engine = $engine;
		return $engine;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ArrayAccess;
use DOMDocument;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\RulesGeneratorList;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
class RulesGenerator implements ArrayAccess, Iterator
{
	use CollectionProxy;
	protected $collection;
	public function __construct()
	{
		$this->collection = new RulesGeneratorList;
		$this->collection->append('AutoCloseIfVoid');
		$this->collection->append('AutoReopenFormattingElements');
		$this->collection->append('BlockElementsFosterFormattingElements');
		$this->collection->append('DisableAutoLineBreaksIfNewLinesArePreserved');
		$this->collection->append('EnforceContentModels');
		$this->collection->append('EnforceOptionalEndTags');
		$this->collection->append('IgnoreTagsInCode');
		$this->collection->append('IgnoreTextIfDisallowed');
		$this->collection->append('IgnoreWhitespaceAroundBlockElements');
		$this->collection->append('TrimFirstLineInCodeBlocks');
	}
	public function getRules(TagCollection $tags, array $options = [])
	{
		$parentHTML = (isset($options['parentHTML'])) ? $options['parentHTML'] : '<div>';
		$rootForensics = $this->generateRootForensics($parentHTML);
		$templateForensics = [];
		foreach ($tags as $tagName => $tag)
		{
			$template = (isset($tag->template)) ? $tag->template : '<xsl:apply-templates/>';
			$templateForensics[$tagName] = new TemplateForensics($template);
		}
		$rules = $this->generateRulesets($templateForensics, $rootForensics);
		unset($rules['root']['autoClose']);
		unset($rules['root']['autoReopen']);
		unset($rules['root']['breakParagraph']);
		unset($rules['root']['closeAncestor']);
		unset($rules['root']['closeParent']);
		unset($rules['root']['fosterParent']);
		unset($rules['root']['ignoreSurroundingWhitespace']);
		unset($rules['root']['isTransparent']);
		unset($rules['root']['requireAncestor']);
		unset($rules['root']['requireParent']);
		return $rules;
	}
	protected function generateRootForensics($html)
	{
		$dom = new DOMDocument;
		$dom->loadHTML($html);
		$body = $dom->getElementsByTagName('body')->item(0);
		$node = $body;
		while ($node->firstChild)
			$node = $node->firstChild;
		$node->appendChild($dom->createElementNS(
			'http://www.w3.org/1999/XSL/Transform',
			'xsl:apply-templates'
		));
		return new TemplateForensics($dom->saveXML($body));
	}
	protected function generateRulesets(array $templateForensics, TemplateForensics $rootForensics)
	{
		$rules = [
			'root' => $this->generateRuleset($rootForensics, $templateForensics),
			'tags' => []
		];
		foreach ($templateForensics as $tagName => $src)
			$rules['tags'][$tagName] = $this->generateRuleset($src, $templateForensics);
		return $rules;
	}
	protected function generateRuleset(TemplateForensics $src, array $targets)
	{
		$rules = [];
		foreach ($this->collection as $rulesGenerator)
		{
			if ($rulesGenerator instanceof BooleanRulesGenerator)
				foreach ($rulesGenerator->generateBooleanRules($src) as $ruleName => $bool)
					$rules[$ruleName] = $bool;
			if ($rulesGenerator instanceof TargetedRulesGenerator)
				foreach ($targets as $tagName => $trg)
					foreach ($rulesGenerator->generateTargetedRules($src, $trg) as $ruleName)
						$rules[$ruleName][] = $tagName;
		}
		return $rules;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class AutoCloseIfVoid implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isVoid()) ? ['autoClose' => \true] : [];
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class AutoReopenFormattingElements implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isFormattingElement()) ? ['autoReopen' => \true] : [];
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
class BlockElementsFosterFormattingElements implements TargetedRulesGenerator
{
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		return ($src->isBlock() && $trg->isFormattingElement()) ? ['fosterParent'] : [];
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class DisableAutoLineBreaksIfNewLinesArePreserved implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->preservesNewLines()) ? ['disableAutoLineBreaks' => \true] : [];
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
class EnforceContentModels implements BooleanRulesGenerator, TargetedRulesGenerator
{
	protected $br;
	public function __construct()
	{
		$this->br = new TemplateForensics('<br/>');
	}
	public function generateBooleanRules(TemplateForensics $src)
	{
		$rules = [];
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
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		$rules = [];
		if (!$src->allowsChild($trg))
			$rules[] = 'denyChild';
		if (!$src->allowsDescendant($trg))
			$rules[] = 'denyDescendant';
		return $rules;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
class EnforceOptionalEndTags implements TargetedRulesGenerator
{
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		return ($src->closesParent($trg)) ? ['closeParent'] : [];
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class IgnoreTagsInCode implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		$xpath = new DOMXPath($src->getDOM());
		if ($xpath->evaluate('count(//code//xsl:apply-templates)'))
			return ['ignoreTags' => \true];
		return [];
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class IgnoreTextIfDisallowed implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->allowsText()) ? [] : ['ignoreText' => \true];
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class IgnoreWhitespaceAroundBlockElements implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isBlock()) ? ['ignoreSurroundingWhitespace' => \true] : [];
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class TrimFirstLineInCodeBlocks implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		$rules = [];
		$xpath = new DOMXPath($src->getDOM());
		if ($xpath->evaluate('count(//pre//code//xsl:apply-templates)') > 0)
			$rules['trimFirstLine'] = \true;
		return $rules;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateCheckList;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowElementNS;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowXPathFunction;
use s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashScriptAccess;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
class TemplateChecker implements ArrayAccess, Iterator
{
	use CollectionProxy;
	protected $collection;
	protected $disabled = \false;
	public function __construct()
	{
		$this->collection = new TemplateCheckList;
		$this->collection->append('DisallowAttributeSets');
		$this->collection->append('DisallowCopy');
		$this->collection->append('DisallowDisableOutputEscaping');
		$this->collection->append('DisallowDynamicAttributeNames');
		$this->collection->append('DisallowDynamicElementNames');
		$this->collection->append('DisallowObjectParamsWithGeneratedName');
		$this->collection->append('DisallowPHPTags');
		$this->collection->append('DisallowUnsafeCopyOf');
		$this->collection->append('DisallowUnsafeDynamicCSS');
		$this->collection->append('DisallowUnsafeDynamicJS');
		$this->collection->append('DisallowUnsafeDynamicURL');
		$this->collection->append(new DisallowElementNS('http://icl.com/saxon', 'output'));
		$this->collection->append(new DisallowXPathFunction('document'));
		$this->collection->append(new RestrictFlashScriptAccess('sameDomain', \true));
	}
	public function checkTag(Tag $tag)
	{
		if (isset($tag->template) && !($tag->template instanceof UnsafeTemplate))
		{
			$template = (string) $tag->template;
			$this->checkTemplate($template, $tag);
		}
	}
	public function checkTemplate($template, Tag $tag = \null)
	{
		if ($this->disabled)
			return;
		if (!isset($tag))
			$tag = new Tag;
		$dom = TemplateHelper::loadTemplate($template);
		foreach ($this->collection as $check)
			$check->check($dom->documentElement, $tag);
	}
	public function disable()
	{
		$this->disabled = \true;
	}
	public function enable()
	{
		$this->disabled = \false;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
abstract class AbstractDynamicContentCheck extends TemplateCheck
{
	protected $ignoreUnknownAttributes = \false;
	abstract protected function getNodes(DOMElement $template);
	abstract protected function isSafe(Attribute $attribute);
	public function check(DOMElement $template, Tag $tag)
	{
		foreach ($this->getNodes($template) as $node)
			$this->checkNode($node, $tag);
	}
	public function detectUnknownAttributes()
	{
		$this->ignoreUnknownAttributes = \false;
	}
	public function ignoreUnknownAttributes()
	{
		$this->ignoreUnknownAttributes = \true;
	}
	protected function checkAttribute(DOMNode $node, Tag $tag, $attrName)
	{
		if (!isset($tag->attributes[$attrName]))
		{
			if ($this->ignoreUnknownAttributes)
				return;
			throw new UnsafeTemplateException("Cannot assess the safety of unknown attribute '" . $attrName . "'", $node);
		}
		if (!$this->tagFiltersAttributes($tag) || !$this->isSafe($tag->attributes[$attrName]))
			throw new UnsafeTemplateException("Attribute '" . $attrName . "' is not properly sanitized to be used in this context", $node);
	}
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		foreach (AVTHelper::parse($attribute->value) as $token)
			if ($token[0] === 'expression')
				$this->checkExpression($attribute, $token[1], $tag);
	}
	protected function checkContext(DOMNode $node)
	{
		$xpath     = new DOMXPath($node->ownerDocument);
		$ancestors = $xpath->query('ancestor::xsl:for-each', $node);
		if ($ancestors->length)
			throw new UnsafeTemplateException("Cannot assess context due to '" . $ancestors->item(0)->nodeName . "'", $node);
	}
	protected function checkCopyOfNode(DOMElement $node, Tag $tag)
	{
		$this->checkSelectNode($node->getAttributeNode('select'), $tag);
	}
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		$xpath = new DOMXPath($element->ownerDocument);
		$predicate = ($element->localName === 'attribute') ? '' : '[not(ancestor::xsl:attribute)]';
		$query = './/xsl:value-of' . $predicate;
		foreach ($xpath->query($query, $element) as $valueOf)
			$this->checkSelectNode($valueOf->getAttributeNode('select'), $tag);
		$query = './/xsl:apply-templates' . $predicate;
		foreach ($xpath->query($query, $element) as $applyTemplates)
			throw new UnsafeTemplateException('Cannot allow unfiltered data in this context', $applyTemplates);
	}
	protected function checkExpression(DOMNode $node, $expr, Tag $tag)
	{
		$this->checkContext($node);
		if (\preg_match('/^\\$(\\w+)$/', $expr, $m))
		{
			$this->checkVariable($node, $tag, $m[1]);
			return;
		}
		if ($this->isExpressionSafe($expr))
			return;
		if (\preg_match('/^@(\\w+)$/', $expr, $m))
		{
			$this->checkAttribute($node, $tag, $m[1]);
			return;
		}
		throw new UnsafeTemplateException("Cannot assess the safety of expression '" . $expr . "'", $node);
	}
	protected function checkNode(DOMNode $node, Tag $tag)
	{
		if ($node instanceof DOMAttr)
			$this->checkAttributeNode($node, $tag);
		elseif ($node instanceof DOMElement)
			if ($node->namespaceURI === self::XMLNS_XSL
			 && $node->localName    === 'copy-of')
				$this->checkCopyOfNode($node, $tag);
			else
				$this->checkElementNode($node, $tag);
	}
	protected function checkVariable(DOMNode $node, $tag, $qname)
	{
		$this->checkVariableDeclaration($node, $tag, 'xsl:param[@name="' . $qname . '"]');
		$this->checkVariableDeclaration($node, $tag, 'xsl:variable[@name="' . $qname . '"]');
	}
	protected function checkVariableDeclaration(DOMNode $node, $tag, $query)
	{
		$query = 'ancestor-or-self::*/preceding-sibling::' . $query . '[@select]';
		$xpath = new DOMXPath($node->ownerDocument);
		foreach ($xpath->query($query, $node) as $varNode)
		{
			try
			{
				$this->checkExpression($varNode, $varNode->getAttribute('select'), $tag);
			}
			catch (UnsafeTemplateException $e)
			{
				$e->setNode($node);
				throw $e;
			}
		}
	}
	protected function checkSelectNode(DOMAttr $select, Tag $tag)
	{
		$this->checkExpression($select, $select->value, $tag);
	}
	protected function isExpressionSafe($expr)
	{
		return \false;
	}
	protected function tagFiltersAttributes(Tag $tag)
	{
		return $tag->filterChain->containsCallback('s9e\\TextFormatter\\Parser::filterAttributes');
	}
}

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
		$nodes = [];
		foreach ($this->template->ownerDocument->getElementsByTagName($tagName) as $node)
			if (!$this->onlyIfDynamic || $this->isDynamic($node))
				$nodes[] = $node;
		return $nodes;
	}
	protected function getObjectParams(DOMElement $object)
	{
		$params      = [];
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

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowAttributeSets extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$nodes = $xpath->query('//@use-attribute-sets');
		if ($nodes->length)
			throw new UnsafeTemplateException('Cannot assess the safety of attribute sets', $nodes->item(0));
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowCopy extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'copy');
		$node  = $nodes->item(0);
		if ($node)
			throw new UnsafeTemplateException("Cannot assess the safety of an '" . $node->nodeName . "' element", $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowDisableOutputEscaping extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$node  = $xpath->query('//@disable-output-escaping')->item(0);
		if ($node)
			throw new UnsafeTemplateException("The template contains a 'disable-output-escaping' attribute", $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowDynamicAttributeNames extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute');
		foreach ($nodes as $node)
			if (\strpos($node->getAttribute('name'), '{') !== \false)
				throw new UnsafeTemplateException('Dynamic <xsl:attribute/> names are disallowed', $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowDynamicElementNames extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'element');
		foreach ($nodes as $node)
			if (\strpos($node->getAttribute('name'), '{') !== \false)
				throw new UnsafeTemplateException('Dynamic <xsl:element/> names are disallowed', $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowElementNS extends TemplateCheck
{
	public $elName;
	public $namespaceURI;
	public function __construct($namespaceURI, $elName)
	{
		$this->namespaceURI  = $namespaceURI;
		$this->elName        = $elName;
	}
	public function check(DOMElement $template, Tag $tag)
	{
		$node = $template->getElementsByTagNameNS($this->namespaceURI, $this->elName)->item(0);
		if ($node)
			throw new UnsafeTemplateException("Element '" . $node->nodeName . "' is disallowed", $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowObjectParamsWithGeneratedName extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//object//param[contains(@name, "{") or .//xsl:attribute[translate(@name, "NAME", "name") = "name"]]';
		$nodes = $xpath->query($query);
		foreach ($nodes as $node)
			throw new UnsafeTemplateException("A 'param' element with a suspect name has been found", $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowPHPTags extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$queries = [
			'//processing-instruction()["php" = translate(name(),"HP","hp")]'
				=> 'PHP tags are not allowed in the template',
			'//script["php" = translate(@language,"HP","hp")]'
				=> 'PHP tags are not allowed in the template',
			'//xsl:processing-instruction["php" = translate(@name,"HP","hp")]'
				=> 'PHP tags are not allowed in the output',
			'//xsl:processing-instruction[contains(@name, "{")]'
				=> 'Dynamic processing instructions are not allowed',
		];
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($queries as $query => $error)
		{
			$nodes = $xpath->query($query); 
			if ($nodes->length)
				throw new UnsafeTemplateException($error, $nodes->item(0));
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowUnsafeCopyOf extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'copy-of');
		foreach ($nodes as $node)
		{
			$expr = $node->getAttribute('select');
			if (!\preg_match('#^@[-\\w]*$#D', $expr))
				throw new UnsafeTemplateException("Cannot assess the safety of '" . $node->nodeName . "' select expression '" . $expr . "'", $node);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowXPathFunction extends TemplateCheck
{
	public $funcName;
	public function __construct($funcName)
	{
		$this->funcName = $funcName;
	}
	public function check(DOMElement $template, Tag $tag)
	{
		$regexp = '#(?!<\\pL)' . \preg_quote($this->funcName, '#') . '\\s*\\(#iu';
		$regexp = \str_replace('\\:', '\\s*:\\s*', $regexp);
		foreach ($this->getExpressions($template) as $expr => $node)
		{
			$expr = \preg_replace('#([\'"]).*?\\1#s', '', $expr);
			if (\preg_match($regexp, $expr))
				throw new UnsafeTemplateException('An XPath expression uses the ' . $this->funcName . '() function', $node);
		}
	}
	protected function getExpressions(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$exprs = [];
		foreach ($xpath->query('//@*') as $attribute)
			if ($attribute->parentNode->namespaceURI === self::XMLNS_XSL)
			{
				$expr = $attribute->value;
				$exprs[$expr] = $attribute;
			}
			else
				foreach (AVTHelper::parse($attribute->value) as $token)
					if ($token[0] === 'expression')
						$exprs[$token[1]] = $attribute;
		return $exprs;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class FixUnescapedCurlyBracesInHtmlAttributes extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
			$this->fixAttribute($attribute);
	}
	protected function fixAttribute(DOMAttr $attribute)
	{
		$parentNode = $attribute->parentNode;
		if ($parentNode->namespaceURI === self::XMLNS_XSL)
			return;
		$attribute->value = \htmlspecialchars(
			\preg_replace(
				'(\\b(?:do|else|(?:if|while)\\s*\\(.*?\\))\\s*\\{(?![{@]))',
				'$0{',
				$attribute->value
			),
			\ENT_NOQUOTES,
			'UTF-8'
		);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMException;
use DOMText;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineAttributes extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/xsl:attribute';
		foreach ($xpath->query($query) as $attribute)
			$this->inlineAttribute($attribute);
	}
	protected function inlineAttribute(DOMElement $attribute)
	{
		$value = '';
		foreach ($attribute->childNodes as $node)
			if ($node instanceof DOMText
			 || [$node->namespaceURI, $node->localName] === [self::XMLNS_XSL, 'text'])
				$value .= \preg_replace('([{}])', '$0$0', $node->textContent);
			elseif ([$node->namespaceURI, $node->localName] === [self::XMLNS_XSL, 'value-of'])
				$value .= '{' . $node->getAttribute('select') . '}';
			else
				return;
		$attribute->parentNode->setAttribute($attribute->getAttribute('name'), $value);
		$attribute->parentNode->removeChild($attribute);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineCDATA extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//text()') as $textNode)
			if ($textNode->nodeType === \XML_CDATA_SECTION_NODE)
				$textNode->parentNode->replaceChild(
					$dom->createTextNode($textNode->textContent),
					$textNode
				);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMException;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineElements extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom = $template->ownerDocument;
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'element') as $element)
		{
			$elName = $element->getAttribute('name');
			try
			{
				$newElement = ($element->hasAttribute('namespace'))
				            ? $dom->createElementNS($element->getAttribute('namespace'), $elName)
				            : $dom->createElement($elName);
			}
			catch (DOMException $e)
			{
				continue;
			}
			$element->parentNode->replaceChild($newElement, $element);
			while ($element->firstChild)
				$newElement->appendChild($element->removeChild($element->firstChild));
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineInferredValues extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:if | //xsl:when';
		foreach ($xpath->query($query) as $node)
		{
			$map = TemplateParser::parseEqualityExpr($node->getAttribute('test'));
			if ($map === \false || \count($map) !== 1 || \count($map[\key($map)]) !== 1)
				continue;
			$expr  = \key($map);
			$value = \end($map[$expr]);
			$this->inlineInferredValue($node, $expr, $value);
		}
	}
	protected function inlineInferredValue(DOMNode $node, $expr, $value)
	{
		$xpath = new DOMXPath($node->ownerDocument);
		$query = './/xsl:value-of[@select="' . $expr . '"]';
		foreach ($xpath->query($query, $node) as $valueOf)
			$this->replaceValueOf($valueOf, $value);
		$query = './/*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{' . $expr . '}")]';
		foreach ($xpath->query($query, $node) as $attribute)
			$this->replaceAttribute($attribute, $expr, $value);
	}
	protected function replaceAttribute(DOMAttr $attribute, $expr, $value)
	{
		AVTHelper::replace(
			$attribute,
			function ($token) use ($expr, $value)
			{
				if ($token[0] === 'expression' && $token[1] === $expr)
					$token = ['literal', $value];
				return $token;
			}
		);
	}
	protected function replaceValueOf(DOMElement $valueOf, $value)
	{
		$valueOf->parentNode->replaceChild(
			$valueOf->ownerDocument->createTextNode($value),
			$valueOf
		);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineTextElements extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//xsl:text') as $node)
		{
			if (\trim($node->textContent) === '')
				if ($node->previousSibling && $node->previousSibling->nodeType === \XML_TEXT_NODE)
					;
				elseif ($node->nextSibling && $node->nextSibling->nodeType === \XML_TEXT_NODE)
					;
				else
					continue;
			$node->parentNode->replaceChild(
				$dom->createTextNode($node->textContent),
				$node
			);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
class InlineXPathLiterals extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query('//xsl:value-of') as $valueOf)
		{
			$textContent = $this->getTextContent($valueOf->getAttribute('select'));
			if ($textContent !== \false)
				$this->replaceElement($valueOf, $textContent);
		}
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			AVTHelper::replace(
				$attribute,
				function ($token)
				{
					if ($token[0] === 'expression')
					{
						$textContent = $this->getTextContent($token[1]);
						if ($textContent !== \false)
							$token = ['literal', $textContent];
					}
					return $token;
				}
			);
		}
	}
	protected function getTextContent($expr)
	{
		$expr = \trim($expr);
		if (\preg_match('(^(?:\'[^\']*\'|"[^"]*")$)', $expr))
			return \substr($expr, 1, -1);
		if (\preg_match('(^0*([0-9]+)$)', $expr, $m))
			return $m[1];
		return \false;
	}
	protected function replaceElement(DOMElement $valueOf, $textContent)
	{
		$valueOf->parentNode->replaceChild(
			$valueOf->ownerDocument->createTextNode($textContent),
			$valueOf
		);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMNode;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class MergeIdenticalConditionalBranches extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'choose') as $choose)
		{
			self::mergeCompatibleBranches($choose);
			self::mergeConsecutiveBranches($choose);
		}
	}
	protected static function mergeCompatibleBranches(DOMElement $choose)
	{
		$node = $choose->firstChild;
		while ($node)
		{
			$nodes = self::collectCompatibleBranches($node);
			if (\count($nodes) > 1)
			{
				$node = \end($nodes)->nextSibling;
				self::mergeBranches($nodes);
			}
			else
				$node = $node->nextSibling;
		}
	}
	protected static function mergeConsecutiveBranches(DOMElement $choose)
	{
		$nodes = [];
		foreach ($choose->childNodes as $node)
			if (self::isXslWhen($node))
				$nodes[] = $node;
		$i = \count($nodes);
		while (--$i > 0)
			self::mergeBranches([$nodes[$i - 1], $nodes[$i]]);
	}
	protected static function collectCompatibleBranches(DOMNode $node)
	{
		$nodes  = [];
		$key    = \null;
		$values = [];
		while ($node && self::isXslWhen($node))
		{
			$branch = TemplateParser::parseEqualityExpr($node->getAttribute('test'));
			if ($branch === \false || \count($branch) !== 1)
				break;
			if (isset($key) && \key($branch) !== $key)
				break;
			if (\array_intersect($values, \end($branch)))
				break;
			$key    = \key($branch);
			$values = \array_merge($values, \end($branch));
			$nodes[] = $node;
			$node    = $node->nextSibling;
		}
		return $nodes;
	}
	protected static function mergeBranches(array $nodes)
	{
		$sortedNodes = [];
		foreach ($nodes as $node)
		{
			$outerXML = $node->ownerDocument->saveXML($node);
			$innerXML = \preg_replace('([^>]+>(.*)<[^<]+)s', '$1', $outerXML);
			$sortedNodes[$innerXML][] = $node;
		}
		foreach ($sortedNodes as $identicalNodes)
		{
			if (\count($identicalNodes) < 2)
				continue;
			$expr = [];
			foreach ($identicalNodes as $i => $node)
			{
				$expr[] = $node->getAttribute('test');
				if ($i > 0)
					$node->parentNode->removeChild($node);
			}
			$identicalNodes[0]->setAttribute('test', \implode(' or ', $expr));
		}
	}
	protected static function isXslWhen(DOMNode $node)
	{
		return ($node->namespaceURI === self::XMLNS_XSL && $node->localName === 'when');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class MinifyXPathExpressions extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:*/@*[contains(., " ")][contains("matchselectest", name())]';
		foreach ($xpath->query($query) as $attribute)
			$attribute->parentNode->setAttribute(
				$attribute->nodeName,
				XPathHelper::minify($attribute->nodeValue)
			);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., " ")]';
		foreach ($xpath->query($query) as $attribute)
		{
			AVTHelper::replace(
				$attribute,
				function ($token)
				{
					if ($token[0] === 'expression')
						$token[1] = XPathHelper::minify($token[1]);
					return $token;
				}
			);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class NormalizeAttributeNames extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query('.//@*', $template) as $attribute)
		{
			$attrName = self::lowercase($attribute->localName);
			if ($attrName !== $attribute->localName)
			{
				$attribute->parentNode->setAttribute($attrName, $attribute->value);
				$attribute->parentNode->removeAttributeNode($attribute);
			}
		}
		foreach ($xpath->query('//xsl:attribute[not(contains(@name, "{"))]') as $attribute)
		{
			$attrName = self::lowercase($attribute->getAttribute('name'));
			$attribute->setAttribute('name', $attrName);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class NormalizeElementNames extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//*[namespace-uri() != "' . self::XMLNS_XSL . '"]') as $element)
		{
			$elName = self::lowercase($element->localName);
			if ($elName === $element->localName)
				continue;
			$newElement = (\is_null($element->namespaceURI))
			            ? $dom->createElement($elName)
			            : $dom->createElementNS($element->namespaceURI, $elName);
			while ($element->firstChild)
				$newElement->appendChild($element->removeChild($element->firstChild));
			foreach ($element->attributes as $attribute)
				$newElement->setAttributeNS(
					$attribute->namespaceURI,
					$attribute->nodeName,
					$attribute->value
				);
			$element->parentNode->replaceChild($newElement, $element);
		}
		foreach ($xpath->query('//xsl:element[not(contains(@name, "{"))]') as $element)
		{
			$elName = self::lowercase($element->getAttribute('name'));
			$element->setAttribute('name', $elName);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Parser\BuiltInFilters;
class NormalizeUrls extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		foreach (TemplateHelper::getURLNodes($template->ownerDocument) as $node)
			if ($node instanceof DOMAttr)
				$this->normalizeAttribute($node);
			elseif ($node instanceof DOMElement)
				$this->normalizeElement($node);
	}
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$tokens = AVTHelper::parse(\trim($attribute->value));
		$attrValue = '';
		foreach ($tokens as $_f6b3b659)
		{
			list($type, $content) = $_f6b3b659;
			if ($type === 'literal')
				$attrValue .= BuiltInFilters::sanitizeUrl($content);
			else
				$attrValue .= '{' . $content . '}';
		}
		$attrValue = $this->unescapeBrackets($attrValue);
		$attribute->value = \htmlspecialchars($attrValue);
	}
	protected function normalizeElement(DOMElement $element)
	{
		$xpath = new DOMXPath($element->ownerDocument);
		$query = './/text()[normalize-space() != ""]';
		foreach ($xpath->query($query, $element) as $i => $node)
		{
			$value = BuiltInFilters::sanitizeUrl($node->nodeValue);
			if (!$i)
				$value = $this->unescapeBrackets(\ltrim($value));
			$node->nodeValue = $value;
		}
		if (isset($node))
			$node->nodeValue = \rtrim($node->nodeValue);
	}
	protected function unescapeBrackets($url)
	{
		return \preg_replace('#^(\\w+://)%5B([-\\w:._%]+)%5D#i', '$1[$2]', $url);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class OptimizeConditionalAttributes extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//xsl:if'
		       . "[starts-with(@test, '@')]"
		       . '[count(descendant::node()) = 2][xsl:attribute[@name = substring(../@test, 2)][xsl:value-of[@select = ../../@test]]]';
		foreach ($xpath->query($query) as $if)
		{
			$copyOf = $dom->createElementNS(self::XMLNS_XSL, 'xsl:copy-of');
			$copyOf->setAttribute('select', $if->getAttribute('test'));
			$if->parentNode->replaceChild($copyOf, $if);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class OptimizeConditionalValueOf extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:if[count(descendant::node()) = 1]/xsl:value-of';
		foreach ($xpath->query($query) as $valueOf)
		{
			$if     = $valueOf->parentNode;
			$test   = $if->getAttribute('test');
			$select = $valueOf->getAttribute('select');
			if ($select !== $test
			 || !\preg_match('#^@[-\\w]+$#D', $select))
				continue;
			$if->parentNode->replaceChild(
				$if->removeChild($valueOf),
				$if
			);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class OptimizeNestedConditionals extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:choose/xsl:otherwise[count(node()) = 1]/xsl:choose';
		foreach ($xpath->query($query) as $innerChoose)
		{
			$otherwise   = $innerChoose->parentNode;
			$outerChoose = $otherwise->parentNode;
			while ($innerChoose->firstChild)
				$outerChoose->appendChild($innerChoose->removeChild($innerChoose->firstChild));
			$outerChoose->removeChild($otherwise);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class PreserveSingleSpaces extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//text()[. = " "][not(parent::xsl:text)]';
		foreach ($xpath->query($query) as $textNode)
			$textNode->parentNode->replaceChild(
				$dom->createElementNS(self::XMLNS_XSL, 'text', ' '),
				$textNode
			);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class RemoveComments extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query('//comment()') as $comment)
			$comment->parentNode->removeChild($comment);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class RemoveInterElementWhitespace extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//text()[normalize-space() = ""][. != " "][not(parent::xsl:text)]';
		foreach ($xpath->query($query) as $textNode)
			$textNode->parentNode->removeChild($textNode);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license'); The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateNormalizationList;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
class TemplateNormalizer implements ArrayAccess, Iterator
{
	use CollectionProxy;
	protected $collection;
	public function __construct()
	{
		$this->collection = new TemplateNormalizationList;
		$this->collection->append('PreserveSingleSpaces');
		$this->collection->append('RemoveComments');
		$this->collection->append('RemoveInterElementWhitespace');
		$this->collection->append('FixUnescapedCurlyBracesInHtmlAttributes');
		$this->collection->append('InlineAttributes');
		$this->collection->append('InlineCDATA');
		$this->collection->append('InlineElements');
		$this->collection->append('InlineInferredValues');
		$this->collection->append('InlineTextElements');
		$this->collection->append('InlineXPathLiterals');
		$this->collection->append('MinifyXPathExpressions');
		$this->collection->append('NormalizeAttributeNames');
		$this->collection->append('NormalizeElementNames');
		$this->collection->append('NormalizeUrls');
		$this->collection->append('OptimizeConditionalAttributes');
		$this->collection->append('OptimizeConditionalValueOf');
	}
	public function normalizeTag(Tag $tag)
	{
		if (isset($tag->template) && !$tag->template->isNormalized())
			$tag->template->normalize($this);
	}
	public function normalizeTemplate($template)
	{
		$dom = TemplateHelper::loadTemplate($template);
		$applied = [];
		$loops = 5;
		do
		{
			$old = $template;
			foreach ($this->collection as $k => $normalization)
			{
				if (isset($applied[$k]) && !empty($normalization->onlyOnce))
					continue;
				$normalization->normalize($dom->documentElement);
				$applied[$k] = 1;
			}
			$template = TemplateHelper::saveTemplate($dom);
		}
		while (--$loops && $template !== $old);
		return $template;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\HostnameList;
use s9e\TextFormatter\Configurator\Collections\SchemeList;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
class UrlConfig implements ConfigProvider
{
	protected $allowedSchemes;
	protected $disallowedHosts;
	protected $restrictedHosts;
	public function __construct()
	{
		$this->disallowedHosts = new HostnameList;
		$this->restrictedHosts = new HostnameList;
		$this->allowedSchemes   = new SchemeList;
		$this->allowedSchemes[] = 'http';
		$this->allowedSchemes[] = 'https';
	}
	public function asConfig()
	{
		return ConfigHelper::toArray(\get_object_vars($this));
	}
	public function allowScheme($scheme)
	{
		if (\strtolower($scheme) === 'javascript')
			throw new RuntimeException('The JavaScript URL scheme cannot be allowed');
		$this->allowedSchemes[] = $scheme;
	}
	public function disallowHost($host, $matchSubdomains = \true)
	{
		$this->disallowedHosts[] = $host;
		if ($matchSubdomains && \substr($host, 0, 1) !== '*')
			$this->disallowedHosts[] = '*.' . $host;
	}
	public function disallowScheme($scheme)
	{
		$this->allowedSchemes->remove($scheme);
	}
	public function getAllowedSchemes()
	{
		return \iterator_to_array($this->allowedSchemes);
	}
	public function restrictHost($host, $matchSubdomains = \true)
	{
		$this->restrictedHosts[] = $host;
		if ($matchSubdomains && \substr($host, 0, 1) !== '*')
			$this->restrictedHosts[] = '*.' . $host;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
class AttributePreprocessorCollection extends Collection
{
	public function add($attrName, $regexp)
	{
		$attrName = AttributeName::normalize($attrName);
		$k = \serialize([$attrName, $regexp]);
		$this->items[$k] = new AttributePreprocessor($regexp);
		return $this->items[$k];
	}
	public function key()
	{
		list($attrName) = \unserialize(\key($this->items));
		return $attrName;
	}
	public function merge($attributePreprocessors)
	{
		$error = \false;
		if ($attributePreprocessors instanceof AttributePreprocessorCollection)
			foreach ($attributePreprocessors as $attrName => $attributePreprocessor)
				$this->add($attrName, $attributePreprocessor->getRegexp());
		elseif (\is_array($attributePreprocessors))
		{
			foreach ($attributePreprocessors as $values)
			{
				if (!\is_array($values))
				{
					$error = \true;
					break;
				}
				list($attrName, $value) = $values;
				if ($value instanceof AttributePreprocessor)
					$value = $value->getRegexp();
				$this->add($attrName, $value);
			}
		}
		else
			$error = \true;
		if ($error)
			throw new InvalidArgumentException('merge() expects an instance of AttributePreprocessorCollection or a 2D array where each element is a [attribute name, regexp] pair');
	}
	public function asConfig()
	{
		$config = [];
		foreach ($this->items as $k => $ap)
		{
			list($attrName, $regexp) = \unserialize($k);
			$config[] = [
				$attrName,
				new Regexp($regexp, \true),
				RegexpParser::getCaptureNames($regexp)
			];
		}
		return $config;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
class NormalizedCollection extends Collection implements ArrayAccess
{
	protected $onDuplicateAction = 'error';
	public function onDuplicate($action = \null)
	{
		$old = $this->onDuplicateAction;
		if (\func_num_args() && $action !== 'error' && $action !== 'ignore' && $action !== 'replace')
			throw new InvalidArgumentException("Invalid onDuplicate action '" . $action . "'. Expected: 'error', 'ignore' or 'replace'");
		$this->onDuplicateAction = $action;
		return $old;
	}
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Item '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Item '" . $key . "' does not exist");
	}
	public function normalizeKey($key)
	{
		return $key;
	}
	public function normalizeValue($value)
	{
		return $value;
	}
	public function add($key, $value = \null)
	{
		if ($this->exists($key))
			if ($this->onDuplicateAction === 'ignore')
				return $this->get($key);
			elseif ($this->onDuplicateAction === 'error')
				throw $this->getAlreadyExistsException($key);
		return $this->set($key, $value);
	}
	public function contains($value)
	{
		return \in_array($this->normalizeValue($value), $this->items);
	}
	public function delete($key)
	{
		$key = $this->normalizeKey($key);
		unset($this->items[$key]);
	}
	public function exists($key)
	{
		$key = $this->normalizeKey($key);
		return \array_key_exists($key, $this->items);
	}
	public function get($key)
	{
		if (!$this->exists($key))
			throw $this->getNotExistException($key);
		$key = $this->normalizeKey($key);
		return $this->items[$key];
	}
	public function indexOf($value)
	{
		return \array_search($this->normalizeValue($value), $this->items);
	}
	public function set($key, $value)
	{
		$key = $this->normalizeKey($key);
		$this->items[$key] = $this->normalizeValue($value);
		return $this->items[$key];
	}
	public function offsetExists($offset)
	{
		return $this->exists($offset);
	}
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}
	public function offsetUnset($offset)
	{
		$this->delete($offset);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Parser;
class Ruleset extends Collection implements ArrayAccess, ConfigProvider
{
	public function __construct()
	{
		$this->defaultChildRule('allow');
		$this->defaultDescendantRule('allow');
	}
	public function offsetExists($k)
	{
		return isset($this->items[$k]);
	}
	public function offsetGet($k)
	{
		return $this->items[$k];
	}
	public function offsetSet($k, $v)
	{
		throw new RuntimeException('Not supported');
	}
	public function offsetUnset($k)
	{
		return $this->remove($k);
	}
	public function asConfig()
	{
		$config = $this->items;
		unset($config['allowChild']);
		unset($config['allowDescendant']);
		unset($config['defaultChildRule']);
		unset($config['defaultDescendantRule']);
		unset($config['denyChild']);
		unset($config['denyDescendant']);
		unset($config['requireParent']);
		$bitValues = [
			'autoClose'                   => Parser::RULE_AUTO_CLOSE,
			'autoReopen'                  => Parser::RULE_AUTO_REOPEN,
			'breakParagraph'              => Parser::RULE_BREAK_PARAGRAPH,
			'createParagraphs'            => Parser::RULE_CREATE_PARAGRAPHS,
			'disableAutoLineBreaks'       => Parser::RULE_DISABLE_AUTO_BR,
			'enableAutoLineBreaks'        => Parser::RULE_ENABLE_AUTO_BR,
			'ignoreSurroundingWhitespace' => Parser::RULE_IGNORE_WHITESPACE,
			'ignoreTags'                  => Parser::RULE_IGNORE_TAGS,
			'ignoreText'                  => Parser::RULE_IGNORE_TEXT,
			'isTransparent'               => Parser::RULE_IS_TRANSPARENT,
			'preventLineBreaks'           => Parser::RULE_PREVENT_BR,
			'suspendAutoLineBreaks'       => Parser::RULE_SUSPEND_AUTO_BR,
			'trimFirstLine'               => Parser::RULE_TRIM_FIRST_LINE
		];
		$bitfield = 0;
		foreach ($bitValues as $ruleName => $bitValue)
		{
			if (!empty($config[$ruleName]))
				$bitfield |= $bitValue;
			unset($config[$ruleName]);
		}
		foreach (['closeAncestor', 'closeParent', 'fosterParent'] as $ruleName)
			if (isset($config[$ruleName]))
			{
				$targets = \array_fill_keys($config[$ruleName], 1);
				$config[$ruleName] = new Dictionary($targets);
			}
		$config['flags'] = $bitfield;
		return $config;
	}
	public function merge($rules, $overwrite = \true)
	{
		if (!\is_array($rules)
		 && !($rules instanceof self))
			throw new InvalidArgumentException('merge() expects an array or an instance of Ruleset');
		foreach ($rules as $action => $value)
			if (\is_array($value))
				foreach ($value as $tagName)
					$this->$action($tagName);
			elseif ($overwrite || !isset($this->items[$action]))
				$this->$action($value);
	}
	public function remove($type, $tagName = \null)
	{
		if (\preg_match('(^default(?:Child|Descendant)Rule)', $type))
			throw new RuntimeException('Cannot remove ' . $type);
		if (isset($tagName))
		{
			$tagName = TagName::normalize($tagName);
			if (isset($this->items[$type]))
			{
				$this->items[$type] = \array_diff(
					$this->items[$type],
					[$tagName]
				);
				if (empty($this->items[$type]))
					unset($this->items[$type]);
				else
					$this->items[$type] = \array_values($this->items[$type]);
			}
		}
		else
			unset($this->items[$type]);
	}
	protected function addBooleanRule($ruleName, $bool)
	{
		if (!\is_bool($bool))
			throw new InvalidArgumentException($ruleName . '() expects a boolean');
		$this->items[$ruleName] = $bool;
		return $this;
	}
	protected function addTargetedRule($ruleName, $tagName)
	{
		$this->items[$ruleName][] = TagName::normalize($tagName);
		return $this;
	}
	public function allowChild($tagName)
	{
		return $this->addTargetedRule('allowChild', $tagName);
	}
	public function allowDescendant($tagName)
	{
		return $this->addTargetedRule('allowDescendant', $tagName);
	}
	public function autoClose($bool = \true)
	{
		return $this->addBooleanRule('autoClose', $bool);
	}
	public function autoReopen($bool = \true)
	{
		return $this->addBooleanRule('autoReopen', $bool);
	}
	public function breakParagraph($bool = \true)
	{
		return $this->addBooleanRule('breakParagraph', $bool);
	}
	public function closeAncestor($tagName)
	{
		return $this->addTargetedRule('closeAncestor', $tagName);
	}
	public function closeParent($tagName)
	{
		return $this->addTargetedRule('closeParent', $tagName);
	}
	public function createParagraphs($bool = \true)
	{
		return $this->addBooleanRule('createParagraphs', $bool);
	}
	public function defaultChildRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
			throw new InvalidArgumentException("defaultChildRule() only accepts 'allow' or 'deny'");
		$this->items['defaultChildRule'] = $rule;
		return $this;
	}
	public function defaultDescendantRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
			throw new InvalidArgumentException("defaultDescendantRule() only accepts 'allow' or 'deny'");
		$this->items['defaultDescendantRule'] = $rule;
		return $this;
	}
	public function denyChild($tagName)
	{
		return $this->addTargetedRule('denyChild', $tagName);
	}
	public function denyDescendant($tagName)
	{
		return $this->addTargetedRule('denyDescendant', $tagName);
	}
	public function disableAutoLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('disableAutoLineBreaks', $bool);
	}
	public function enableAutoLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('enableAutoLineBreaks', $bool);
	}
	public function fosterParent($tagName)
	{
		return $this->addTargetedRule('fosterParent', $tagName);
	}
	public function ignoreSurroundingWhitespace($bool = \true)
	{
		return $this->addBooleanRule('ignoreSurroundingWhitespace', $bool);
	}
	public function ignoreTags($bool = \true)
	{
		return $this->addBooleanRule('ignoreTags', $bool);
	}
	public function ignoreText($bool = \true)
	{
		return $this->addBooleanRule('ignoreText', $bool);
	}
	public function isTransparent($bool = \true)
	{
		return $this->addBooleanRule('isTransparent', $bool);
	}
	public function preventLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('preventLineBreaks', $bool);
	}
	public function requireParent($tagName)
	{
		return $this->addTargetedRule('requireParent', $tagName);
	}
	public function requireAncestor($tagName)
	{
		return $this->addTargetedRule('requireAncestor', $tagName);
	}
	public function suspendAutoLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('suspendAutoLineBreaks', $bool);
	}
	public function trimFirstLine($bool = \true)
	{
		return $this->addBooleanRule('trimFirstLine', $bool);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
abstract class Filter extends ProgrammableCallback
{
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
class DisallowUnsafeDynamicCSS extends AbstractDynamicContentCheck
{
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getCSSNodes($template->ownerDocument);
	}
	protected function isExpressionSafe($expr)
	{
		return XPathHelper::isExpressionNumeric($expr);
	}
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeInCSS();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
class DisallowUnsafeDynamicJS extends AbstractDynamicContentCheck
{
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getJSNodes($template->ownerDocument);
	}
	protected function isExpressionSafe($expr)
	{
		return XPathHelper::isExpressionNumeric($expr);
	}
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeInJS();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMAttr;
use DOMElement;
use DOMText;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
class DisallowUnsafeDynamicURL extends AbstractDynamicContentCheck
{
	protected $exceptionRegexp = '(^(?:(?!data|\\w*script)\\w+:|[^:]*/|#))i';
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getURLNodes($template->ownerDocument);
	}
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeAsURL();
	}
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		if (\preg_match($this->exceptionRegexp, $attribute->value))
			return;
		parent::checkAttributeNode($attribute, $tag);
	}
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		if ($element->firstChild
		 && $element->firstChild instanceof DOMText
		 && \preg_match($this->exceptionRegexp, $element->firstChild->textContent))
			return;
		parent::checkElementNode($element, $tag);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
class RestrictFlashScriptAccess extends AbstractFlashRestriction
{
	public $defaultSetting = 'sameDomain';
	protected $settingName = 'allowScriptAccess';
	protected $settings = [
		'always'     => 3,
		'samedomain' => 2,
		'never'      => 1
	];
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
class AttributeCollection extends NormalizedCollection
{
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Attribute '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Attribute '" . $key . "' does not exist");
	}
	public function normalizeKey($key)
	{
		return AttributeName::normalize($key);
	}
	public function normalizeValue($value)
	{
		return ($value instanceof Attribute)
		     ? $value
		     : new Attribute($value);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
class AttributeFilterCollection extends NormalizedCollection
{
	public function get($key)
	{
		$key = $this->normalizeKey($key);
		if (!$this->exists($key))
			if ($key[0] === '#')
				$this->set($key, self::getDefaultFilter(\substr($key, 1)));
			else
				$this->set($key, new AttributeFilter($key));
		$filter = parent::get($key);
		$filter = clone $filter;
		return $filter;
	}
	public static function getDefaultFilter($filterName)
	{
		$filterName = \ucfirst(\strtolower($filterName));
		$className  = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\' . $filterName . 'Filter';
		if (!\class_exists($className))
			throw new InvalidArgumentException("Unknown attribute filter '" . $filterName . "'");
		return new $className;
	}
	public function normalizeKey($key)
	{
		if (\preg_match('/^#[a-z_0-9]+$/Di', $key))
			return \strtolower($key);
		if (\is_string($key) && \is_callable($key))
			return $key;
		throw new InvalidArgumentException("Invalid filter name '" . $key . "'");
	}
	public function normalizeValue($value)
	{
		if ($value instanceof AttributeFilter)
			return $value;
		if (\is_callable($value))
			return new AttributeFilter($value);
		throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback or an instance of s9e\\TextFormatter\\Configurator\\Items\\AttributeFilter');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
class NormalizedList extends NormalizedCollection
{
	public function add($value, $void = \null)
	{
		return $this->append($value);
	}
	public function append($value)
	{
		$value = $this->normalizeValue($value);
		$this->items[] = $value;
		return $value;
	}
	public function delete($key)
	{
		parent::delete($key);
		$this->items = \array_values($this->items);
	}
	public function insert($offset, $value)
	{
		$offset = $this->normalizeKey($offset);
		$value  = $this->normalizeValue($value);
		\array_splice($this->items, $offset, 0, [$value]);
		return $value;
	}
	public function normalizeKey($key)
	{
		$normalizedKey = \filter_var(
			$key,
			\FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => 0,
					'max_range' => \count($this->items)
				]
			]
		);
		if ($normalizedKey === \false)
			throw new InvalidArgumentException("Invalid offset '" . $key . "'");
		return $normalizedKey;
	}
	public function offsetSet($offset, $value)
	{
		if ($offset === \null)
			$this->append($value);
		else
			parent::offsetSet($offset, $value);
	}
	public function prepend($value)
	{
		$value = $this->normalizeValue($value);
		\array_unshift($this->items, $value);
		return $value;
	}
	public function remove($value)
	{
		$keys = \array_keys($this->items, $this->normalizeValue($value));
		foreach ($keys as $k)
			unset($this->items[$k]);
		$this->items = \array_values($this->items);
		return \count($keys);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class PluginCollection extends NormalizedCollection
{
	protected $configurator;
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}
	public function finalize()
	{
		foreach ($this->items as $plugin)
			$plugin->finalize();
	}
	public function normalizeKey($pluginName)
	{
		if (!\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $pluginName))
			throw new InvalidArgumentException("Invalid plugin name '" . $pluginName . "'");
		return $pluginName;
	}
	public function normalizeValue($value)
	{
		if (\is_string($value) && \class_exists($value))
			$value = new $value($this->configurator);
		if ($value instanceof ConfiguratorBase)
			return $value;
		throw new InvalidArgumentException('PluginCollection::normalizeValue() expects a class name or an object that implements s9e\\TextFormatter\\Plugins\\ConfiguratorBase');
	}
	public function load($pluginName, array $overrideProps = [])
	{
		$pluginName = $this->normalizeKey($pluginName);
		$className  = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Configurator';
		if (!\class_exists($className))
			throw new RuntimeException("Class '" . $className . "' does not exist");
		$plugin = new $className($this->configurator, $overrideProps);
		$this->set($pluginName, $plugin);
		return $plugin;
	}
	public function asConfig()
	{
		$plugins = parent::asConfig();
		foreach ($plugins as $pluginName => &$pluginConfig)
		{
			$plugin = $this->get($pluginName);
			$pluginConfig += $plugin->getBaseProperties();
			if ($pluginConfig['quickMatch'] === \false)
				unset($pluginConfig['quickMatch']);
			if (!isset($pluginConfig['regexp']))
				unset($pluginConfig['regexpLimit']);
			if (!isset($pluginConfig['parser']))
			{
				$pluginConfig['parser'] = new Variant;
				$pluginConfig['parser']->setDynamic('JS', [$plugin, 'getJSParser']);
			}
			$className = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';
			if ($pluginConfig['className'] === $className)
				unset($pluginConfig['className']);
		}
		unset($pluginConfig);
		return $plugins;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Validators\TagName;
class TagCollection extends NormalizedCollection
{
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Tag '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Tag '" . $key . "' does not exist");
	}
	public function normalizeKey($key)
	{
		return TagName::normalize($key);
	}
	public function normalizeValue($value)
	{
		return ($value instanceof Tag)
		     ? $value
		     : new Tag($value);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\Validators\TemplateParameterName;
class TemplateParameterCollection extends NormalizedCollection
{
	public function normalizeKey($key)
	{
		return TemplateParameterName::normalize($key);
	}
	public function normalizeValue($value)
	{
		return (string) $value;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;
class AttributeFilter extends Filter
{
	use TemplateSafeness;
	public function __construct($callback)
	{
		parent::__construct($callback);
		$this->resetParameters();
		$this->addParameterByName('attrValue');
	}
	public function isSafeInJS()
	{
		$safeCallbacks = [
			'urlencode',
			'strtotime',
			'rawurlencode'
		];
		if (\in_array($this->callback, $safeCallbacks, \true))
			return \true;
		return $this->isSafe('InJS');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
class TagFilter extends Filter
{
	public function __construct($callback)
	{
		parent::__construct($callback);
		$this->resetParameters();
		$this->addParameterByName('tag');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
abstract class FilterChain extends NormalizedList
{
	abstract protected function getFilterClassName();
	public function containsCallback(callable $callback)
	{
		$pc = new ProgrammableCallback($callback);
		$callback = $pc->getCallback();
		foreach ($this->items as $filter)
			if ($callback === $filter->getCallback())
				return \true;
		return \false;
	}
	public function normalizeValue($value)
	{
		$className  = $this->getFilterClassName();
		if ($value instanceof $className)
			return $value;
		if (!\is_callable($value))
			throw new InvalidArgumentException('Filter ' . \var_export($value, \true) . ' is neither callable nor an instance of ' . $className);
		return new $className($value);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
class HostnameList extends NormalizedList
{
	public function asConfig()
	{
		if (empty($this->items))
			return \null;
		$regexp = new Regexp($this->getRegexp());
		return $regexp->asConfig();
	}
	public function getRegexp()
	{
		$hosts = [];
		foreach ($this->items as $host)
			$hosts[] = $this->normalizeHostmask($host);
		$regexp = RegexpBuilder::fromList(
			$hosts,
			[
				'specialChars' => [
					'*' => '.*',
					'^' => '^',
					'$' => '$'
				]
			]
		);
		return '/' . $regexp . '/DSis';
	}
	protected function normalizeHostmask($host)
	{
		if (\preg_match('#[\\x80-\xff]#', $host) && \function_exists('idn_to_ascii'))
			$host = \idn_to_ascii($host);
		if (\substr($host, 0, 1) === '*')
			$host = \ltrim($host, '*');
		else
			$host = '^' . $host;
		if (\substr($host, -1) === '*')
			$host = \rtrim($host, '*');
		else
			$host .= '$';
		return $host;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
class RulesGeneratorList extends NormalizedList
{
	public function normalizeValue($generator)
	{
		if (\is_string($generator))
		{
			$className = 's9e\\TextFormatter\\Configurator\\RulesGenerators\\' . $generator;
			if (\class_exists($className))
				$generator = new $className;
		}
		if (!($generator instanceof BooleanRulesGenerator)
		 && !($generator instanceof TargetedRulesGenerator))
			throw new InvalidArgumentException('Invalid rules generator ' . \var_export($generator, \true));
		return $generator;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
class SchemeList extends NormalizedList
{
	public function asConfig()
	{
		$regexp = new Regexp('/^' . RegexpBuilder::fromList($this->items) . '$/Di');
		return $regexp->asConfig();
	}
	public function normalizeValue($scheme)
	{
		if (!\preg_match('#^[a-z][a-z0-9+\\-.]*$#Di', $scheme))
			throw new InvalidArgumentException("Invalid scheme name '" . $scheme . "'");
		return \strtolower($scheme);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\TemplateCheck;
class TemplateCheckList extends NormalizedList
{
	public function normalizeValue($check)
	{
		if (!($check instanceof TemplateCheck))
		{
			$className = 's9e\\TextFormatter\\Configurator\\TemplateChecks\\' . $check;
			$check     = new $className;
		}
		return $check;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Configurator\TemplateNormalizations\Custom;
class TemplateNormalizationList extends NormalizedList
{
	public function normalizeValue($value)
	{
		if ($value instanceof TemplateNormalization)
			return $value;
		if (\is_callable($value))
			return new Custom($value);
		$className = 's9e\\TextFormatter\\Configurator\\TemplateNormalizations\\' . $value;
		return new $className;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
class UrlFilter extends AttributeFilter
{
	public function __construct()
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterUrl');
		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('urlConfig');
		$this->addParameterByName('logger');
		$this->setJS('BuiltInFilters.filterUrl');
	}
	public function isSafeInCSS()
	{
		return \true;
	}
	public function isSafeInJS()
	{
		return \true;
	}
	public function isSafeAsURL()
	{
		return \true;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
class AttributeFilterChain extends FilterChain
{
	public function getFilterClassName()
	{
		return 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilter';
	}
	public function normalizeValue($value)
	{
		if (\is_string($value) && \preg_match('(^#\\w+$)', $value))
			$value = AttributeFilterCollection::getDefaultFilter(\substr($value, 1));
		return parent::normalizeValue($value);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
class TagFilterChain extends FilterChain
{
	public function getFilterClassName()
	{
		return 's9e\\TextFormatter\\Configurator\\Items\\TagFilter';
	}
}