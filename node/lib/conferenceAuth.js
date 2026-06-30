//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
var freepbx = null,
		MODULE = "ucp|Conferencespro";

module.exports.init = function(fpbx) {
	freepbx = fpbx;
};

function queryRows(sql, params) {
	return freepbx.db.query(sql, params);
}

function parseSettingValue(row) {
	if (!row) {
		return null;
	}
	if (row.type === "json-arr") {
		try {
			return JSON.parse(row.val);
		} catch (e) {
			return row.val;
		}
	}
	if (row.type === "bool") {
		return row.val === "1" || row.val === 1 || row.val === true || row.val === "true";
	}
	return row.val;
}

function getUserSetting(uid, key) {
	return queryRows(
		"SELECT val, type FROM userman_users_settings WHERE uid = ? AND module = ? AND `key` = ? LIMIT 1",
		[uid, MODULE, key]
	).then(function(rows) {
		if (rows && rows.length) {
			return parseSettingValue(rows[0]);
		}
		return null;
	});
}

function getGroupIds(uid) {
	return queryRows("SELECT id, users FROM userman_groups", []).then(function(rows) {
		var gids = [],
				uidNum = parseInt(uid, 10);
		rows.forEach(function(row) {
			var users = [];
			try {
				users = JSON.parse(row.users || "[]");
			} catch (e) {
				users = [];
			}
			if (users.indexOf(uidNum) !== -1 || users.indexOf(String(uid)) !== -1) {
				gids.push(row.id);
			}
		});
		return gids;
	});
}

function getGroupSetting(gid, key) {
	return queryRows(
		"SELECT val, type FROM userman_groups_settings WHERE gid = ? AND module = ? AND `key` = ? LIMIT 1",
		[gid, MODULE, key]
	).then(function(rows) {
		if (rows && rows.length) {
			return parseSettingValue(rows[0]);
		}
		return null;
	});
}

function getCombinedSetting(uid, key) {
	return getUserSetting(uid, key).then(function(userVal) {
		if (userVal !== null) {
			return userVal;
		}
		return getGroupIds(uid).then(function(gids) {
			var chain = Promise.resolve(null);
			gids.forEach(function(gid) {
				chain = chain.then(function(found) {
					if (found !== null) {
						return found;
					}
					return getGroupSetting(gid, key);
				});
			});
			return chain;
		});
	});
}

function getLinkedConferenceExten(uid) {
	return queryRows(
		"SELECT default_extension FROM userman_users WHERE id = ? LIMIT 1",
		[uid]
	).then(function(rows) {
		if (!rows.length || !rows[0].default_extension || rows[0].default_extension === "none") {
			return null;
		}
		return queryRows(
			"SELECT value FROM conferencespro WHERE setting = 'prefix' LIMIT 1",
			[]
		).then(function(prefixRows) {
			var prefix = (prefixRows.length && prefixRows[0].value) ? prefixRows[0].value : "8";
			return String(prefix) + String(rows[0].default_extension);
		});
	});
}

function conferenceExists(exten) {
	return queryRows(
		"SELECT exten FROM meetme WHERE exten = ? LIMIT 1",
		[String(exten)]
	).then(function(rows) {
		return rows.length > 0;
	});
}

function resolveAssigned(uid, assigned) {
	if (!Array.isArray(assigned)) {
		return Promise.resolve([]);
	}
	if (assigned.indexOf("linked") === -1) {
		return Promise.resolve(assigned);
	}
	return getLinkedConferenceExten(uid).then(function(linked) {
		var resolved = assigned.slice();
		if (linked) {
			var key = resolved.indexOf("linked");
			resolved[key] = linked;
		}
		return resolved;
	});
}

module.exports.isConferenceAllowed = function(uid, conference, callback) {
	if (!uid || conference === null || typeof conference === "undefined") {
		return callback(null, false);
	}
	var exten = String(conference).replace(/[^\d]/g, "");
	if (!exten) {
		return callback(null, false);
	}

	getCombinedSetting(uid, "enable").then(function(enabled) {
		if (!enabled) {
			return false;
		}
		return getCombinedSetting(uid, "assigned");
	}).then(function(assigned) {
		if (!assigned) {
			return false;
		}
		return resolveAssigned(uid, assigned).then(function(resolved) {
			if (resolved.indexOf("*") !== -1) {
				return conferenceExists(exten);
			}
			return conferenceExists(exten).then(function(exists) {
				return exists && resolved.indexOf(exten) !== -1;
			});
		});
	}).then(function(allowed) {
		callback(null, allowed);
	}).catch(function(err) {
		console.log("Conference authorization error: " + err);
		callback(err, false);
	});
};
