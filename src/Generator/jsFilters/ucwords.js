function(str)
{
	return str.replace(
		/^[a-z]|\s[a-z]/g,
		function(m)
		{
			return m.toUpperCase()
		}
	);
}