import React, { Component } from 'react';
import { Col, DropdownButton, MenuItem } from 'react-bootstrap';

// Stateful
class Extension extends Component {	

	constructor(props){
    	super(props);
    	// Set-up our initial state
    	this.state = {
      		status: '',
      		style: 'default'
      		/* 
      		default' (grey), 'primary' (blue), 'success' (green), 
      		'info' (light-blue), 'warning' (orange), 'danger' (red)
      		*/
    	};
    	
		// this.enviar_nombre = this.enviar_nombre.bind(this);
		this.analizar = this.analizar.bind(this);
		props.socket.on("pbx-message", this.analizar); // Broadcast
  	}

	analizar(msg){	
		// llega string, parseo a Object 	
		var res = JSON.parse(msg);
		if(res.peer === res.channeltype+"/"+this.props.ext){
			
			if(res.peerstatus === 'Unregistered')
				this.setState({response: res.msg, status: msg, style: "danger"});
			if(res.peerstatus === 'Registered')
				this.setState({response: res.msg, status: msg, style: "info"});

		}else{
			console.log(msg);
		}
	}

	/*
  	enviar_nombre(){
  		var nombre = this.props.name+' '+this.props.lastname;
		var instruccion = {ext: this.props.ext, nombre: nombre};
  		this.props.socket.emit("pbx-message", JSON.stringify(instruccion));
  	}
  	*/

	render() {
		// console.log("Render"); console.log(this.props); console.log(this.state);
		var label = this.props.name + " " + this.props.lastname + " " + this.props.ext;
		return(
			<Col xs={3} sm={3} md={3}>
				<DropdownButton bsStyle={this.state.style} title={label} id={this.props.ext}>
					<MenuItem eventKey="1">Call</MenuItem>
      				<MenuItem eventKey="2">Barge</MenuItem>
                </DropdownButton>
			</Col>
		);
	}
}

export default Extension;

/*
 onClick={this.enviar_nombre}>                
</ButtonGroup>	
				<div>Status: <br /> {this.state.status}</div>				

	this.sumar = this.sumar.bind(this);
	this.restar = this.restar.bind(this);

  	sumar(){
  		this.setState({counter: this.state.counter + 1});				
  	}

  	restar(){
		if(this.state.counter > 0 ){
  			this.setState({counter: this.state.counter - 1});
		}		
  	}
  		<Button bsStyle="primary" onClick={this.sumar}> + </Button>
		<Button bsStyle="primary" onClick={this.restar}> - </Button>
		<p>
			Puntos: {this.state.counter}
		</p>
		<div><b>{this.state.response}</b></div>					
  	*/
