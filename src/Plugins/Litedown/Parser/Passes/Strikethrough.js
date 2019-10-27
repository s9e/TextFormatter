function parse()
{
	parseInlineMarkup('~~', /~~[^\x17]+?~~(?!~)/g, 'DEL');
}