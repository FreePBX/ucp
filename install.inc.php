<?php
function ucp_module_install_check_callback($mods = array()) {
    global $active_modules;
    $ret = array();
    $current_mod = 'fw_ari';
    $conflicting_mods = array('ucp');
    foreach($mods as $k => $v) {
        if (in_array($k, $conflicting_mods) && !in_array($active_modules[$current_mod]['status'],array(MODULE_STATUS_NOTINSTALLED,MODULE_STATUS_BROKEN))) {
            $ret[] = $v['name'];
        }
    }
    if (!empty($ret)) {
        $modules = implode(',',$ret);
        return _('Failed to install ' . $modules . ' due to the following conflicting module(s): ' . $active_modules[$current_mod]['displayname']);
    }
    return TRUE;
}