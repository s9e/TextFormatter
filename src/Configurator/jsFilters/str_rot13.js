function(str)
{
	return str.replace(
		/[a-z]/gi,
		function(c)
		{
			return String.fromCharCode(c.charCodeAt(0) + ((c.toLowerCase() < 'n') ? 13 : -13));
		}
	);
}