<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//
//  Written for CodeX by Stephane Bouhet
//

require('pre.php');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');

?>
<html>
<head>
<title>Tracker Selection</title>
<link rel="stylesheet" type="text/css" href="<? echo util_get_css_theme(); ?>">
<script language="JavaScript">

function doSelection() {
	if ( form_selection.artifact_type_id.value != "" ) {
		window.opener.<? echo $opener_form; ?>.<? echo $opener_field; ?>.value = form_selection.artifact_type_id.value;
	}
	close();
}

</script>
</head>
<body>
<form name="form_selection">
<table>
<tr valign="center"><td>
<div align="center">
<?
	//
	//	get the Group object
	//
	$group = group_get_object($group_id);
	if (!$group || !is_object($group) || $group->isError()) {
		exit_no_group();
	}

	$atf = new ArtifactTypeFactory($group);
	$results = $atf->getArtifactTypesFromId($group_id);
	if ( $results ) {
		echo '<select name="artifact_type_id" size="5">';
	}
		
	$count = 0;
    while ($trackers_array = db_fetch_array($results)) {
    	echo '<option value="'.$trackers_array["group_artifact_id"].'">'.$trackers_array["name"].'</option>';
    	$count ++;
    }

?>
<? if ( $count > 0 ) { ?>
</select>
</td>
<td>
<input type="button" name="selection" value="Choose" onClick="doSelection()">
<? } else { ?>
<b>No tracker available for this project!</b>
<br><br><input type="button" value="Close" onClick="window.close()">
</td>
<td>
<? } ?>
</div>
</td></tr>
<table>
</form>
</body>
</html>
