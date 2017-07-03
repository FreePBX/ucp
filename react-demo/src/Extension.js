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
    	
		this.analize = this.analize.bind(this);
		props.socket.on("pbx-message", this.analize);
  	}

	analize(msg){	
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

	render() {
		var label = this.props.name + " ("+this.props.ext+")";
		return(
			<Col xs={3} sm={3} md={3}>
				<DropdownButton bsStyle={this.state.style} title={label} id={this.props.ext}>
					<MenuItem eventKey="1">Action 1</MenuItem>
      				<MenuItem eventKey="2">Action 2</MenuItem>
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
