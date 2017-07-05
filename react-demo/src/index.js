import React from 'react';
import ReactDOM from 'react-dom';
import App from './App';
import registerServiceWorker from './registerServiceWorker';
import './index.css';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap/dist/css/bootstrap-theme.css';
import io from 'socket.io-client';

var dev = "6";
var host = "http://freepbxdev"+dev+".sangoma.net:8080";
const socket = io.connect(host); // process.env.SOCKET_URL);

socket.on('connect', function(){
	ReactDOM.render(<App socket={socket} />, document.getElementById('root'));	    
});

socket.on('connect_timeout', function() {
	console.log("Timeout");
});

socket.on('connect_failed', function() {
    console.log('Connection Failed');
});

registerServiceWorker();
