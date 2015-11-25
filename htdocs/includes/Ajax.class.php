<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is Part of the User Control Panel Object
 * A replacement for the Asterisk Recording Interface
 * for FreePBX
 *
 * Manages all security of ajax requests.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace UCP;
class Ajax extends UCP {

	public $storage = 'null';
	private $headers = array();
	//unused right now
	public $settings = array( "authenticate" => true, "allowremote" => false );

	public function __construct($UCP) {
		$this->UCP = $UCP;
		$this->init();
	}

	public function init() {
		$this->getHeaders();
	}

	public function doRequest($module = null, $command = null) {
		$this->UCP->Modgettext->textdomain("ucp");
		switch($command) {
			case 'ucpsettings':
				$this->addHeader('HTTP/1.0','200');
				$user = $this->UCP->User->getUser();
				if(($_POST['key'] == 'username' || $_POST['key'] == 'password') && !$this->UCP->User->canChange($_POST['key'])) {
					return array(
						"status" => false
					);
				}
				$this->UCP->Modgettext->push_textdomain("ucp");
				switch($_POST['key']) {
					case 'fname':
					case 'lname':
					case 'email':
					case 'title':
					case 'company':
					case 'fax':
					case 'cell':
					case 'displayname':
					case 'work':
					case 'home':
						$this->UCP->FreePBX->Userman->updateUserExtraData($user['id'],array($_POST['key'] => $_POST['value']));
						$ret = array(
							"status" => true
						);
					break;
					case 'notifications':
					break;
					case 'usernamecheck':
						$user = $this->UCP->FreePBX->Userman->getUserByUsername($_POST['value']);
						$ret = array(
							"status" => empty($user)
						);
					break;
					case 'username':
						$status = $this->UCP->FreePBX->Userman->updateUser($user['id'],$user['username'], $_POST['value'], $user['default_extension'], $user['description']);
						$ret = array(
							"status" => $status['status']
						);
					break;
					case 'password':
						$status = $this->UCP->FreePBX->Userman->updateUser($user['id'],$user['username'], $user['username'], $user['default_extension'], $user['description'], array(), $_POST['value']);
						$ret = array(
							"status" => $status['status']
						);
					break;
					default:
						$ret = array(
							"status" => false,
							"message" => 'Invalid Parameter'
						);
				}
				$this->UCP->Modgettext->pop_textdomain();
			break;
			case 'template':
				$this->UCP->Modgettext->push_textdomain("ucp");
				$file = dirname(__DIR__).'/views/templates/'.basename($_REQUEST['type']).'.php';
				if(ctype_alpha($_REQUEST['type']) && file_exists($file)) {
					$this->addHeader('HTTP/1.0','200');
					$template = $_POST['template'];
					if($_REQUEST['type'] == 'chat') {
						$mods = $this->UCP->Modules->getModulesByMethod('getChatHistory');
						$template['history'] = array();
						foreach($mods as $m) {
							$this->UCP->Modgettext->push_textdomain(strtolower($m));
							$template['history'] = $this->UCP->Modules->$m->getChatHistory($_POST['template']['from'],$_POST['template']['to'],$_POST['newWindow']);
							$this->UCP->Modgettext->pop_textdomain();
						}
					}
					$this->UCP->Modgettext->push_textdomain("ucp");
					$ret = array(
						"status" => true,
						"contents" => $this->UCP->View->load_view($file, $template)
					);
					$this->UCP->Modgettext->pop_textdomain();
				} else {
					$this->triggerFatal();
				}
				$this->UCP->Modgettext->pop_textdomain();
			break;
			case 'staticsettings':
				$this->UCP->Modgettext->push_textdomain("ucp");
				$mods = $this->UCP->Modules->getModulesByMethod('getStaticSettings');
				$settings = array();
				foreach($mods as $m) {
					$this->UCP->Modgettext->push_textdomain(strtolower($m));
					$settings[$m] = $this->UCP->Modules->$m->getStaticSettings();
					$this->UCP->Modgettext->pop_textdomain();
				}
				$ret = array(
					"status" => true,
					"settings" => $settings
				);
				$this->UCP->Modgettext->pop_textdomain();
			break;
			case 'poll':
				$ret = $this->poll();
				if($ret === false) {
					$this->triggerFatal();
				}
				$this->addHeader('HTTP/1.0','200');
			break;
			default:
				if (!$module || !$command) {
					$this->triggerFatal(_("Module or Command were null. Check your code."));
				}

				$ucMod = ucfirst(strtolower($module));
				if ($module != 'UCP' && $module != 'User' && class_exists(__NAMESPACE__."\\".$ucMod)) {
					$this->triggerFatal(_("The class $module already existed. Ajax MUST load it, for security reasons"));
				}

				//Part of the login functionality, thats the only place its used!
				if($module == 'User' || $module == 'UCP') {
					// Is someone trying to be tricky with filenames?
					$file = dirname(__FILE__).'/'.$ucMod.'.class.php';
					if((strpos($module, ".") !== false) || !file_exists($file)) {
						$this->triggerFatal("Module requested invalid");
					}

					// Note, that Self_Helper will throw an exception if the file doesn't exist, or if it does
					// exist but doesn't define the class.
					$this->injectClass($ucMod, $file);

					$thisModule = $this->$ucMod;
				} else {
					$this->UCP->Modules->injectClass($ucMod);

					$thisModule = $this->UCP->Modules->$ucMod;
				}

				if (!method_exists($thisModule, "ajaxRequest")) {
					$this->ajaxError(501, 'ajaxRequest not found');
				}

				$this->UCP->Modgettext->push_textdomain(strtolower($module));
				if (!$thisModule->ajaxRequest($command, $this->settings)) {
					$this->ajaxError(403, 'ajaxRequest declined');
				}

				if (method_exists($thisModule, "ajaxCustomHandler")) {
					$ret = $thisModule->ajaxCustomHandler();
					if($ret === true) {
						exit;
					}
				}

				if (!method_exists($thisModule, "ajaxHandler")) {
					$this->ajaxError(501, 'ajaxHandler not found');
				}

				// Right. Now we can actually do it!
				//TODO: Use Request Handler from BMO here
				$ret = $thisModule->ajaxHandler();
				if($ret === false) {
					$this->triggerFatal();
				}
				$this->UCP->Modgettext->pop_textdomain();
				$this->addHeader('HTTP/1.0','200');
			break;
		}
		//some helpers
		if(!is_array($ret) && is_bool($ret)) {
			$ret = array(
				"status" => $ret,
				"message" => "unknown"
			);
		} elseif(!is_array($ret) && is_string($ret)) {
			$ret = array(
				"status" => true,
				"message" => $ret
			);
		}
		$output = $this->generateResponse($ret);
		$this->sendHeaders();
		echo $output;
		exit;
	}

	public function poll() {
		$modules = $this->UCP->Modules->getModulesByMethod('poll');
		$modData = array();
		//TODO: Use Request Handler object from BMO
		$data = !empty($_POST['data']) ? $_POST['data'] : array();
		foreach($modules as $module) {
			$mdata = !empty($_POST['mdata'][$module]) ? $_POST['mdata'][$module] : array();
			$this->UCP->Modgettext->push_textdomain(strtolower($module));
			if(!empty($mdata)) {
				$modData[$module] = $this->UCP->Modules->$module->poll($data,$mdata);
			} else {
				$modData[$module] = $this->UCP->Modules->$module->poll($data);
			}
			$this->UCP->Modgettext->pop_textdomain();
		}
		return array(
			"status" => true,
			"modData" => $modData
		);
	}

	public function ajaxError($errnum, $message = 'Unknown Error') {
		$this->addHeader('HTTP/1.0',$errnum);
		$output = $this->generateResponse(array("error" => $message));
		$this->sendHeaders();
		echo $output;
		exit;
	}

	private function triggerFatal($message = 'Unknown Error') {
		$this->ajaxError(500, $message);
	}

	private function getUrl() {
		return isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])
			? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			: '';
	}

	private function getBody() {
		return empty($this->body) ? file_get_contents('php://input') : $this->body;
	}

	/**
	 * Get Known Headers from the Remote
	 *
	 * Get headers and then store them in an object hash
	 *
	 * @access private
	 */
	private function getHeaders() {
		$h = array(
			'accept'        => '',
			'address'		=> '',
			'content_type'	=> '',
			'host' 			=> '',
			'ip'			=> '',
			'nonce'			=> '',
			'port'			=> '',
			'signature'		=> '',
			'timestamp'		=> '',
			'token'			=> '',
			'uri'			=> '',
			'request'		=> '',
			'user_agent'	=> '',
			'verb'			=> '',
		);

		foreach ($_SERVER as $k => $v) {
			switch ($k) {
				case 'HTTP_ACCEPT':
					$h['accept'] = $v;
				break;
				case 'HTTP_HOST':
					$h['host'] = $v;
				break;
				case 'CONTENT_TYPE':
					$h['content_type'] = $v;
				break;
				case 'SERVER_NAME':
					$h['address'] = $v;
				break;
				case 'SERVER_PORT':
					$h['port'] = $v;
				break;
				case 'REMOTE_ADDR':
					$h['ip'] = $v;
				break;
				case 'REQUEST_URI':
					$h['request'] = $v;
				break;
				case 'HTTP_TOKEN':
					$h['token'] = $v;
				break;
				case 'HTTP_NONCE':
					$h['nonce'] = $v;
				break;
				case 'HTTP_SIGNATURE':
					$h['signature'] = $v;
				break;
				case 'HTTP_USER_AGENT':
					$h['user_agent'] = $v;
				break;
				case 'REQUEST_METHOD':
					$h['verb'] = strtolower($v);
				break;
				case 'PATH_INFO':
					$h['uri'] = $v;
				break;
				default:
				break;
			}
		}

		if(empty($h['uri'])) {
			$h['uri'] = $h['request'];
		}

		$this->req = new \StdClass();
		$this->req->headers = $this->arrayToObject($h);
	}

	/**
	 * Get Server Protocol
	 *
	 * Not used yet
	 *
	 * @return string http
	 * @access private
	 */
	private function getProtocol() {
		return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on"
			? 'https'
			: 'http';
	}

	/**
	 * Prepare headers to be returned
	 *
	 * Note: if just type is set, it will be assumed to be a value
	 *
	 * @param mixed $type type of header to be returned
	 * @param mixed $value value header should be set to
	 * @return $object New Object
	 * @access private
	 */
	public function addHeader($type, $value = '') {
		$responses = array(
			200	=> 'OK',
			201	=> 'Created',
			202	=> 'Accepted',
			204	=> 'No Content',
			301	=> 'Moved Permanently',
			303	=> 'See Other',
			304	=> 'Not Modified',
			307	=> 'Temporary Redirect',
			400	=> 'Bad Request',
			401	=> 'Unauthorized',
			402	=> 'Forbidden',
			404	=> 'Not Found',
			405	=> 'Method Not Allowed',
			406	=> 'Not Acceptable',
			409	=> 'Conflict',
			412	=> 'Precondition Failed',
			415	=> 'Unsupported Media Type',
			500	=> 'Internal Server Error',
			503 => 'Service Unavailable'
		);

		if ($type && !$value) {
			$value = $type;
			$type = 'HTTP/1.1';
		}

		//clean up type
		$type = str_replace(array('_', ' '), '-', trim($type));
		//HTTP responses headers
		if ($type == 'HTTP/1.1') {
			$value = ucfirst($value);
			//ok is always fully capitalized, not just its first letter
			if ($value == 'Ok') {
				$value = 'OK';
			}

			if (array_key_exists($value, $responses) || $value = array_search($value, $responses)) {
				$this->headers['HTTP/1.1'] = $value . ' ' . $responses[$value];
				return true;
			} else {
				return false;
			}
		} //end HTTP responses

		//all other headers. Not sure if/how we can validate them more...
		$this->headers[$type] = $value;

		return true;
	}

	/**
	 * Send Headers to PHP
	 *
	 * Gets headers from this Object (if set) and sends them to the PHP compiler
	 *
	 * @access private
	 */
	private function sendHeaders() {
		//send http header
		if (isset($this->headers['HTTP/1.1'])) {
			header('HTTP/1.1 ' . $this->headers['HTTP/1.1']);
			unset($this->headers['HTTP/1.1']);
		} else {
			header('HTTP/1.1 200 OK'); //defualt to 200
		}

		//send all headers, if any
		if ($this->headers) {
			foreach ($this->headers as $k => $v) {
				header($k . ': ' . $v);
				//unlist sent headers, as this mehtod can be called more than once
				unset($this->headers[$k]);
			}
		}

		//CORS: http://en.wikipedia.org/wiki/Cross-origin_resource_sharing
		$origin = !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "";
		header('Access-Control-Allow-Headers:Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control, X-Auth-Token');
		header('Access-Control-Allow-Methods: '.strtoupper($this->req->headers->verb));
		header('Access-Control-Allow-Origin:'.$origin);
		header('Access-Control-Max-Age:86400');
		header('Allow: '.strtoupper($this->req->headers->verb));
	}

	/**
	 * Generate Response
	 *
	 * Generates a response after determining the accepted response from the client
	 *
	 * @param mixed $body Array of what should be in the body
	 * @return string XML or JSON or WHATever
	 * @access private
	 */
    private function generateResponse($body) {
        $ret = false;

		if(!is_array($body)) {
			$body = array("message" => $body);
		}

		$accepts = explode(",",$this->req->headers->accept);
		foreach($accepts as $accept) {
			//strip off content accept priority
			$accept = preg_replace('/;(.*)/i','',$accept);
	        switch($accept) {
				case "text/json":
				case "application/json":
					$this->addHeader('Content-Type', 'text/json');
					return json_encode($body);
					break;
				case "text/xml":
				case "application/xml":
					$this->addHeader('Content-Type', 'text/xml');
					//DOMDocument provides us with pretty print XML. Which is...pretty.
					require_once(dirname(__FILE__).'/Array2XML2.class.php');
					$xml = \Array2XML2::createXML('response', $body);
					return $xml->saveXML();
	        }
		}

		//If nothing is defined then just default to showing json
		//TODO: move this up into the switch statement?
		$this->addHeader('Content-Type', 'text/json');
		return json_encode($body);
    }

	/**
	 * Turn Array into an Object
	 *
	 * This turns any PHP array hash into a PHP Object. It's a cheat, but it works
	 *
	 * @param $arr The array
	 * @return object The PHP Object
	 * @access private
	 */
	private function arrayToObject($arr) {
		return json_decode(json_encode($arr), false);
	}
}
