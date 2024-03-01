//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
var subscribes = {},
		conferenceSocket = {},
		conferenceData = {},
		utils = require("../lib/utils.js"),
		conf = {};

Conferencespro = function(freepbx) {
	var ami = freepbx.astman,
			io = freepbx.server.io,
			db = freepbx.db,
			config = freepbx.config.getAll();
			conf = require("../lib/conferences.js")(freepbx);

	conferenceSocket = io.of("/conferences");
	conferenceSocket.on("connection", function(socket) {
		//Socket id is to to id so we can talk to it easier
		var id = socket.conn.id;
		subscribes[id] = {};

		/**
		 * Subscribe to a "room" (conference)
		 * @param  {string} data Conference #
		 */
		socket.on("subscribe", function(data) {
			//TODO: need to check if they are allowed to view this conference
			console.log("Client [" + socket.handshake.address + "] subscribed to Conference " + data);
			const room = data.toString();
			socket.join(room);
			var conferenceData = conf.list(room);
			if (conferenceData !== false) {
				conferenceSocket.to(room).emit('list',conferenceData);
			} else {
				conferenceSocket.to(room).emit('list',{});
			}
		});

		/**
		 * List all information about conference
		 * @param  {string} The conference #
		 */
		socket.on("list", function(data) {
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

		/**
		 * Mute channel on a conference
		 * @param  {object} {conference: 4000, channel: PJSIP/1000, enabled: true/false}
		 */
		socket.on("mute", function(data) {
			if (data.enable) {
				conf.mute(data.conference, data.channel);
			} else {
				conf.unmute(data.conference, data.channel);
			}
		});

		/**
		 * Kick user from conference
		 * @param  {object} {conference: 4000, channel: PJSIP/1000}
		 */
		socket.on("kick", function(data) {
			conf.kick(data.conference, data.channel);
		});

		/**
		 * Lock conference
		 * @param  {object} {conference: 4000, enabled: true/false}
		 */
		socket.on("lock", function(data) {
			if (data.enable) {
				conf.lock(data.conference);
			} else {
				conf.unlock(data.conference);
			}
		});

		/**
		 * Unsubsribe from a "room" (conference)
		 * @param  {string} The conference #
		 */
		socket.on("unsubscribe", function(data) {
			console.log("Client [" + socket.handshake.address + "] unsubscribed from Conference " + data);
			socket.leave(data);
		});
	});
	//Anything below here is outside the connection scope
	//because we broadcast to all who are in our "room" (room == conference #)

	/**
	 * Talking event
	 * Notifies when a user is talking
	 * @param  {object} Data returned from Asterisk AMI
	 */
	conf.on("talking", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("talking", data);
	});

	/**
	* Mute event
	* Notifies when a user is muted/unmuted
	* @param  {object} Data returned from Asterisk AMI
	*/
	conf.on("mute", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("mute", data);
	});

	/**
	* Join event
	* Notified when a user joins a conference
	* @param  {object} Data returned from Asterisk AMI
	*/
	conf.on("join", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("join", data);
	});

	/**
	* Leave event
	* Notified when a user leaves a conference
	* @param  {object} Data returned from Asterisk AMI
	*/
	conf.on("leave", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("leave", data);
	});

	/**
	* Start event
	* Notified when a conference is starting
	* @param  {object} Data returned from Asterisk AMI
	*/
	conf.on("start", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("starting", data);
	});

	/**
	* End event
	* Notified when a conference is ending
	* @param  {object} Data returned from Asterisk AMI
	*/
	conf.on("end", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("ending", data);
	});

	/**
	* Kick event
	* Notified when a user is kicked from a conference
	* Will also get a Leave event, so might not be useful to use this
	* @param  {object} Data returned from Asterisk AMI
	*/
	conf.on("lock", function(data) {
		conferenceSocket.to(data.conference.toString()).emit("lock", data);
	});
};

module.exports = Conferencespro;
