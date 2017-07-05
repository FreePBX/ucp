var io = require('socket.io')();

var freepbx = new require('/usr/src/freepbx/ucp/node/lib/freepbx.js')();

freepbx.on('ready', function(){
	var ami = freepbx.astman;
	var db  = freepbx.db;
	var jsondev = [];
	var devices = [];

	ami.on('extensionstatus', function(evt){
		//If no actionid is sent then this is an isolated event
		if(typeof evt.actionid === 'undefined' || evt.actionid === null){
			var element = {
				ext    : evt.exten,
				status : evt.statustext	
			}
			io.emit('Event-ExtensionStatus',element);
		}
		else{
			//For now only local extensions are required (parking lots are not included)
			if(evt.context == 'ext-local'){
				console.log(evt);
				var displayName = 'Unknown';
				devices.map(function(val){
					if(evt.exten == val.id)
						displayName = val.description;
				})
				var element = {
					ext     : evt.exten,
					name    : displayName,
					status  : evt.statustext
				};
				jsondev.push(element);
			}
		}
	});

	ami.on('extensionstatelistcomplete', function(evt){
		console.log(jsondev);
		//sort the result by the extension number
		jsondev.sort(function(a,b){
			if(a.ext < b.ext)
				return -1;
			if(a.ext > b.ext)
				return 1;
			return 0;
		});
		io.emit('Event-ExtensionStateListComplete', jsondev);
		jsondev = [];
		devices = [];

	});

	io.on('connection', function(socket){
		socket.on('Action-ExtensionStateList', function(msg){
			console.log(msg);
			var query = db.query('SELECT * FROM devices');
                        query.on('result', function(qres) {
                        	qres.on('data', function(row) {
                                	devrow = {
                                        	id : row.id,
                                        	description : row.description
                                        }; 
                                        devices.push(devrow);
                                });
				
                        });
			query.on('end', function(){
				ami.action({
                           		'action' : 'ExtensionStateList',
                           		'actionid' : msg.actionid
                        	}, function(err, res){
					console.log(res);
                                	io.emit('Action-ExtensionStateList', res); 
				});
			});
		});

		socket.on('Action-Originate', function(msg){
			ami.action({
				'action' : 'Originate',
				'actionid' : msg.actionid,
				'channel' : 'Local/' + msg.ext,
				'exten' : msg.callto,
				'context' : 'from-internal',
				'priority' : 1
			}, function(err, res){
				io.emit('Action-Originate', res);
			});
		});

		socket.on('Action-ChanSpy', function(msg){
			console.log(msg);
			var data = 'Local/' + msg.spy + ', q';
			if(typeof msg.whisper !== 'undefined')
				data += 'dw';
			ami.action({
				'action' : 'Originate',
				'actionid' : msg.actionid,
				'channel' : 'Local/' + msg.ext,
				'application' : 'ChanSpy',
				'data' : data
			}, function(err, res){
				console.log(res);
				io.emit('Action-ChanSpy', res);
			}); 
		});
	}); 
});

io.listen(8080);
