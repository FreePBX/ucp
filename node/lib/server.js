//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
/**
 * This is the master FreePBX Object used to control and operate FreePBX
 * @type {[type]}
 */
var EventEmitter = require( "events" ).EventEmitter,
		http = require("http"),
		https = require("https"),
		server = null,
		serverS = null,
		fs = require("fs"),
		express = require('express'),
		app = express(),
		freepbx = {},
		obj = {},
		run = 0,
		port = 8001,
		host = "0.0.0.0",
		enabled = true,
		enabledS = false,
		portS = 8003,
		hostS = "0.0.0.0",
		key = '',
		cert = '',
		cabundle = '';

const io = require("socket.io")({
	cors: {
		origin: true, 
		methods: ["GET", "POST"],
		credentials: true 
	},
	cookie: true
});

Server = function(fpbx) {
	fpbx.server = this;

	var config = {},
			amistatus = "disconnected",
			context = {},
			properties = [ "on", "once", "addListener", "removeListener", "removeAllListeners",
											"listeners", "setMaxListeners", "emit" ];

	freepbx = fpbx;
	context.emitter = new EventEmitter();
	context.held = [];

	properties.map(function(property) {
		Object.defineProperty(obj, property, {
			value: context.emitter[property].bind(context.emitter)
		});
	});

	enabled = fpbx.config.get("NODEJSENABLED");
	host = fpbx.config.get("NODEJSBINDADDRESS") ? fpbx.config.get("NODEJSBINDADDRESS") : host;
	port = fpbx.config.get("NODEJSBINDPORT") ? fpbx.config.get("NODEJSBINDPORT") : port;

	enabledS = fpbx.config.get("NODEJSTLSENABLED");
	hostS = fpbx.config.get("NODEJSHTTPSBINDADDRESS") ? fpbx.config.get("NODEJSHTTPSBINDADDRESS") : hostS;
	portS = fpbx.config.get("NODEJSHTTPSBINDPORT") ? fpbx.config.get("NODEJSHTTPSBINDPORT") : portS;
	key = fpbx.config.get("NODEJSTLSPRIVATEKEY") ? fpbx.config.get("NODEJSTLSPRIVATEKEY") : key;
	cert = fpbx.config.get("NODEJSTLSCERTFILE") ? fpbx.config.get("NODEJSTLSCERTFILE") : cert;
	cabundle = fpbx.config.get("NODEJSTLSCABUNDLEFILE") ? fpbx.config.get("NODEJSTLSCABUNDLEFILE") : cabundle;
	if(key === '' || cert === '') {
		enabledS = false;
	} else {
		try {
			fs.statSync(key);
			fs.statSync(cert);
			if(cabundle.length > 0) {
				fs.statSync(cabundle);
			}
		} catch (e) {
			console.log("The cert or key is not accessible. HTTPS Server disabled");
			enabledS = false;
		}
	}

	io.use(checkAuth);

	if(enabledS) {
		var options = {};
		if(cabundle.length > 0) {
			var c = [], ca = [], chainLines = fs.readFileSync(cabundle).toString().split("\n");
			chainLines.forEach(function(line) {
				c.push(line);
				if (line.match(/-END CERTIFICATE-/)) {
					ca.push(c.join("\n"));
					c = [];
				}
			});
			options = {
				ca: ca,
				key: fs.readFileSync(key),
				cert: fs.readFileSync(cert)
			};
		} else {
			options = {
				key: fs.readFileSync(key),
				cert: fs.readFileSync(cert)
			};
		}
		serverS = https.createServer(options, app);
		io.attach(serverS);
	}

	server = http.createServer(app);
	io.attach(server);

	app.get('/', function(req, res) {
		res.sendFile(__dirname + '/public/index.html');
	});

	start();
	amistatus = "connected";

	fpbx.astman.on("close", function() {
		if (amistatus != "disconnected") {
			fpbx.emit("disconnect");
			stop();
			amistatus = "disconnected";
		}
	});

	fpbx.astman.on("connect", function() {
		if (amistatus != "connected") {
			amistatus = "connected";
			start();
		}
	});

	obj.io = io;
	obj.stop = stop;
	obj.start = start;
	return obj;
};

start = function() {
	if (enabled) {
		if(!server.address()) {
			server.listen(port, host, function() {
				console.log('Server up and running at %s port', port);
			});
		}
	}
	if(enabledS && serverS !== null) {
		if(!serverS.address()) {
			serverS.listen(portS, hostS, function() {
				console.log('Secure Server up and running at %s port', portS);
			});
		}
	}
	if(!enabled && !enabledS) {
		console.log("Server is disabled. Exiting");
		process.exit(1);
	}
};

stop = function() {
	if (server.address()) {
		console.log("Shutting down server on port " + host + ":" + port);
		server.close();
	}
	if (serverS !== null && serverS.address()) {
		console.log("Shutting down server on port " + hostS + ":" + portS);
		serverS.close();
	}
};

checkAuth = function(socket, next) {
	var auth = false,
			address = null,
			suppliedToken = (typeof socket.handshake.query.token != "undefined") ? socket.handshake.query.token : "empty";

	run++; // 0 -> 1
	suppliedToken = freepbx.db.escape(suppliedToken);
	address = freepbx.db.escape(socket.handshake.address);
	address = address.replace(/^::ffff:([\d]+\.)/, "$1"); //ipv4 mapped into ipv6
	var prep = freepbx.db.prepare('SELECT * FROM ucp_sessions WHERE session = :session AND address = :address');
	freepbx.db.queryStream(prep({ session: suppliedToken, address: address }))
		.on('data', function (row) {
			var prep = freepbx.db.prepare('UPDATE ucp_sessions SET socketid = :socketid WHERE session = :session AND address = :address');
			var query = freepbx.db.queryStream(prep({ session: suppliedToken, address: address, socketid: socket.id }));
			auth = true;
		})
		.on('end', function () {
		if (auth) {
			console.log("Token [" + suppliedToken + "] from: " + address + " was accepted");
			next();
		} else {
			console.log("Token [" + suppliedToken + "] from: " + address + " was rejected");
			next(new Error("not authorized"));
		}
	}).on("error", function(e) {
		console.log("Error while checking authorization?");
		next(new Error("not authorized"));
	});
};

module.exports = Server;
