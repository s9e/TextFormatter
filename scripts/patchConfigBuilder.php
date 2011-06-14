#!/usr/bin/php
<?php

/**
* @todo use http://dev.w3.org/html5/spec/syntax.html#optional-tags for closeParent rules
*/

use s9e\Toolkit\SimpleDOM\SimpleDOM;

include __DIR__ . '/../src/SimpleDOM/SimpleDOM.php';

$filepath = '/tmp/Overview.html';
//$filepath = '/tmp/grouping-content.html';

if (!file_exists($filepath))
{
	file_put_contents($filepath, file_get_contents('http://dev.w3.org/html5/spec/Overview.html'));
}

$page = SimpleDOM::loadHTMLFile($filepath);

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

		if (!$elName || preg_match('#^(?:html|head|base|link|meta|title|style|script|noscript|body|iframe)$#', $elName))
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

			$value = rtrim(preg_replace('#\\s+#', ' ', strtolower($el->textContent())), '.');

			switch (rtrim($dt, ':'))
			{
				case 'Categories':
					if ($value === 'none')
					{
						continue;
					}

					$xpath = '';

					if (preg_match('#^((?:flow|phrasing|metadata|sectioning|heading|interactive|embedded) content|sectioning root|transparent|labelable element)$#', $value, $m))
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
						$category = 'phrasing content';
					}
					elseif (preg_match('#^if the (?:element\'s )?([a-z]+) attribute is (not )?in the ([a-z]+) state: +(interactive content)$#', $value, $m))
					{
						$category = $m[4];
						$xpath = '@' . $m[1] . (($m[2]) ? '!=' : '=') . '"' . $m[3] . '"';
					}
					elseif (preg_match('#formatblock candidate|form-associated#', $value))
					{
						continue;
					}
					else
					{
						echo $el->asXML();
						die ("Could not interpret '$value' as $elName's category\n");
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
					else
					{
						die("Could not interpret '$value' as $elName's content model\n");
					}	
					break;
			}
		}
	}
}

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
			ksort($element[$k]);
			$category = serialize(array_keys($element[$k]));

			foreach ($element[$k] as $elName => $xpaths)
			{
				foreach ($xpaths as $xpath => $void)
				{
					$elements[$elName]['categories'][$category][$xpath] = 0;
				}
			}

			$element[preg_replace('#Element$#D', 'Category', $k)][$category][$xpath] = 0;
		}
	}
}

// Count the number of tags per category and replace the "transparent" pseudo-category
foreach ($elements as &$element)
{
	$element += array(
		'categories' => array()
	);

	foreach ($element['categories'] as $category => $xpaths)
	{
		foreach ($xpaths as $xpath => $void)
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
	}

	if (isset($element['allowChildCategory']['transparent']))
	{
		foreach ($element['categories'] as $catName => $xpaths)
		{
			$element['allowChildCategory'][$catName] = $element['allowChildCategory']['transparent'];
		}

		unset($element['allowChildCategory']['transparent']);
	}
}
unset($element);

ksort($elements);
print_r($elements);

foreach ($categories as $k => &$v)
{
	$v = sprintf('%03d', $v) . $k;
}
unset($v);

arsort($categories);
$categories = array_flip(array_keys($categories));

$arr = array();
foreach ($elements as $elName => &$element)
{
	$el = array('c'  => 0);

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

		foreach ($element[$k] as $category => $xpaths)
		{
			$bitNumber = $categories[$category];
			$el[$v]  |= 1 << $bitNumber;

			if (!isset($xpaths['']))
			{
				foreach ($xpaths as $xpath => $void)
				{
					$el[$v . $bitNumber] = $xpath;
				}
			}
		}
	}

	$arr[$elName] = $el;
}

$php = '';
foreach ($arr as $elName => $values)
{
	$sep = "\n\t\t'" . $elName . "'=>array(";
	foreach ($values as $k => $v)
	{
		$php .= $sep . "'$k'=>" . var_export($v, true);
		$sep = ',';
	}
	$php .= '),';
}

$php = substr($php, 0, -1);

$filepath = __DIR__ . '/../src/TextFormatter/ConfigBuilder.php';
$file = file_get_contents($filepath);

if (!preg_match('#(?<=protected \\$htmlElements = array\\()(.*?)(?=\\n\\t\\);)#s', $file, $m, PREG_OFFSET_CAPTURE))
{
	die("Could not find the location in the file\n");
}

$file = substr($file, 0, $m[0][1]) . $php . substr($file, $m[0][1] + strlen($m[0][0]));

file_put_contents($filepath, $file);

die("Done.\n");