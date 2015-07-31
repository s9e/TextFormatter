<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $attrName = 'seq';
	protected $aliases = array();
	protected $forceImageSize = \true;
	protected $imageSet = 'twemoji';
	protected $imageSize = 16;
	protected $imageType = 'png';
	protected $tagName = 'EMOJI';
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
			return;
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName)->filterChain->append(
			$this->configurator->attributeFilters['#identifier']
		);
		$this->resetTemplate();
	}
	public function addAlias($alias, $emoji)
	{
		$this->aliases[$alias] = $emoji;
	}
	public function forceImageSize()
	{
		$this->forceImageSize = \true;
		$this->resetTemplate();
	}
	public function removeAlias($alias)
	{
		unset($this->aliases[$alias]);
	}
	public function omitImageSize()
	{
		$this->forceImageSize = \false;
		$this->resetTemplate();
	}
	public function getAliases()
	{
		return $this->aliases;
	}
	public function setImageSize($size)
	{
		$this->imageSize = (int) $size;
		$this->resetTemplate();
	}
	public function useEmojiOne()
	{
		$this->imageSet = 'emojione';
		$this->resetTemplate();
	}
	public function usePNG()
	{
		$this->imageType = 'png';
		$this->resetTemplate();
	}
	public function useSVG()
	{
		$this->imageType = 'svg';
		$this->resetTemplate();
	}
	public function useTwemoji()
	{
		$this->imageSet = 'twemoji';
		$this->resetTemplate();
	}
	public function asConfig()
	{
		$config = array(
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		);
		if (!empty($this->aliases))
		{
			$aliases = \array_keys($this->aliases);
			$regexp  = '/' . RegexpBuilder::fromList($aliases) . '/';
			$config['aliases']       = $this->aliases;
			$config['aliasesRegexp'] = new Regexp($regexp, \true);
			$quickMatch = ConfigHelper::generateQuickMatchFromList($aliases);
			if ($quickMatch !== \false)
				$config['aliasesQuickMatch'] = $quickMatch;
		}
		return $config;
	}
	protected function getEmojiOneTemplate()
	{
		$template = '<img alt="{.}" class="emoji"';
		if ($this->forceImageSize)
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		$template .= '>
			<xsl:attribute name="src">
				<xsl:text>//cdn.jsdelivr.net/emojione/assets/' . $this->imageType . '/</xsl:text>
				<xsl:if test="contains(@seq, \'-20e3\') or @seq = \'a9\' or @seq = \'ae\'">00</xsl:if>
				<xsl:value-of select="translate(@seq, \'abcdef\', \'ABCDEF\')"/>
				<xsl:text>.' . $this->imageType . '</xsl:text>
			</xsl:attribute>
		</img>';
		return $template;
	}
	protected function getTargetSize(array $sizes)
	{
		$k = 0;
		foreach ($sizes as $k => $size)
			if ($size >= $this->imageSize)
				break;
		return $sizes[$k];
	}
	protected function getTemplate()
	{
		return ($this->imageSet === 'emojione') ? $this->getEmojiOneTemplate() :  $this->getTwemojiTemplate();
	}
	protected function getTwemojiTemplate()
	{
		$template = '<img alt="{.}" class="emoji" draggable="false"';
		if ($this->forceImageSize)
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		$template .= ' src="//twemoji.maxcdn.com/';
		if ($this->imageType === 'svg')
			$template .= 'svg';
		else
		{
			$size = $this->getTargetSize(array(16, 36, 72));
			$template .= $size . 'x' . $size;
		}
		$template .= '/{@seq}.' . $this->imageType . '"/>';
		return $template;
	}
	protected function resetTemplate()
	{
		$this->getTag()->template = $this->getTemplate();
	}
}