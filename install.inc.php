<?php
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
function ucp_module_install_check_callback($mods = []) {
    global $active_modules;
    $ret = [];
    $current_mod = 'fw_ari';
    $conflicting_mods = ['ucp'];
    foreach($mods as $k => $v) {
        if (in_array($k, $conflicting_mods) && !empty($active_modules[$current_mod]) && !in_array($active_modules[$current_mod]['status'],[MODULE_STATUS_NOTINSTALLED, MODULE_STATUS_BROKEN])) {
            $ret[] = $v['name'];
        }
    }
    if (!empty($ret)) {
        $modules = implode(',',$ret);
        return sprintf(_('Failed to install %s due to the following conflicting module(s): %s'),$modules,$active_modules[$current_mod]['displayname']);
    }
    return TRUE;
}
