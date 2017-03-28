<?php
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006 Sangoma Technologies, Inc
 */
ob_start();
$bootstrap_settings = array();
$bootstrap_settings['freepbx_auth'] = false;
//TODO: We need to make sure security is 100%!
$restrict_mods = true; //Set to true so that we just load framework and the page wont bomb out because we have no session
include '/etc/freepbx.conf';

include(dirname(__FILE__).'/includes/bootstrap.php');
try {
	$ucp = \UCP\UCP::create();
	$ucp->Modgettext->textdomain("ucp");
} catch(\Exception $e) {
	if(isset($_REQUEST['quietmode'])) {
		echo json_encode(array("status" => false, "message" => "UCP is disabled"));
	} else {
		echo "<html><head><title>UCP</title></head><body style='background-color: rgb(211, 234, 255);'><div style='border-radius: 5px;border: 1px solid black;text-align: center;padding: 5px;width: 90%;margin: auto;left: 0px;right: 0px;background-color: rgba(53, 77, 255, 0.18);'>"._('UCP is currently disabled. Please talk to your system Administrator')."</div></body></html>";
	}
	die();
}
ob_end_clean();
//TIME: 0.069080114364624

$user = $ucp->User->getUser();
$d = $ucp->View->setGUILocales($user);
$lang = $d['language'];

if(isset($_REQUEST['logout'])) {
	if($user) {
		$ucp->User->logout();
	}
	$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
	$url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://".$_SERVER['HTTP_HOST']. $uri_parts[0];
	header('Location: '.$url);
	die();
}

$ucp->Session->isMobile = $ucp->detect->isMobile();
$ucp->Session->isTablet = $ucp->detect->isTablet();
//TIME: 0.1443829536438

//http://htmlpurifier.org/docs/enduser-utf8.html#fixcharset
header('Content-Type:text/html; charset=UTF-8');

//Second part of this IF statement
if((isset($_REQUEST['quietmode']) && $user !== false && !empty($user)) ||
	(isset($_REQUEST['command']) && ($_REQUEST['command'] == 'login' ||
																	$_REQUEST['command'] == 'forgot' ||
																	$_REQUEST['command'] == 'reset'))) {
	$m = !empty($_REQUEST['module']) ? $_REQUEST['module'] : null;
	$ucp->Ajax->doRequest($m,$_REQUEST['command']);
	die();
} elseif(isset($_REQUEST['quietmode']) && ($user === false || empty($user))) {
	header("HTTP/1.0 401 Unauthorized");
	$json = json_encode(array("status" => "false", "message" => "forbidden"));
	die($json);
}
//TIME: 0.11812996864319

/* Start Display GUI Items */
$displayvars = array();
$displayvars['user'] = $user;

$displayvars['error_warning'] = '';
$displayvars['error_danger'] = '';

//Check .htaccess and make sure it actually works
$nt = $ucp->notifications;
if ( !isset($_SERVER['HTACCESS']) && preg_match("/apache/i", $_SERVER['SERVER_SOFTWARE'])) {
	// No .htaccess support
	if(!$nt->exists('ucp', 'htaccess')) {
		$nt->add_security('ucp', 'htaccess', _('.htaccess files are disabled on this webserver. Please enable them'),
		sprintf(_("To protect the integrity of your server, you must allow overrides in your webserver's configuration file for the User Control Panel. For more information see: %s"), '<a href="http://wiki.freepbx.org/display/F2/Webserver+Overrides">http://wiki.freepbx.org/display/F2/Webserver+Overrides</a>'));
	}
} elseif(!preg_match("/apache/i", $_SERVER['SERVER_SOFTWARE'])) {
	$sql = "SELECT value FROM admin WHERE variable = 'htaccess'";
	$sth = FreePBX::Database()->prepare($sql);
	$sth->execute();
	$o = $sth->fetch();

	if(empty($o)) {
		if($nt->exists('ucp', 'htaccess')) {
			$nt->delete('ucp', 'htaccess');
		}
		$nt->add_warning('ucp', 'htaccess', _('.htaccess files are not supported on this webserver.'),
		sprintf(_("htaccess files help protect the integrity of your server. Please make sure file paths and directories are locked down properly. For more information see: %s"), '<a href="http://wiki.freepbx.org/display/F2/Webserver+Overrides">http://wiki.freepbx.org/display/F2/Webserver+Overrides</a>'),"http://wiki.freepbx.org/display/F2/Webserver+Overrides",true,true);
		$sql = "REPLACE INTO admin (`value`, `variable`) VALUES (1, 'htaccess')";
		$sth = FreePBX::Database()->prepare($sql);
		$sth->execute();
	}
} else {
	if($nt->exists('ucp', 'htaccess')) {
		$nt->delete('ucp', 'htaccess');
	}
}
//TIME: 0.1096019744873


try {
	$active_modules = $ucp->Modules->getActiveModules();
} catch(\Exception $e) {
	echo "<html><head><title>"._("UCP")."</title></head><body style='background-color: rgb(211, 234, 255);'><div style='border-radius: 5px;border: 1px solid black;text-align: center;padding: 5px;width: 90%;margin: auto;left: 0px;right: 0px;background-color: rgba(53, 77, 255, 0.18);'>"._('There was an error trying to load UCP').":<br>".$e->getMessage()."</div></body></html>";
	die();
}
//TIME: 0.37255001068115

$all_widgets = $ucp->Dashboards->getAllWidgets();
$all_simple_widgets = $ucp->Dashboards->getAllSimpleWidgets();

//TIME: 1.2794749736786


//Simple widgets by user
$usw = (array)json_decode($ucp->Dashboards->getSimpleLayout(),true);
$user_small_widgets = array();
foreach($usw as $id => $widget) {
	$name = ucfirst(strtolower($widget['rawname']));
	$id = $widget['id'];
	$info = $all_simple_widgets['widget'][$name]['list'][$widget['widget_type_id']];
	$display = $all_simple_widgets['widget'][$name]['display'];
	if(empty($info)) {
		continue;
	}
	$user_small_widgets[$id] = $widget;
	$user_small_widgets[$id]['widget_name'] = $info['display'];
	$user_small_widgets[$id]['name'] = $display;
	$user_small_widgets[$id]['hasSettings'] = !empty($info['hasSettings']);
}

$user_dashboards = $ucp->Dashboards->getDashboards();
foreach($user_dashboards as $dashboard_info){
	$tmp = $ucp->Modules->Widgets->getWidgetsFromDashboard($dashboard_info["id"]);
	$id = $dashboard_info["id"];
	$displayvars['dashboards_info'][$id] = json_decode($tmp,true);
}
$active_dashboard_id = "";

if(!empty($_REQUEST["dashboard"])){
	$active_dashboard_id = $_REQUEST["dashboard"];
}else {
	if(!empty($user_dashboards)){

		foreach($user_dashboards as $dashboard_info){
			$active_dashboard_id = $dashboard_info["id"];
			break;
		}
	}
}

//TIME: 1.2543160915375

$displayvars['all_widgets'] = $all_widgets;
$displayvars['all_simple_widgets'] = $all_simple_widgets;

$displayvars['active_dashboard'] = $active_dashboard_id;
$displayvars['user_dashboards'] = $user_dashboards;

$displayvars['user_small_widgets'] = $user_small_widgets;

/***********************/
/* DASHBOARD SELECTION */
/***********************/

$compressed = FreePBX::Config()->get("USE_PACKAGED_JS");
$displayvars['ucpcss'] = $ucp->getCss();
$displayvars['ucpmoduleless'] = $ucp->Modules->getGlobalLess();

$displayvars['version'] = $ucp->getVersion();
$displayvars['iconsdir'] = FreePBX::Config()->get('VIEW_UCP_ICONS_FOLDER');
//TODO: needs to not be global
$browser = new \Sinergi\BrowserDetector\Browser();

$ie = 10;
$displayvars['shiv'] = ($browser->getName() === \Sinergi\BrowserDetector\Browser::IE && $browser->getVersion() < $ie);
$displayvars['menu'] = ($user && !empty($user)) ? $ucp->Modules->generateMenu() : array();

$version	 = $ucp->getVersion();
$version_tag = '?load_version=' . urlencode($version);
if (FreePBX::Config()->get('FORCE_JS_CSS_IMG_DOWNLOAD')) {
	$this_time_append	= '.' . time();
	$version_tag 		.= $this_time_append;
}
$displayvars['version_tag'] = $version_tag;

$ucp->View->show_view(__DIR__.'/views/header.php',$displayvars);

if(!empty($user["id"])){
	$ucp->View->show_view(__DIR__.'/views/dashboard-header.php',$displayvars);
}

if($user && !empty($user)) {
	$display = 'dashboard';
} else {
	if(isset($_REQUEST['forgot'])) {
		$display = 'forgot';
	} else {
		$display = '';
	}
	if(!empty($_REQUEST['display']) || !empty($_REQUEST['mod']) || isset($_REQUEST['logout'])) {
		//TODO: logout code?
	}
}

switch($display) {
	case "dashboard":
		$displayvars['display'] = $ucp->Modules->Widgets->getDisplay($active_dashboard_id);
		$ucp->View->show_view(__DIR__.'/views/dashboard.php',$displayvars);
	break;
	case "forgot":
		$displayvars['token'] = $ucp->Session->generateToken('login');
		$user = $ucp->User->validateResetToken($_REQUEST['forgot']);
		if(!empty($user)) {
			$displayvars['username'] = $user['username'];
			$displayvars['ftoken'] = $_REQUEST['forgot'];
			$ucp->View->show_view(__DIR__.'/views/forgot.php',$displayvars);
		} else {
			$displayvars['error_danger'] = _("Invalid Token");
			$ucp->View->show_view(__DIR__.'/views/login.php',$displayvars);
		}
	break;
	default:
		$displayvars['token'] = $ucp->Session->generateToken('login');

		$browser = new \Sinergi\BrowserDetector\Browser();

		$ie = 10;
		if ($browser->getName() === \Sinergi\BrowserDetector\Browser::IE && $browser->getVersion() < $ie) {
			$displayvars['error_danger'] = sprintf(_("Internet Explorer %s is not supported. Functionality will be deteriorated until you upgrade to %s or higher"),$browser->getVersion(), $ie);
		}
		$ucp->View->show_view(dirname(__FILE__).'/views/login.php',$displayvars);
	break;
}

$displayvars['language'] = $ucp->Modules->getGlobalLanguageJSON($lang);
$displayvars['lang'] = $lang;
$displayvars['ucpserver'] = json_encode($ucp->getServerSettings());
$displayvars['modules'] = json_encode($active_modules);
$displayvars['gScripts'] = $ucp->getScripts(false,$compressed);
$displayvars['scripts'] = $ucp->Modules->getGlobalScripts(false,$compressed);
$displayvars['timezone'] = $ucp->View->getTimezone();
$displayvars['timeformat'] = $ucp->View->getTimeFormat();
$displayvars['datetimeformat'] = $ucp->View->getDateTimeFormat();
$displayvars['dateformat'] = $ucp->View->getDateFormat();
$displayvars['desktop'] = (!$ucp->Session->isMobile && !$ucp->Session->isTablet);
$mods = $ucp->Modules->getModulesByMethod('getStaticSettings');
$displayvars['moduleSettings'] = array();
foreach($mods as $m) {
	$ucp->Modgettext->push_textdomain(strtolower($m));
	$displayvars['moduleSettings'][$m] = $ucp->Modules->$m->getStaticSettings();
	$ucp->Modgettext->pop_textdomain();
}
$ucp->Modgettext->push_textdomain("ucp");

if(!empty($user["id"])) {
	$displayvars['year'] = date('Y',time());
	$dbfc = FreePBX::Config()->get('VIEW_UCP_FOOTER_CONTENT');
	$displayvars['dashboard_footer_content'] = $ucp->View->load_view(__DIR__."/".$dbfc, array("year" => date('Y',time())));
	$ucp->View->show_view(__DIR__ . '/views/dashboard-footer.php', $displayvars);
}

$ucp->View->show_view(dirname(__FILE__).'/views/footer.php',$displayvars);
