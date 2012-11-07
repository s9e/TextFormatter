#!/usr/bin/php
<?php

use s9e\SimpleDOM\SimpleDOM;

include 's9e/SimpleDOM/src/SimpleDOM.php';

$filepath = '/tmp/single-page.html';

if (!file_exists($filepath))
{
	copy(
		'compress.zlib://http://dev.w3.org/html5/spec/single-page.html',
		$filepath,
		stream_context_create(array(
			'http' => array(
				'header' => "Accept-Encoding: gzip"
			)
		))
	);
}

$page = SimpleDOM::loadHTMLFile($filepath);


//==============================================================================
// Tags on the "adoption agency" list
//==============================================================================

$nodes = $page->xpath('/html/body/div[@class="impl"]/dl[@class="switch"]/dt[@id="adoptionAgency"]');

if (!$nodes)
{
	die("Could not find the adoption agency list\n");
}

if (!preg_match_all('#"(\\w+)"#', $nodes[0]->textContent(), $matches))
{
	die("Could not parse the adoption agency list\n");
}

// Use element names as keys
$autoReopen = array_flip($matches[1]);

//==============================================================================
// Void elements
//==============================================================================

$nodes = $page->xpath('/html/body/dl/dt[dfn/@id="void-elements"]/following-sibling::dd[1]/code/a');

if (!$nodes)
{
	die("Could not find the void elements\n");
}

$voidElements = array();
foreach ($nodes as $node)
{
	$elName = $node->textContent();
	$voidElements[$elName] = 1;
}

//==============================================================================
// End tags that can be omitted => closeParent rules
//==============================================================================

$closeParent = array();

foreach ($page->xpath('/html/body/h5[@id="optional-tags"]/following-sibling::p[following-sibling::h5/@id="element-restrictions"]') as $p)
{
	$text = preg_replace('#\\s+#', ' ', $p->textContent());

	if (!preg_match("#^An? ([a-z0-5]+) element's end tag may be omitted if the \\1 element is immediately followed by a(?:n(other)?)? #", $text, $m))
	{
		continue;
	}

	$elName = $m[1];

	$text = substr($text, strlen($m[0]));
	$text = preg_replace('# or if there is no more content in the parent element\\.$#', '', $text);
	$text = rtrim($text, ' .,');

	if (preg_match('#^([a-z]+) element$#', $text, $m))
	{
		$closeParent[$m[1]][$elName] = 0;
	}
	elseif (preg_match('#^([a-z]+) element or an? ([a-z]+) element$#', $text, $m)
	     || preg_match('#^([a-z]+) or ([a-z]+) element$#', $text, $m)
	     || preg_match('#^([a-z]+) element, or if it is immediately followed by an? ([a-z]+) element$#', $text, $m))
	{
		$closeParent[$m[1]][$elName] = 0;
		$closeParent[$m[2]][$elName] = 0;
	}
	elseif (preg_match('#([a-z0-9 ,]+), or ([a-z]+), element, or if there is no more content in the parent element and the parent element is not an a element$#', $text, $m))
	{
		$closeParent[$m[2]][$elName] = 0;

		foreach (explode(', ', $m[1]) as $target)
		{
			$closeParent[$target][$elName] = 0;
		}
	}
	else
	{
		die("Could not interpret '$text'\n");
	}
}

//==============================================================================
// Content models
//==============================================================================

$elements = array();

foreach ($page->body->h4 as $h4)
{
	if (!isset($h4->dfn, $h4->dfn->code))
	{
		continue;
	}

	foreach ($h4->xpath('dfn/code') as $code)
	{
		$elName = (string) $code;

		$dl = $h4->firstOf('following-sibling::dl');

		foreach ($dl->xpath('dt|dd') as $el)
		{
			if ($el->getName() === 'dt')
			{
				$dt = $el->textContent();
				continue;
			}

			// Normalize whitespace and terminating punctuation
			$value = rtrim(preg_replace('#\\s+#', ' ', strtolower($el->textContent())), '.');

			// Remove <a> tags
			$value = preg_replace('#<a[^>]+>|</a>#', '', $value);

			switch (rtrim($dt, ':'))
			{
				case 'Categories':
					if ($value === 'none')
					{
						continue;
					}

					$xpath = '';

					if (preg_match('#^((?:palpable|flow|phrasing|metadata|sectioning|heading|interactive|embedded) content|sectioning root|transparent|labelable element)$#', $value, $m))
					{
						$category = $m[1];
					}
					elseif (preg_match('#^if the ([a-z]+) attribute is present: +([a-z ]+)$#', $value, $m)
					     || preg_match('#^if the element has a ([a-z]+) attribute: +([a-z ]+)$#', $value, $m))
					{
						$category = $m[2];
						$xpath = '@' . $m[1];
					}
					elseif ($value === 'when the element only contains phrasing content: phrasing content')
					{
						/**
						* @todo add XPath condition to check for non-phrasing content markup inside
						*       template, e.g. not(.//div | .//p ...)
						*/
						$category = 'phrasing content';
					}
					elseif (preg_match('#^if the (?:element\'s )?([a-z]+) attribute is (not )?in the ([a-z]+) state: +(interactive content|palpable content)$#', $value, $m))
					{
						$category = $m[4];
						$xpath = '@' . $m[1] . (($m[2]) ? '!=' : '=') . '"' . $m[3] . '"';
					}
					elseif (preg_match('#^if the (?:element\'s )?([a-z]+) attribute is (not )?in the ([a-z]+) state or the ([a-z]+) state: +(interactive content|palpable content)$#', $value, $m))
					{
						$category = $m[5];

						if ($m[2])
						{
							$xpath = '@' . $m[1] . '!="' . $m[3] . '" and @' . $m[1] . '!="' . $m[4] . '"';
						}
						else
						{
							$xpath = '@' . $m[1] . '="' . $m[3] . '" or @' . $m[1] . '="' . $m[4] . '"';
						}
					}
					elseif (preg_match('#if the element\'s children include at least one (?:[a-z_\\- ]+): palpable content$#', $value))
					{
						$category = 'palpable content';
					}
					elseif (preg_match('#formatblock candidate|form-associated#', $value))
					{
						continue;
					}
					else
					{
						echo $el->asXML(), "\n";
						die("Could not interpret '$value' as $elName's category\n");
					}

					$elements[$elName]['categories'][$category][$xpath] = 0;
					break;

				case 'Content model':
					if ($value === 'empty')
					{
						$elements[$elName]['denyAll'][''] = 0;
						break 2;
					}

					$value = preg_replace('#^(?:either|or): #', '', $value);

					if (preg_match('#^((?:flow|phrasing|metadata|sectioning|heading|interactive|embedded) content|sectioning root|transparent)$#', $value, $m))
					{
						$elements[$elName]['allowChildCategory'][$m[1]][''] = 0;
					}
					elseif (preg_match('#^a ([a-z]+) element followed by a ([a-z]+) element$#', $value, $m))
					{
						$elements[$elName]['allowChildElement'][$m[1]][''] = 0;
						$elements[$elName]['allowChildElement'][$m[2]][''] = 0;
						break;
					}
					elseif (preg_match('#^if the ([a-z]+) attribute is present: empty$#', $value, $m))
					{
						$elements[$elName]['denyAll']['@' . $m[1]] = 0;
					}
					elseif (preg_match('#^if the ([a-z]+) attribute is absent: zero or more ([a-z]+) elements$#', $value, $m))
					{
						$elements[$elName]['allowChildElement'][$m[2]]['not(@' . $m[1] . ')'] = 0;
					}
					elseif (preg_match('#^optionally a (legend) element, followed by (flow content)$#', $value, $m))
					{
						$elements[$elName]['allowChildElement'][$m[1]][''] = 0;
						$elements[$elName]['allowChildCategory'][$m[2]][''] = 0;
					}
					elseif ($value === 'one or more h1, h2, h3, h4, h5, and/or h6 elements')
					{
						$elements[$elName]['allowChildElement']['h1'][''] = 0;
						$elements[$elName]['allowChildElement']['h2'][''] = 0;
						$elements[$elName]['allowChildElement']['h3'][''] = 0;
						$elements[$elName]['allowChildElement']['h4'][''] = 0;
						$elements[$elName]['allowChildElement']['h5'][''] = 0;
						$elements[$elName]['allowChildElement']['h6'][''] = 0;
					}
					elseif (preg_match('#^flow content, but with no header or footer element descendants$#', $value))
					{
						$elements[$elName]['allowChildCategory']['flow content'][''] = 0;
						$elements[$elName]['denyDescendantElement']['header'][''] = 0;
						$elements[$elName]['denyDescendantElement']['footer'][''] = 0;
					}
					elseif (preg_match('#^flow content, but with no header, footer, sectioning content, or heading content descendants$#', $value))
					{
						$elements[$elName]['allowChildCategory']['flow content'][''] = 0;
						$elements[$elName]['denyDescendantElement']['header'][''] = 0;
						$elements[$elName]['denyDescendantElement']['footer'][''] = 0;
						$elements[$elName]['denyDescendantCategory']['sectioning content'][''] = 0;
						$elements[$elName]['denyDescendantCategory']['heading content'][''] = 0;
					}
					elseif (preg_match('#^flow content, but with no heading content descendants, no sectioning content descendants, and no header, footer, or address element descendants$#', $value))
					{
						$elements[$elName]['allowChildCategory']['flow content'][''] = 0;
						$elements[$elName]['denyDescendantElement']['header'][''] = 0;
						$elements[$elName]['denyDescendantElement']['footer'][''] = 0;
						$elements[$elName]['denyDescendantElement']['address'][''] = 0;
						$elements[$elName]['denyDescendantCategory']['heading content'][''] = 0;
						$elements[$elName]['denyDescendantCategory']['sectioning content'][''] = 0;
					}
					elseif (preg_match('#^((?:phrasing|flow|interactive) content), but (?:there must be|with) no (?:descendant )?([a-z]+) element( descendant)?s?$#', $value, $m))
					{
						$elements[$elName]['allowChildCategory'][$m[1]][''] = 0;
						$elements[$elName]['denyDescendantElement'][$m[2]][''] = 0;
					}
					elseif (preg_match('#^zero or more ([a-z]+)(?: or ([a-z]+))? elements ?$#', $value, $m))
					{
						$elements[$elName]['allowChildElement'][$m[1]][''] = 0;

						if (isset($m[2]))
						{
							$elements[$elName]['allowChildElement'][$m[2]][''] = 0;
						}
					}
					elseif (preg_match('#^zero or more groups each consisting of one or more\\s+([a-z]+) elements followed by one or more ([a-z]+)\\s+elements$#', $value, $m))
					{
						$elements[$elName]['allowChildElement'][$m[1]][''] = 0;
						$elements[$elName]['allowChildElement'][$m[2]][''] = 0;
					}
					elseif (preg_match('#^(transparent|phrasing content), but there must be no (interactive content) descendant$#', $value, $m))
					{
						$elements[$elName]['allowChildCategory'][$m[1]][''] = 0;
						$elements[$elName]['denyDescendantCategory'][$m[2]][''] = 0;
					}
					elseif (preg_match('#^one ([a-z]+) element followed by (flow content)$#', $value, $m))
					{
						$elements[$elName]['allowChildElement'][$m[1]][''] = 0;
						$elements[$elName]['allowChildCategory'][$m[2]][''] = 0;
					}
					elseif (preg_match('#^(flow content) followed by one ([a-z]+) element$#', $value, $m))
					{
						$elements[$elName]['allowChildCategory'][$m[1]][''] = 0;
						$elements[$elName]['allowChildElement'][$m[2]][''] = 0;
					}
					elseif ($value === 'text')
					{
						$elements[$elName]['allowText'] = 0;
					}
					elseif (preg_match('#^zero or more ([a-z]+) elements, then, (transparent)$#', $value, $m))
					{
						$elements[$elName]['allowChildElement'][$m[1]][''] = 0;
						$elements[$elName]['allowChildCategory'][$m[2]][''] = 0;
					}
					elseif ($value === 'one or more groups of: phrasing content followed either by a single rt element, or an rp element, an rt element, and another rp element')
					{
						$elements[$elName]['allowChildCategory']['phrasing content'][''] = 0;
						$elements[$elName]['allowChildElement']['rt'][''] = 0;
						$elements[$elName]['allowChildElement']['rp'][''] = 0;
					}
					elseif ($value === 'if the element has a src attribute: zero or more track elements, then transparent, but with no media element descendants')
					{
						$elements[$elName]['allowChildCategory']['transparent']['@src'] = 0;
						$elements[$elName]['allowChildElement']['track']['@src'] = 0;
						$elements[$elName]['denyChildCategory']['media']['@src'] = 0;
					}
					elseif (preg_match('#^if the element does not have a src attribute: (?:zero|one) or more source elements, then zero or more track elements, then transparent, but with no media element descendants$#', $value, $m))
					{
						$elements[$elName]['allowChildCategory']['transparent']['not(@src)'] = 0;
						$elements[$elName]['allowChildElement']['source']['not(@src)'] = 0;
						$elements[$elName]['denyChildCategory']['media']['not(@src)'] = 0;
					}
					elseif ($value === 'in this order: optionally a caption element, followed by zero or more colgroup elements, followed optionally by a thead element, followed optionally by a tfoot element, followed by either zero or more tbody elements or one or more tr elements, followed optionally by a tfoot element (but there can only be one tfoot element child in total)')
					{
						$elements[$elName]['allowChildElement']['caption'][''] = 0;
						$elements[$elName]['allowChildElement']['colgroup'][''] = 0;
						$elements[$elName]['allowChildElement']['thead'][''] = 0;
						$elements[$elName]['allowChildElement']['tfoot'][''] = 0;
						$elements[$elName]['allowChildElement']['tbody'][''] = 0;
						$elements[$elName]['allowChildElement']['tr'][''] = 0;
					}
					elseif ($value === "phrasing content, but with no descendant labelable elements unless it is the element's labeled control, and no descendant label elements")
					{
						// ignores the part that says "no descendant labelable elements unless it is
						// the element's labeled control"
						$elements[$elName]['allowChildCategory']['phrasing content'][''] = 0;
						$elements[$elName]['denyDescendantElement']['label'][''] = 0;
					}
					elseif ($value === 'zero or more param elements, then flow content and/or interactive content')
					{
						$elements[$elName]['allowChildElement']['param'][''] = 0;
						$elements[$elName]['allowChildCategory']['flow content'][''] = 0;
						$elements[$elName]['allowChildCategory']['interactive content'][''] = 0;
					}
					elseif ($elName === 'ruby' && $value === 'see prose')
					{
						// Ruby's content model is so complicated that the specs have to refer to
						// the "prose" where its exact content model is discussed. Here, we'll take
						// a big shortcut and hardcode something that makes sense in our context
						$elements[$elName]['allowChildCategory']['phrasing content'][''] = 0;
						$elements[$elName]['denyDescendantElement']['ruby'][''] = 0;
						$elements[$elName]['allowChildElement']['rt'][''] = 0;
						$elements[$elName]['allowChildElement']['rp'][''] = 0;
					}
					elseif ($value === 'if the document is an iframe srcdoc document or if title information is available from a higher-level protocol: zero or more elements of metadata content'
					     || $value === 'otherwise: one or more elements of metadata content, of which exactly one is a title element')
					{
						$elements[$elName]['allowChildCategory']['metadata content'][''] = 0;
					}
					elseif ($elName === 'style')
					{
						$elements[$elName]['denyAll'][''] = 0;
					}
					elseif ($elName === 'script')
					{
						$elements[$elName]['denyAll'][''] = 0;
					}
					elseif ($elName === 'noscript')
					{
						// This is a simplification of noscript's actual content model, which
						// differs whether it's found in <head> or in <body>
						$elements[$elName]['allowChildCategory']['transparent'][''] = 0;
						$elements[$elName]['denyDescendantElement']['noscript'][''] = 0;
					}
					elseif ($elName === 'iframe')
					{
						$elements[$elName]['denyAll'][''] = 0;
					}
					else
					{
						print("Could not interpret '$value' as $elName's content model\n");
					}	
					break;
			}
		}
	}
}

// Flatten XPath queries for each target
foreach ($elements as $elName => &$element)
{
	$flatten = array(
		'categories',
		'allowChildElement',
		'allowChildCategory',
		'denyChildElement',
		'denyChildCategory',
		'allowDescendantElement',
		'allowDescendantCategory',
		'denyDescendantElement',
		'denyDescendantCategory'
	);

	foreach ($flatten as $k)
	{
		if (!isset($element[$k]))
		{
			continue;
		}

		foreach ($element[$k] as &$xpath)
		{
			if (isset($xpath['']))
			{
				$xpath = '';
			}
			else
			{
				$xpath = implode(' or ', array_keys($xpath));

				// Optimize "@foo or not(@foo)" away
				$xpath = preg_replace('#^(@[a-z]+) or not\\(\\1\\)$#D', '', $xpath);
			}
		}
		unset($xpath);
	}
}
unset($element);

$categories = array();

// Create special categories for specific tag groups
foreach ($elements as &$element)
{
	$convert = array(
		'allowChildElement',
		'denyDescendantElement'
	);

	foreach ($convert as $k)
	{
		if (isset($element[$k]))
		{
			// Sort elements by name so their order remain consistent for serialization
			ksort($element[$k]);

			// Sort elements by XPath condition
			$xpathElements = array();
			foreach ($element[$k] as $elName => $xpath)
			{
				$xpathElements[$xpath][] = $elName;
			}

			foreach ($xpathElements as $xpath => $elNames)
			{
				$category = serialize(array_unique($elNames));

				foreach ($elNames as $elName)
				{
					// Add our pseudo-category to each element of the group
					$elements[$elName]['categories'][$category] = '';
				}

				$element[preg_replace('#Element$#D', 'Category', $k)][$category] = $xpath;
			}
		}
	}
}
unset($element);

// Count the number of tags per category and remove the "transparent" pseudo-category
foreach ($elements as $elName => &$element)
{
	$element += array(
		'categories' => array()
	);

	foreach ($element['categories'] as $category => $xpath)
	{
		if (isset($categories[$category]))
		{
			++$categories[$category];
		}
		else
		{
			$categories[$category] = 1;
		}
	}

	if (isset($element['allowChildCategory']['transparent']))
	{
		if ($element['allowChildCategory']['transparent'] !== '')
		{
			// There's currently no real-world example of that, this is only future-proofing
			echo "!!! ATTENTION !!! The $elName element uses a conditionally transparent content model, which isn't currently supported\n";
		}

		$element['transparent'] = 1;
		unset($element['allowChildCategory']['transparent']);
	}
}
unset($element);

// Concatenate each category's number to its name so we can sort them by frequency then name
foreach ($categories as $k => &$v)
{
	$v = sprintf('%03d', $v) . $k;
}
unset($v);

// Sort the categories then flip their keys so their values go in ascending order starting from 0
arsort($categories);
$categories = array_flip(array_keys($categories));

$arr = array();
foreach ($elements as $elName => $element)
{
	$el = array();

	$fields = array(
		'categories' => 'c',
		'allowChildCategory' => 'ac',
		'denyDescendantCategory' => 'dd'
	);

	foreach ($fields as $k => $v)
	{
		if (!isset($element[$k]))
		{
			continue;
		}

		$el[$v] = 0;

		foreach ($element[$k] as $category => $xpath)
		{
			$bitNumber = $categories[$category];
			$el[$v] |= 1 << $bitNumber;

			if ($xpath)
			{
				$el[$v . $bitNumber] = $xpath;
			}
		}
	}

	// Test whether this element allows text nodes
	$noText = true;
	if (isset($el['ac']))
	{
		foreach (array('flow content', 'palpable content', 'phrasing content') as $category)
		{
			if ($el['ac'] & (1 << $categories[$category]))
			{
				$noText = false;
				break;
			}
		}
	}

	if ($noText && !isset($element['allowText']))
	{
		$el['nt'] = 1;
	}

	if (!empty($element['denyAll']))
	{
		$el['da'] = 1;

		if (!isset($element['denyAll']['']))
		{
			$xpath = key($element['denyAll']);
			if ($xpath)
			{
				$el['da0'] = $xpath;
			}
		}
	}

	if (isset($voidElements[$elName]))
	{
		$el['v'] = 1;
	}

	if (!empty($element['transparent']))
	{
		$el['t'] = 1;
		unset($el['nt']);
	}

	// Mark elements that are not phrasing content -- "b" stands for "block"
	if (!($el['c'] & (1 << $categories['phrasing content'])))
	{
		$el['b'] = 1;
	}

	// Mark elements that are on the "adoption agency" list
	if (isset($autoReopen[$elName]))
	{
		$el['ar'] = 1;
	}

	$arr[$elName] = $el;
}

// Sort the elements so that their order remain consistent across revisions
ksort($arr);

$php = '';
foreach ($arr as $elName => $elValues)
{
	$phpValues = array();

	foreach ($elValues as $k => $v)
	{
		if ($k === 'c' || $k === 'ac' || $k === 'dd')
		{
			$str = '"';

			// Build a bitfield using the octal notation, starting with the least significant byte
			do
			{
				$str .= '\\' . decoct($v & 255);
				$v >>= 8;
			}
			while ($v);

			$str .= '"';
		}
		else
		{
			$str = var_export($v, true);
		}

		$phpValues[$k] = $str;
	}

	if (isset($closeParent[$elName]))
	{
		ksort($closeParent[$elName]);
		$phpValues['cp'] = "array('" . implode("','", array_keys($closeParent[$elName])) . "')";
	}

	$php .= "\n\t\t'" . $elName . "'=>array(";
	foreach ($phpValues as $k => $v)
	{
		$php .= "'$k'=>$v,";
	}
	$php = substr($php, 0, -1) . '),';
}

$php = substr($php, 0, -1);

$filepath = __DIR__ . '/../src/Configurator/Helpers/HTML5/TemplateForensics.php';
$file = file_get_contents($filepath);

if (!preg_match('#(?<=static \\$htmlElements = array\\()(.*?)(?=\\n\\t\\);)#s', $file, $m, PREG_OFFSET_CAPTURE))
{
	die("Could not find the location in the file\n");
}

$file = substr($file, 0, $m[0][1]) . $php . substr($file, $m[0][1] + strlen($m[0][0]));

file_put_contents($filepath, $file);

die("Done.\n");