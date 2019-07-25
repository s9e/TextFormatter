function parse()
{
	parseAbstractScript('SUB', '~', /~[^\x17\s!"#$%&\'()*+,\-.\/:;<=>?@[\]^_`{}|~]+~?/g, /~\([^\x17()]+\)/g);
}