<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<xsl:template match="B"><b><xsl:apply-templates select="I"/></b></xsl:template>
	<xsl:template match="I"><i><xsl:apply-templates/></i></xsl:template>

</xsl:stylesheet>