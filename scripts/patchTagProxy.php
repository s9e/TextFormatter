#!/usr/bin/php
<?php

use s9e\SimpleDOM\SimpleDOM;

include 's9e/SimpleDOM/src/SimpleDOM.php';

$filepath = '/tmp/Overview.html';
//$filepath = '/tmp/grouping-content.html';

if (!file_exists($filepath))
{
	copy(
		'compress.zlib://http://dev.w3.org/html5/spec/Overview.html',
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
// End tags that can be omitted => closeParent rules
//==============================================================================

$closeParent = array();

foreach ($page->xpath('//h5[@id="optional-tags"]/following-sibling::p[following-sibling::h5/@id="element-restrictions"]') as $p)
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

		if (!$elName || preg_match('#^(?:html|head|base|link|meta|title|style|script|noscript|body|iframe|command)$#', $elName))
		{
			continue;
		}

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
						$elements[$elName]['isEmpty'][''] = 0;
						break 2;
					}

					$value = preg_replace('#^(?:either|or): #', '', $value);

					if (preg_match('#^((?:flow|phrasing|metadata|sectioning|heading|interactive|embedded) content|sectioning root|transparent)$#', $value, $m))
					{
						$elements[$elName]['allowChildCategory'][$m[1]][''] = 0;
					}
					elseif (preg_match('#^A ([a-z]+) element followed by a ([a-z]+) element$#', $value, $m))
					{
						$elements[$elName]['allowChildElement'][$m[1]][''] = 0;
						$elements[$elName]['allowChildElement'][$m[2]][''] = 0;
						break;
					}
					elseif (preg_match('#^if the ([a-z]+) attribute is present: empty$#', $value, $m))
					{
						$elements[$elName]['isEmpty']['@' . $m[1]] = 0;
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
						$elements[$elName]['denyAll'] = true;
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
					else
					{
						die("Could not interpret '$value' as $elName's content model\n");
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
foreach ($elements as $elName => &$element)
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
			$el[$v]  |= 1 << $bitNumber;

			if ($xpath)
			{
				$el[$v . $bitNumber] = $xpath;
			}
		}
	}

	if (!empty($element['transparent']))
	{
		$el['t'] = 1;
	}

	$arr[$elName] = $el;
}

// Sort the elements so that their order remain consistent across revisions
ksort($arr);

$php = '';
foreach ($arr as $elName => $values)
{
	$sep = "\n\t\t'" . $elName . "'=>array(";

	foreach ($values as $k => $v)
	{
		$php .= $sep . "'$k'=>";

		if ($v >= 0x80000000)
		{
			$php .= '0x' . dechex($v);
		}
		else
		{
			$php .= var_export($v, true);
		}

		$sep = ',';
	}

	if (isset($closeParent[$elName]))
	{
		ksort($closeParent[$elName]);
		$php .= $sep . "'cp'=>array('" . implode("','", array_keys($closeParent[$elName])) . "')";
	}

	$php .= '),';
}

$php = substr($php, 0, -1);

$filepath = __DIR__ . '/../src/ConfigBuilder/Helpers/HTML5/TagProxy.php';
$file = file_get_contents($filepath);

if (!preg_match('#(?<=static \\$htmlElements = array\\()(.*?)(?=\\n\\t\\);)#s', $file, $m, PREG_OFFSET_CAPTURE))
{
	die("Could not find the location in the file\n");
}

$file = substr($file, 0, $m[0][1]) . $php . substr($file, $m[0][1] + strlen($m[0][0]));

file_put_contents($filepath, $file);

die("Done.\n");