//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
// Applied on every UCP Node start so npm install cannot leave CRLF injection in AMI values.
var fs = require("fs"),
		path = require("path"),
		amiPath = path.join(__dirname, "..", "node_modules", "asterisk-manager", "lib", "ami.js"),
		needle = "    msg.push([nkey, nval].join(': '));",
		patch = "    nval = String(nval).replace(/[\\r\\n]/g, '');\n\n    msg.push([nkey, nval].join(': '));";

if (fs.existsSync(amiPath)) {
	var source = fs.readFileSync(amiPath, "utf8");
	if (source.indexOf("replace(/[\\r\\n]/g, '')") === -1 && source.indexOf(needle) !== -1) {
		fs.writeFileSync(amiPath, source.replace(needle, patch));
	}
}
