//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
//TODO: These extra packages are needed for this library. Need to check this...
//yum install icu
//yum install libicu-devel
var EventEmitter = require( "events" ).EventEmitter,
		xmpp = {},
		Client = require("node-xmpp-client"),
		ltx = require("ltx"),
		sessions = {},
		credentials = {},
		xmppSocket = {},
		util = require('util'),
		obj = {},
		a = require('async'),
		uuid = require('node-uuid');

Xmpp = function(freepbx) {
	var context = {},
			ami = freepbx.astman,
			io = freepbx.server.io,
			db = freepbx.db,
			config = freepbx.config.getAll(),
			properties = [ "on", "once", "addListener", "removeListener", "removeAllListeners",
											"listeners", "setMaxListeners", "emit" ];

	context.emitter = new EventEmitter();
	context.held = [];

	properties.map(function(property) {
		Object.defineProperty(obj, property, {
			value: context.emitter[property].bind(context.emitter)
		});
	});

	xmppSocket = io.of("/xmpp");
	xmppSocket.on("connection", function(socket) {
		var id = socket.conn.id,
				suppliedToken = (typeof socket.handshake.query.token != "undefined") ? socket.handshake.query.token : "empty",
				user = null;
				suppliedToken = freepbx.db.escape(suppliedToken);

		socket.join(id); //join my own room

		socket.on("login", function(data) {
			freepbx.db.queryStream("SELECT x.*, o.value as host FROM xmpp_options o, xmpp_users x, ucp_sessions s WHERE x.user = s.uid AND o.keyword = 'domain' AND s.session = '" + suppliedToken + "'")
				.on("data", function (row) {
					user = row;
				})
				.on("end", function () {
				var username,password;
				if (user !== null) {
					if(!data.username && !data.password && typeof credentials[suppliedToken] !== "undefined" && credentials[suppliedToken].username && credentials[suppliedToken].password) {
						username = credentials[suppliedToken].username;
						password = credentials[suppliedToken].password;
					} else if(data.username && data.password) {
						credentials[suppliedToken] = {
							"username":data.username,
							"password":data.password
						};
						username = data.username;
						password = data.password;
					} else {
						xmppSocket.to(id).emit("prompt");
						return;
					}
					sessions[id] = connect(username + "@" + user.host, password, id, io, socket);
				}
			});
		});

		socket.on("addUser", function(user) {
			if (typeof sessions[id] !== "undefined") {
				sessions[id].send("<iq type='set' id='set1'><query xmlns='jabber:iq:roster'><item jid='" + user + "' name=''></item></query></iq>");
			}
		});

		socket.on("removeUser", function(user) {
			if (typeof sessions[id] !== "undefined") {
				sessions[id].send("<iq type='set' id='remove1'><query xmlns='jabber:iq:roster'><item jid='" + user + "' subscription='remove'/></query></iq>");
			}
		});

		socket.on("subscribe", function(user) {
			if (typeof sessions[id] !== "undefined") {
				sessions[id].send("<presence to='" + user + "' type='subscribe'/>");
			}
		});

		socket.on("unsubscribe", function(user) {
			if (typeof sessions[id] !== "undefined") {
				sessions[id].send("<presence to='" + user + "' type='unsubscribe'/>");
			}
		});

		socket.on("subscribed", function(user) {
			if (typeof sessions[id] !== "undefined") {
				sessions[id].send("<presence to='" + user + "' type='subscribed'/>");
			}
		});

		socket.on("unsubscribed", function(user) {
			if (typeof sessions[id] !== "undefined") {
				sessions[id].send("<presence to='" + user + "' type='unsubscribed'/>");
			}
		});

		socket.on("setPresence", function(presence) {
			if (typeof sessions[id] !== "undefined") {
				if (presence !== null && typeof presence !== "undefined" && typeof presence.State !== "undefined") {
					switch (presence.State) {
						case "away":
							sessions[id].send("<presence xml:lang='en'><show>away</show><status>" + presence.Message + "</status><priority>1</priority></presence>");
						break;
						case "chat":
							sessions[id].send("<presence xml:lang='en'><show>chat</show><status>" + presence.Message + "</status><priority>1</priority></presence>");
						break;
						case "dnd":
							sessions[id].send("<presence xml:lang='en'><show>dnd</show><status>" + presence.Message + "</status><priority>1</priority></presence>");
						break;
						case "xa":
							sessions[id].send("<presence xml:lang='en'><show>xa</show><status>" + presence.Message + "</status><priority>1</priority></presence>");
						break;
						case "available":
							sessions[id].send("<presence><status>" + presence.Message + "</status><priority>1</priority></presence>");
						break;
						case "not_set":
						case "unavailable":
							sessions[id].send("<presence type='unavailable'><priority>1</priority></presence>");
						break;
						default:
						break;
					}
				} else {
					sessions[id].send("<presence type='unavailable'><priority>1</priority></presence>");
				}
			}
		});

		socket.on("logout", function() {
			if(typeof credentials[suppliedToken] !== "undefined") {
				credentials[suppliedToken] = {};
				delete(credentials[suppliedToken]);
			}
		});

		socket.on("disconnect", function() {
			if (typeof sessions[id] !== "undefined") {
				sessions[id].end();
				delete sessions[id];
			}
		});
	});

	return obj;
};

connect = function(username, password, id, io, socket) {
	console.log("XMPP: Attemping to connect " + username);
	var client = new Client({
		jid: username,
		password: password,
		host: "127.0.0.1",
		autostart: false,
		reconnect: true
	}),
	jid = {};

	client.on("online", function(data) {
		jid = data.jid;
		xmppSocket.to(id).emit("online", data);
		client.send("<iq from='" + jid.user + "@" + jid.localhost + "/" + jid.resource + "' type='get' id='roster'><query xmlns='jabber:iq:roster'/></iq>");
		console.log("XMPP: connected " + jid.user);
	});

	client.on("offline", function(data) {
		console.log("XMPP: disconnected " + jid.user);
	});

	client.on("error", function(err) {
		console.log("XMPP: there was an error connecting: " + err);
	});

	socket.on("probe", function(user) {
		client.send("<presence type='probe' from='" + jid.user + "@" + jid.domain + "/" + jid.resource + "' to='" + user + "'/>");
	});

	socket.on("raw", function(raw) {
		if (typeof sessions[id] !== "undefined") {
			console.log("sending");
			sessions[id].send(raw);
		}
	});

	socket.on("listrooms", function() {
		if (typeof sessions[id] !== "undefined") {
			a.waterfall([
				function(callback) {
					var sid = uuid.v4();
					obj.once("stanza-"+sid,function(stanza) {
						callback(null, stanza);
					});
					sessions[id].send(new ltx.Element("iq", {
						from: jid.user + "@" + jid.domain + "/" + jid.resource,
						to: jid.domain,
						type: "get",
						id: sid
					}).c("query", {
						xmlns: 'http://jabber.org/protocol/disco#items'
					}));
				},
				function(stanza, callback) {
					var sid = uuid.v4();
					var server = stanza.getChild('query').getChild('item').attrs.jid;
					obj.once("stanza-"+sid,function(stanza) {
						callback(null, stanza);
					});
					sessions[id].send(new ltx.Element("iq", {
						from: jid.user + "@" + jid.domain + "/" + jid.resource,
						to: server,
						type: "get",
						id: sid
					}).c("query", {
						xmlns: 'http://jabber.org/protocol/disco#items'
					}));
				},
				function(stanza, callback) {
					var rooms = [];
					a.each(stanza.children[0].children, function(room, callback2) {
						var sid = uuid.v4();
						obj.once("stanza-"+sid,function(s) {
							//room.attrs.features = s.getChild('query').getChildren('feature');
							rooms.push(room.attrs);
							callback2();
						});
						sessions[id].send(new ltx.Element("iq", {
							from: jid.user + "@" + jid.domain + "/" + jid.resource,
							to: room.attrs.jid,
							type: "get",
							id: sid
						}).c("query", {
							xmlns: 'http://jabber.org/protocol/disco#info'
						}));
						//zulu@conference.pbx.ca.sangoma.com
						//<iq type='get' from='" + jid.user + "@" + jid.domain + "/" + jid.resource + "' to='zulu@conference.pbx.ca.sangoma.com' id='list1'><query xmlns='http://jabber.org/protocol/disco#info'/></iq>

					}, function(err) {
						callback(null, rooms);
					});

				}
			], function (err, result) {
				console.log(result);
				io.of("xmpp").to(id).emit("roomlist", result);
			});

		}
	});

	socket.on("message", function(data) {
		if (typeof sessions[id] !== "undefined" && sessions[id] !== null) {
			var chat = new ltx.Element("message", { to: data.to, type: "chat", id: data.id })
					.c("body")
					.t(data.message)
					.c("active", { xmlns: "http://jabber.org/protocol/chatstates" });

			sessions[id].send("<message from='" + jid.user + "@" + jid.domain + "/" + jid.resource + "' to='" + data.to + "' type='chat' id='" + data.id + "'><body>" + data.message + "</body><active xmlns='http://jabber.org/protocol/chatstates'/></message>");
		}
	});

	socket.on("composing", function(data) {
		sessions[id].send("<message from='" + jid.user + "@" + jid.domain + "/" + jid.resource + "' to='" + data.to + "' type='chat' id='" + data.id + "'><" + data.state + " xmlns='http://jabber.org/protocol/chatstates'/></message>");
	});

	client.on("stanza", function(stanza) {
		obj.emit("stanza-"+stanza.attrs.id,stanza);
		//console.log(util.inspect(stanza, {showHidden: false, depth: null}));
		if (stanza.is("message") && stanza.attrs.type === "chat") {
			if (typeof stanza.getChild("body") !== "undefined") {
				xmppSocket.to(id).emit("message", {
					id: stanza.attrs.id,
					to: {
						username: stanza.attrs.to.split(/@/)[0],
						host: stanza.attrs.to.split(/[@|\/]/)[1],
						client: stanza.attrs.to.split(/\//)[1]
					},
					from: {
						username: stanza.attrs.from.split(/@/)[0],
						host: stanza.attrs.from.split(/[@|\/]/)[1],
						client: stanza.attrs.from.split(/\//)[1]
					},
					message: stanza.getChildText("body")
				});
			} else if (typeof stanza.getChild("composing") !== "undefined") {
				xmppSocket.to(id).emit("typing", {
					id: stanza.attrs.id,
					to: {
						username: stanza.attrs.to.split(/@/)[0],
						host: stanza.attrs.to.split(/[@|\/]/)[1],
						client: stanza.attrs.to.split(/\//)[1]
					},
					from: {
						username: stanza.attrs.from.split(/@/)[0],
						host: stanza.attrs.from.split(/[@|\/]/)[1],
						client: stanza.attrs.from.split(/\//)[1]
					},
					typing: true
				});
			} else if (typeof stanza.getChild("paused") !== "undefined" || typeof stanza.getChild("active") !== "undefined") {
				xmppSocket.to(id).emit("typing", {
					id: stanza.attrs.id,
					to: {
						username: stanza.attrs.to.split(/@/)[0],
						host: stanza.attrs.to.split(/[@|\/]/)[1],
						client: stanza.attrs.to.split(/\//)[1]
					},
					from: {
						username: stanza.attrs.from.split(/@/)[0],
						host: stanza.attrs.from.split(/[@|\/]/)[1],
						client: stanza.attrs.from.split(/\//)[1]
					},
					typing: false
				});
			} else if (typeof stanza.getChild("inactive") !== "undefined" || typeof stanza.getChild("gone") !== "undefined") {
			} else {
				//console.log(stanza);
			}
		} else if (stanza.is("iq")) {
			switch (stanza.attrs.type) {
				case "result":
					switch (stanza.attrs.id) {
						case "roster":
							var q = stanza.getChild("query"),
									list = {},
									u = null;
							for (i = 0; i < q.getChildren("item").length; i++) {
								u = q.getChildren("item")[i];
								list[i] = {
									user: u.attrs.jid,
									subscription: u.attrs.subscription,
									name: u.attrs.name
								};
							}
							xmppSocket.to(id).emit("roster", list);
						break;
					}
				break;
				case "get":
					console.log(stanza);
				break;
				case "set":
					if (stanza.getChild("query").getChild("item") !== "undefined") {
						switch (stanza.getChild("query").getChild("item").attrs.ask) {
							case "unsubscribe":
							case "subscribe":
								//console.log(stanza.getChild("query").getChild("item"));
								//xmppSocket.to(id).emit("subscribe", stanza.getChild("query").getChild("item").attrs);
							break;
						}
					}
					var p = new ltx.Element("iq", {
						from: jid.user + "@" + jid.domain + "/" + jid.resource,
						to: jid.domain,
						type: "result",
						id: stanza.attrs.id
					});
					client.send(p);
				break;
			}
		} else if (stanza.is("presence")) {
			if (typeof stanza.attrs.type !== "undefined") {
				switch (stanza.attrs.type) {
					case "unavailable":
						xmppSocket.to(id).emit("updatePresence", {
							username: stanza.attrs.from.split(/@/)[0],
							host: stanza.attrs.from.split(/[@|\/]/)[1],
							client: stanza.attrs.from.split(/\//)[1],
							show: "unavailable",
							status: ""
						});
					break;
					case "subscribe":
					case "unsubscribe":
					case "unsubscribed":
					case "subscribe":
						xmppSocket.to(id).emit(stanza.attrs.type, {
							username: stanza.attrs.from.split(/@/)[0],
							host: stanza.attrs.from.split(/[@|\/]/)[1],
						});
					break;
				}
			} else {
				var show = stanza.getChildText("show"),
						status = stanza.getChildText("status");

				show = (show === null) ? "available" : show;
				status = (status === null) ? "" : status;
				xmppSocket.to(id).emit("updatePresence", {
					username: stanza.attrs.from.split(/@/)[0],
					host: stanza.attrs.from.split(/[@|\/]/)[1],
					client: stanza.attrs.from.split(/\//)[1],
					show: show,
					status: status
				});
			}
		} else {
			console.log(stanza);
		}
	});

	client.connect();

	return client;
};

module.exports = Xmpp;
