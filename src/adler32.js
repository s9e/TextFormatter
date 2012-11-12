function(str)
{
	var pos = -1,
		len = str.length,
		a = 1,
		b = 0,
		c;

	while (++pos < len)
	{
		c = str.charCodeAt(pos);

		if (c < 0x80)
		{
			// 0xxx xxxx
			b += a += c;
		}
		else
		{
			if (c < 0x800)
			{
				// 110y yyyy 10xx xxxx
				b += a += (c >> 6) | 0xC0;
			}
			else
			{
				// 1110 zzzz 10yy yyyy 10xx xxxx
				b += a += (c >> 12) | 0xE0;
				b += a += ((c >> 6) & 63) | 0x80;
			}

			b += a += (c & 63) | 0x80;
		}
	}

	// Having the modulo outside of the loop means that a and b can overflow if the input string is
	// bigger than 8 MiB
	a %= 65521;
	b %= 65521;

	// Returns as 32 bit unsigned
	return (b * 65536) + a;

	// Returns as 32 bit signed
	return (b << 16) | a;
}