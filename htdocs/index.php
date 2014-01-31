<?php
$bootstrap_settings = array();
$bootstrap_settings['freepbx_auth'] = false;
//TODO: We need to make sure security is 100%!
$restrict_mods = true; //Set to true so that we just load framework and the page wont bomb out because we have no session
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) { 
      include_once('/etc/asterisk/freepbx.conf'); 
}
include(dirname(__FILE__).'/includes/UCP.class.php');
$ucp = \UCP\UCP::create();

if(isset($_REQUEST['logout'])) {
	$ucp->User->logout();
	header('Location: ?');
	die();
}

if(isset($_REQUEST['quietmode'])) {
	$ucp->Ajax->doRequest($_REQUEST['module'],$_REQUEST['command']);
	die();
}

require dirname(__FILE__).'/includes/less/Cache.php';
Less_Cache::$cache_dir = dirname(__FILE__).'/assets/css/compiled';

$displayvars = array();

$btfiles = array();
$btfiles[dirname(__FILE__).'/assets/less/bootstrap.less'] = '/ucp/';
$displayvars['bootstrapcssless'] = Less_Cache::Get( $btfiles );

$ucpfiles = array();
$ucpfiles[dirname(__FILE__).'/assets/less/UCP.less'] = '/ucp/';
$displayvars['ucpcssless'] = Less_Cache::Get( $ucpfiles );

$displayvars['error_warning'] = '';
$displayvars['error_danger'] = '';

//Check .htaccess and make sure it actually works
$nt = $ucp->notifications;
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

foreach($ucp->FreePBX->Config->get_conf_settings() as $key => $data) {
	$amp_conf[$key] = $data['value'];
}
$amp_conf['JQUERY_CSS'] = str_replace('assets/','',$amp_conf['JQUERY_CSS']);
$displayvars['amp_conf'] = $amp_conf;

$displayvars['version']			= getVersion();
$displayvars['version_tag']		= '?load_version=' . urlencode($displayvars['version']);

if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
	$displayvars['this_time_append']	= '.' . time();
	$displayvars['version_tag'] 		.= $this_time_append;
} else {
	$displayvars['this_time_append'] = '';
}

if(!isset($_SERVER['HTTP_X_PJAX'])) {
	show_view(dirname(__FILE__).'/views/header.php',$displayvars);
}

if($ucp->User->getUser()) {
	$display = !empty($_REQUEST['display']) ? $_REQUEST['display'] : 'dashboard';
	$module = !empty($_REQUEST['mod']) ? $_REQUEST['mod'] : 'home';
} else {
	$display = '';
	$module = '';
	if(!empty($_REQUEST['display']) || !empty($_REQUEST['mod']) || isset($_REQUEST['logout'])) {

	}
}

$displayvars['menu'] = $ucp->Modules->generateMenu();
$displayvars['active_module'] = $module;
$mclass = ucfirst($module);
switch($display) {
	case "dashboard":
		$dashboard_content = $ucp->Modules->$mclass->getDisplay();
		if(isset($_SERVER['HTTP_X_PJAX'])) {
			if(!empty($_REQUEST['mod'])) {
				echo $dashboard_content;
				exit();
			}
		}
		$displayvars['dashboard_content'] = $dashboard_content;
		show_view(dirname(__FILE__).'/views/dashboard.php',$displayvars);
	break;
	default:
		$displayvars['token'] = $ucp->Session->generateToken('login');
		show_view(dirname(__FILE__).'/views/login.php',$displayvars);
		break;
}

if(!isset($_SERVER['HTTP_X_PJAX'])) {
	show_view(dirname(__FILE__).'/views/footer.php',$displayvars);
}


function convert($size) {
	$unit=array('b','kb','mb','gb','tb','pb');
	return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

//dbug("Using ".convert(memory_get_usage(true))); // 123 kb