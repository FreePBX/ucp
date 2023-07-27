<?php
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
out('Remove all UCP tables');
$tables = ['ucp_sessions'];
foreach ($tables as $table) {
	$sql = "DROP TABLE IF EXISTS {$table}";
	$result = $db->query($sql);
	if (DB::IsError($result)) {
		die_freepbx($result->getDebugInfo());
	}
	unset($result);
}
