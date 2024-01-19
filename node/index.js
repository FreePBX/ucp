//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
var server = {},
		freepbx = {};

freepbx = new require("./lib/freepbx.js")();
freepbx.on("ready", function() {
	console.log( freepbx.config.get("DASHBOARD_FREEPBX_BRAND")+" is Ready!" );
	if(!freepbx.config.get("NODEJSENABLED")) {
		console.log( "UCP Node Server is not enabled!" );
		process.exit(0);
	}
	//At this point {freepbx} should have:
	//freepbx.db = Database Object
	//freepbx.confg = The FreePBX Configuration Object
	//freepbx.astman = The Asterisk Manager Connection
	console.log("Asterisk version is: " + freepbx.config.get("ASTVERSION"));
	freepbx.server = new require("./lib/server.js")(freepbx);

	freepbx.loadModules();
});
