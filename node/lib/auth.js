//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
var freepbx = null;

module.exports.init = function(fpbx) {
	freepbx = fpbx;
};

module.exports.checkAuth = function(socket, next) {
	var auth = false,
			address = null,
			suppliedToken = (typeof socket.handshake.query.token != "undefined") ? socket.handshake.query.token : "empty";

	suppliedToken = freepbx.db.escape(suppliedToken);
	address = freepbx.db.escape(socket.handshake.address);
	address = address.replace(/^::ffff:([\d]+\.)/, "$1"); //ipv4 mapped into ipv6
	var prep = freepbx.db.prepare('SELECT * FROM ucp_sessions WHERE session = :session AND address = :address');
	freepbx.db.queryStream(prep({ session: suppliedToken, address: address }))
		.on('data', function (row) {
			socket.ucpUid = row.uid;
			var prep = freepbx.db.prepare('UPDATE ucp_sessions SET socketid = :socketid WHERE session = :session AND address = :address');
			freepbx.db.queryStream(prep({ session: suppliedToken, address: address, socketid: socket.id }));
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
