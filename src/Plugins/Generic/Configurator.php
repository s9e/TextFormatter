<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Generic;

use DOMAttr;
use DOMText;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	protected $collection = array();

	public function add($regexp, $template, $tagName = \null)
	{
		$valid = \false;

		try
		{
			$valid = @\preg_match_all($regexp, '', $m);
		}
		catch (Exception $e)
		{
		}

		if ($valid === \false)
			throw new InvalidArgumentException('Invalid regexp');

		$assertion         = '(?<!\\\\)(?:\\\\\\\\)*\\K';
		$captureSubpattern = '(?:\\\\[0-9]+|\\$[0-9]+|\\$\\{[0-9]+\\})';
		$capturesRegexp    = '#' . $assertion . $captureSubpattern . '#';

		$captures = array();
		\preg_match_all($capturesRegexp, $template, $matches);
		foreach ($matches[0] as $match)
		{
			$idx = \trim($match, '\\${}');
			$captures[$idx] = \false;
		}

		$dom   = TemplateHelper::loadTemplate($template);
		$xpath = new DOMXPath($dom);

		$passthrough = array();
		foreach ($xpath->query('//text()') as $node)
		{
			\preg_match_all($capturesRegexp, $node->textContent, $matches);
			foreach ($matches[0] as $match)
			{
				$idx = \trim($match, '\\${}');

				$passthrough[$idx] = \false;
			}
		}

		$urlCaptures = array();
		$nodes       = TemplateHelper::getURLNodes($dom);
		foreach ($nodes as $node)
			if ($node instanceof DOMAttr
			 && \preg_match('#^\\s*' . $captureSubpattern . '#', $node->value, $m))
			{
				$idx = \preg_replace('#\\D+#', '', $m[0]);
				$urlCaptures[$idx] = $idx;
			}

		if (!isset($tagName))
			$tagName = \sprintf('G%08X', \crc32($regexp));

		$tag = new Tag;

		$regexpInfo = RegexpParser::parse($regexp);

		$attributes = array();

		$idx = 0;

		foreach ($regexpInfo['tokens'] as $token)
		{
			if ($token['type'] !== 'capturingSubpatternStart')
				continue;

			++$idx;

			if (!isset($token['name']))
			{
				if (!isset($captures[$idx]))
					continue;

				if (isset($passthrough[$idx]) && \preg_match('#^\\.[*+]\\??$#D', $token['content']))
					$passthrough[$idx] = \true;
			}

			$attributes[$idx] = $token;
		}

		$passthrough    = \array_filter($passthrough);
		$passthroughIdx = 0;
		if (\count($passthrough) === 1)
		{
			$passthroughIdx = \key($passthrough);

			if (!isset($urlCaptures[$passthroughIdx]))
				unset($attributes[$passthroughIdx]);
		}

		$newNamedSubpatterns = array();

		foreach ($attributes as $idx => $token)
		{
			if (isset($token['name']))
				$attrName = $token['name'];
			elseif (isset($captures[$idx]))
			{
				$attrName = '_' . $idx;

				$newNamedSubpatterns[$attrName] = $token['pos'];
			}
			else
				throw new LogicException('Tried to create an attribute for an unused capture with no name. Please file a bug');

			if (isset($tag->attributes[$attrName]))
				throw new RuntimeException('Duplicate named subpatterns are not allowed');

			$attribute = $tag->attributes->add($attrName);
			$attribute->required = \true;

			$endToken = $regexpInfo['tokens'][$token['endToken']];
			$lpos = $token['pos'];
			$rpos = $endToken['pos'] + $endToken['len'];

			$attrRegexp = $regexpInfo['delimiter']
			            . '^' . \substr($regexpInfo['regexp'], $lpos, $rpos - $lpos) . '$'
			            . $regexpInfo['delimiter']
			            . \str_replace('D', '', $regexpInfo['modifiers'])
			            . 'D';

			$filter = $this->configurator->attributeFilters->get('#regexp');
			$filter->setRegexp($attrRegexp);
			$attribute->filterChain->append($filter);

			if (isset($urlCaptures[$idx]))
			{
				$filter = $this->configurator->attributeFilters->get('#url');
				$attribute->filterChain->append($filter);
			}

			$captures[$idx] = $attrName;
		}

		foreach (\array_reverse($newNamedSubpatterns) as $attrName => $pos)
			$regexp = \substr_replace($regexp, '?<' . $attrName . '>', 2 + $pos, 0);

		$captures = \array_filter($captures);

		$template = TemplateHelper::replaceTokens(
			$template,
			$capturesRegexp,
			function ($m, $node) use ($captures, $passthroughIdx)
			{
				$idx  = (int) \trim($m[0], '\\${}');

				if ($idx === 0)
					return array('expression', '.');

				if ($idx === $passthroughIdx)
					if (!isset($captures[$idx])
					 || $node instanceof DOMText)
						return array('passthrough', \false);

				if (isset($captures[$idx]))
					return array('expression', '@' . $captures[$idx]);

				return array('literal', '');
			}
		);

		$template = TemplateHelper::replaceTokens(
			$template,
			'#\\\\+[0-9${\\\\]#',
			function ($m)
			{
				return array('literal', \stripslashes($m[0]));
			}
		);

		$tag->template = $template;

		$this->configurator->templateNormalizer->normalizeTag($tag);

		$this->configurator->templateChecker->checkTag($tag);

		$this->configurator->tags->add($tagName, $tag);

		$this->collection[$tagName] = array($regexp, $passthroughIdx);

		return $tagName;
	}

	public function asConfig()
	{
		if (!\count($this->collection))
			return;

		$generics   = array();
		$jsGenerics = array();
		foreach ($this->collection as $tagName => $_2321574420)
		{
			list($regexp, $passthroughIdx) = $_2321574420;
			$generics[] = array($tagName, $regexp, $passthroughIdx);

			if (isset($this->configurator->javascript))
			{
				$jsRegexp = RegexpConvertor::toJS($regexp);
				$jsRegexp->flags .= 'g';

				$jsGenerics[] = array($tagName, $jsRegexp, $passthroughIdx, $jsRegexp->map);
			}
		}

		$variant = new Variant($generics);
		if (isset($this->configurator->javascript))
			$variant->set('JS', $jsGenerics);

		return array('generics' => $variant);
	}
}