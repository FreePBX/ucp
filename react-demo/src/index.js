import React from 'react';
import ReactDOM from 'react-dom';
import App from './App';
import registerServiceWorker from './registerServiceWorker';
import './index.css';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap/dist/css/bootstrap-theme.css';

// import { SocketProvider } from 'socket.io-react';
import io from 'socket.io-client';


var host = "http://freepbxdev6.sangoma.net:8080";
// const socket = io.connect("http://freepbxdev5.sangoma.net:8080"); 
const socket = io.connect(host); 

// process.env.SOCKET_URL);

var from_pbx = 
[	{id: 100, ext: 100, name: "J.P.",    lastname: "Romero",   socket: null},
	{id: 200, ext: 200, name: "Luis",    lastname: "Abarca",   socket: null},
	{id: 300, ext: 300, name: "Alberto", lastname: "Santos",   socket: null},
	{id: 400, ext: 400, name: "Paul",    lastname: "Estrella", socket: null}];

var d = new Date();
var msg = {actionid: d.getTime()};
socket.emit("Action-ExtensionStateList", JSON.stringify(msg));
socket.on("Action-ExtensionStateList", function(msg){
	console.log(msg);
});

ReactDOM.render(<App extensions={from_pbx} socket={socket} />, document.getElementById('root'));	


registerServiceWorker();
