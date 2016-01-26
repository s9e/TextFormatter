function(str)
{
	return str.replace(
		/(?:^|\s)[a-z]/g,
		function(m)
		{
			return m.toUpperCase()
		}
	);
}