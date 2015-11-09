//simple xhr abstraction.
function xhr(url, callback, type, params) {
	type = type || "get";
	var xhr = new XMLHttpRequest();
	xhr.open(type, url);
	xhr.onreadystatechange = function(){
		if(this.readyState == 4 && this.status == 200){
			callback(JSON.parse(this.responseText));
		}
	}
	xhr.send(params);
}
//sets the value to check if the page has changed.
var page = window.location.hash
function hideroutes(){
	var routes = document.getElementsByClassName("app");
	for (var i = 0; i <routes.length; i++){
		routes[i].style.display = "none";
	}
}
//function to route to a path.
function route(path){
	var parts = path.substring(1).split("../");
	var root = parts[0];
	parts.splice(0,1);
	hideroutes();
	try{
		document.getElementById(root).style.display = "block";
		//invoke controller
		window[root](parts); 
	}catch(e){
		document.getElementById('default').style.display = "flex";
		default_controller();
	}
}
function emptyElement(element){
	var child, reserve = [];
	while (element.lastChild){
		try{
			child = element.lastChild.classList.contains('no-hide');
			if (child){
				reserve.push(element.lastChild);
			}
			element.removeChild(element.lastChild);
			child = false;
		}catch(e){
			console.log(e)
			element.removeChild(element.lastChild);
		}
	}
	for (e in reserve)
		element.appendChild(reserve[e]);
}
function showClients(){
	var clients = document.getElementById('clients');
	emptyElement(clients);
	xhr('/api/clients/list', function(response){
		var ul = document.createElement('ul'), li, a, span;
		for (var i=0; i<response.length; i++){
			li = document.createElement('li');
			a = document.createElement('a');
			span = document.createElement('span');
			a.href="#/clients/"+response[i].abbr;
			a.appendChild(document.createTextNode(response[i].name));
			li.appendChild(a);
			li.appendChild(document.createTextNode(" "));
			span.appendChild(document.createTextNode(response[i].abbr))
			li.appendChild(span);
			ul.appendChild(li);
		}
		clients.appendChild(ul);
	});
}