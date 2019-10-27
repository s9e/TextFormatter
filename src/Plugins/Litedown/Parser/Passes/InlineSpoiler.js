function parse()
{
	parseInlineMarkup('>!', />![^\x17]+?!</g,     'ISPOILER');
	parseInlineMarkup('||', /\|\|[^\x17]+?\|\|/g, 'ISPOILER');
}