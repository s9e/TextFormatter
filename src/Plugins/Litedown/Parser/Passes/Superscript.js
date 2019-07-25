function parse()
{
	parseAbstractScript('SUP', '^', /\^[^\x17\s!"#$%&\'()*+,\-.\/:;<=>?@[\]^_`{}|~]+\^?/g, /\^\([^\x17()]+\)/g);
}