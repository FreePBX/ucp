<?php
if ( !isset($_SERVER['HTACCESS']) ) {
  // No .htaccess support
}
$bootstrap_settings = array();
//TODO: We need to make sure security is 100%!
$restrict_mods = true; //Set to true so that we just load framework and the page wont bomb out because we have no session
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) { 
      include_once('/etc/asterisk/freepbx.conf'); 
}
global $amp_conf;

//Check .htaccess and make sure it actually works
$nt =& notifications::create();
if ( !isset($_SERVER['HTACCESS']) ) {
	// No .htaccess support
	if(!$nt->exists('ucp', 'htaccess')) {
		$nt->add_security('ucp', 'htaccess', _('.htaccess files are disable on this webserver. Please enable them'), 
		_('To Protect the integrity of your server, you must set AllowOverride to All in the Apache configuration file for the User Control Panel'));
	}
} else {
	if($nt->exists('ucp', 'htaccess')) {
		$nt->delete('ucp', 'htaccess');
	}
}

$displayvars = array();
$displayvars['version']			= get_framework_version();
$displayvars['version_tag']		= '?load_version=' . urlencode($displayvars['version']);

if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
	$displayvars['this_time_append']	= '.' . time();
	$displayvars['version_tag'] 		.= $this_time_append;
} else {
	$displayvars['this_time_append'] = '';
}

$displayvars['mainstyle_css']      = $amp_conf['BRAND_CSS_ALT_MAINSTYLE'] 
                       ? $amp_conf['BRAND_CSS_ALT_MAINSTYLE'] 
                       : 'assets/css/mainstyle.css';
$displayvars['framework_css'] = ($amp_conf['DISABLE_CSS_AUTOGEN'] || !file_exists($amp_conf['mainstyle_css_generated'])) ? $displayvars['mainstyle_css'] : $amp_conf['mainstyle_css_generated'];
$displayvars['css_ver'] = '.' . filectime($displayvars['framework_css']);

$displayvars['use_popover_css'] = $use_popover_css;
$displayvars['popover_css'] = $amp_conf['BRAND_CSS_ALT_POPOVER'] ? $amp_conf['BRAND_CSS_ALT_POPOVER'] : 'assets/css/popover.css';
$displayvars['amp_conf'] = $amp_conf;
show_view(dirname(__FILE__).'/views/header.php',$displayvars);

$displayvars = array();
$displayvars['amp_conf'] = $amp_conf;
show_view(dirname(__FILE__).'/views/login.php',$displayvars);

$displayvars = array();
$displayvars['amp_conf'] = $amp_conf;
$displayvars['version']			= get_framework_version();
$displayvars['version_tag']		= '?load_version=' . urlencode($displayvars['version']);

show_view(dirname(__FILE__).'/views/footer.php',$displayvars);