#!/usr/bin/php
<?php

foreach (array_keys(get_defined_vars()) as $var)
{
	unset($$var);
}
unset($var);

$LocationPath = 'RelativeLocationPath|AbsoluteLocationPath';
$AbsoluteLocationPath = '//? RelativeLocationPath?|AbbreviatedAbsoluteLocationPath';
$RelativeLocationPath = 'Step|(?<RelativeLocationPath0>(?:Step //?)* Step) //? Step';

$Step = 'AxisSpecifier NodeTest (?<predicates0>(?&predicates))?|AbbreviatedStep';
$AxisSpecifier = 'AxisName ::|AbbreviatedAxisSpecifier';

$AxisName = 'ancestor|ancestor-or-self|attribute|child|descendant|descendant-or-self|following|following-sibling|namespace|parent|preceding|preceding-sibling|self';

$NodeTest = 'NameTest|NodeType \\( \\)|processing-instruction \\( Literal \\)';

$Predicate = '\\[ PredicateExpr \\]';
$PredicateExpr = 'Expr';
$predicates = 'Predicate (?<predicates0>(?&predicates))?';

$AbbreviatedAbsoluteLocationPath = '// RelativeLocationPath';
$AbbreviatedRelativeLocationPath = 'Step // RelativeLocationPath';
$AbbreviatedStep = '\\.\\.?';
$AbbreviatedAxisSpecifier = '@?';

$Expr = 'OrExpr';
$PrimaryExpr = 'VariableReference|\\( Expr \\)|Literal|Number|FunctionCall';

$FunctionCall = 'FunctionName \\( (?<arguments0>(?&arguments))? \\)';
$arguments    = 'Argument (?:, (?<arguments0>(?&arguments)))?';
$Argument     = 'Expr';

$UnionExpr  = 'PathExpr (?:\\| UnionExpr)?';
$PathExpr   = 'FilterExpr (?://? RelativeLocationPath)?|LocationPath';
$FilterExpr = 'PrimaryExpr (?<predicates0>(?&predicates))?';

$OrExpr  = 'AndExpr (?:or AndExpr)?';
$AndExpr = 'EqualityExpr (?:and AndExpr)?';
$EqualityExpr   = 'RelationalExpr (?:!?= RelationalExpr)?';
$RelationalExpr = 'AdditiveExpr (?:[<>]=? AdditiveExpr)?';

$AdditiveExpr = 'MultiplicativeExpr (?:[-+] MultiplicativeExpr)?';
$MultiplicativeExpr = 'UnaryExpr (?:(?:MultiplyOperator|div|mod) UnaryExpr)?';
$UnaryExpr = 'UnionExpr|- UnaryExpr';

$Literal = '"[^"]*"|\'[^\']*\'';
$Number  = 'Digits (?:\\. Digits?)?|\\. Digits';
$Digits  = '[0-9]+';
$Operator = 'OperatorName|MultiplyOperator|//?|\\||\\+|-|=|!=|<=?|>=?';
$OperatorName = 'and|or|mod|div';
$MultiplyOperator = '\\*';
$FunctionName = '(?!NodeType)QName';
$VariableReference = '\\$ QName';
$NameTest = '\\*|NCName : \\*|QName';
$NodeType = 'comment|text|processing-instruction|node';

$QName          = 'PrefixedName|UnprefixedName';
$PrefixedName   = 'Prefix : LocalPart';
$UnprefixedName = 'LocalPart';
$Prefix         = 'NCName';
$LocalPart      = 'NCName';
$NCName         = '[-\\w]+';

$tokens = get_defined_vars();

foreach ($tokens as $tokenName => &$expr)
{
	// Turn TokenNames into references, but avoid matching (?<TokenName>)
	$expr = preg_replace('/(?<![<\\w])[A-Z]\\w+/', '(?&$0)', str_replace(' ', '\\s*', $expr));
}
unset($expr);

$regexps = [];
foreach ($tokens as $tokenName => $expr)
{
	$regexp = '(^\\s*(?:' . $expr . ')\\s*$)';

	// Capture the first generation of references, with their name appended with a number. They will
	// be reparsed at runtime
	$i = 0;
	$regexp = preg_replace_callback(
		'/\\(\\?&(\\w+)\\)/',
		function ($m) use (&$i)
		{
			return '(?<' . $m[1] . ++$i . '>' . $m[0] . ')';
		},
		$regexp
	);

	$k = 'a';
	$defined = [];
	do
	{
		$continue = false;

		$regexp = preg_replace_callback(
			'/\\(\\?&(\\w+)\\)/',
			function ($m) use (&$continue, &$defined, &$k, $tokens)
			{
				$name = $m[1];

				if (isset($defined[$name]))
				{
					return '(?&' . $defined[$name] . ')';
				}

				$continue = true;

				// Rename the capture to a single letter
				$defined[$name] = $defined[$k] = $k;
				++$k;

				// Remove named captures from subpatterns
				$regexp = preg_replace('/\\(\\?<[^>]+>/', '(?:', $tokens[$name]);

				return '(?<' . $defined[$name] . '>' . $regexp . ')';
			},
			$regexp
		);
	}
	while ($continue);

	// Collect the names of all references
	preg_match_all('/\\(\\?&([^)]+)/', $regexp, $m);

	// Replace named captures that are not used as a reference and that do not end with a digit
	$regexp = preg_replace(
		'/\\(\\?<(?!' . implode('>|', $m[1]) . '>)\\w+(?<!\\d)>/',
		'(?:',
		$regexp
	);

	// Replace non-capturing subpatterns that only contain a reference
	$regexp = preg_replace('/\\(\\?:(\\(\\?&[^)]+\\))\\)/', '$1', $regexp);

	// Iteratively replace unnecessary subpatterns that contain a simple expression
	do
	{
		$regexp = preg_replace('/\\(\\?:([^(|)]+)(?<!\\\\)\\)(?![?*+])/', '$1', $regexp, -1, $cnt);
	}
	while ($cnt);

	$regexps[$tokenName] = $regexp;
}

ksort($regexps);

$php = '';
foreach ($regexps as $k => $v)
{
	$php .= "\n\t\t" . var_export($k, true) . ' => ' . var_export($v, true). ',';
}
$php = substr($php, 0, -1);

$filepath = __DIR__ . '/../src/s9e/TextFormatter/Configurator/Helpers/XPathParser.php';
$file = file_get_contents($filepath);

if (!preg_match('#(?<=static \\$regexps = \\[)(.*?)(?=\\n\\t\\];)#s', $file, $m, PREG_OFFSET_CAPTURE))
{
	die("Could not find the location in the file\n");
}

$file = substr($file, 0, $m[0][1]) . $php . substr($file, $m[0][1] + strlen($m[0][0]));

file_put_contents($filepath, $file);

die("Done.\n");