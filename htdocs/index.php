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

if(isset($_REQUEST['quietmode'])) {
	$ucp->Ajax->display();
	exit();
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

$display = !empty($_REQUEST['display']) ? $_REQUEST['display'] : '';
$module = !empty($_REQUEST['mod']) ? $_REQUEST['mod'] : 'home';

$displayvars['modules'] = array(
	"home" => array(
		"rawname" => "home",
		"name" => "Home",
		"content" => "This is where we would put content dynamically after page load through pjax"
	),
	"voicemail" => array(
		"rawname" => "voicemail",
		"name" => "VM",
		"badge"	=> 42,
		"content" => "Voicemail Page horray!<br/>Lets Show stuff<br/>1<br/>2<br/>3<br/>4<br/>5<br/>6<br/>7<br/>8<br/>9<br/>10<br/>11<br/>1<br/>2<br/>3<br/>4<br/>5<br/>6<br/>7<br/>8<br/>9<br/>10<br/>11<br/>1<br/>2<br/>3<br/>4<br/>5<br/>6<br/>7<br/>8<br/>9<br/>10<br/>11<br/>"
	));
$displayvars['active_module'] = $module;
switch($display) {
	case "dashboard":
		$dashboard_content = $displayvars['modules'][$module]['content'];
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
		show_view(dirname(__FILE__).'/views/login.php',$displayvars);
		break;
}

if(!isset($_SERVER['HTTP_X_PJAX'])) {
	show_view(dirname(__FILE__).'/views/footer.php',$displayvars);
}


