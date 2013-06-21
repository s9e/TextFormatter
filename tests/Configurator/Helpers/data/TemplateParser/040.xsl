<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<!-- The XSLT processor removes any content from void elements in HTML mode -->
	<xsl:template match="br"><br>foo</br></xsl:template>

</xsl:stylesheet>