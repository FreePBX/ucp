## Installation

This app works in a directory previuosly created with [create-react-app](https://github.com/facebookincubator/create-react-app)

## Dependencies

In order to work with Bootstrap component it's important to install React Bootstrap and Bootstrap from npm

```
npm install --save react-bootstrap bootstrap@3
```

## Running the app

```
cd app-working-directory
npm start
```

## Protocol for events and action/response handling using Socket.io & Ami

### Events (Server -> Client)

Client							Server	
socket.on("Event-<EventName1>", function(msg)); 	<-- io.emit("Event-<EventName1>", {json-string});

==========================================================================================

### Actions (Client <-> Server)

Client								Server

Action
socket.emit("Action-<ActionName1>", {json-string})	-->	socket.on("Action-<ActionName1>", function(msg))

Response
socket.on("Action-<ActionName1>", function(msg))	<--	io.emit("Action-<ActionName1>", {json-string});





