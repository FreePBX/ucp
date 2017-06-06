//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
var EventEmitter = require( "events" ).EventEmitter,
		obj = {},
		config = {}.
		ami = {},
		conferenceData = {},
		utils = require("./utils.js");

Conferences = function(freepbx) {
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

	config = freepbx.config.getAll();
	ami = freepbx.astman;
	bind();
	startup();

	obj.list = list;
	obj.mute = mute;
	obj.unmute = unmute;
	obj.kick = kick;
	obj.lock = lock;
	obj.unlock = unlock;

	return obj;
};

startup = function() {
	ami.on("confbridgelistcomplete", function(evt) {});

	ami.on("confbridgelist", function(evt) {
		//talking is not defined in list so don't overwrite what we already have
		var talking = (typeof conferenceData[evt.conference].users[evt.channel] !== "undefined" && conferenceData[evt.conference].users[evt.channel] !== null) ? conferenceData[evt.conference].users[evt.channel].talking : false;
		//turn all word variables into t/f
		evt.admin = (typeof evt.admin !== "undefined" && evt.admin == "Yes");
		evt.endmarked = (typeof evt.endmarked !== "undefined" && evt.endmarked == "Yes");
		evt.waitmarked = (typeof evt.waitmarked !== "undefined" && evt.waitmarked == "Yes");
		evt.markeduser = (typeof evt.markeduser !== "undefined" && evt.markeduser == "Yes");
		evt.muted = (typeof evt.muted !== "undefined" && evt.muted == "Yes");
		evt.waiting = (typeof evt.waiting !== "undefined" && evt.waiting == "Yes");
		conferenceData[evt.conference].users[evt.channel] = evt;
		conferenceData[evt.conference].users[evt.channel].talking = talking;
	});

	ami.on("confbridgelistroomscomplete", function(evt) {});

	ami.on("confbridgelistrooms", function(evt) {
		conferenceData[evt.conference] = { users: {}, locked: (evt.locked == "Yes") };
		ami.action({
				"action": "confbridgelist",
				"actionid": "startup",
				"conference": evt.conference
		}, function(err, res) {});
	});

	ami.action({
			"action": "confbridgelistrooms",
			"actionid": "startup"
	}, function(err, res) {});
};

mute = function(conference, channel) {
	ami.action({
			"action": "confbridgemute",
			"actionid": "1",
			"conference": conference,
			"channel": channel
	}, function(err, res) {
		if (typeof res.response !== "undefined" && res.response == "Success") {
			conferenceData[conference].users[channel].muted = true;
			if (utils.versionCompare(config.ASTVERSION, "12.0.0", "<")) {
				obj.emit("mute", {
					conference: conference,
					channel: channel,
					enabled: true
				});
			}
		}
	});
};

unmute = function(conference, channel) {
	ami.action({
			"action": "confbridgeunmute",
			"actionid": "1",
			"conference": conference,
			"channel": channel
	}, function(err, res) {
		if (typeof res.response !== "undefined" && res.response == "Success") {
			conferenceData[conference].users[channel].muted = false;
			if (utils.versionCompare(config.ASTVERSION, "12.0.0", "<")) {
				obj.emit("mute", {
					conference: conference,
					channel: channel,
					enabled: false
				});
			}
		}
	});
};

kick = function(conference, channel) {
	ami.action({
			"action": "confbridgekick",
			"actionid": "1",
			"conference": conference,
			"channel": channel
	}, function(err, res) {
		obj.emit("kick", {
			conference: conference,
			channel: channel,
			enabled: false
		});
	});
};

lock = function(conference) {
	ami.action({
			"action": "confbridgelock",
			"actionid": "1",
			"conference": conference
	}, function(err, res) {
		obj.emit("lock", {
			conference: conference,
			enabled: true
		});
	});
};

unlock = function(conference) {
	ami.action({
			"action": "confbridgeunlock",
			"actionid": "1",
			"conference": conference
	}, function(err, res) {
		obj.emit("lock", {
			conference: conference,
			enabled: false
		});
	});
};

list = function(conference) {
	if (typeof conferenceData[conference] !== "undefined" && conferenceData[conference] !== null) {
		return conferenceData[conference];
	} else {
		return false;
	}
};

bind = function() {
	var cobj = this;
	ami.on("confbridgejoin", function(evt) {
		conferenceData[evt.conference].users[evt.channel] = evt;
		conferenceData[evt.conference].users[evt.channel].talking = false;
		obj.emit("join", evt);
		//this is so we can get user states
		ami.action({
				"action": "confbridgelist",
				"actionid": "startup",
				"conference": evt.conference
		}, function(err, res) {});
	});

	ami.on("confbridgeleave", function(evt) {
		conferenceData[evt.conference].users[evt.channel] = null;
		obj.emit("leave", evt);
	});

	ami.on("confbridgetalking", function(evt) {
		//got a talking event but there is no conference so re-ask for conferences
		if(typeof conferenceData[evt.conference] === "undefined" || typeof conferenceData[evt.conference].users[evt.channel] === "undefined") {
			ami.action({
					"action": "confbridgelistrooms",
					"actionid": "startup"
			}, function(err, res) {});
			return;
		}
		conferenceData[evt.conference].users[evt.channel].talking = (evt.talkingstatus == "on");
		obj.emit("talking", {
			conference: evt.conference,
			channel: evt.channel,
			enabled: (evt.talkingstatus == "on")
		});
	});

	ami.on("confbridgestart", function(evt) {
		conferenceData[evt.conference] = { users: {}, locked: false };
		obj.emit("start", evt);
	});

	ami.on("confbridgeend", function(evt) {
		conferenceData[evt.conference] = null;
		obj.emit("end", evt);
	});

	ami.on("confbridgemute", function(evt) {
		if (typeof conferenceData[evt.conference].users[evt.channel] !== "undefined" && conferenceData[evt.conference].users[evt.channel] !== null) {
			conferenceData[evt.conference].users[evt.channel].talking = false;
		}
		obj.emit("mute", {
			conference: evt.conference,
			channel: evt.channel,
			enabled: true
		});
	});

	ami.on("confbridgeunmute", function(evt) {
		obj.emit("mute", {
			conference: evt.conference,
			channel: evt.channel,
			enabled: false
		});
	});
};

generateActionId = function() {
	return require("crypto").randomBytes(64).toString("hex");
};

module.exports = Conferences;
