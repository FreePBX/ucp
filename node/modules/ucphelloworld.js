Ucphelloworld = function(freepbx) {
	var ami = freepbx.astman, //https://github.com/pipobscure/NodeJS-AsteriskManager
			io = freepbx.server.io, //https://github.com/socketio/socket.io
			db = freepbx.db, //https://github.com/mscdex/node-mariasql
			config = freepbx.config.getAll(); //AMPCONF freepbx advanced settings array

	//The namespace to bind to
	conferenceSocket = io.of("/ucphelloworld");
	//on connection event
	conferenceSocket.on("connection", function(socket) {
		var id = socket.conn.id;
		socket.join(id); //join 'room' based on my id
		socket.on("hello", function(data) {
			console.log(id + " said hello! Lets say hello back!");
			//emit to self!
			conferenceSocket.to(id).emit('hello','world');
		});
	});
};

module.exports = Ucphelloworld;
