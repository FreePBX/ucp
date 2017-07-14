// Technology = Channel Type

var io = require('socket.io')();

var freepbx = new require('/usr/src/freepbx/ucp/node/lib/freepbx.js')();

freepbx.on('ready', function(){
	var ami = freepbx.astman,
	    db  = freepbx.db,
	    jsondev = [],
	    devices = [],
	    queues  = [],
	    jsonqueues = [],
	    jsonparkinglots = [],
	    // Function to get statistics of an specific queue
	    queueStatus = function(actionid, queue){
		jsonqueues[actionid] = [];
		ami.action({
			'action'   : 'QueueStatus',
			'actionid' : actionid,
			'queue'    : queue
		});
	};

	ami.on('managerevent',function(msg){
	//	if(msg.event == 'QueueCallerLeave' || msg.event == 'QueueCallerJoin' || msg.event == 'QueueCallerAbandon' || msg.event == 'QueueMemberStatus' || msg.event == 'QueueParams' || msg.event == 'QueueMember')
  		console.log(msg);
	});
	/*	Event: ExtensionStatus
		Description: Event raised when an extension status changed (i.e. Registered/Unregistered) or 
		if the action ExtensionStateList was sent
		Return:	If it is an isolated event it returns an object with the extension and the new status,
		if not it will only store it in an array (jsondev)
			{
				ext     : extension number
				status  : new status of the extension
			}
	*/
	ami.on('extensionstatus', function(evt){
		//If no actionid is sent then this is an isolated event
		if(typeof evt.actionid === 'undefined' || evt.actionid === null){
			var element = {
				ext     : evt.exten,
				status  : evt.statustext	
			};
			io.emit('Event-ExtensionStatus',element);
		}
		else{
			if(evt.context == 'ext-local'){
				var displayName = 'Unknown';
				if(typeof devices[evt.actionid] !== 'undefined' && devices[evt.actionid] !== null){
					devices[evt.actionid].map(function(val){
						if(evt.exten == val.id)
							displayName = val.description;
					});
				}
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
		Return: It returns an object with all the extensions and status as below
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
				calls     : number of active calls
				holdtime  : total hold time
				talktime  : total talk time
				completed : number of calls completed
				abandoned : number of calls abandoned
			}
	*/
	ami.on('queueparams', function(evt){
		if(typeof jsonqueues[evt.actionid] === 'undefined' || jsonqueues[evt.actionid] === null)
                        jsonqueues[evt.actionid] = [];
		if(evt.queue != 'default'){
			var queueName = 'Unknown';
			if(typeof queues[evt.actionid] !== 'undefined' && queues[evt.actionid] !== null){
				queues[evt.actionid].map(function(val){
					if(evt.queue == val.queue)
						queueName = val.description;
				});
			}
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
				lastcall   : date of last call (in seconds)
				incall     : 1 agent is in call, 0 not
				status     : status of this agent
				paused     : indicates if this agent is currently in pause
			}
	*/
	ami.on('queuemember', function(evt){
		if(typeof jsonqueues[evt.actionid] === 'undefined' || jsonqueues[evt.actionid] === null)
                        jsonqueues[evt.actionid] = [];
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
		Return: It returns an object with the union of the objects created by the Events QueueParams and QueueMember
	*/ 
	ami.on('queuestatuscomplete', function(evt){
		jsonqueues[evt.actionid].sort(function(a,b){
                        if(a.queue < b.queue)
                                return -1;
                        if(a.queue > b.queue)
                                return 1;
                        return 0;
                });
		io.emit('Event-QueueStatusComplete', jsonqueues[evt.actionid]);
		jsonqueues[evt.actionid] = [];
		queues[evt.actionid] = [];
	});

	/*	Event: PeerStatus
 		Description: Event raised when a peer changed its status
		Return: It returns an object with the following data
			{
				ext        : number of the peer that changed status
				tech       : technology of the peer
				peerstatus : new status of the peer
			}
	*/
	ami.on('peerstatus', function(evt){
		var peer = evt.peer.split('/');
		var element = {
			ext        : peer[1],
			tech       : peer[0],
			peerstatus : evt.peerstatus		
		};
		io.emit('Event-PeerStatus', element);
	});

	/*	Event: QueueMemberStatus
 		Description: Event raised when a queue members status has changed
		Return: It returns an object with the following data
			{
				queue : number of the queue where the agent belongs
				callstaken : number of calls taken by this agent
				paused : 0 agent not paused, 1 agent is paused
				membername : name of the agent or extension
				interface : the queue member's channel technology or location
				penalty : penalty number of the agent
				incall : 1 agent is in call, 0 not
				membership : type of agent (static or dynamic)
				lastcall : date of last call (in seconds)
				status : status of the agent
					0 - AST_DEVICE_UNKNOWN
					1 - AST_DEVICE_NOT_INUSE
					2 - AST_DEVICE_INUSE
					3 - AST_DEVICE_BUSY
					4 - AST_DEVICE_INVALID
					5 - AST_DEVICE_UNAVAILABLE
					6 - AST_DEVICE_RINGING
					7 - AST_DEVICE_RINGINUSE
					8 - AST_DEVICE_ONHOLD
			}
	*/
	ami.on('queuememberstatus', function(evt){
		var element = {
			queue      : evt.queue,
			callstaken : evt.callstaken,
			paused     : evt.paused,
			membername : evt.membername,
			interface  : evt.interface,
			penalty    : evt.penalty,
			incall     : evt.incall,
			membership : evt.membership,
			lastcall   : evt.lastcall,
			status     : evt.status	
		};
		io.emit('Event-QueueMemberStatus', element);
	});

	/*	Event: QueueMemberAdded
 		Description: Event raised when a member is added to the queue	 
		Return: It returns an object with the following data
                        {
                                queue : number of the queue where the agent belongs
                                callstaken : number of calls taken by this agent
                                paused : 0 agent not paused, 1 agent is paused
                                membername : name of the agent or extension
                                interface : the queue member's channel technology or location
                                penalty : penalty number of the agent
                                incall : 1 agent is in call, 0 not
                                membership : type of agent (static or dynamic)
                                lastcall : date of last call (in seconds)
                                status : status of the agent
                                        0 - AST_DEVICE_UNKNOWN
                                        1 - AST_DEVICE_NOT_INUSE
                                        2 - AST_DEVICE_INUSE
                                        3 - AST_DEVICE_BUSY
                                        4 - AST_DEVICE_INVALID
                                        5 - AST_DEVICE_UNAVAILABLE
                                        6 - AST_DEVICE_RINGING
                                        7 - AST_DEVICE_RINGINUSE
                                        8 - AST_DEVICE_ONHOLD
                        }
	*/	
	ami.on('queuememberadded', function(evt){
		var element = {
                        queue      : evt.queue,
                        callstaken : evt.callstaken,
                        paused     : evt.paused,
                        membername : evt.membername,
                        interface  : evt.interface,
                        penalty    : evt.penalty,
                        incall     : evt.incall,
                        membership : evt.membership,
                        lastcall   : evt.lastcall,
                        status     : evt.status
                };
                io.emit('Event-QueueMemberAdded', element);
	});

	/*	Event: QueueMemberRemoved
 		Description: Event raised when a member is removed from the queue
		Return: It returns an object with the following data
                        {
                                queue : number of the queue where the agent belongs
                                callstaken : number of calls taken by this agent
                                paused : 0 agent not paused, 1 agent is paused
                                membername : name of the agent or extension
                                interface : the queue member's channel technology or location
                                penalty : penalty number of the agent
                                incall : 1 agent is in call, 0 not
                                membership : type of agent (static or dynamic)
                                lastcall : date of last call (in seconds)
                                status : status of the agent
                                        0 - AST_DEVICE_UNKNOWN
                                        1 - AST_DEVICE_NOT_INUSE
                                        2 - AST_DEVICE_INUSE
                                        3 - AST_DEVICE_BUSY
                                        4 - AST_DEVICE_INVALID
                                        5 - AST_DEVICE_UNAVAILABLE
                                        6 - AST_DEVICE_RINGING
                                        7 - AST_DEVICE_RINGINUSE
                                        8 - AST_DEVICE_ONHOLD
                        }	
	*/
	ami.on('queuememberremoved', function(evt){
		var element = {
                        queue      : evt.queue,
                        callstaken : evt.callstaken,
                        paused     : evt.paused,
                        membername : evt.membername,
                        interface  : evt.interface,
                        penalty    : evt.penalty,
                        incall     : evt.incall,
                        membership : evt.membership,
                        lastcall   : evt.lastcall,
                        status     : evt.status
                };
                io.emit('Event-QueueMemberRemoved', element);
	});	

	/*	Event: Parkinglot
 		Description: Event raised when the action Parkinglots is called
		Return: It returns an object with the following data
			{
				name       : name of the parking lot
				startspace : initial space of the parking lot
				stopspace  : final space of the parking lot
			}
	*/
	ami.on('parkinglot', function(evt){
		var element = {
			name       : evt.name,
			startspace : evt.startspace,
			stopspace  : evt.stopspace		
		};
		jsonparkinglots[evt.actionid].push(element);
	});

	/*	Event: ParkinglotsComplete
 		Description: Event raised when all the Events Parkinglot have displayed
		Return: It returns the objects stored by the Event Parkinglot
	*/
	ami.on('parkinglotscomplete', function(evt){
		io.emit('Event-ParkinglotsComplete', jsonparkinglots[evt.actionid]);
		jsonparkinglots[evt.actionid] = [];
	});

	/*	Event: Newexten
 		Description: Event raised when a channel enters a new extension, context or priority
		Return: It returns an object with the following data
			{
				channel          : channel of the call
				calleridnum      : extension that belongs to the channel
				channelstatedesc : description of the status of the channel
				connectedlinenum : calling number
			}
	*/
	ami.on('newexten', function(evt){
		var element = {
			channel          : evt.channel,
			calleridnum      : evt.calleridnum,
			channelstatedesc : evt.channelstatedesc,
			connectedlinenum : evt.connectedlinenum
		};
		io.emit('Event-Newexten', element);
	});

	/*	Event: Hangup
 		Description: Event raised when a channel is hang up
		Return: It returns an object with the following data
                        {
                                channel          : channel of the call
                                calleridnum      : extension that belongs to the channel
                                channelstatedesc : description of the status of the channel
                                connectedlinenum : calling number
                        }
	*/
	ami.on('hangup', function(evt){
		var element = {
			channel : evt.channel,
			calleridnum      : evt.calleridnum,
			channelstatedesc : evt.channelstatedesc,
			connectedlinenum : evt.connectedlinenum
		}
		io.emit('Event-Hangup', element);
	});

	/*	Event: ParkedCall
 		Description: Event raised when a call is parked
		Return: It returns an object with the following information
			{
				parkeechannel          : channel that is parked
				parkeechannelstatedesc : state of the parkee
				parkeecalleridnum      : number of the line parkee
				parkeeconnectedlinenum : last number connected to the call parkee
				parkingspace           : parking lot number used
			}
	*/
	ami.on('parkedcall', function(evt){
		var element = {
			parkeechannel          : evt.parkeechannel,
			parkeechannelstatedesc : evt.parkeechannelstatedesc,
			parkeecalleridnum      : evt.parkeecalleridnum,
			parkeeconnectedlinenum : evt.parkeeconnectedlinenum,
			parkingspace           : evt.parkingspace	
		};
		io.emit('Event-ParkedCall', element);
	});

	/*	Event: UnparkedCall
 		Description : Event raised when a parked call is retrieved to some extension
		Return: It returns an object with the following information
                        {
                                parkeechannel          : channel that is parked
                                parkeechannelstatedesc : state of the parkee
                                parkeecalleridnum      : number of the line parkee
                                parkeeconnectedlinenum : last number connected to the call parkee
                                parkingspace           : parking lot number used
                        }
	*/
	ami.on('unparkedcall', function(evt){
		var element = {
                        parkeechannel          : evt.parkeechannel,
                        parkeechannelstatedesc : evt.parkeechannelstatedesc,
                        parkeecalleridnum      : evt.parkeecalleridnum,
                        parkeeconnectedlinenum : evt.parkeeconnectedlinenum,
                        parkingspace           : evt.parkingspace
                };
                io.emit('Event-UnparkedCall', element);
	});

	/*	Event: ParkedCallSwap
 		Description: Event raised when a channel takes the place of a previously parked channel
		Return: It returns an object with the following information
                        {
                                parkeechannel          : channel that is parked
                                parkeechannelstatedesc : state of the parkee
                                parkeecalleridnum      : number of the line parkee
                                parkeeconnectedlinenum : last number connected to the call parkee
                                parkingspace           : parking lot number used
                        }
	*/
	ami.on('parkedcallswap', function(evt){
		var element = {
                        parkeechannel          : evt.parkeechannel,
                        parkeechannelstatedesc : evt.parkeechannelstatedesc,
                        parkeecalleridnum      : evt.parkeecalleridnum,
                        parkeeconnectedlinenum : evt.parkeeconnectedlinenum,
                        parkingspace           : evt.parkingspace
                };
                io.emit('Event-ParkedCallSwap', element);
	});

	/*	Event: ParkedCallGiveUp
 		Description: Event raised when a channel leaves a parking lot because it hung up without being answered
		Return: It returns an object with the following information
                        {
                                parkeechannel          : channel that is parked
                                parkeechannelstatedesc : state of the parkee
                                parkeecalleridnum      : number of the line parkee
                                parkeeconnectedlinenum : last number connected to the call parkee
                                parkingspace           : parking lot number used
                        }
	*/
	ami.on('parkedcallgiveup', function(evt){
		var element = {
                        parkeechannel          : evt.parkeechannel,
                        parkeechannelstatedesc : evt.parkeechannelstatedesc,
                        parkeecalleridnum      : evt.parkeecalleridnum,
                        parkeeconnectedlinenum : evt.parkeeconnectedlinenum,
                        parkingspace           : evt.parkingspace
                };
                io.emit('Event-ParkedCallGiveUp', element);
	});

	/*	Event: ParkedCallTimeOut
 		Description: Event raised when a channel leaves a parking lot due to reaching the time limit of being parked
		Return: It returns an object with the following information
                        {
                                parkeechannel          : channel that is parked
                                parkeechannelstatedesc : state of the parkee
                                parkeecalleridnum      : number of the line parkee
                                parkeeconnectedlinenum : last number connected to the call parkee
                                parkingspace           : parking lot number used
                        }
	*/
	ami.on('parkedcalltimeout', function(evt){
		var element = {
                        parkeechannel          : evt.parkeechannel,
                        parkeechannelstatedesc : evt.parkeechannelstatedesc,
                        parkeecalleridnum      : evt.parkeecalleridnum,
                        parkeeconnectedlinenum : evt.parkeeconnectedlinenum,
                        parkingspace           : evt.parkingspace
                };
                io.emit('Event-ParkedCallTimeOut', element);
	});

	/*	Event: QueueCallerJoin
 		Description: Event raised when a call join a queue
                Return: It sends an action to get the status of the queue
	*/
	ami.on('queuecallerjoin', function(evt){
                queueStatus('QCJ-112233', evt.queue);
        });

	/*	Event: QueueCallerAbandon
 		Description: Event raised when a caller abandons the queue
                Return: It sends an action to get the status of the queue
        */
	ami.on('queuecallerabandon', function(evt){
                queueStatus('QCA-112233', evt.queue);
        });

	/*	Event: QueueCallerLeave
 		Description: Event raised when a caller leaves a queue
                Return: It sends an action to get the status of the queue
        */
	ami.on('queuecallerleave', function(evt){
                queueStatus('QCL-112233', evt.queue);
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
                                        	id          : row.id,
                                        	description : row.description
                                        }; 
                                        devices[actionid].push(devrow);
                                });
				
                        });
			query.on('end', function(){
				ami.action({
                           		'action'   : 'ExtensionStateList',
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
					channel  : channel of the caller extension (i.e SIP/100)
					callto   : number to call
				}
		*/
		socket.on('Action-Originate', function(msg){
			ami.action({
				'action'   : 'Originate',
				'actionid' : 'O-' + msg.actionid,
				'channel'  : msg.channel,
				'exten'    : msg.callto,
				'context'  : 'from-internal',
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
					channel  : channel of the caller extension (i.e SIP/100)
					chanspy  : channel to spy
					whisper  : optional parameter, if it is defined
						   whisper mode is enabled
                                }
		*/
		socket.on('Action-ChanSpy', function(msg){
			var data = msg.chanspy + ',bq';
			if(typeof msg.whisper !== 'undefined')
				data += 'dw';
			ami.action({
				'action'      : 'Originate',
				'actionid'    : 'CS-' + msg.actionid,
				'channel'     : msg.channel,
				'application' : 'ChanSpy',
				'data'        : data
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
					channel  : channel to record
				}
		*/
		socket.on('Action-Monitor', function(msg){
			ami.action({
				'action'   : 'Monitor',
				'actionid' : 'M-' + msg.actionid,
				'channel'  : msg.channel,
				'file'     : msg.channel + '_' + msg.actionid,
				'mix'      : true
			}, function(err, res){
				io.emit('Action-Monitor', res);
			});
		});

		/*	Action: StopMonitor
 			Description: Stop the recording on a channel
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
					channel  : channel to stop recording 
				}
		*/
		socket.on('Action-StopMonitor', function(msg){
			ami.action({
				'action'   : 'StopMonitor',
				'actionid' : 'SM-' + msg.actionid,
				'channel'  : msg.channel
			}, function(err, res){
				io.emit('Action-StopMonitor', res);
			});
		});

		/*	Action: PauseMonitor
 			Description: Pause a recording on a channel temporarily
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
					channel  : channel to pause the recording
				}
		*/
		socket.on('Action-PauseMonitor', function(msg){
			ami.action({
				'action'   : 'PauseMonitor',
				'actionid' : 'PM-' + msg.actionid,
				'channel'  : msg.channel
			}, function(err, res){
				io.emit('Action-PauseMonitor', res);
			});
		});

		/*	Action: UnpauseMonitor
 			Description: Unpause a recording of a channel
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
					channel  : channel to unpause the recording
				}
		*/
		socket.on('Action-UnpauseMonitor', function(msg){
			ami.action({
				'action'   : 'UnpauseMonitor',
				'actionid' : 'UPM-' + msg.actionid,
				'channel'  : msg.channel
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
                                                queue       : row.extension,
                                                description : row.descr
                                        };
                                        queues[actionid].push(queuerow);
                                });

                        });
			query.on('end', function(){
				ami.action({
					'action'   : 'QueueStatus',
					'actionid' : actionid
				}, function(err, res){
					io.emit('Action-QueueStatus', res);
				});
			});
		});

		/*	Action: Atxfer
 			Description: Makes an attended transfer
			Parameters: This action should receive an object with the following data
				{
					actionid   : reference number of the action
					channel    : channel to transfer
					transferto : extension to transfer to
				}
		*/
		socket.on('Action-Atxfer', function(msg){
			var actionid = 'AT-' + msg.actionid;
			ami.action({
				'action'   : 'Atxfer',
				'actionid' : actionid,
				'channel'  : msg.channel,
				'exten'    : msg.transferto,
				'context'  : 'from-internal'
			}, function(err, res){
				io.emit('Action-Atxfer', res);
			});
		});

		/*	Action: BlindTransfer
 			Description: Makes a blind transfer
			Parameters: This action should receive an object with the following data
				{
					actionid   : reference number of the action
					channel    : channel to transfer
					transferto : extension to transfer to
				}
		*/
		socket.on('Action-BlindTransfer', function(msg){
			var actionid = 'BT-' + msg.actionid;
			ami.action({
				'action'   : 'BlindTransfer',
				'actionid' : actionid,
				'channel'  : msg.channel,
				'exten'    : msg.transferto,
				'context'  : 'from-internal'
			}, function(err, res){
				io.emit('Action-BlindTransfer', res);
			});
		});

		/*	Action: Parkinglots
 			Description: Get all the parking lots available
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action
				}
		*/
		socket.on('Action-Parkinglots', function(msg){
			var actionid = 'PL-' + msg.actionid;
			jsonparkinglots[actionid] = [];
			ami.action({
				'action'   : 'Parkinglots',
				'actionid' : actionid
			}, function(err, res){
				io.emit('Action-Parkinglots', res);
			});
		});

		/*	Action: Park
 			Description: Park a channel
			Parameters: This action should receive an object with the following data
				{
					actionid : reference number of the action,
					channel  : channel to park
				}
		*/
		socket.on('Action-Park', function(msg){
			var actionid = 'P-' + msg.actionid;
			ami.action({
				'action'   : 'Park',
				'actionid' : actionid,
				'channel'  : msg.channel
			}, function(err, res){
				io.emit('Action-Park', res);
			});
		});
	}); 
});

io.listen(8080);
