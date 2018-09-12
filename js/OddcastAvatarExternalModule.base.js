// This object is defined globally so it can be used in other modules (like Inline Descriptive Pop-ups).
var OddcastAvatarExternalModule = {
	initializeBase: function(){
		OddcastAvatarExternalModule.listenForMessages()
	},
	showBody: function(){
		// The body is hidden in the before render hook for prevent any unnecessary "flashes" of content being rearranged/reloaded.
		// This method is used to make it visible again.
		$('body').css('visibility', 'visible')
	},
	addProperties: function(properties){
		for(var name in properties){
			if(OddcastAvatarExternalModule[name]){
				throw "Could not add property with name '" + name + "' because it already exists!"
			}

			OddcastAvatarExternalModule[name] = properties[name]
		}
	},
	callOnTarget: function(target, arguments){
		// Convert arguments to an array
		arguments = Array.prototype.slice.call(arguments)

		target.postMessage({
			oddcastMethod: arguments.shift(),
			arguments: arguments
		}, location.protocol + '//' + location.host)
	},
	listenForMessages: function(){
		// Adapted from https://davidwalsh.name/window-iframe
		var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
		var eventer = window[eventMethod];
		var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

		eventer(messageEvent, function (e) {
			var data = e.data
			var method = data.oddcastMethod
			if(!method){
				return
			}

			OddcastAvatarExternalModule[method].apply(OddcastAvatarExternalModule, data.arguments)
		}, false);
	},
	onActivity: function(action){
		$(document).on('mousemove keydown mousedown touchstart touchmove', action)
	}
}

OddcastAvatarExternalModule.initializeBase()