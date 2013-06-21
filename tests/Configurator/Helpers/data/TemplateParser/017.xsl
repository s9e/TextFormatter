<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<xsl:template match="FOO">
		<!-- Ensure that empty cases are not improperly removed -->
		<xsl:choose>
			<xsl:when test="@a">A</xsl:when>
			<xsl:when test="@b"></xsl:when>
			<xsl:otherwise>C</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

</xsl:stylesheet>