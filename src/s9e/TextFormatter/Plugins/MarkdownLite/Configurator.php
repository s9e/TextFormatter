<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MarkdownLite;

use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var array Default tags
	*/
	protected $tags = [
		'C'      => '<code class="inline"><xsl:apply-templates /></code>',
		'CODE'   => [
			'attributes' => [
				'lang' => [
					'filterChain' => ['#simpletext'],
					'required'    => false
				]
			],
			'template' =>
				'<pre>
					<code class="{@lang}">
						<xsl:apply-templates />
					</code>
				</pre>'
		],
		'DEL'    => '<del><xsl:apply-templates/></del>',
		'EM'     => '<em><xsl:apply-templates/></em>',
		'H1'     => '<h1><xsl:apply-templates/></h1>',
		'H2'     => '<h2><xsl:apply-templates/></h2>',
		'H3'     => '<h3><xsl:apply-templates/></h3>',
		'H4'     => '<h4><xsl:apply-templates/></h4>',
		'H5'     => '<h5><xsl:apply-templates/></h5>',
		'H6'     => '<h6><xsl:apply-templates/></h6>',
		'HR'     => '<hr/>',
		'IMG'    => [
			'attributes' => [
				'alt'   => ['required' => false],
				'src'   => ['filterChain' => ['#url']],
				'title' => ['required' => false]
			],
			'template' => '<img alt="{@alt}" src="{@src}" title="{@title}"/>'
		],
		'LI'     => '<li><xsl:apply-templates/></li>',
		'LIST'   => [
			'attributes' => [
				'type' => [
					'filterChain' => ['#simpletext'],
					'required'    => false
				]
			],
			'template' =>
				'<xsl:choose>
					<xsl:when test="not(@type)">
						<ul><xsl:apply-templates /></ul>
					</xsl:when>
					<xsl:when test="contains(\'upperlowerdecim\',substring(@type,1,5))">
						<ol style="list-style-type:{@type}"><xsl:apply-templates /></ol>
					</xsl:when>
					<xsl:otherwise>
						<ul style="list-style-type:{@type}"><xsl:apply-templates /></ul>
					</xsl:otherwise>
				</xsl:choose>'
		],
		'QUOTE'  => '<blockquote><xsl:apply-templates/></blockquote>',
		'STRONG' => '<strong><xsl:apply-templates/></strong>',
		'SUP'    => '<sup><xsl:apply-templates/></sup>',
		'URL'    => [
			'attributes' => [
				'url' => [
					'filterChain' => ['#url']
				]
			],
			'template' => '<a href="{@url}"><xsl:apply-templates/></a>'
		]
	];

	/**
	* {@inheritdoc}
	*/
	protected function setUp()
	{
		$this->configurator->rulesGenerator->append('ManageParagraphs');

		foreach ($this->tags as $tagName => $tagConfig)
		{
			// Skip this tag if it already exists
			if (isset($this->configurator->tags[$tagName]))
			{
				continue;
			}

			// If the tag's config is a single string, it's really its default template
			if (is_string($tagConfig))
			{
				$tagConfig = ['template' => $tagConfig];
			}

			// Replace default filters in the definition
			if (isset($tagConfig['attributes']))
			{
				foreach ($tagConfig['attributes'] as &$attributeConfig)
				{
					if (isset($attributeConfig['filterChain']))
					{
						foreach ($attributeConfig['filterChain'] as &$filter)
						{
							if (is_string($filter) && $filter[0] === '#')
							{
								$filter = $this->configurator->attributeFilters[$filter];
							}
						}
						unset($filter);
					}
				}
				unset($attributeConfig);
			}

			// Add this tag
			$this->configurator->tags->add($tagName, $tagConfig);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		return [];
	}
}