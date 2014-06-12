<?php
ob_start();
$bootstrap_settings = array();
$bootstrap_settings['freepbx_auth'] = false;
//TODO: We need to make sure security is 100%!
$restrict_mods = true; //Set to true so that we just load framework and the page wont bomb out because we have no session
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
      include_once('/etc/asterisk/freepbx.conf');
}
include(dirname(__FILE__).'/includes/UCP.class.php');
$ucp = \UCP\UCP::create();
ob_end_clean();

$user = $ucp->User->getUser();

if(isset($_REQUEST['logout']) && $user) {
	$ucp->User->logout();
	if(isset($_SERVER['HTTP_X_PJAX'])) {
		//Forces pjax to refresh the entire page
		header("X-PJAX-Version: logout");
	}
} else {
	//Send back only PJAX relevant data
	//This is to force a complete page refresh if/when UCP gets updates
	//The header HTTP_X_PJAX comes from the JS PJAX lib, letting us know we don't need the whole html document
	if(isset($_SERVER['HTTP_X_PJAX'])) {
		header("X-PJAX-Version: ".$ucp->getVersion());
	}
}

//Second part of this IF statement
if((isset($_REQUEST['quietmode']) && $user !== false && !empty($user)) || (isset($_REQUEST['command']) && $_REQUEST['command'] == 'login')) {
    $m = !empty($_REQUEST['module']) ? $_REQUEST['module'] : null;
	$ucp->Ajax->doRequest($m,$_REQUEST['command']);
	die();
} elseif(isset($_REQUEST['quietmode']) && ($user === false || empty($user))) {
	header("HTTP/1.0 403 Forbidden");
	$json = json_encode(array("status" => "false", "message" => "forbidden"));
	die($json);
}

/* Start Display GUI Items */
$displayvars = array();
$displayvars['user'] = $user;

//TODO: these need to be included in UCP_HELPERS
require dirname(__FILE__).'/includes/less/Cache.php';
require dirname(__FILE__).'/includes/js/Minifier.php';
//TODO: needs to be an array of directories that need to be created on install
if(!file_exists(dirname(__FILE__).'/assets/css/compiled') && !mkdir(dirname(__FILE__).'/assets/css/compiled')) {
	die('Can Not Create Cache Folder at '.dirname(__FILE__).'/assets/css/compiled');
}
Less_Cache::$cache_dir = dirname(__FILE__).'/assets/css/compiled';

//Needs to be one unified LESS file along with the module LESS file
$btfiles = array();
$btfiles[dirname(__FILE__).'/assets/less/bootstrap.less'] = '/ucp/';
$displayvars['bootstrapcssless'] = Less_Cache::Get( $btfiles );

$ucpfiles = array();
$ucpfiles[dirname(__FILE__).'/assets/less/UCP.less'] = '/ucp/';
$displayvars['ucpcssless'] = Less_Cache::Get( $ucpfiles );

$ucpfiles = array();
$ucpfiles[dirname(__FILE__).'/assets/less/font-awesome/font-awesome.less'] = '/ucp/assets/';
$displayvars['facssless'] = Less_Cache::Get( $ucpfiles );

$displayvars['ucpmoduleless'] = $ucp->Modules->getGlobalLess();

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

$displayvars['version']			= getVersion();
$displayvars['version_tag']		= '?load_version=' . urlencode($displayvars['version']);

if(!isset($_SERVER['HTTP_X_PJAX'])) {
	$displayvars['version'] = $ucp->getVersion();
	//TODO: needs to not be global
	show_view(dirname(__FILE__).'/views/header.php',$displayvars);
}

if($user && !empty($user)) {
	//TODO: this is depreciated and needs to be removed
	$display = !empty($_REQUEST['display']) ? $_REQUEST['display'] : 'dashboard';
	$module = !empty($_REQUEST['mod']) ? $_REQUEST['mod'] : 'home';
	$displayvars['menu'] = $ucp->Modules->generateMenu();
} else {
	$display = '';
	$module = '';
	$displayvars['menu'] = array();
	if(!empty($_REQUEST['display']) || !empty($_REQUEST['mod']) || isset($_REQUEST['logout'])) {
		//TODO: logout code?
	}
}

$displayvars['active_module'] = $module;
$mclass = ucfirst(strtolower($module));
switch($display) {
	//TODO: needs to be cleaned up to not have to use dashboard
	case "dashboard":
		$dashboard_content = '<div id="module-page-'.$module.'">'.$ucp->Modules->$mclass->getDisplay().'</div>';
		if(isset($_SERVER['HTTP_X_PJAX'])) {
			if(!empty($_REQUEST['mod'])) {
				echo $dashboard_content;
				exit();
			}
		}
		$displayvars['dashboard_content'] = $dashboard_content;
		$displayvars['year'] = date('Y',time());
		show_view(dirname(__FILE__).'/views/dashboard.php',$displayvars);
	break;
	default:
		$displayvars['token'] = $ucp->Session->generateToken('login');
		show_view(dirname(__FILE__).'/views/login.php',$displayvars);
	break;
}

if(!isset($_SERVER['HTTP_X_PJAX'])) {
	$displayvars['modules'] = json_encode($ucp->Modules->getActiveModules());
	$displayvars['scripts'] = $ucp->Modules->getGlobalScripts();
	show_view(dirname(__FILE__).'/views/footer.php',$displayvars);
}
