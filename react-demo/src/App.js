import React, { Component } from 'react';
import { Panel } from 'react-bootstrap';
import './App.css';
import Extension from './Extension';

class App extends Component {        
    constructor(props){
        super(props);
        this.state = {
            extensions: [] // Empty array
        };

        var d = new Date();
        var msg = {actionid: d.getTime()};
        props.socket.emit("Action-ExtensionStateList", msg);
    
        this.fill_extensions = this.fill_extensions.bind(this);
        props.socket.on("Event-ExtensionStateListComplete", this.fill_extensions);
    }    

    fill_extensions(extensions)
    {
        var from_pbx = [];

        for (let ext of extensions) {
                console.log(ext);
                // It's important in react the Component to have and id
                ext.id = ext.ext;
                from_pbx.push(ext);
        }
        this.setState({extensions: from_pbx});
    }

    render(){
        return(            
			<Panel header="Extensions">
            	{this.state.extensions.map(function(extension, index) {            		
                	return (					
                	<Extension
                    	name={extension.name}
                    	ext={extension.ext}
                    	key={extension.id}
                    	socket={this.props.socket} />
                	);
                	}.bind(this))}
			</Panel>
        );    
    }    
}

export default App;