function(str)
{
	return str.replace(/^[ \n\r\t\0\v]+/g, '');
}