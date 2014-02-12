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
		header("X-PJAX-Version: logout");
	}
} else {
	/* Advanced PreVis Stuff */
	if(isset($_SERVER['HTTP_X_PJAX'])) {
		header("X-PJAX-Version: ".$ucp->getVersion());
	}
}

if((isset($_REQUEST['quietmode']) && $user) || (isset($_REQUEST['command']) && $_REQUEST['command'] == 'login')) {
	$ucp->Ajax->doRequest($_REQUEST['module'],$_REQUEST['command']);
	die();
} elseif(isset($_REQUEST['quietmode']) && !$user) {
	header("HTTP/1.0 403 Forbidden");
	$json = json_encode(array("status" => "false", "message" => "forbidden"));
	die($json);
}

if(isset($_REQUEST['stream']) && $user) {
	dl_file_resumable('/var/spool/asterisk/voicemail/default/'.escapeshellcmd($_REQUEST['extension']).'/'.escapeshellcmd($_REQUEST['folder']).'/'.escapeshellcmd($_REQUEST['msg']));
	die();
}

/* Start Visualization Stuff */
$displayvars = array();
$displayvars['user'] = $user;

require dirname(__FILE__).'/includes/less/Cache.php';
Less_Cache::$cache_dir = dirname(__FILE__).'/assets/css/compiled';

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
	$displayvars['version'] = $ucp->getVersion();
	show_view(dirname(__FILE__).'/views/header.php',$displayvars);
}

if($user) {
	$display = !empty($_REQUEST['display']) ? $_REQUEST['display'] : 'dashboard';
	$module = !empty($_REQUEST['mod']) ? $_REQUEST['mod'] : 'home';
	$displayvars['menu'] = $ucp->Modules->generateMenu();
	
} else {
	$display = '';
	$module = '';
	$displayvars['menu'] = array();
	if(!empty($_REQUEST['display']) || !empty($_REQUEST['mod']) || isset($_REQUEST['logout'])) {

	}
}

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

function dl_file_resumable($file, $is_resume=TRUE) {
	//First, see if the file exists
	if (!is_file($file)) {
		header("HTTP/1.0 404 Not Found");
		die("<b>404 File not found!</b>");
	}

	//Gather relevent info about file
	$size = filesize($file);
	$fileinfo = pathinfo($file);
	
	//workaround for IE filename bug with multiple periods / multiple dots in filename
	//that adds square brackets to filename - eg. setup.abc.exe becomes setup[1].abc.exe
	$filename = (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) ? preg_replace('/\./', '%2e', $fileinfo['basename'], substr_count($fileinfo['basename'], '.') - 1) :
	$fileinfo['basename'];

	$file_extension = strtolower($fileinfo['extension']);

	//This will set the Content-Type to the appropriate setting for the file
	switch($file_extension) {
		case 'mpeg':
		case 'mp3': 
			$ctype='audio/mpeg';
		break;
		case 'm4a':
			$ctype='audio/mp4';
		break;
		case 'oga':
		case 'ogg':
			$ctype='audio/ogg';
		break;
		case 'webm':
			$ctype='audio/webma';
		break;
		case 'wav':
			$ctype='audio/wav';
		break;
		default:
			header("HTTP/1.0 403 Forbidden");
			die("<b>403 Forbidden!</b>");
		break;
	}

	//check if http_range is sent by browser (or download manager)
	if($is_resume && isset($_SERVER['HTTP_RANGE'])) {
		list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

		if ($size_unit == 'bytes') {
			//multiple ranges could be specified at the same time, but for simplicity only serve the first range
			//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
			list($range, $extra_ranges) = explode(',', $range_orig, 2);
		} else {
			$range = '';
		}
	} else {
		$range = '';
	}

	//figure out download piece from range (if set)
	list($seek_start, $seek_end) = explode('-', $range, 2);

	//set start and end based on range (if set), else set defaults
	//also check for invalid ranges.
	$seek_end = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)),($size - 1));
	$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);

	//add headers if resumable
	if ($is_resume) {
		//Only send partial content header if downloading a piece of the file (IE workaround)
		if ($seek_start > 0 || $seek_end < ($size - 1)) {
			header('HTTP/1.1 206 Partial Content');
		}

		header('Accept-Ranges: bytes');
		header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$size);
	}

	//headers for IE Bugs (is this necessary?)
	//header("Cache-Control: cache, must-revalidate");   
	//header("Pragma: public");

	header('Content-Type: ' . $ctype);
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Content-Length: '.($seek_end - $seek_start + 1));

	//open the file
	$fp = fopen($file, 'rb');
	//seek to start of missing part
	fseek($fp, $seek_start);

	//start buffered download
	while(!feof($fp)) {
		//reset time limit for big files
		set_time_limit(0);
		print(fread($fp, 1024*8));
		flush();
		ob_flush();
	}

	fclose($fp);
	exit;
}