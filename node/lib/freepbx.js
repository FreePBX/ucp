//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
/**
 * This is the master FreePBX Object used to control and operate FreePBX
 * @type {[type]}
 */
var EventEmitter = require( "events" ).EventEmitter,
		ini = require("ini"),
		fs = require("fs"),
		nodeMaria = require("mariasql"),
		obj = {};

FreePBX = function() {

	var config = {},
			context = {},
			properties = [ "on", "once", "addListener", "removeListener", "removeAllListeners",
											"listeners", "setMaxListeners", "emit" ];

	context.emitter = new EventEmitter();
	context.held = [];

	properties.map(function(property) {
		Object.defineProperty(obj, property, {
			value: context.emitter[property].bind(context.emitter)
		});
	});

	process.stdout.write("Starting FreePBX...");

	var runner = require('child_process');
	runner.exec(
		'php -r \'$bootstrap_settings["skip_astman"] = true; $restrict_mods = true; $bootstrap_settings["returnimmediately"] = true; include("/etc/freepbx.conf"); print json_encode($amp_conf);\'',
		function (err, stdout, stderr) {
			var config = JSON.parse(stdout);
			connect2database(config, function(db) {
				obj.db = db;
				ampconf = new require("./config.js")(db);
				ampconf.on("loaded", function(configs) {
					obj.config = ampconf;
					connect2AstMan(ampconf.getAll(), function(astman) {
						obj.astman = astman;
						obj.emit("ready");
					});
				});
			});
	  }
	);

	obj.loadModules = loadModules;
	return obj;
};

/**
 * Connect to MySQL
 * Uses non-blocking events
 * @param  {object}   config   Configuration Parameters
 * @param  {Function} callback Callback function when connected to DB
 */
connect2database = function(config, callback) {
	var db = {},
			init = false;
	if(typeof config.AMPDBSOCK !== "undefined" && config.AMPDBSOCK.length) {
		db = new nodeMaria({
			user: config.AMPDBUSER,
			password: config.AMPDBPASS,
			db: config.AMPDBNAME,
			unixSocket: config.AMPDBSOCK,
			charset: 'UTF8'
		});
	} else {
		db = new nodeMaria({
			host: config.AMPDBHOST,
			user: config.AMPDBUSER,
			password: config.AMPDBPASS,
			db: config.AMPDBNAME,
			charset: 'UTF8'
		});
	}

	db.on("ready", function() {
		if (!init) {
			init = true;
			callback(db);
		}
	});

	db.query("SELECT CONNECTION_ID()");

	db.on("error", function(evt) {
		console.warn(evt);
		console.log(config);
		obj.emit("disconnect");
		throw "There was an error with MySQL Connection";
	});

	db.on("close", function() {
		obj.emit("disconnect");
		throw "The MySQL connection was closed!";
	});

	db.on("end", function() {
		obj.emit("disconnect");
		throw "The MySQL connection was closed!";
	});
};

/**
 * Connect to Asterisk Manager
 * @param {object}   config   An object of configuration values
 * @param {Function} callback Function to callback when connected
 */
connect2AstMan = function(config, callback) {
	var astman = {},
			init = false,
			status = "disconnected";
	astman = new require("asterisk-manager")(
		config.ASTMANAGERPORT,
		config.ASTMANAGERHOST,
		config.AMPMGRUSER,
		config.AMPMGRPASS,
	true);

	astman.keepConnected();

	astman.on("connect", function(evt) {
		if (!init) {
			init = true;
			callback(astman);
		} else {
			console.log("Regained Connection to Asterisk");
		}
		if (status != "connected") {
			status = "connected";
		}
	});

	astman.on("error", function(evt) {
		switch (evt.code) {
			case "ECONNREFUSED":
				console.error("Unable to connect to asterisk!");
				throw "There was an error with Asterisk Manager Connection";
			break;
		}
	});

	astman.on("close", function(evt) {
		throw "There was an error with Asterisk Manager Connection";
	});
};

/**
 * Automatically load all modules from the modules folder into the namespace
 */
loadModules = function() {
	console.log("Loading all UCP Modules...");
	require("fs").readdirSync("./modules").forEach(function(file) {
		console.log("\tLoading..." + file);
		require("../modules/" + file)(obj);
	});
	console.log("Done!");
};

module.exports = FreePBX;
