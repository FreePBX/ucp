<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is Part of the User Control Panel Object
 * A replacement for the Asterisk Recording Interface
 * for FreePBX
 *
 * Processes all module actions for the top level UCP Object
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace UCP;
#[\AllowDynamicProperties]
class Module_Helpers extends UCP {

	function __construct($UCP) {
		$this->UCP = $UCP;
	}

	/**
	 * PHP Magic __get - runs AutoLoader if BMO doesn't already have the object.
	 *
	 * @param $var Class Name
	 * @return $object New Object
	 * @access public
	 */
	public function __get($var) {
		if (isset(Modules::create()->$var)) {
			$this->$var = Modules::create()->$var;
			return $this->$var;
		}

		return $this->autoLoad($var);
	}

	/**
	 * PHP Magic __call - runs AutoLoader
	 *
	 * Note that this doesn't cache the object to BMO::$obj, just to
	 * $this->$obj
	 *
	 * @param $var Class Name
	 * @param $args Any params to be passed to the new object
	 * @return $object New Object
	 * @access public
	 */
	public function __call($var, $args) {
		return $this->autoLoad($var, $args);
	}

	/**
	 * PHP Magic __callStatic - runs AutoLoader
	 *
	 * Note that this doesn't cache the object to BMO::$obj, just to
	 * $this->$obj
	 *
	 * @param $var Class Name
	 * @param $args Any params to be passed to the new object
	 * @return $object New Object
	 * @access public
	 */
	public static function __callStatic($var, $args) {
		return $this->autoLoad($var, $args);
	}

	/**
	 * AutoLoader for BMO.
	 *
	 * This implements a half-arsed spl_autoload that ignore PSR1 and PSR4. I am
	 * admitting that at the start so no-one gets on my case about it.
	 *
	 * However, as we're having no end of issues with PHP Autoloading things properly
	 * (as of PHP 5.3.3, which is our minimum version at this point in time), this will
	 * do in the interim.
	 *
	 * This tries to load the BMO Object called. It looks first in the BMO Library
	 * dir, which is assumed to be the same directory as this file. It then grabs
	 * a list of all active modules, and looks through them for the class requested.
	 *
	 * If it doesn't find it, it'll throw an exception telling you why.
	 *
	 * @return object
	 * @access private
	 */
	function autoload() {
		// Figure out what is wanted, and return it.
		if (func_num_args() == 0) {
			throw new \Exception("Nothing given to the AutoLoader");
		}

		// If we have TWO arguments, we've been called by __call, if we only have
		// one we've been called by __get.

		$args = func_get_args();
		$var  = $args[0];

		if ($var == "UCP") {
			throw new \Exception("No. You ALREADY HAVE the UCP Object. You don't need another one.");
		}

		// Ensure no-one's trying to include something with a path in it.
		if (strpos((string) $var, "/") || strpos((string) $var, "..")) {
			throw new \Exception("Invalid include given to AutoLoader - $var");
		}

		// This will throw an Exception if it can't find the class.
		$this->loadObject($var);

		// Now, we may have paramters (__call), or we may not..
		if (isset($args[1])) {
			// We do. We were __call'ed. Sanity check
			if (isset($args[1][1])) {
				throw new \Exception("Multiple params to autoload (__call) not supported. Don't do that. Or re-write this.");
			}
			$class      = __NAMESPACE__ . "\\Modules\\$var";
			$this->$var = new $class($this, $args[1][0]);
		}
		else {
			$class                  = __NAMESPACE__ . "\\Modules\\$var";
			$this->$var             = new $class($this);
			Modules::create()->$var = $this->$var;

		}
		return $this->$var;
	}

	/**
	 * Find the file for the object $objname
	 */
	private function loadObject($objname, $hint = null) {
		$try2 = null;
		// If it already exists, we're fine.
		if (class_exists(__NAMESPACE__ . "\\Modules\\" . $objname)) {
			return true;
		}

		// This is the file we loaded the class from, for debugging later.
		$loaded = false;

		if ($hint) {
			if (!file_exists($hint)) {
				throw new \Exception("I was asked to load $objname, with a hint of $hint, and it didn't exist");
			}
			else {
				$try = $hint;
			}
		}
		else {
			// Does this exist as a default Library inside UCP?
			$try  = dirname(__DIR__) . "/modules/$objname.class.php";
			$try2 = dirname(__DIR__) . "/modules/$objname/$objname.class.php";
		}

		if (file_exists($try)) {
			include $try;
			$loaded = $try;
		}
		elseif (file_exists($try2)) {
			include $try2;
			$loaded = $try2;
		}
		else {
			//TODO: Something here??
		}

		// Right, after all of this we should now have our object ready to create.
		if (!class_exists(__NAMESPACE__ . "\\Modules\\" . $objname)) {
			// Bad things have happened.
			if (!$loaded) {
				throw new \Exception("I was unable to locate the UCP Class $objname. I looked everywhere for $objname.class.php");
			}

			// We loaded a file that claimed to represent that class, but didn't.
			throw new \Exception("I loaded the file $try, but it doesn't define the class $objname");
		}

		return true;
	}

	/** Implement hints for autoloading */
	public function injectClass($classname, $hint = null) {
		$this->loadObject($classname, $hint);
		$this->autoLoad($classname);
	}
}