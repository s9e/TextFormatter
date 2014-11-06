<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Regexp;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MediaSiteCollection;

class Configurator extends ConfiguratorBase
{
	public $allowedFilters = array(
		'hexdec',
		'urldecode'
	);

	protected $appendTemplate = '';

	protected $captureURLs = \true;

	protected $collection;

	protected $createBBCodes = \true;

	protected $preferredRenderingMethods = array('template', 'iframe', 'flash');

	public $sitesDir;

	protected function setUp()
	{
		$this->collection = new MediaSiteCollection;

		$this->configurator->registeredVars['mediasites'] = $this->collection;

		$tag = $this->configurator->tags->add('MEDIA');

		$tag->rules->autoClose();
		$tag->rules->ignoreTags();

		$tag->filterChain->clear();
		$tag->filterChain
		    ->append(array(__NAMESPACE__ . '\\Parser', 'filterTag'))
		    ->addParameterByName('parser')
		    ->addParameterByName('mediasites')
		    ->setJS(\file_get_contents(__DIR__ . '/Parser/tagFilter.js'));

		if ($this->createBBCodes)
			$this->configurator->BBCodes->set('MEDIA', array('contentAttributes' => array('url')));

		if (!isset($this->sitesDir))
			$this->sitesDir = __DIR__ . '/Configurator/sites';
	}

	public function asConfig()
	{
		if (!$this->captureURLs)
			return \false;

		$char = "\xEE\x80\x80";

		$hasSchemes = \false;
		$patterns   = array();
		foreach ($this->collection as $site)
		{
			if (isset($site['host']))
				foreach ((array) $site['host'] as $host)
					$patterns[] = $char . $host . '/';

			if (isset($site['scheme']))
				foreach ((array) $site['scheme'] as $scheme)
				{
					$hasSchemes = \true;
					$patterns[] = $scheme . ':';
				}
		}

		if (empty($patterns))
			return \false;

		$regexp = RegexpBuilder::fromList(
			$patterns,
			array(
				'delimiter'    => '#',
				'specialChars' => array($char => 'https?://(?:[-.\\w]+\\.)?')
			)
		);

		$regexp = \preg_replace('(^\\(\\?:)', '(?>', $regexp);

		$regexp = '#\\b' . $regexp . '[^["\'\\s]+' . '(?!\\S)' . '#S';

		return array(
			'quickMatch' => ($hasSchemes) ? ':' : '://',
			'regexp'     => $regexp
		);
	}

	public function add($siteId, array $siteConfig = \null)
	{
		$siteId = $this->normalizeId($siteId);

		if (!isset($siteConfig))
			$siteConfig = $this->getDefaultSite($siteId);

		$this->collection[$siteId] = $siteConfig;

		$tag = new Tag;

		$tag->rules->autoClose();
		$tag->rules->ignoreTags();

		$attributes = array(
			'url' => array('type' => 'url')
		);

		if (isset($siteConfig['scrape']))
		{
			if (!isset($siteConfig['scrape'][0]))
				$siteConfig['scrape'] = array($siteConfig['scrape']);

			$scrapeConfig = array();
			foreach ($siteConfig['scrape'] as $scrape)
			{
				$attrNames = array();
				foreach ((array) $scrape['extract'] as $extractRegexp)
				{
					$attributePreprocessor = new AttributePreprocessor($extractRegexp);

					foreach ($attributePreprocessor->getAttributes() as $attrName => $attrRegexp)
					{
						$attrNames[] = $attrName;
						$attributes[$attrName]['regexp'] = $attrRegexp;
					}
				}

				$attrNames = \array_unique($attrNames);
				\sort($attrNames);

				if (!isset($scrape['match']))
					$scrape['match'] = '//';
				$entry = array($scrape['match'], $scrape['extract'], $attrNames);
				if (isset($scrape['url']))
					$entry[] = $scrape['url'];

				$scrapeConfig[] = $entry;
			}

			$tag->filterChain->insert(1, __NAMESPACE__ . '\\Parser::scrape')
			                 ->addParameterByName('scrapeConfig')
			                 ->addParameterByName('cacheDir')
			                 ->setVar('scrapeConfig', $scrapeConfig)
			                 ->setJS('function(){return true;}');
		}

		if (isset($siteConfig['extract']))
			foreach ((array) $siteConfig['extract'] as $regexp)
			{
				$attrRegexps = $tag->attributePreprocessors->add('url', $regexp)->getAttributes();

				foreach ($attrRegexps as $attrName => $attrRegexp)
					$attributes[$attrName]['regexp'] = $attrRegexp;
			}

		if (isset($siteConfig['attributes']))
			foreach ($siteConfig['attributes'] as $attrName => $attrConfig)
				foreach ($attrConfig as $configName => $configValue)
					$attributes[$attrName][$configName] = $configValue;

		$hasRequiredAttribute = \false;
		foreach ($attributes as $attrName => $attrConfig)
		{
			$attribute = $tag->attributes->add($attrName);

			if (isset($attrConfig['preFilter']))
				$this->appendFilter($attribute, $attrConfig['preFilter']);

			if (isset($attrConfig['type']))
			{
				$filter = $this->configurator->attributeFilters['#' . $attrConfig['type']];
				$attribute->filterChain->append($filter);
			}
			elseif (isset($attrConfig['regexp']))
				$attribute->filterChain->append(new Regexp($attrConfig['regexp']));

			if (isset($attrConfig['required']))
				$attribute->required = $attrConfig['required'];
			else
				$attribute->required = ($attrName === 'id');

			if (isset($attrConfig['postFilter']))
				$this->appendFilter($attribute, $attrConfig['postFilter']);

			$hasRequiredAttribute |= $attribute->required;
		}

		if (isset($attributes['id']['regexp']))
		{
			$attrRegexp = \preg_replace('/\\^\\(\\?[:>]/', "^(?'id'", $attributes['id']['regexp']);

			$tag->attributePreprocessors->add('url', $attrRegexp);
		}

		if (!$hasRequiredAttribute)
			$tag->filterChain
			    ->append(array(__NAMESPACE__ . '\\Parser', 'hasNonDefaultAttribute'))
			    ->setJS(\file_get_contents(__DIR__ . '/Parser/hasNonDefaultAttribute.js'));

		foreach ($this->preferredRenderingMethods as $renderingMethod)
		{
			if (!isset($siteConfig[$renderingMethod]))
				continue;

			$methodName = 'build' . \ucfirst($renderingMethod);

			$tag->template = $this->$methodName($siteConfig) . $this->appendTemplate;

			break;
		}

		$this->configurator->templateNormalizer->normalizeTag($tag);

		$this->configurator->templateChecker->checkTag($tag);

		$this->configurator->tags->add($siteId, $tag);

		if ($this->createBBCodes)
			$this->configurator->BBCodes->add(
				$siteId,
				array(
					'defaultAttribute'  => 'url',
					'contentAttributes' => array('url')
				)
			);

		return $tag;
	}

	protected function appendFilter(Attribute $attribute, $filter)
	{
		if (!\in_array($filter, $this->allowedFilters, \true))
			throw new RuntimeException("Filter '" . $filter . "' is not allowed");

		$attribute->filterChain->append($this->configurator->attributeFilters[$filter]);
	}

	protected function buildFlash(array $siteConfig)
	{
		$template = '<object type="application/x-shockwave-flash" typemustmatch="">';
		$template .= $this->generateAttributes(array(
			'width'  => $siteConfig['flash']['width'],
			'height' => $siteConfig['flash']['height'],
			'data'   => $siteConfig['flash']['src']
		));
		$template .= '<param name="allowfullscreen" value="true"/>';
		if (isset($siteConfig['flash']['flashvars']))
		{
			$template .= '<param name="flashvars">';
			$template .= $this->generateAttributes(array(
				'value' => $siteConfig['flash']['flashvars']
			));
			$template .= '</param>';
		}
		$template .= '<embed type="application/x-shockwave-flash">';
		$template .= $this->generateAttributes(array(
			'src'    => $siteConfig['flash']['src'],
			'width'  => $siteConfig['flash']['width'],
			'height' => $siteConfig['flash']['height'],
			'allowfullscreen' => ''
		));
		if (isset($siteConfig['flash']['flashvars']))
			$template .= $this->generateAttributes(array(
				'flashvars' => $siteConfig['flash']['flashvars']
			));
		$template .= '</embed></object>';

		return $template;
	}

	protected function buildIframe(array $siteConfig)
	{
		$attributes = $siteConfig['iframe'];

		$attributes += array(
			'allowfullscreen' => '',
			'frameborder'     => '0',
			'scrolling'       => 'no'
		);

		$template = '<iframe>' . $this->generateAttributes($attributes) . '</iframe>';

		return $template;
	}

	protected function buildTemplate(array $siteConfig)
	{
		return $siteConfig['template'];
	}

	public function appendTemplate($template = '')
	{
		$this->appendTemplate
			= $this->configurator->templateNormalizer->normalizeTemplate($template);
	}

	protected function generateAttributes(array $attributes)
	{
		$xsl = '';
		foreach ($attributes as $attrName => $innerXML)
		{
			if (\strpos($innerXML, '<') === \false)
			{
				$tokens   = AVTHelper::parse($innerXML);
				$innerXML = '';
				foreach ($tokens as $_3134889776)
				{
					list($type, $content) = $_3134889776;
					if ($type === 'literal')
						$innerXML .= \htmlspecialchars($content, 0, 'UTF-8');
					else
						$innerXML .= '<xsl:value-of select="' . \htmlspecialchars($content, 3, 'UTF-8') . '"/>';
				}
			}

			$xsl .= '<xsl:attribute name="' . \htmlspecialchars($attrName, 3, 'UTF-8') . '">' . $innerXML . '</xsl:attribute>';
		}

		return $xsl;
	}

	protected function getConfigFromXmlFile($filepath)
	{
		$dom = new DOMDocument;
		if (!$dom->load($filepath))
			throw new RuntimeException('Invalid XML');

		return $this->getElementConfig($dom->documentElement);
	}

	protected function getDefaultSite($siteId)
	{
		$filepath = $this->sitesDir . '/' . $siteId . '.xml';

		if (!\file_exists($filepath))
			throw new RuntimeException("Unknown media site '" . $siteId . "'");

		return $this->getConfigFromXmlFile($filepath);
	}

	protected function getElementConfig(DOMElement $element)
	{
		$config = array();
		foreach ($element->attributes as $attribute)
			$config[$attribute->name] = $attribute->value;

		$childNodes = array();
		foreach ($element->childNodes as $childNode)
		{
			if ($childNode->nodeType !== 1)
				continue;

			if (!$childNode->attributes->length && $childNode->childNodes->length === 1)
				$value = $childNode->nodeValue;
			else
				$value = $this->getElementConfig($childNode);

			$childNodes[$childNode->nodeName][] = $value;
		}

		foreach ($childNodes as $nodeName => $childNodes)
			if (\count($childNodes) === 1)
				$config[$nodeName] = \end($childNodes);
			else
				$config[$nodeName] = $childNodes;

		return $config;
	}

	protected function normalizeId($siteId)
	{
		$siteId = \strtolower($siteId);

		if (!\preg_match('(^[a-z0-9]+$)', $siteId))
			throw new InvalidArgumentException('Invalid site ID');

		return $siteId;
	}
}