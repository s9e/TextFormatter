function(str)
{
	return str.replace(/^[ \n\r\t\0\x0B]+/g, '').replace(/[ \n\r\t\0\x0B]+$/g, '');
}