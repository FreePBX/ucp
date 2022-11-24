<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */

//TODO: In 15 this needs to be namespaced!
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
//progress bar 
use Symfony\Component\Console\Helper\ProgressBar;
class Ucp implements \BMO {
	private $message;
	private $registeredHooks = array();
	private $brand = 'FreePBX';
	private $tokenCache = false;

	//node server
	private $nodever = "6.5.0";
	private $npmver = "3.10.3";
	private $icuver = "50.1.2";
	private $gcc = "4.8.5";
	private $nodeloc = "/tmp";

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}

		$this->FreePBX = $freepbx;
		$this->Userman = $this->FreePBX->Userman;
		$this->db = $freepbx->Database;

		$this->brand = \FreePBX::Config()->get("DASHBOARD_FREEPBX_BRAND");
		$this->nodeloc = __DIR__."/node";
	}


	/**
	 * get_OS : Retruns the name of the operating system.
	 * 
	 * @return string
	 */
	public function get_OS(){
        if(file_exists("/etc/os-release")){
            $file_content   = file_get_contents("/etc/os-release");
            $f_content      = explode("\n",$file_content);
            foreach($f_content as $line){
                list($key, $value)  = explode("=", $line);
                if(!empty($key)){
                    $var[trim($key)]= str_replace('"','',trim($value));
                }        
            }
            extract($var); 
            if(!empty($ID)){
                return $ID;
            }
        }
        
        $os = php_uname();
        preg_match('/\bubuntu\b|\bdebian\b|\bcentos\b|\bfreepbx+[0-9]{2}\b/', strtolower($os), $matches, PREG_OFFSET_CAPTURE, 0);
        if(!empty($matches[0][0])){
            return trim($matches[0][0]); 
        }            
        return "unknown";
    }

	public function install() {
		$settings = array(
			'NODEJSENABLED' => true,
			'NODEJSTLSENABLED' => false,
			'NODEJSBINDADDRESS' => '::',
			'NODEJSBINDPORT' => '8001',
			'NODEJSHTTPSBINDADDRESS' => '::',
			'NODEJSHTTPSBINDPORT' => '8003',
			'NODEJSTLSCERTFILE' => '',
			'NODEJSTLSPRIVATEKEY' => ''
		);

		$info = $this->FreePBX->Modules->getInfo('ucpnode');
		if(!empty($info['ucpnode'])) {
			foreach($settings as $setting => $value) {
				if($this->FreePBX->Config->conf_setting_exists($setting)) {
					$settings[$setting] = $this->FreePBX->Config->get($setting);
				}
			}

			$modclass = \module_functions::create();
			//$modclass->uninstall('ucpnode');
			$modclass->delete('ucpnode');
		}

		exec("g++ --version",$output,$ret); //g++ (GCC) 4.8.5 20150623 (Red Hat 4.8.5-4)
		if(!empty($ret) || empty($output)) {
			out(_("gcc-c++ is not installed"));
			return false;
		}

		$output = exec("node --version"); //v0.10.29
		$output = str_replace("v","",trim($output));
		if(empty($output)) {
			out(_("Node is not installed"));
			return false;
		}
		if(version_compare($output,$this->nodever,"<")) {
			out(sprintf(_("Node version is: %s requirement is %s. Run 'yum upgrade nodejs' from the CLI as root"),$output,$this->nodever));
			return false;
		}


		$output = exec("npm --version"); //v0.10.29
		$output = trim($output);
		if(empty($output)) {
			out(_("Node Package Manager is not installed"));
			return false;
		}
		if(version_compare($output,$this->npmver,"<")) {
			out(sprintf(_("NPM version is: %s requirement is %s. Run 'yum upgrade nodejs' from the CLI as root"),$output,$this->npmver));
			return false;
		}

		$os	= trim($this->get_OS());
		out(_("System")." : ".$os);
		switch ($os) {
			case "ubuntu":
			case "debian":
          	case "raspbian":
				$output = exec("pkg-config --modversion icu-i18n", $out, $retval);
				$output = trim($output);
				if(empty($output)) {
						out(_("icu, pkg-config or pkgconf is not installed. You need to run: apt-get install icu libicu-devel pkg-config pkgconf"));
						return false;
				}
				break;
			default:				
				$output = exec("icu-config --version"); //v4.2.1
				$output = trim($output);
				if(empty($output)) {
						out(_("icu is not installed. You need to run: yum install icu libicu-devel"));
						return false;
				}
		}

		if(version_compare($output,$this->icuver,"<")) {
			out(sprintf(_("ICU version is: %s requirement is %s"),$output,$this->icuver));
			return false;
		}

		$webgroup = $this->FreePBX->Config->get('AMPASTERISKWEBGROUP');

		$data = posix_getgrgid(filegroup($this->getHomeDir()));
		if($data['name'] != $webgroup) {
			out(sprintf(_("Home directory [%s] is not writable"),$this->getHomeDir()));
			return false;
		}

		if(file_exists($this->getHomeDir()."/.npm")) {
			$data = posix_getgrgid(filegroup($this->getHomeDir()."/.npm"));
			if($data['name'] != $webgroup) {
				out(sprintf(_("Home directory [%s] is not writable"),$this->getHomeDir()."/.npm"));
				return false;
			}
		}

		outn(_("Installing/Updating Required Libraries. This may take a while..."));
		if (php_sapi_name() == "cli") {
			out("The following messages are ONLY FOR DEBUGGING. Ignore anything that says 'WARN' or is just a warning");
		}

		$npmstatus = $this->FreePBX->Pm2->installNodeDependencies($this->nodeloc,function($data) {
			outn($data);
		});
		if(!$npmstatus) {
			out("");
			out(_("Failed updating libraries!"));
		} else {
			out("");
			out(_("Finished updating libraries!"));
		}

		$set = array();
		$set['module'] = 'ucp';
		$set['category'] = 'UCP NodeJS Server';

		// NODEJSENABLED
		$set['value'] = $settings['NODEJSENABLED'];
		$set['defaultval'] =& $set['value'];
		$set['options'] = '';
		$set['name'] = 'Enable the NodeJS Server';
		$set['description'] = 'Whether to enable the backend server for UCP which allows instantaneous updates to the interface';
		$set['emptyok'] = 0;
		$set['level'] = 1;
		$set['readonly'] = 0;
		$set['type'] = CONF_TYPE_BOOL;
		$this->FreePBX->Config->define_conf_setting('NODEJSENABLED',$set);

		// NODEJSTLSENABLED
		$set['value'] = $settings['NODEJSTLSENABLED'];
		$set['defaultval'] =& $set['value'];
		$set['options'] = '';
		$set['name'] = 'Enable TLS for the NodeJS Server';
		$set['description'] = 'Whether to enable TLS for the backend server for UCP which allows instantaneous updates to the interface';
		$set['emptyok'] = 0;
		$set['level'] = 1;
		$set['readonly'] = 0;
		$set['type'] = CONF_TYPE_BOOL;
		$this->FreePBX->Config->define_conf_setting('NODEJSTLSENABLED',$set);

		// NODEJSBINDADDRESS
		$set['value'] = $settings['NODEJSBINDADDRESS'];
		$set['defaultval'] =& $set['value'];
		$set['options'] = '';
		$set['name'] = 'NodeJS Bind Address';
		$set['description'] = 'Address to bind to. Default is "::" (Listen for all IPv4 and IPv6 Connections)';
		$set['emptyok'] = 0;
		$set['type'] = CONF_TYPE_TEXT;
		$set['level'] = 2;
		$set['readonly'] = 0;
		$this->FreePBX->Config->define_conf_setting('NODEJSBINDADDRESS',$set);

		// NODEJSBINDPORT
		$set['value'] = $settings['NODEJSBINDPORT'];
		$set['defaultval'] =& $set['value'];
		$set['options'] = '';
		$set['name'] = 'NodeJS Bind Port';
		$set['description'] = 'Port to bind to. Default is 8001';
		$set['emptyok'] = 0;
		$set['options'] = array(10,65536);
		$set['type'] = CONF_TYPE_INT;
		$set['level'] = 2;
		$set['readonly'] = 0;
		$this->FreePBX->Config->define_conf_setting('NODEJSBINDPORT',$set);

		// NODEJSHTTPSBINDADDRESS
		$set['value'] = $settings['NODEJSHTTPSBINDADDRESS'];
		$set['defaultval'] =& $set['value'];
		$set['options'] = '';
		$set['name'] = 'NodeJS HTTPS Bind Address';
		$set['description'] = 'Address to bind to. Default is "::" (Listen for all IPv4 and IPv6 Connections)';
		$set['emptyok'] = 0;
		$set['type'] = CONF_TYPE_TEXT;
		$set['level'] = 2;
		$set['readonly'] = 0;
		$this->FreePBX->Config->define_conf_setting('NODEJSHTTPSBINDADDRESS',$set);

		// NODEJSHTTPSBINDPORT
		$set['value'] = $settings['NODEJSHTTPSBINDPORT'];
		$set['defaultval'] =& $set['value'];
		$set['options'] = '';
		$set['name'] = 'NodeJS HTTPS Bind Port';
		$set['description'] = 'Port to bind to. Default is 8003';
		$set['emptyok'] = 0;
		$set['options'] = array(10,65536);
		$set['type'] = CONF_TYPE_INT;
		$set['level'] = 2;
		$set['readonly'] = 0;
		$this->FreePBX->Config->define_conf_setting('NODEJSHTTPSBINDPORT',$set);

		// NODEJSTLSCERTFILE
		$set['value'] = $settings['NODEJSTLSCERTFILE'];
		$set['defaultval'] =& $set['value'];
		$set['options'] = '';
		$set['name'] = 'NodeJS HTTPS TLS Certificate Location';
		$set['description'] = 'Sets the path to the HTTPS server certificate. This is required if "Enable TLS for the NodeJS Server" is set to yes.';
		$set['emptyok'] = 1;
		$set['type'] = CONF_TYPE_TEXT;
		$set['level'] = 2;
		$set['readonly'] = 0;
		$this->FreePBX->Config->define_conf_setting('NODEJSTLSCERTFILE',$set);

		// NODEJSTLSPRIVATEKEY
		$set['value'] = $settings['NODEJSTLSPRIVATEKEY'];
		$set['defaultval'] =& $set['value'];
		$set['options'] = '';
		$set['name'] = 'NodeJS HTTPS TLS Private Key Location';
		$set['description'] = 'Sets the path to the HTTPS private key. This is required if "Enable TLS for the NodeJS Server" is set to yes.';
		$set['emptyok'] = 1;
		$set['type'] = CONF_TYPE_TEXT;
		$set['level'] = 2;
		$set['readonly'] = 0;
		$this->FreePBX->Config->define_conf_setting('NODEJSTLSPRIVATEKEY',$set);

		$this->FreePBX->Config->commit_conf_settings();

		$cert = $this->FreePBX->Certman->getDefaultCertDetails();
		if(!empty($cert)) {
			$this->setDefaultCert($cert, false);
		}

		if($this->FreePBX->Modules->checkStatus("sysadmin")) {
			touch("/var/spool/asterisk/incron/ucp.logrotate");
		}

		//If we are root then start it as asterisk, otherwise we arent root so start it as the web user (all we can do really)
		outn(_("Stopping old running processes..."));
		$this->stopFreepbx();
		out(_("Done"));

		$this->expireAllUserSessions();

		if($npmstatus) {
			outn(_("Starting new UCP Node Process..."));
			$started = $this->startFreepbx();
			if(!$started) {
				out(_("Failed!"));
			} else {
				out(sprintf(_("Started with PID %s!"),$started));
			}
		}

		out(_("Refreshing all UCP Assets, this could take a while..."));
		$this->generateUCP(true);
		out("Done!");
	}

	public function uninstall() {
		$path = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT');
		$location = $path.'/ucp';
		unlink($location);

		outn(_("Stopping old running processes..."));
		$this->stopFreepbx();
		out(_("Done"));
		exec("rm -Rf ".$this->nodeloc."/node_modules");
		try {
			$this->FreePBX->Pm2->delete("ucp");
		} catch(\Exception $e) {}
	}
	public function backup(){

	}
	public function restore($backup){

	}

	/**
	 * Force UCP to refresh on next page load
	 * @param  int $uid User Manager ID
	 */
	public function refreshInterface($uid) {
		if(!empty($uid)) {
			$ref = $this->Userman->getModuleSettingByID($uid,'ucp|Global','flushPage');
			if($ref) {
				$this->Userman->setModuleSettingByID($uid,'ucp|Global','flushPage',false);
			}
			return $ref;
		}
		return false;
	}

	public function usermanShowPage() {
		if(isset($_REQUEST['action'])) {
			$mode = ($_REQUEST['action'] == "showgroup" || $_REQUEST['action'] == "addgroup" ) ? "group" : "user";
			switch($_REQUEST['action']) {
				case 'showgroup':
					$group = $this->getGroupByGID($_REQUEST['group']);
					$ausers = array(
						'self' => _("User Primary Extension")
					);
					$users = core_users_list();
					if(!empty($users) && is_array($users)) {
						foreach($users as $list) {
							$ausers[$list[0]] = $list[1] . " &#60;".$list[0]."&#62;";
						}
					}
					$sassigned = $this->Userman->getModuleSettingByGID($_REQUEST['group'],'ucp|Settings','assigned');
					$sassigned = !empty($sassigned) ? $sassigned : array();
					$tempList = $this->Userman->getAllUcpTemplates();
					return array(
						array(
							"title" => "UCP",
							"rawname" => "ucp",
							"content" => load_view(dirname(__FILE__).'/views/users_hook.php',array(
								"mode" => $mode,
								"ausers" => $ausers,
								"sassigned" => $sassigned,
								"mHtml" => $this->constructModuleConfigPages('group',$group,$_REQUEST['action']),
								"user" => array(),
								"allowLogin" => $this->Userman->getModuleSettingByGID($_REQUEST['group'],'ucp|Global','allowLogin'),
								"originate" => $this->Userman->getModuleSettingByGID($_REQUEST['group'],'ucp|Global','originate'),
								"tourMode" => $this->Userman->getModuleSettingByGID($_REQUEST['group'],'ucp|Global','tour'),
								"tempList" => $tempList,
								"assignedTemplate" => $this->Userman->getModuleSettingByGID($_REQUEST['group'],'ucp|template','templateid'),
								"selectTemplate" => $this->Userman->getModuleSettingByGID($_REQUEST['group'],'ucp|template','assigntemplate'))
							)
						)
					);
				break;
				case 'addgroup':
					$ausers = array(
						'self' => _("User Primary Extension")
					);
					$users = core_users_list();
					if(!empty($users) && is_array($users)) {
						foreach($users as $list) {
							$ausers[$list[0]] = $list[1] . " &#60;".$list[0]."&#62;";
						}
					}
					$tempList = $this->Userman->getAllUcpTemplates();
					return array(
						array(
							"title" => "UCP",
							"rawname" => "ucp",
							"content" => load_view(dirname(__FILE__).'/views/users_hook.php',array(
								"mode" => $mode,
								"ausers" => $ausers,
								"sassigned" => array('self'),
								"mHtml" => $this->constructModuleConfigPages('group', array(),$_REQUEST['action']),
								"user" => array(),
								"allowLogin" => true,
								"originate" => false,
								"tourMode" => true,
								"selectTemplate" => false,
								"tempList" => $tempList	)
							)
						)
					);
				break;
				case 'showuser':
					$user = $this->getUserByID($_REQUEST['user']);
					if(!empty($_REQUEST['deletesession'])) {
						$this->expireUserSession($_REQUEST['deletesession']);
						$this->setUsermanMessage(_('Deleted User Session'),'success');
					}
					$ausers = array();
					$sassigned = $this->getSetting($user['username'],'Settings','assigned');
					$users = core_users_list();
					if(!empty($users) && is_array($users)) {
						foreach($users as $list) {
							$ausers[$list[0]] = $list[1] . " &#60;".$list[0]."&#62;";
						}
					}
					$sassigned = !empty($sassigned) ? $sassigned : array();
					$tempList = $this->Userman->getAllUcpTemplates();
					return array(
						array(
							"title" => "UCP",
							"rawname" => "ucp",
							"content" => load_view(dirname(__FILE__).'/views/users_hook.php',array(
								"mode" => $mode,
								"ausers" => $ausers,
								"sassigned" => $sassigned,
								"mHtml" => $this->constructModuleConfigPages('user',$user,$_REQUEST['action']),
								"user" => $user,
								"allowLogin" => FreePBX::create()->Userman->getModuleSettingByID($_REQUEST['user'],'ucp|Global','allowLogin',true),
								"originate" => FreePBX::create()->Userman->getModuleSettingByID($_REQUEST['user'],'ucp|Global','originate',true),
								"tourMode" => FreePBX::create()->Userman->getModuleSettingByID($_REQUEST['user'],'ucp|Global','tour',true),
								"sessions" => $this->getUserSessions($user['id']),
								"tempList" => $tempList,
								"assignedTemplate" => $this->Userman->getModuleSettingByID($_REQUEST['user'],'ucp|template','templateid',true),
								"selectTemplate" => $this->Userman->getModuleSettingByID($_REQUEST['user'],'ucp|template','assigntemplate',true)
								)
							)
						)
					);
				break;
				case 'adduser':
					$ausers = array();
					$users = core_users_list();
					if(!empty($users) && is_array($users)) {
						foreach($users as $list) {
							$ausers[$list[0]] = $list[1] . " &#60;".$list[0]."&#62;";
						}
					}
					$tempList = $this->Userman->getAllUcpTemplates();
					return array(
						array(
							"title" => "UCP",
							"rawname" => "ucp",
							"content" => load_view(dirname(__FILE__).'/views/users_hook.php',array(
								"mode" => $mode,
								"ausers" => $ausers,
								"sassigned" => array('self'),
								"mHtml" => $this->constructModuleConfigPages('user',array(),$_REQUEST['action']),
								"user" => array(),
								"allowLogin" => null,
								"originate" => null,
								"tourMode" => null,
								"sessions" => array(),
								"selectTemplate" => null,
								"tempList" => $tempList
								)
							)
						)
					);
				break;
				default:
				break;
			}
		}
	}

	/**
	 * Hook functionality for sending an email from userman
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function usermanSendEmail($id, $display, $data) {
		$hostname = '';
		if(isset($data['host'])){
			$hostname = $data['host'];
		}
		$link = $this->getUcpLink($hostname);

		$usettings = $this->FreePBX->Userman->getAuthAllPermissions();

		$final = array(
			"\t".sprintf(_('User Control Panel: %s'),$link),
		);
		if(!$data['password'] && $usettings['changePassword']) {
			$token = $this->FreePBX->Userman->generatePasswordResetToken($id,"1 day",true);
			$final[] = "\n".sprintf(_('Password Reset Link (Valid Until: %s): %s'),date("F j, Y, g:i A", $token['valid']),$link."/?forgot=".$token['token']);
		}
		return $final;
	}

	public function validatePasswordResetToken($token) {
		return $this->FreePBX->Userman->validatePasswordResetToken($token);
	}

	public function resetPasswordWithToken($token,$newpassword) {
		return $this->FreePBX->Userman->resetPasswordWithToken($token,$newpassword);
	}


	/**
	 * Get the correct URL for UCP
	 *
	 * This tries to use the UCP port specified in sysadmin,
	 * if available. Otherwise it defaults to however this
	 * was requested, with the /ucp/ path.
	 *
	 * return string $url
	 */
	public function getUcpLink($hostname = null) {
		if(empty($hostname)){
			$hostname = $_SERVER["SERVER_NAME"];
		}else{
			$tmp_data = parse_url($hostname);
			if(isset($tmp_data['host'])){
				$hostname = $tmp_data['host'];
			}else{
				$hostname = $tmp_data['path'];
			}
		}

		// Start by checking if Sysadmin exists. If it does, try using that.
		if($this->FreePBX->Modules->moduleHasMethod("sysadmin","getPorts")) {
			$ports = \FreePBX::Sysadmin()->getPorts();
		} else {
			if(!function_exists('sysadmin_get_portmgmt')) {
				// Is sysadmin installed on this machine, but just not loaded?
				if (file_exists($this->FreePBX->Config()->get('AMPWEBROOT').'/admin/modules/sysadmin/functions.inc.php')) {
					include $this->FreePBX->Config()->get('AMPWEBROOT').'/admin/modules/sysadmin/functions.inc.php';
				}
			}
			if(function_exists('sysadmin_get_portmgmt')) {
				$ports = sysadmin_get_portmgmt();
			} else {
				// No sysadmin on this machine. Let's try and figure out what we've got.
				if (isset($_SERVER["HTTPS"])) {
					// We're using a SSL connection to request this
					$ports = array("sslacp" => $_SERVER["SERVER_PORT"]);
				} else {
					$ports = array("acp" => $_SERVER["SERVER_PORT"]);
				}
			}
		}

		// 1. Prefer SSL Ucp port over anything.
		if (isset($ports['sslucp']) && is_numeric($ports['sslucp'])) {
			if ($ports['sslucp'] == 443) {
				$url = 'https://'. $hostname;
			} else {
				$url = 'https://'.$hostname.":".$ports['sslucp'];
			}
		// 2. Try http UCP Port next
		} else if(isset($ports['ucp']) && is_numeric($ports['ucp'])) {
			if ($ports['ucp'] == 80) {
				$url = 'http://'.$hostname;
			} else {
				$url = 'http://'.$hostname.":".$ports['ucp'];
			}
		// 3. Try sslacp as our third option
		} else if(isset($ports['sslacp']) && is_numeric($ports['sslacp'])) {
			if ($ports['sslacp'] == 443) {
				$url = 'https://'.$hostname.'/ucp';
			} else {
				$url = 'https://'.$hostname.":".$ports['sslacp'].'/ucp';
			}
		// 4. Try normal acp as our third option
		} else if(isset($ports['acp']) && is_numeric($ports['acp'])) {
			if ($ports['acp'] == 80) {
				$url = 'http://'.$hostname.'/ucp';
			} else {
				$url = 'http://'.$hostname.":".$ports['acp'].'/ucp';
			}
		} else {
			// Somehow I didn't get a SERVER_NAME, so I don't know what url
			// to hand back.
			$url = 'invalid://unknown.server.name/ucp';
		}
		return $url;
	}

	/**
	* Sends a password reset email
	* @param {int} $id The userid
	*/
	public function sendPassResetEmail($id) {
		global $amp_conf;
		$user = $this->getUserByID($id);
		if(empty($user) || empty($user['email'])) {
			return false;
		}

		// Forcefully create reset token
		$token = $this->Userman->generatePasswordResetToken($id, null, true);

		if(!$token) {
			freepbx_log(FPBX_LOG_NOTICE, "Unable to generate password token for ".$user['username']);
			return false;
		}

		$user['token'] = $token['token'];
		$user['brand'] = $this->brand;

		$user['link'] = $this->getUcpLink()."/?forgot=".$user['token'];
		$user['valid'] = date("Y-m-d h:i:s A", $token['valid']);

		$template = file_get_contents(__DIR__.'/views/emails/reset_text.tpl');
		preg_match_all('/%([\w|\d]*)%/',$template,$matches);
		foreach($matches[1] as $match) {
			$replacement = !empty($user[$match]) ? $user[$match] : '';
			$template = str_replace('%'.$match.'%',$replacement,$template);
		}

		return $this->Userman->sendEmail($user['id'],$this->brand . " password reset",$template);
	}

	public function delGroup($id,$display,$data) {
		$this->FreePBX->Hooks->processHooks($id,$display,false,$data);
		$group = $this->Userman->getGroupByGID($id);
		if(isset($group['users']) && is_array($group['users'])) {
			foreach($group['users'] as $user) {
				$this->expireUserSessions($user);
			}
		}
	}

	public function addGroup($id, $display, $data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'group') {
			if($_POST['ucp_tour'] == 'true') {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','tour', true);
			} else {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','tour', false);
			}
			if($_POST['ucp_login'] == 'true') {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','allowLogin', true);
			} else {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','allowLogin', false);
			}
			if($_POST['ucp_originate'] == 'yes') {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','originate', true);
			} else {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','originate', false);
			}
			$this->Userman->setModuleSettingByGID($id,'ucp|Settings','assigned', $_POST['ucp_settings']);
			if($_POST['assign_template'] == 'true') {
				$this->Userman->setModuleSettingByGID($id,'ucp|template','assigntemplate',true);
				$this->Userman->setModuleSettingByGID($id,'ucp|template','templateid',$_POST['templateid']);
			} else {
				$this->Userman->setModuleSettingByGID($id,'ucp|template','assigntemplate',false);
				$this->Userman->setModuleSettingByGID($id,'ucp|template','templateid',null);
			}
		}

		$login = $this->Userman->getModuleSettingByGID($id,'ucp|Global','originate');
		$this->FreePBX->Hooks->processHooks($id,$display,$login,$data);

		$group = $this->Userman->getGroupByGID($id);
		foreach($group['users'] as $user) {
			$this->FreePBX->Userman->setModuleSettingByID($user,'ucp|Global','flushPage',true);
		}
	}

	public function updateGroup($id,$display,$data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'group') {
			if($_POST['ucp_tour'] == 'true') {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','tour', true);
			} else {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','tour', false);
			}
			if($_POST['ucp_login'] == 'true') {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','allowLogin', true);
			} else {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','allowLogin', false);
			}
			if($_POST['ucp_originate'] == 'yes') {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','originate', true);
			} else {
				$this->Userman->setModuleSettingByGID($id,'ucp|Global','originate', false);
			}
			$this->Userman->setModuleSettingByGID($id,'ucp|Settings','assigned', $_POST['ucp_settings']);
			if($_POST['assign_template'] == 'true') {
				$this->Userman->setModuleSettingByGID($id,'ucp|template','assigntemplate',true);
				$this->Userman->setModuleSettingByGID($id,'ucp|template','templateid',$_POST['templateid']);
			} else {
				$this->Userman->setModuleSettingByGID($id,'ucp|template','assigntemplate',false);
				$this->Userman->setModuleSettingByGID($id,'ucp|template','templateid',null);
			}
		}

		$login = $this->Userman->getModuleSettingByGID($id,'ucp|Global','originate');
		$this->FreePBX->Hooks->processHooks($id,$display,$login,$data);

		$group = $this->Userman->getGroupByGID($id);
		foreach($group['users'] as $user) {
			$this->FreePBX->Userman->setModuleSettingByID($user,'ucp|Global','flushPage',true);
		}
	}

	/**
	 * Hook functionality from userman when a user is deleted
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function delUser($id, $display, $data) {
		$this->expireUserSessions($id);
		$this->deleteUser($id);
		$this->FreePBX->Hooks->processHooks($id,$display,false,$data);
	}

	/**
	 * Hook functionality from userman when a user is added
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function addUser($id, $display, $data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'user') {
			if(isset($_POST['ucp_login'])) {
				if($_POST['ucp_tour'] == 'true') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','tour',true);
				} elseif($_POST['ucp_tour'] == 'false') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','tour',false);
				} else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','tour',null);
				}
				if($_POST['ucp_login'] == 'true') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',true);
				} elseif($_POST['ucp_login'] == 'false') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',false);
				} else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',null);
				}
				if(isset($_POST['ucp_settings'])) {
					$this->setSettingByID($id,'Settings','assigned',$_POST['ucp_settings']);
				} else {
					$this->setSettingByID($id,'Settings','assigned',null);
				}
				if($_POST['ucp_originate'] == 'yes') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','originate',true);
				} elseif($_POST['ucp_originate'] == 'no') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','originate',false);
				} else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','originate',null);
				}
				if($_POST['assign_template'] == 'true') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','assigntemplate',true);
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','templateid',$_POST['templateid']);
                                } elseif($_POST['assign_template'] == 'false') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','assigntemplate',false);
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','templateid',null);
                                } else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','assigntemplate',null);
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','templateid',null);
                                }
			}
		}
		$login = $this->FreePBX->Userman->getModuleSettingByID($id,'ucp|Global','allowLogin');
		$this->FreePBX->Hooks->processHooks($id,$display,$login,$data);
		$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','flushPage',true);
	}

	/**
	 * Hook functionality from userman when a user is updated
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function updateUser($id, $display, $data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'user') {
			if(isset($_POST['ucp_login'])) {
				if($_POST['ucp_tour'] == 'true') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','tour',true);
				} elseif($_POST['ucp_tour'] == 'false') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','tour',false);
				} else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','tour',null);
				}
				if($_POST['ucp_login'] == 'true') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',true);
				} elseif($_POST['ucp_login'] == 'false') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',false);
				} else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',null);
				}
				if(isset($_POST['ucp_settings'])) {
					$this->setSettingByID($id,'Settings','assigned',$_POST['ucp_settings']);
				} else {
					$this->setSettingByID($id,'Settings','assigned',null);
				}
				if($_POST['ucp_originate'] == 'yes') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','originate',true);
				} elseif($_POST['ucp_originate'] == 'no') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','originate',false);
				} else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','originate',null);
				}
				if($_POST['assign_template'] == 'true') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','assigntemplate',true);
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','templateid',$_POST['templateid']);
				} elseif($_POST['assign_template'] == 'false') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','assigntemplate',false);
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','templateid',null);
				} else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','assigntemplate',null);
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|template','templateid',null);
				}
			}
		}
		$login = $this->FreePBX->Userman->getModuleSettingByID($id,'ucp|Global','allowLogin');
		$this->FreePBX->Hooks->processHooks($id,$display,$login,$data);
		$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','flushPage',true);
		return true;
	}

	/**
	 * Get language from a module and make it json for UCP translations
	 * @param {string} $language The Language name
	 * @param {array} $modules  Array of module rawnames
	 */
	public function getModulesLanguage($language, $modules) {
		if(!class_exists("po2json")) {
			require_once(__DIR__."/includes/po2json.php");
		}
		$final = array();
		$root = $this->FreePBX->Config->get("AMPWEBROOT");
		//first get ucp
		$po = $root."/admin/modules/ucp/i18n/" . $language . "/LC_MESSAGES/ucp.po";
		if(file_exists($po)) {
			$c = new po2json($po,"ucp");
			$array = $c->po2array();
			if(!empty($array)) {
				$final['ucp'] = $array;
			}
		}
		//now get the modules
		foreach ($modules as $module) {
			$module = strtolower($module);
			$po = $root."/admin/modules/".$module."/i18n/" . $language . "/LC_MESSAGES/".$module.".po";
			if(file_exists($po)) {
				$c = new po2json($po,$module);
				$array = $c->po2array();
				if(!empty($array)) {
					$final[$module] = $array;
				}
			}
		}
		return json_encode($final);
	}

	/**
	 * Register a hook from another module
	 * This is semi depreciated as FreePBX 12 has hooking functions now
	 * @param {string} $action The action
	 * @param {string} $class  The class name
	 * @param {string} $method The method name
	 */
	public function registerHook($action,$class,$method) {
		$this->registeredHooks[$action] = array('class' => $class, 'method' => $method);
	}

	/**
	 * Construct Module Configuration Pages
	 * This is used to setup and display module configuration pages
	 * in User Manager
	 * @param {array} $user The user array
	 */
	public function constructModuleConfigPages($mode, $user, $action) {
		$mods = $this->FreePBX->Hooks->processHooks($mode, $user, $action);
		$html = [];
		if(!empty($mods) && is_array($mods)) {
			foreach($mods as $module) {
				if(!empty($module) && is_array($module)) {
					foreach($module as $item) {
						if(empty($item)) {
							continue;
						}
						if(is_array($item)) {
							if(!isset($html[$item['rawname']])) {
								$html[$item['rawname']] = array(
									"title" => $item['title'],
									"rawname" => $item['rawname'],
									"content" => $item['content']
								);
							} else {
								$item['rawname']['content'] .= $item['content'];
							}
						} else {
							if(!isset($html[$mod])) {
								$html[$mod] = array(
									"title" => ucfirst(strtolower($mod)),
									"rawname" => $mod,
									"content" => $item
								);
							} else {
								$item[$mod]['content'] .= $item;
							}
						}
					}
				}
			}
		}
		return $html;
	}

	/**
	 * Retrieve Conf Hook to search all modules and add their respective UCP folders
	 */
	public function genConfig() {
		$this->generateUCP();
	}

	/**
	 * Generate UCP assets if needed
	 * @param {bool} $regenassets = false If set to true regenerate assets even if not needed
	 */
	public function generateUCP($regenassets = false) {
		$moduleFunctionsCreate = module_functions::create();
		$modulef =& $moduleFunctionsCreate;
		$modules = $modulef->getinfo(false);
		$path = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT');
		$location = $path.'/ucp';
		if(!file_exists($location)) {
			symlink(dirname(__FILE__).'/htdocs',$location);
		}
		foreach($modules as $module) {
			if(isset($module['rawname'])) {
				$rawname = trim($module['rawname']);
				if(file_exists($path.'/admin/modules/'.$rawname.'/ucp') && file_exists($path.'/admin/modules/'.$rawname.'/ucp/'.ucfirst($rawname).".class.php")) {
					if($module['status'] == MODULE_STATUS_ENABLED) {
						if(!file_exists($location."/modules/".ucfirst($rawname))) {
							symlink($path.'/admin/modules/'.$rawname.'/ucp',$location.'/modules/'.ucfirst($rawname));
						}
					} elseif($module['status'] != MODULE_STATUS_DISABLED && $module['status'] != MODULE_STATUS_ENABLED) {
						if(file_exists($location."/modules/".ucfirst($rawname)) && is_link($location."/modules/".ucfirst($rawname))) {
							unlink($location."/modules/".ucfirst($rawname));
						}
					}
				}
			}
		}
		if($regenassets) {
			$this->refreshAssets();
		}
	}

	public function deleteUser($uid) {
		//run module functions here if needed otherwise usermanager delete's most of what we are using
	}

	public function writeConfig($conf){
		$this->FreePBX->WriteConfig($conf);
	}

	public function doConfigPageInit($display) {
		switch($_REQUEST['category']) {
			case 'users':
				if(isset($_POST['submit'])) {
					$user = $this->getUserByID($_REQUEST['user']);
					$this->expireUserSessions($_REQUEST['user']);
				}
				if(!empty($_REQUEST['deletesession'])) {
					$this->message = $this->expireUserSession($_REQUEST['deletesession']);
				}
			break;
		}
	}

	/**
	 * Sets a message in user manager
	 * @param {string} $message     The message
	 * @param {string} $type="info" The message type
	 */
	public function setUsermanMessage($message,$type="info") {
		$this->FreePBX->Userman->setMessage(_('Deleted User Session'),'success');
		return true;
	}

	public function getCombinedSettingByID($id,$module,$setting) {
		$assigned = $this->FreePBX->Userman->getCombinedModuleSettingByID($id,'ucp|'.ucfirst(strtolower($module)),$setting);
		return $assigned;
	}

	/**
	 * Get Setting from Userman
	 * @param {string} $username The username
	 * @param {string} $module   The module name
	 * @param {string} $setting  The setting name
	 */
	public function getSetting($username,$module,$setting) {
		$user = $this->getUserByUsername($username);
		$assigned = $this->FreePBX->Userman->getModuleSettingByID($user['id'],'ucp|'.ucfirst(strtolower($module)),$setting,true);
		return $assigned;
	}

	/**
	* Get Setting from Userman
	* @param {int} $id The UID
	* @param {string} $module   The module name
	* @param {string} $setting  The setting name
	*/
	public function getSettingByID($id,$module,$setting) {
		$assigned = $this->FreePBX->Userman->getModuleSettingByID($id,'ucp|'.ucfirst(strtolower($module)),$setting,true);
		return $assigned;
	}

	/**
	* Get Setting from Userman
	* @param {int} $gid The GID
	* @param {string} $module   The module name
	* @param {string} $setting  The setting name
	*/
	public function getSettingByGID($gid,$module,$setting) {
		$assigned = $this->FreePBX->Userman->getModuleSettingByGID($gid,'ucp|'.ucfirst(strtolower($module)),$setting,true);
		return $assigned;
	}

	/**
	 * Set a Setting
	 * @param {string} $username The userman
	 * @param {string} $module   The module name
	 * @param {string} $setting  The setting
	 * @param {mixed} $value    The value
	 */
	public function setSetting($username,$module,$setting,$value) {
		$user = $this->getUserByUsername($username);
		$assigned = $this->FreePBX->Userman->setModuleSettingByID($user['id'],'ucp|'.ucfirst(strtolower($module)),$setting,$value);
		return $assigned;
	}

	/**
	* Set a Setting
	* @param int} $id The UID
	* @param {string} $module   The module name
	* @param {string} $setting  The setting
	* @param {mixed} $value    The value
	*/
	public function setSettingByID($id,$module,$setting,$value) {
		$assigned = $this->FreePBX->Userman->setModuleSettingByID($id,'ucp|'.ucfirst(strtolower($module)),$setting,$value);
		return $assigned;
	}

	/**
	* Set a Setting
	* @param int} $gid The GID
	* @param {string} $module   The module name
	* @param {string} $setting  The setting
	* @param {mixed} $value    The value
	*/
	public function setSettingByGID($gid,$module,$setting,$value) {
		$assigned = $this->FreePBX->Userman->setModuleSettingByGID($gid,'ucp|'.ucfirst(strtolower($module)),$setting,$value);
		return $assigned;
	}

	/**
	 * Get all Userman Users
	 */
	public function getAllUsers() {
		$final = $this->FreePBX->Userman->getAllUsers();
		return !empty($final) ? $final : array();
	}

	/**
	 * Get User by Username
	 * @param {string} $username The username
	 */
	public function getUserByUsername($username) {
		$user = $this->FreePBX->Userman->getUserByUsername($username);
		return $user;
	}

	public function getUserByEmail($email) {
		$user = $this->FreePBX->Userman->getUserByEmail($email);
		return $user;
	}

	/**
	 * Get user by user id
	 * @param {int} $id The user id
	 */
	public function getUserByID($id) {
		$user = $this->FreePBX->Userman->getUserByID($id);
		return $user;
	}

	/**
	* Get user by user id
	* @param {int} $id The user id
	*/
	public function getGroupByGID($id) {
		$user = $this->FreePBX->Userman->getGroupByGID($id);
		return $user;
	}

	/**
	* Trash all sessions (used for upgrade purposes)
	*/
	public function expireAllUserSessions($days = null) {
		if(empty($days)) {
			$sql = "TRUNCATE TABLE ucp_sessions";
		} elseif(ctype_digit($days)) {
			$sql = "DELETE FROM ucp_sessions WHERE `time` < unix_timestamp(now() - interval ".$days." day))";
		} else {
			return false;
		}
		try {
			$sth = $this->db->prepare($sql);
			$sth->execute();
		} catch(\Exception $e) {}
		return true;
	}

	/**
	 * Clear all sessions (which wille ssentially log any user out)
	 * @param {[type]} $uid [description]
	 */
	public function expireUserSessions($uid) {
		$sql = "DELETE FROM ucp_sessions WHERE uid = :uid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':uid' => $uid));
		return true;
	}

	/**
	 * Expire User Session
	 * @param {string} $session The session name
	 */
	public function expireUserSession($session) {
		$sql = "DELETE FROM ucp_sessions WHERE session = :session";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':session' => $session));
		return array("status" => true, "type" => "success", "message" => _("Deleted Session"));
	}

	/**
	 * Get all user sessions
	 * @param {int} $uid The user ID
	 */
	public function getUserSessions($uid) {
		$sql = "SELECT * FROM ucp_sessions WHERE uid = :uid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':uid' => $uid));
		$sessions = $sth->fetchAll(PDO::FETCH_ASSOC);
		return !empty($sessions) ? $sessions : array();
	}

	/**
	 * Delete a single user session
	 * @param {string} $token The string token
	 * @param {int} $uid   The user id
	 */
	public function deleteToken($token, $uid) {
		$sql = "DELETE FROM ucp_sessions WHERE session = :session AND uid = :uid";
		$sth = $this->db->prepare($sql);
		return $sth->execute(array(':session' => $token, ':uid' => $uid));
	}

	/**
	 * Unlock a UCP Session by session token and username
	 * @param {string} $username The username to login to
	 * @param {string} $session  session id
	 */
	public function sessionUnlock($username, $session, $address = "CLI") {
		$user = $this->getUserByUsername(trim($username));
		if(empty($user["id"])) {
			return false;
		}
		session_id(trim($session));
		session_start();
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$_SESSION["UCP_token"] = $token;
		session_write_close();
		$this->storeToken($token, $user["id"], $address);
		return true;
	}

	/**
	 * Store a token, assigned to a session
	 * @param {string} $token   The token
	 * @param {int} $uid     The user ID
	 * @param {string} $address The IP address
	 */
	public function storeToken($token, $uid, $address) {
		$sql = "INSERT INTO ucp_sessions (session, uid, address, time) VALUES (:session, :uid, :address, :time) ON DUPLICATE KEY UPDATE time = VALUES(time)";
		$sth = $this->db->prepare($sql);
		return $sth->execute(array(':session' => $token, ':uid' => $uid, ':address' => $address, ':time' => time()));
	}

	/**
	 * Get token
	 * @param {string} $token The token name
	 */
	public function getToken($token) {
		if(!empty($this->tokenCache)) {
			return $this->tokenCache;
		}
		$expire = $this->FreePBX->Config->get("UCPSESSIONTIMEOUT");
		if(!empty($expire) && ctype_digit($expire)) {
			$this->expireAllUserSessions($expire);
		}

		$sql = "SELECT uid, address FROM ucp_sessions WHERE session = :token";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':token' => $token));
		$this->tokenCache = $sth->fetch(\PDO::FETCH_ASSOC);
		return $this->tokenCache;
	}

	/**
	 * Check credentials through userman
	 * @param {string} $username      The username
	 * @param {string} $password      The password
	 */
	public function checkCredentials($username, $password) {
		return $this->FreePBX->Userman->checkCredentials($username, $password);
	}

	public function refreshAssets() {
		if(!class_exists('UCP\UCP',false)) {
			include(dirname(__FILE__).'/htdocs/includes/bootstrap.php');
		}
		$ucp = \UCP\UCP::create();
		$compressed = $this->FreePBX->Config->get("USE_PACKAGED_JS");

		outn(_("Generating Module Scripts..."));
		$ucp->Modules->getGlobalScripts(true);
		out(_("Done"));

		outn(_("Generating Module CSS..."));
		$ucp->Modules->getGlobalLess(true);
		out(_("Done"));

		outn(_("Generating Main Scripts..."));
		$ucp->getCss(true);
		out(_("Done"));

		outn(_("Generating Main CSS..."));
		$ucp->getScripts(true, $compressed);
		out(_("Done"));
	}

	public function chownFreepbx() {
		$files = array();
		$ampwebroot = $this->FreePBX->Config->get("AMPWEBROOT");
		$files[] = array('type' => 'rdir',
												'path' => $ampwebroot.'/ucp',
												'perms' => 0775);
		return $files;
	}

	public function setDefaultCert($details, $restart=true) {
		$certF = isset($details['integration']['files']['pem']) ? $details['integration']['files']['pem'] : $details['integration']['files']['crt'];
		$keyF = $details['integration']['files']['key'];
		$this->FreePBX->Config->update("NODEJSTLSENABLED",true);
		$this->FreePBX->Config->update("NODEJSTLSCERTFILE",$certF);
		$this->FreePBX->Config->update("NODEJSTLSPRIVATEKEY",$keyF);
		if($restart) {
			try {
				$this->FreePBX->Pm2->restart("ucp");
			} catch(\Exception $e) {}
		}
	}

	public function getHomeDir() {
		$webuser = \FreePBX::Freepbx_conf()->get('AMPASTERISKWEBUSER');
		$web = posix_getpwnam($webuser);
		$home = trim($web['dir']);
		if (!is_dir($home)) {
			// Well, that's handy. It doesn't exist. Let's use ASTSPOOLDIR instead, because
			// that should exist and be writable.
			$home = \FreePBX::Freepbx_conf()->get('ASTSPOOLDIR');
			if (!is_dir($home)) {
				// OK, I give up.
				throw new \Exception(sprintf(_("Asterisk home dir (%s) doesn't exist, and, ASTSPOOLDIR doesn't exist. Aborting"),$home));
			}
		}
		return $home;
	}

	public function dashboardService() {
		$service = array(
			'title' => _('UCP Daemon'),
			'type' => 'unknown',
			'tooltip' => _("Unknown"),
			'order' => 999,
			'glyph-class' => ''
		);
		$data = $this->FreePBX->Pm2->getStatus("ucp");
      	if(!$this->FreePBX->Config->get("NODEJSENABLED")) {
			$service = array_merge($service, $this->genAlertGlyphicon('error', _("UCP Node Disabled in Advanced Settings.")));
			return array($service);
		}
      
		if(!empty($data) && $data['pm2_env']['status'] == 'online') {
			$uptime = $data['pm2_env']['created_at_human_diff'];
			$service = array_merge($service, $this->genAlertGlyphicon('ok', sprintf(_("Running (Uptime: %s)"),$uptime)));
		} else {
			$service = array_merge($service, $this->genAlertGlyphicon('critical', _("UCP Node is not running")));
		}

		return array($service);
	}

	/**
	 * Start FreePBX for fwconsole hook
	 * @param object $output The output object.
	 */
	public function startFreepbx($output=null) {
		if(!$this->FreePBX->Config->get("NODEJSENABLED")) {
			return;
		}
		$status = $this->FreePBX->Pm2->getStatus("ucp");
		switch($status['pm2_env']['status']) {
			case 'online':
				if(is_object($output)) {
					$output->writeln(sprintf(_("UCP Node Server has already been running on PID %s for %s"),$status['pid'],$status['pm2_env']['created_at_human_diff']));
				}
				return $status['pid'];
			break;
			default:
				if(is_object($output)) {
					$output->writeln(_("Starting UCP Node Server..."));
				}
				$this->FreePBX->Pm2->start("ucp",__DIR__."/node/index.js");
				if(is_object($output)) {
					$progress = new ProgressBar($output, 0);
					$progress->setFormat('[%bar%] %elapsed%');
					$progress->start();
				}
				$i = 0;
				while($i < 100) {
					$data = $this->FreePBX->Pm2->getStatus("ucp");
					if(!empty($data) && $data['pm2_env']['status'] == 'online') {
						if(is_object($output)) {
							$progress->finish();
						}
						break;
					}
					if(is_object($output)) {
						$progress->setProgress($i);
					}
					$i++;
					usleep(100000);
				}
				if(is_object($output)) {
					$output->writeln("");
				}
				if(!empty($data)) {
					$this->FreePBX->Pm2->reset("ucp");
					if(is_object($output)) {
						$output->writeln(sprintf(_("Started UCP Node Server. PID is %s"),$data['pid']));
					}
					return $data['pid'];
				}
				if(is_object($output)) {
					$output->write("<error>".sprintf(_("Failed to run: '%s'")."</error>",$command));
				}
			break;
		}
		return false;
	}

	/**
	 * Stop FreePBX for fwconsole hook
	 * @param object $output The output object.
	 */
	public function stopFreepbx($output=null) {
		exec("pgrep -f ucpnode/node/node_modules/forever/bin/monitor",$o);
		if($o) {
			foreach($o as $z) {
				$z = trim($z);
				posix_kill($z, 9);
			}

			exec("pgrep -f ucpnode/node/index.js",$o);
			foreach($o as $z) {
				$z = trim($z);
				posix_kill($z, 9);
			}
		}

		$data = $this->FreePBX->Pm2->getStatus("ucp");
		if(empty($data) || $data['pm2_env']['status'] != 'online') {
			if(is_object($output)) {
				$output->writeln("<error>"._("UCP Node Server is not running")."</error>");
			}
			return false;
		}

		// executes after the command finishes
		if(is_object($output)) {
			$output->writeln(_("Stopping UCP Node Server"));
		}

		$this->FreePBX->Pm2->stop("ucp");
		if(is_object($output)) {
			$progress = new ProgressBar($output, 0);
			$progress->setFormat('[%bar%] %elapsed%');
			$progress->start();
		}
		$i = 0;
		while($i < 100) {
			$data = $this->FreePBX->Pm2->getStatus("ucp");
			if(!empty($data) && $data['pm2_env']['status'] != 'online') {
				if(is_object($output)) {
					$progress->finish();
				}
				break;
			}
			if(is_object($output)) {
				$progress->setProgress($i);
			}
			$i++;
			usleep(100000);
		}
		if(is_object($output)) {
			$output->writeln("");
		}

		$data = $this->FreePBX->Pm2->getStatus("ucp");
		if (empty($data) || $data['pm2_env']['status'] != 'online') {
			if(is_object($output)) {
				$output->writeln(_("Stopped UCP Node Server"));
			}
		} else {
			if(is_object($output)) {
				$output->writeln("<error>".sprintf(_("UCP Node Server Failed: %s")."</error>",$process->getErrorOutput()));
			}
			return false;
		}

		return true;
	}

	private function genAlertGlyphicon($res, $tt = null) {
		$glyphs = array(
			"ok" => "glyphicon-ok text-success",
			"warning" => "glyphicon-warning-sign text-warning",
			"error" => "glyphicon-remove text-danger",
			"unknown" => "glyphicon-question-sign text-info",
			"info" => "glyphicon-info-sign text-info",
			"critical" => "glyphicon-fire text-danger"
		);
		// Are we being asked for an alert we actually know about?
		if (!isset($glyphs[$res])) {
			return array('type' => 'unknown', "tooltip" => sprintf(_("Don't know what %s is").$res), "glyph-class" => $glyphs['unknown']);
		}

		if ($tt === null) {
			// No Tooltip
			return array('type' => $res, "tooltip" => null, "glyph-class" => $glyphs[$res]);
		} else {
			// Generate a tooltip
			$html = '';
			if (is_array($tt)) {
				foreach ($tt as $line) {
					$html .= htmlentities($line, ENT_QUOTES, "UTF-8")."\n";
				}
			} else {
				$html .= htmlentities($tt, ENT_QUOTES, "UTF-8");
			}

			return array('type' => $res, "tooltip" => $html, "glyph-class" => $glyphs[$res]);
		}
		return '';
	}

	private function generateRunAsAsteriskCommand($command) {
		return $this->FreePBX->Pm2->generateRunAsAsteriskCommand($command,$this->nodeloc);
	}

	public function getUserIdByKey($key) {
		$uid = $this->FreePBX->Userman->getUidFromUnlockkey($key);
		return $uid;
	}
}
