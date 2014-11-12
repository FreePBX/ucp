<?php
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
ob_start();
$bootstrap_settings = array();
$bootstrap_settings['freepbx_auth'] = false;
//TODO: We need to make sure security is 100%!
$restrict_mods = true; //Set to true so that we just load framework and the page wont bomb out because we have no session
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	include_once('/etc/asterisk/freepbx.conf');
}

$lang = !empty($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en_US';
putenv('LC_ALL='.$lang);
setlocale(LC_ALL, $lang);

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

\Emojione::$imagePathPNG = 'assets/images/emoji/png/'; // defaults to jsdelivr's free CDN
\Emojione::$imagePathSVG = 'assets/images/emoji/svg/'; // defaults to jsdelivr's free CDN

$user = $ucp->User->getUser();

if(isset($_REQUEST['logout']) && $user) {
	$ucp->User->logout();
	$user = $ucp->User->getUser();
}
if(empty($ucp->Session->isMobile)) {
	$ucp->Session->isMobile = $ucp->detect->isMobile();
	$ucp->Session->isTablet = $ucp->detect->isTablet();
}
//Send back only PJAX relevant data
//This is to force a complete page refresh if/when UCP gets updates
//The header HTTP_X_PJAX comes from the JS PJAX lib, letting us know we don't need the whole html document
if(isset($_SERVER['HTTP_X_PJAX'])) {
	header("X-PJAX-Version: ".$ucp->getVersion());
}

//http://htmlpurifier.org/docs/enduser-utf8.html#fixcharset
header('Content-Type:text/html; charset=UTF-8');

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

$lesses = $ucp->getLess();

$displayvars['bootstrapcssless'] = $lesses['bootstrapcssless'];
$displayvars['ucpcssless'] = $lesses['ucpcssless'];
$displayvars['facssless'] = $lesses['facssless'];
$displayvars['sfcssless'] = $lesses['sfcssless'];

$displayvars['ucpmoduleless'] = $ucp->Modules->getGlobalLess();

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

try {
	$active_modules = $ucp->Modules->getActiveModules();
} catch(\Exception $e) {
	echo "<html><head><title>UCP</title></head><body style='background-color: rgb(211, 234, 255);'><div style='border-radius: 5px;border: 1px solid black;text-align: center;padding: 5px;width: 90%;margin: auto;left: 0px;right: 0px;background-color: rgba(53, 77, 255, 0.18);'>"._('There was an error trying to load UCP').":<br>".$e->getMessage()."</div></body></html>";
	die();
}

if(!isset($_SERVER['HTTP_X_PJAX'])) {
	$displayvars['version'] = $ucp->getVersion();
	//TODO: needs to not be global
	$ucp->View->show_view(dirname(__FILE__).'/views/header.php',$displayvars);
}

if($user && !empty($user)) {
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

switch($display) {
	case "settings":
	case "dashboard":
		if($display == "settings") {
			$ucp->Modgettext->push_textdomain("ucp");
			$displayvars['desktop'] = (!$ucp->Session->isMobile && !$ucp->Session->isTablet);
			$dashboard_content = $ucp->View->load_view(dirname(__FILE__).'/views/settings.php',$displayvars);
			$displayvars['active_module'] = 'ucpsettings';
			$ucp->Modgettext->pop_textdomain();
		} else {
			if($module != "home") {
				$ucp->Modgettext->push_textdomain(strtolower($module));
			} else {
				$ucp->Modgettext->push_textdomain("ucp");
			}
			$displayvars['active_module'] = $module;
			$mclass = ucfirst(strtolower($module));
			if(in_array($mclass,$active_modules)) {
				$dashboard_content = $ucp->View->load_view(dirname(__FILE__).'/views/module.php',array("module" => $module, "display" => $ucp->Modules->$mclass->getDisplay()));
			} else {
				$ucp->Modgettext->pop_textdomain();
				$dashboard_content = sprintf(_('Unknown Module %s'),$module);
			}
			$ucp->Modgettext->pop_textdomain();
		}

		if(isset($_SERVER['HTTP_X_PJAX'])) {
			if(!empty($_REQUEST['mod']) || ($display == 'settings')) {
				echo $dashboard_content;
				exit();
			}
		}
		$displayvars['dashboard_content'] = $dashboard_content;
		$displayvars['year'] = date('Y',time());
		$modules = $ucp->Modules->getActiveModules();

		$displayvars['navItems'] = array();
		foreach($ucp->Modules->getModulesByMethod('getNavItems') as $m) {
			$mc = ucfirst(strtolower($m));
			$item = $ucp->Modules->$mc->getNavItems();
			if(!empty($item)) {
				foreach($item as $i) {
					$displayvars['navItems'][] = $i;
				}
			}
		}
		$o = $ucp->getSetting($user['username'],'Webrtc','originate');
		$originate = !empty($o) ? '<li class="originate"><a>'._("Originate Call").'</a></li>' : '';
		$displayvars['navItems']['settings'] = array(
			"rawname" => "settings",
			"badge" => false,
			"icon" => "fa-cog",
			"menu" => array(
				"html" => '<li>' . $originate . '</li><li><a data-pjax href="?display=settings">' . _('Settings') . '</a></li><li><a class="logout" href="?logout=1">' . _('Logout') . '</a></li>'
			)
		);
		$ucp->View->show_view(dirname(__FILE__).'/views/dashboard.php',$displayvars);
	break;
	default:
		$displayvars['token'] = $ucp->Session->generateToken('login');
		$ucp->View->show_view(dirname(__FILE__).'/views/login.php',$displayvars);
	break;
}

if(!isset($_SERVER['HTTP_X_PJAX'])) {
	$displayvars['language'] = $ucp->Modules->getGlobalLanguageJSON($lang);
	$displayvars['modules'] = json_encode($active_modules);
	$displayvars['gScripts'] = $ucp->getScripts();
	$displayvars['scripts'] = $ucp->Modules->getGlobalScripts();
	$displayvars['desktop'] = (!$ucp->Session->isMobile && !$ucp->Session->isTablet);
	$ucp->View->show_view(dirname(__FILE__).'/views/footer.php',$displayvars);
}
