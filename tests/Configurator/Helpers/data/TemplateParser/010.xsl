<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<xsl:template match="B">
		<b>
			<xsl:if test="@foo">
				<xsl:attribute name="title">
					<xsl:value-of select="@bar"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates/>
		</b>
	</xsl:template>

</xsl:stylesheet>