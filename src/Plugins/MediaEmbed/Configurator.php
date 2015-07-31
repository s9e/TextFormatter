<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\RegexpFilter;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\CachedSiteDefinitionProvider;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MediaSiteCollection;
class Configurator extends ConfiguratorBase
{
	public $allowedFilters = [
		'hexdec',
		'urldecode'
	];
	protected $appendTemplate = '';
	protected $captureURLs = \true;
	protected $collection;
	protected $createMediaBBCode = \true;
	public $createIndividualBBCodes = \false;
	public $defaultSites;
	protected $preferredRenderingMethods = ['template', 'iframe', 'flash'];
	protected $responsiveEmbeds = \false;
	protected function setUp()
	{
		$this->collection = new MediaSiteCollection;
		$this->configurator->registeredVars['mediasites'] = $this->collection;
		$tag = $this->configurator->tags->add('MEDIA');
		$tag->rules->autoClose();
		$tag->rules->ignoreTags();
		$tag->filterChain->clear();
		$tag->filterChain
		    ->append([__NAMESPACE__ . '\\Parser', 'filterTag'])
		    ->addParameterByName('parser')
		    ->addParameterByName('mediasites')
		    ->setJS(\file_get_contents(__DIR__ . '/Parser/tagFilter.js'));
		if ($this->createMediaBBCode)
			$this->configurator->BBCodes->set('MEDIA', ['contentAttributes' => ['url']]);
		if (!isset($this->defaultSites))
			$this->defaultSites = new CachedSiteDefinitionProvider;
	}
	public function asConfig()
	{
		if (!$this->captureURLs)
			return;
		$char = "\xEE\x80\x80";
		$hasSchemes = \false;
		$patterns   = [];
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
			return;
		$regexp = RegexpBuilder::fromList(
			$patterns,
			[
				'delimiter'    => '#',
				'specialChars' => [$char => 'https?://(?:[-.\\w]+\\.)?']
			]
		);
		$regexp = \preg_replace('(^\\(\\?:)', '(?>', $regexp);
		$regexp = '#\\b' . $regexp . '[^["\'\\s]+(?!\\S)#S';
		return [
			'quickMatch' => ($hasSchemes) ? ':' : '://',
			'regexp'     => $regexp
		];
	}
	public function add($siteId, array $siteConfig = \null)
	{
		$siteId = $this->normalizeId($siteId);
		if (!isset($siteConfig))
			$siteConfig = $this->defaultSites->get($siteId);
		$this->collection[$siteId] = $siteConfig;
		$tag = new Tag;
		$tag->rules->autoClose();
		$tag->rules->ignoreTags();
		$attributes = [
			'url' => ['type' => 'url']
		];
		if (isset($siteConfig['scrape']))
			$attributes += $this->addScrapes($tag, $siteConfig['scrape']);
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
				$attribute->filterChain->append(new RegexpFilter($attrConfig['regexp']));
			if (isset($attrConfig['required']))
				$attribute->required = $attrConfig['required'];
			else
				$attribute->required = ($attrName === 'id');
			if (isset($attrConfig['postFilter']))
				$this->appendFilter($attribute, $attrConfig['postFilter']);
			if (isset($attrConfig['defaultValue']))
				$attribute->defaultValue = $attrConfig['defaultValue'];
			$hasRequiredAttribute |= $attribute->required;
		}
		if (isset($attributes['id']['regexp']))
		{
			$attrRegexp = \preg_replace('/\\^\\(\\?[:>]/', "^(?'id'", $attributes['id']['regexp']);
			$tag->attributePreprocessors->add('url', $attrRegexp);
		}
		if (!$hasRequiredAttribute)
			$tag->filterChain
				->append([__NAMESPACE__ . '\\Parser', 'hasNonDefaultAttribute'])
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
		if ($this->createIndividualBBCodes)
			$this->configurator->BBCodes->add(
				$siteId,
				[
					'defaultAttribute'  => 'url',
					'contentAttributes' => ['url']
				]
			);
		return $tag;
	}
	public function appendTemplate($template = '')
	{
		$this->appendTemplate = $this->configurator->templateNormalizer->normalizeTemplate($template);
	}
	public function disableResponsiveEmbeds()
	{
		$this->responsiveEmbeds = \false;
	}
	public function enableResponsiveEmbeds()
	{
		$this->responsiveEmbeds = \true;
	}
	protected function addResponsiveStyle(array $attributes)
	{
		$css = 'position:absolute;top:0;left:0;width:100%;height:100%';
		if (isset($attributes['style']))
			$attributes['style'] .= ';' . $css;
		else
			$attributes['style'] = $css;
		return $attributes;
	}
	protected function addResponsiveWrapper($template, array $attributes)
	{
		$height = \trim($attributes['height'], '{}');
		$width  = \trim($attributes['width'], '{}');
		$isFixedHeight = (bool) \preg_match('(^\\d+$)D', $height);
		$isFixedWidth  = (bool) \preg_match('(^\\d+$)D', $width);
		if ($isFixedHeight && $isFixedWidth)
			$padding = \round(100 * $height / $width, 2);
		else
		{
			if (!\preg_match('(^[@$]?[-\\w]+$)D', $height))
				$height = '(' . $height . ')';
			if (!\preg_match('(^[@$]?[-\\w]+$)D', $width))
				$width = '(' . $width . ')';
			$padding = '<xsl:value-of select="100*' . $height . ' div'. $width . '"/>';
		}
		return '<div><xsl:attribute name="style">display:inline-block;width:100%;max-width:' . $width . 'px</xsl:attribute><div><xsl:attribute name="style">height:0;position:relative;padding-top:' . $padding . '%</xsl:attribute>' . $template . '</div></div>';
	}
	protected function addScrapes(Tag $tag, array $scrapes)
	{
		if (!isset($scrapes[0]))
			$scrapes = [$scrapes];
		$attributes   = [];
		$scrapeConfig = [];
		foreach ($scrapes as $scrape)
		{
			$attrNames = [];
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
			$entry = [$scrape['match'], $scrape['extract'], $attrNames];
			if (isset($scrape['url']))
				$entry[] = $scrape['url'];
			$scrapeConfig[] = $entry;
		}
		$tag->filterChain->insert(1, __NAMESPACE__ . '\\Parser::scrape')
						 ->addParameterByName('scrapeConfig')
						 ->addParameterByName('cacheDir')
						 ->setVar('scrapeConfig', $scrapeConfig)
						 ->setJS('function(){return true;}');
		return $attributes;
	}
	protected function appendFilter(Attribute $attribute, $filter)
	{
		if (!\in_array($filter, $this->allowedFilters, \true))
			throw new RuntimeException("Filter '" . $filter . "' is not allowed");
		$attribute->filterChain->append($this->configurator->attributeFilters[$filter]);
	}
	protected function buildFlash(array $siteConfig)
	{
		$attributes = [
			'width'  => $siteConfig['flash']['width'],
			'height' => $siteConfig['flash']['height'],
			'data'   => $siteConfig['flash']['src']
		];
		if (isset($siteConfig['flash']['base']))
			$attributes['base'] = $siteConfig['flash']['base'];
		if (isset($siteConfig['flash']['style']))
			$attributes['style'] = $siteConfig['flash']['style'];
		$isResponsive = $this->responsiveEmbeds && empty($siteConfig['unresponsive']) && $this->canBeResponsive($attributes);
		if ($isResponsive)
			$attributes = $this->addResponsiveStyle($attributes);
		$template = '<object type="application/x-shockwave-flash" typemustmatch="">';
		$template .= $this->generateAttributes($attributes, $isResponsive);
		$template .= '<param name="allowfullscreen" value="true"/>';
		if (isset($siteConfig['flash']['flashvars']))
		{
			$template .= '<param name="flashvars">';
			$template .= $this->generateAttributes([
				'value' => $siteConfig['flash']['flashvars']
			]);
			$template .= '</param>';
		}
		$template .= '<embed type="application/x-shockwave-flash">';
		$attributes['src'] = $attributes['data'];
		$attributes['allowfullscreen'] = '';
		unset($attributes['data']);
		if (isset($siteConfig['flash']['flashvars']))
			$attributes['flashvars'] = $siteConfig['flash']['flashvars'];
		$template .= $this->generateAttributes($attributes);
		$template .= '</embed></object>';
		if ($isResponsive)
			$template = $this->addResponsiveWrapper($template, $attributes);
		return $template;
	}
	protected function buildIframe(array $siteConfig)
	{
		$attributes = $siteConfig['iframe'];
		$attributes += [
			'allowfullscreen' => '',
			'frameborder'     => '0',
			'scrolling'       => 'no'
		];
		$isResponsive = $this->responsiveEmbeds && empty($siteConfig['unresponsive']) && $this->canBeResponsive($attributes);
		$template = '<iframe>' . $this->generateAttributes($attributes, $isResponsive) . '</iframe>';
		if ($isResponsive)
			$template = $this->addResponsiveWrapper($template, $attributes);
		return $template;
	}
	protected function buildTemplate(array $siteConfig)
	{
		return $siteConfig['template'];
	}
	protected function canBeResponsive(array $attributes)
	{
		return !\preg_match('([%<])', $attributes['width'] . $attributes['height']);
	}
	protected function generateAttributes(array $attributes, $addResponsive = \false)
	{
		if ($addResponsive)
			$attributes = $this->addResponsiveStyle($attributes);
		$xsl = '';
		foreach ($attributes as $attrName => $innerXML)
		{
			if (\strpos($innerXML, '<') === \false)
			{
				$tokens   = AVTHelper::parse($innerXML);
				$innerXML = '';
				foreach ($tokens as $_bada9f30)
				{
					list($type, $content) = $_bada9f30;
					if ($type === 'literal')
						$innerXML .= \htmlspecialchars($content, \ENT_NOQUOTES, 'UTF-8');
					else
						$innerXML .= '<xsl:value-of select="' . \htmlspecialchars($content, \ENT_QUOTES, 'UTF-8') . '"/>';
				}
			}
			$xsl .= '<xsl:attribute name="' . \htmlspecialchars($attrName, \ENT_QUOTES, 'UTF-8') . '">' . $innerXML . '</xsl:attribute>';
		}
		return $xsl;
	}
	protected function normalizeId($siteId)
	{
		$siteId = \strtolower($siteId);
		if (!\preg_match('(^[a-z0-9]+$)', $siteId))
			throw new InvalidArgumentException('Invalid site ID');
		return $siteId;
	}
}