//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
var freepbx = null;

module.exports.init = function(fpbx) {
	freepbx = fpbx;
};

module.exports.checkAuth = function(socket, next) {
	var suppliedToken = (typeof socket.handshake.query.token != "undefined") ? socket.handshake.query.token : "empty",
			address = socket.handshake.address;

	address = address.replace(/^::ffff:([\d]+\.)/, "$1"); //ipv4 mapped into ipv6

	freepbx.db.query(
		'SELECT * FROM ucp_sessions WHERE session = ? AND address = ?',
		[suppliedToken, address]
	).then(function(rows) {
		if (rows && rows.length) {
			socket.ucpUid = rows[0].uid;
			return freepbx.db.query(
				'UPDATE ucp_sessions SET socketid = ? WHERE session = ? AND address = ?',
				[socket.id, suppliedToken, address]
			).then(function() {
				console.log("Token [" + suppliedToken + "] from: " + address + " was accepted");
				next();
			});
		}
		console.log("Token [" + suppliedToken + "] from: " + address + " was rejected");
		next(new Error("not authorized"));
	}).catch(function(e) {
		console.log("Error while checking authorization?", e);
		next(new Error("not authorized"));
	});
};

