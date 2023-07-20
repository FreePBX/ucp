//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
/**
 * This module is a small fork of the freepbx_conf class
 * It will read the database for all FreePBX Configuration Values
 * and place them inside of a private object called (configs)
 */
var configs = {},
		db = null,
		EventEmitter = require( "events" ).EventEmitter,
		obj = {};

Config = function(database) {
	db = database;

	var context = {},
			properties = [ "on", "once", "addListener", "removeListener", "removeAllListeners",
											"listeners", "setMaxListeners", "emit" ];

	context.emitter = new EventEmitter();
	context.held = [];

	properties.map(function(property) {
		Object.defineProperty(obj, property, {
			value: context.emitter[property].bind(context.emitter)
		});
	});

	refreshCache(function() {
		obj.emit("loaded", configs);
	});

	obj.get = get;
	obj.update = update;
	obj.getAll = getAll;
	return obj;
};

/**
 * Refresh the local setting cache
 * @param {Function} callback Callback when the refresh has finished
 */
refreshCache = function(callback) {
	db.queryStream("SELECT * FROM freepbx_settings")
		.on('data', function (row) {
			var val = row.value;
			if (row.type == "bool") {
				val = (row.value !== 0) ? true : false;
			}
			configs[row.keyword] = val;
		})
		.on('end', function () {
		callback();
	});

};

/**
 * Get All Settings
 * @param {bool}   cached   If true then get all cached settings else use callback to get all new settings
 * @param {Function} callback The callback used if cached is false
 */
getAll = function(cached, callback) {
	cached = (typeof cached !== "undefined" && !cached) ? false : true;
	if (!cached) {
		refreshCache(function() {
			callback(configs);
		});
	} else {
		return configs;
	}
};

/**
 * Get Individual Setting
 * @param  {string}   keyword  The setting keyword
 * @param  {bool}   cached   Whether to get the cached value or retrieve it
 * @param  {Function} callback Callback used if cached is false
 * @return {mixed}     The returned setting
 */
get = function(keyword, cached, callback) {
	cached = (typeof cached !== "undefined" && !cached) ? false : true;
	if (!cached) {
		refreshCache(function() {
			callback({ keyword: keyword, value: configs[keyword] });
		});
	} else {
		return configs[keyword];
	}
};

/**
 * Used to update a setting
 * @param  {string}   keyword  The setting keyword
 * @param  {mixed}   value    The setting's value
 * @param  {bool}   commit   Whether to commit the setting locally (false) or remotely (true)
 * @param  {Function} callback The callback function (used after commit)
 */
update = function(keyword, value, commit, callback) {

};

module.exports = Config;
