function (attrValue)
{
	return /^\d+$/.test(attrValue) ? attrValue : false;
}