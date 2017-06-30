import React, { Component } from 'react';
import { Panel } from 'react-bootstrap';
import './App.css';
import Extension from './Extension';

class App extends Component {        
    constructor(props){
        super(props);

        // this.props.socket.emit("pbx-message", "request-pbx-state");
        this.state = {
           extensions: this.props.extensions
        };
    }    

    render(){
        return(            
			<Panel header="Extensions">
            	{this.state.extensions.map(function(ext, index) {            		
                	return (					
                	<Extension
                    	name={ext.name}
                    	lastname={ext.lastname}
                    	ext={ext.ext}
                    	key={ext.id}
                    	socket={this.props.socket} />
                	);
                	}.bind(this))}
			</Panel>
        );    
    }    
}

export default App;

/*
 <div>
    <Extension name="J. P."   lastname="Romero" ext="100" socket={this.props.socket}/>
    <Extension name="Luis"    lastname="Abarca" ext="200" socket={this.props.socket} />
    <Extension name="Alberto" lastname="Santos" ext="300" socket={this.props.socket} />
</div>
*/