//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
var subscribes = {},
		conferenceSocket = {},
		conferenceData = {},
		utils = require("../lib/utils.js"),
		conferenceAuth = require("../lib/conferenceAuth.js"),
		conf = {};

Conferencespro = function(freepbx) {
	var ami = freepbx.astman,
			io = freepbx.server.io,
			db = freepbx.db,
			config = freepbx.config.getAll();
			conf = require("../lib/conferences.js")(freepbx);

	conferenceAuth.init(freepbx);

	conferenceSocket = io.of("/conferences");
	conferenceSocket.on("connection", function(socket) {
		var id = socket.conn.id;
		subscribes[id] = {};

		function requireConference(conference, callback) {
			if (!socket.ucpUid) {
				return callback(false);
			}
			conferenceAuth.isConferenceAllowed(socket.ucpUid, conference, function(err, allowed) {
				if (err || !allowed) {
					console.log("Client [" + socket.handshake.address + "] denied conference " + conference);
					return callback(false);
				}
				callback(true);
			});
		}

		socket.on("subscribe", function(data) {
			const room = data.toString();
			requireConference(room, function(allowed) {
				if (!allowed) {
					return;
				}
				console.log("Client [" + socket.handshake.address + "] subscribed to Conference " + data);
				socket.join(room);
				var conferenceData = conf.list(room);
				if (conferenceData !== false) {
					conferenceSocket.to(room).emit('list',conferenceData);
				} else {
					conferenceSocket.to(room).emit('list',{});
				}
			});
		});

		socket.on("list", function(data) {
			requireConference(data, function(allowed) {
				if (!allowed) {
					return;
				}
				console.log("Client [" + socket.handshake.address + "] asked for the list of Conference " + data);
				var conferenceData = conf.list(data);
				if (conferenceData !== false) {
					conferenceData.status = true;
					conferenceData.conference = data;
					const room = data.toString();
					conferenceSocket.to(room).emit('list',conferenceData);
				} else {
					conferenceSocket.to(room).emit('list',{status: false});
				}
			});
		});

		socket.on("mute", function(data) {
			requireConference(data.conference, function(allowed) {
				if (!allowed) {
					return;
				}
				if (data.enable) {
					conf.mute(data.conference, data.channel);
				} else {
					conf.unmute(data.conference, data.channel);
				}
			});
		});

		socket.on("kick", function(data) {
			requireConference(data.conference, function(allowed) {
				if (!allowed) {
					return;
				}
				conf.kick(data.conference, data.channel);
			});
		});

		socket.on("lock", function(data) {
			requireConference(data.conference, function(allowed) {
				if (!allowed) {
					return;
				}
				if (data.enable) {
					conf.lock(data.conference);
				} else {
					conf.unlock(data.conference);
				}
			});
		});

		socket.on("unsubscribe", function(data) {
			console.log("Client [" + socket.handshake.address + "] unsubscribed from Conference " + data);
			socket.leave(data);
		});
	});

	conf.on("talking", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("talking", data);
	});

	conf.on("mute", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("mute", data);
	});

	conf.on("join", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("join", data);
	});

	conf.on("leave", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("leave", data);
	});

	conf.on("start", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("starting", data);
	});

	conf.on("end", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("ending", data);
	});

	conf.on("lock", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("lock", data);
	});
};

module.exports = Conferencespro;
