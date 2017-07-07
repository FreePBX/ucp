var io = require('socket.io')();

var freepbx = new require('/usr/src/freepbx/ucp/node/lib/freepbx.js')();

freepbx.on('ready', function(){
	var ami = freepbx.astman;
	var db  = freepbx.db;
	var jsondev = [];
	var devices = [];
	var queues  = [];
	var jsonqueues = [];

/*	ami.on('managerevent',function(msg){
		console.log(msg);
	});*/
	/*	Event: ExtensionStatus
		Description: Event raised when an extension status changed (i.e. Registered/Unregistered) or 
		if the action ExtensionStateList was sent
		Return:	If it is an isolated event it will return an object with the extension and the new status,
		if not it will only store it in an array (jsondev)
			{
				ext    : extension number
				status : new status of the extension
			}
	*/
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
				var displayName = 'Unknown';
				devices[evt.actionid].map(function(val){
					if(evt.exten == val.id)
						displayName = val.description;
				})
				var tech = evt.hint.split("/");
				var element = {
					ext     : evt.exten,
					name    : displayName,
					status  : evt.statustext,
					tech    : tech[0]	
				};
				jsondev[evt.actionid].push(element);
			}
		}
	});


	/*	Event: ExtensionStateListComplete
 		Description: Event raised when all the events ExtensionStatus have already displayed.
		This event is only raised when previously an action ExtensionStateList is sent
		Return: It will return an object with all the extensions and status as below
			{
				ext    : extension number
				name   : callerid or display name
				status : extension status (Idle, Busy, Unavailable, etc)
				tech   : technology of the extension
			}
  	*/
	ami.on('extensionstatelistcomplete', function(evt){
		//sort the result by the extension number
		jsondev[evt.actionid].sort(function(a,b){
			if(a.ext < b.ext)
				return -1;
			if(a.ext > b.ext)
				return 1;
			return 0;
		});
		io.emit('Event-ExtensionStateListComplete', jsondev[evt.actionid]);
		jsondev[evt.actionid] = [];
		devices[evt.actionid] = [];

	});

	/*	Event: QueueParams
 		Description : Event raised when the action QueueStatus is received and show
		all the queues availables
		Return: It creates an object with the following information
			{
				type      : queue (to indicate that this is a queue)
				queue     : number of the queue
				queueName : name of the queue
				strategy  : strategy of the queue
				calls     : number of calls received
				holdtime  : total hold time
				talktime  : total talk time
				completed : number of calls completed
				abandoned : number of calls abandoned
			}
	*/
	ami.on('queueparams', function(evt){
		if(evt.queue != 'default'){
			var queueName = 'Unknown';
			queues[evt.actionid].map(function(val){
				if(evt.queue == val.queue)
					queueName = val.description;
			});
			var element = {
				type      : 'queue',
				queue     : evt.queue,
				queueName : queueName,
				strategy  : evt.strategy,
				calls     : evt.calls,
				holdtime  : evt.holdtime,
				talktime  : evt.talktime,
				completed : evt.completed,
				abandoned : evt.abandoned
			};
			jsonqueues[evt.actionid].push(element);
		}
	});

	/*	Event: QueueMember
 		Description: Event raised when the action QueueStatus is received and show
		all the members that belong to each queue
		Return: It creates an object with the following information
			{
				type       : member (to indicate that this is a queue member)
				queue      : number of the queue that this agent belongs
				name       : name of the agent or extension
				location   : channel of the agent or extension
				membership : type of agent (static or dynamic)
				penalty    : penalty number of the agent
				callstaken : number of calls taken by this agent
				lastcall   : last call of this agent
				incall     : time of current call
				status     : status of this agent
				paused     : indicates if this agent is currently in pause
			}
	*/
	ami.on('queuemember', function(evt){
		var element = {
			type       : 'member',
			queue      : evt.queue,
			name       : evt.name,
			location   : evt.name,
			membership : evt.membership,
			penalty    : evt.penalty,
			callstaken : evt.callstaken,
			lastcall   : evt.lastcall,
			incall     : evt.incall,
			status     : evt.status,
			paused     : evt.paused
		};
		jsonqueues[evt.actionid].push(element);
	});

	/*	Event: QueueStausComplete
 		Description: Event raised when all the events QueueParams and QueueMember have already displayed.
		This event is only raised when previously an action QueueStatus is sent
		Return: It return an object with the union of the objects created by the Events QueueParams and QueueMember
	*/ 
	ami.on('queuestatuscomplete', function(evt){
		io.emit('Event-QueueStatusComplete', jsonqueues[evt.actionid]);
		jsonqueues[evt.actionid] = [];
		queues[evt.actionid] = [];
	});

	//All the AMI actions will be manage through the socket connection
	io.on('connection', function(socket){

		/*	Action: ExtensionStateList
 			Description: This action is used to get all the extensions created
			and also the status of each one
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
				}
		*/
		socket.on('Action-ExtensionStateList', function(msg){
			var actionid = 'ESL-' + msg.actionid;
			jsondev[actionid] = [];
			devices[actionid] = [];	
			var query = db.query('SELECT * FROM devices');
                        query.on('result', function(qres) {
                        	qres.on('data', function(row) {
                                	devrow = {
                                        	id : row.id,
                                        	description : row.description
                                        }; 
                                        devices[actionid].push(devrow);
                                });
				
                        });
			query.on('end', function(){
				ami.action({
                           		'action' : 'ExtensionStateList',
                           		'actionid' : actionid
                        	}, function(err, res){
                                	io.emit('Action-ExtensionStateList', res); 
				});
			});
		});

		/*	Action: Originate
 			Description: This action generates an outgoing call to an extension
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
					tech     : technology of the caller extension
					ext      : caller extension
					callto   : number to call
				}
		*/
		socket.on('Action-Originate', function(msg){
			ami.action({
				'action' : 'Originate',
				'actionid' : 'O-' + msg.actionid,
				'channel' : msg.tech + '/' + msg.ext,
				'exten' : msg.callto,
				'context' : 'from-internal',
				'priority' : 1
			}, function(err, res){
				io.emit('Action-Originate', res);
			});
		});

		/*	Action: ChanSpy
 			Description: This action starts a channel spy and whispering (optional)
			Parameters: This action should receive an object with the following data
                                {
                                        actionid : reference number of the action
                                        tech     : technology of the caller extension
                                        ext      : caller extension
                                        techspy  : technology of the extension to spy
					spy      : extension to spy
                                }
		*/
		socket.on('Action-ChanSpy', function(msg){
			var data = msg.techspy + '/' + msg.spy + ',bq';
			if(typeof msg.whisper !== 'undefined')
				data += 'dw';
			ami.action({
				'action' : 'Originate',
				'actionid' : 'CS-' + msg.actionid,
				'channel' : msg.tech + '/' + msg.ext,
				'application' : 'ChanSpy',
				'data' : data
			}, function(err, res){
				io.emit('Action-ChanSpy', res);
			}); 
		});

		/*	Action: Monitor
 			Description: This action starts recording on a channel and save it in the
			default asterisk monitor folder with the name "extension-actionid.wav"
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
					tech	 : technology of the extension to record
					ext	 : extension number to record
				}
		*/
		socket.on('Action-Monitor', function(msg){
			ami.action({
				'action' : 'Monitor',
				'actionid' : 'M-' + msg.actionid,
				'channel' : msg.tech + '/' + msg.ext,
				'file' : msg.ext + '-' + msg.actionid,
				'mix' : true
			}, function(err, res){
				io.emit('Action-Monitor', res);
			});
		});

		/*	Action: StopMonitor
 			Description: Stop the recording on a channel
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
					tech     : technology of the extension to stop recording
					ext	 : extension number
				}
		*/
		socket.on('Action-StopMonitor', function(msg){
			ami.action({
				'action' : 'StopMonitor',
				'actionid' : 'SM-' + msg.actionid,
				'channel' : msg.tech + '/' + msg.ext
			}, function(err, res){
				io.emit('Action-StopMonitor', res);
			});
		});

		/*	Action: PauseMonitor
 			Description: Pause a recording on a channel temporarily
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
					tech     : technology of the extension to pause the recording
					ext	 : extension number
				}
		*/
		socket.on('Action-PauseMonitor', function(msg){
			ami.action({
				'action' : 'PauseMonitor',
				'actionid' : 'PM-' + msg.actionid,
				'channel' : msg.tech + '/' + msg.ext
			}, function(err, res){
				io.emit('Action-PauseMonitor', res);
			});
		});

		/*	Action: UnpauseMonitor
 			Description: Unpause a recording of a channel
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
					tech     : technology of the extension to unpause the recording
					ext      : extension number
				}
		*/
		socket.on('Action-UnpauseMonitor', function(msg){
			ami.action({
				'action' : 'UnpauseMonitor',
				'actionid' : 'UPM-' + msg.actionid,
				'channel' : msg.tech + '/' + msg.ext
			}, function(err, res){
				io.emit('Action-UnpauseMonitor', res);
			});
		});

		/*	Action: QueueStatus
 			Description: Generates events to get the current queues and the members of each queue
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
				}
		*/
		socket.on('Action-QueueStatus', function(msg){
			var actionid = 'QS-' + msg.actionid;
			jsonqueues[actionid] = [];
			queues[actionid] = [];
			var query = db.query('SELECT extension, descr FROM queues_config');
			query.on('result', function(qres) {
                                qres.on('data', function(row) {
                                        queuerow = {
                                                queue : row.extension,
                                                description : row.descr
                                        };
                                        queues[actionid].push(queuerow);
                                });

                        });
			query.on('end', function(){
				ami.action({
					'action' : 'QueueStatus',
					'actionid' : actionid
				}, function(err, res){
					io.emit('Action-QueueStatus');
				});
			});	
		})
	}); 
});

io.listen(8080);
