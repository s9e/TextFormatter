#!/usr/bin/php
<?php

foreach (array_keys(get_defined_vars()) as $var)
{
	unset($$var);
}
unset($var);

$LocationPath = 'RelativeLocationPath | AbsoluteLocationPath';
$AbsoluteLocationPath = '//? RelativeLocationPath? | AbbreviatedAbsoluteLocationPath';
$RelativeLocationPath = 'Step (?://? Step )*';

$Step = 'AxisSpecifier NodeTest Predicate* | AbbreviatedStep';
$AxisSpecifier = 'AxisName :: | AbbreviatedAxisSpecifier';

$AxisName = 'ancestor | ancestor-or-self | attribute | child | descendant | descendant-or-self | following | following-sibling | namespace | parent | preceding | preceding-sibling | self';

$NodeTest = 'NameTest | NodeType \\( \\) | processing-instruction \\( Literal \\)';

$Predicate = '\\[ PredicateExpr \\]';
$PredicateExpr = 'Expr';

$AbbreviatedAbsoluteLocationPath = '// RelativeLocationPath';
$AbbreviatedRelativeLocationPath = 'Step // RelativeLocationPath';
$AbbreviatedStep = '\\.\\.?';
$AbbreviatedAxisSpecifier = '@?';

$Expr = 'OrExpr';
$PrimaryExpr = 'VariableReference | \\( Expr \\) | Literal | Number | FunctionCall';

$FunctionCall = 'FunctionName \\( (?:Argument (?:, Argument )* )? \\)';
$Argument     = 'Expr';

$UnionExpr  = 'PathExpr (?:\\| UnionExpr)?';
$PathExpr   = 'FilterExpr (?://? RelativeLocationPath)? | LocationPath';
$FilterExpr = 'PrimaryExpr Predicate*';

$OrExpr  = 'AndExpr (?:or AndExpr)?';
$AndExpr = 'EqualityExpr (?:and AndExpr)?';
$EqualityExpr   = 'RelationalExpr (?:!?= RelationalExpr)?';
$RelationalExpr = 'AdditiveExpr (?:[<>]=? AdditiveExpr)?';

$AdditiveExpr = 'MultiplicativeExpr (?:[-+] MultiplicativeExpr)?';
$MultiplicativeExpr = 'UnaryExpr (?:(?:MultiplyOperator|div|mod) UnaryExpr)?';
$UnaryExpr = 'UnionExpr | - UnaryExpr';

$Literal = '"[^"]*" | \'[^\']*\'';
$Number  = 'Digits (?:\\. Digits?)? | \\. Digits';
$Digits  = '[0-9]+';
$Operator = 'OperatorName | MultiplyOperator | //? | \\| | \\+ | - | = | != | <=? | >=?';
$OperatorName = 'and | or | mod | div';
$MultiplyOperator = '\\*';
$FunctionName = '(?!NodeType)QName';
$VariableReference = '\\$ QName';
$NameTest = '\\* | NCName : \\* | QName';
$NodeType = 'comment | text | processing-instruction | node';

$QName          = 'PrefixedName | UnprefixedName';
$PrefixedName   = 'Prefix : LocalPart';
$UnprefixedName = 'LocalPart';
$Prefix         = 'NCName';
$LocalPart      = 'NCName';
$NCName         = '[-\\w]+';

$tokens = get_defined_vars();

foreach ($tokens as $tokenName => &$expr)
{
	$expr = preg_replace('/[A-Z]\\w+/', '(?&$0)', str_replace(' ', '\\s*', $expr));
}
unset($expr);

$regexps = [];
foreach ($tokens as $tokenName => $expr)
{
	$regexp = '(^\\s*' . $expr . '\\s*$)';

	$i = 0;
	$regexp = preg_replace_callback(
		'/\\(\\?&(\\w+)\\)/',
		function ($m) use (&$i)
		{
			return '(?<' . $m[1] . ++$i . '>' . $m[0] . ')';
		},
		$regexp
	);

	$defined = [];
	do
	{
		$continue = false;

		$regexp = preg_replace_callback(
			'/\\(\\?&(\\w+)\\)/',
			function ($m) use (&$continue, &$defined, $tokens)
			{
				$name = $m[1];

				if (isset($defined[$name]))
				{
					return $m[0];
				}

				$defined[$name] = 1;
				$continue = true;

				return '(?<' . $name . '>' . $tokens[$name] . ')';
			},
			$regexp
		);
	}
	while ($continue);

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