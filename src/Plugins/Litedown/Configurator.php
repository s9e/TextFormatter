<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown;

use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\Litedown\Parser\Slugger;

class Configurator extends ConfiguratorBase
{
	/**
	* @var bool Whether to decode HTML entities in attribute values
	*/
	public $decodeHtmlEntities = false;

	/**
	* @var array Default tags
	*/
	protected $tags = [
		'C'      => '<code><xsl:apply-templates/></code>',
		'CODE'   => [
			'attributes' => [
				'lang' => [
					'filterChain' => ['#simpletext'],
					'required'    => false
				]
			],
			'template' =>
				'<pre>
					<code>
						<xsl:if test="@lang">
							<xsl:attribute name="class">
								<xsl:text>language-</xsl:text>
								<xsl:value-of select="@lang"/>
							</xsl:attribute>
						</xsl:if>
						<xsl:apply-templates/>
					</code>
				</pre>'
		],
		'DEL'    => '<del><xsl:apply-templates/></del>',
		'EM'     => '<em><xsl:apply-templates/></em>',
		'EMAIL'  => [
			'attributes' => ['email' => ['filterChain' => ['#email']]],
			'template'   => '<a href="mailto:{@email}"><xsl:apply-templates/></a>'
		],
		'H1'     => '<h1><xsl:apply-templates/></h1>',
		'H2'     => '<h2><xsl:apply-templates/></h2>',
		'H3'     => '<h3><xsl:apply-templates/></h3>',
		'H4'     => '<h4><xsl:apply-templates/></h4>',
		'H5'     => '<h5><xsl:apply-templates/></h5>',
		'H6'     => '<h6><xsl:apply-templates/></h6>',
		'HR'     => '<hr/>',
		'IMG'    => [
			'attributes' => [
				'alt'   => ['required'    => false   ],
				'src'   => ['filterChain' => ['#url']],
				'title' => ['required'    => false   ]
			],
			'template' => '<img src="{@src}"><xsl:copy-of select="@alt"/><xsl:copy-of select="@title"/></img>'
		],
		'ISPOILER' => '<span class="spoiler" data-s9e-livepreview-ignore-attrs="style" onclick="removeAttribute(\'style\')" style="background:#444;color:transparent"><xsl:apply-templates/></span>',
		'LI'     => '<li><xsl:apply-templates/></li>',
		'LIST'   => [
			'attributes' => [
				'start' => [
					'filterChain' => ['#uint'],
					'required'    => false
				],
				'type' => [
					'filterChain' => ['#simpletext'],
					'required'    => false
				]
			],
			'template' =>
				'<xsl:choose>
					<xsl:when test="not(@type)">
						<ul><xsl:apply-templates/></ul>
					</xsl:when>
					<xsl:otherwise>
						<ol><xsl:copy-of select="@start"/><xsl:apply-templates/></ol>
					</xsl:otherwise>
				</xsl:choose>'
		],
		'QUOTE'   => '<blockquote><xsl:apply-templates/></blockquote>',
		'SPOILER' => '<details class="spoiler" data-s9e-livepreview-ignore-attrs="open"><xsl:apply-templates/></details>',
		'STRONG'  => '<strong><xsl:apply-templates/></strong>',
		'SUB'     => '<sub><xsl:apply-templates/></sub>',
		'SUP'     => '<sup><xsl:apply-templates/></sup>',
		'URL'     => [
			'attributes' => [
				'title' => ['required'    => false   ],
				'url'   => ['filterChain' => ['#url']]
			],
			'template' => '<a href="{@url}"><xsl:copy-of select="@title"/><xsl:apply-templates/></a>'
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

			// Add this tag
			$this->configurator->tags->add($tagName, $tagConfig);
		}
	}

	/**
	* Add an "id" attribute to headers
	*
	* @param  string $prefix Prefix used for the "id" value
	* @return void
	*/
	public function addHeadersId(string $prefix = ''): void
	{
		for ($i = 1; $i <= 6; ++$i)
		{
			$tagName = 'H' . $i;
			if (isset($this->configurator->tags[$tagName]))
			{
				$this->addHeaderId($this->configurator->tags[$tagName], $prefix);
			}
		}
	}

	/**
	* Add an "id" attribute to given tag
	*
	* @param  Tag    $tag
	* @param  string $prefix Prefix used for the "id" value
	* @return void
	*/
	protected function addHeaderId(Tag $tag, string $prefix): void
	{
		if (!isset($tag->attributes['slug']))
		{
			unset($tag->attributes['slug']);
		}

		$tag->attributes->add('slug')->required = false;
		$tag->filterChain
			->append(Slugger::class . '::setTagSlug($tag, $innerText)')
			->setJS(Slugger::getJS());

		$dom = $tag->template->asDOM();
		foreach ($dom->query('//xsl:if[@test = "@slug"]') as $if)
		{
			// Remove any pre-existing xsl:if from previous invocations
			$if->remove();
		}
		foreach ($dom->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6') as $header)
		{
			$header->prependXslIf('@slug')
			       ->appendXslAttribute('id', $prefix)
			       ->appendXslValueOf('@slug');
		}
		$dom->saveChanges();
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		return ['decodeHtmlEntities' => (bool) $this->decodeHtmlEntities];
	}

	/**
	* {@inheritdoc}
	*/
	public function getJSHints()
	{
		return ['LITEDOWN_DECODE_HTML_ENTITIES' => (int) $this->decodeHtmlEntities];
	}

	/**
	* {@inheritdoc}
	*/
	public function getJSParser()
	{
		$js = file_get_contents(__DIR__ . '/Parser/ParsedText.js') . "\n"
		    . file_get_contents(__DIR__ . '/Parser/Passes/AbstractInlineMarkup.js') . "\n"
		    . file_get_contents(__DIR__ . '/Parser/Passes/AbstractScript.js') . "\n"
		    . file_get_contents(__DIR__ . '/Parser/LinkAttributesSetter.js');

		$passes = [
			'Blocks',
			'LinkReferences',
			'InlineCode',
			'Images',
			'InlineSpoiler',
			'Links',
			'Strikethrough',
			'Subscript',
			'Superscript',
			'Emphasis',
			'ForcedLineBreaks'
		];
		foreach ($passes as $pass)
		{
			$js .= "\n(function(){\n"
			     . file_get_contents(__DIR__ . '/Parser/Passes/' . $pass . '.js') . "\n"
			     . "parse();\n"
			     . "})();";
		}

		return $js;
	}
}