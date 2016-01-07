if(document.getElementById('cal-prev-month')){
	document.getElementById('cal-prev-month').addEventListener('click', function(){
		var root = document.getElementById('cal-root');
		buildCalendar(buildMonthArray(new Date(root.dataset.year, parseInt(root.dataset.month)-1, 1)));
	});
	document.getElementById('cal-next-month').addEventListener('click', function(){
		var root = document.getElementById('cal-root');
		buildCalendar(buildMonthArray(new Date(root.dataset.year, parseInt(root.dataset.month)+1, 1)));
	});
}
function buildMonthArray(dateObject){
	var	year = dateObject.getFullYear();
	var month = dateObject.getMonth();
	var rows = [];
	var row_index = 0
	rows[0] = [];
	dateObject.setDate(1);
	var offset = dateObject.getDay();
	while (dateObject.getMonth() == month){
		rows[row_index][offset] = dateObject.getDate();
		dateObject.setDate(dateObject.getDate()+1);
		offset++;
		if(offset == 7){
			row_index++;
			rows[row_index] = [];
			offset = 0;
		}
	}
	return {
		'year': year,
		'month': month,
		'cal': rows
	}
}
function buildCalendar(monthOject){
	var cal = monthOject.cal;
	var row;
	var td;
	var root = document.getElementById('cal-root');
	var year = document.getElementById('cal-year');
	emptyElement(year);
	year.appendChild(document.createTextNode(monthOject.year));
	var month = document.getElementById('cal-month');
	emptyElement(month);
	month.appendChild(document.createTextNode(monthOject.month+1));
	var month = monthOject.month;
	root.dataset.month = monthOject.month;
	root.dataset.year = monthOject.year;
	var tbody = document.getElementById('cal-body');
	emptyElement(tbody);
	for(var i = 0; i<cal.length; i++){
		row = document.createElement('tr');
		for(var j = 0; j<7; j++){
			td = document.createElement('td');
			if(cal[i][j] != undefined){
				td.appendChild(document.createTextNode(cal[i][j]));
				td.date = {year: monthOject.year, month: monthOject.month, day: cal[i][j]}
				td.addEventListener('click', function(e){
					var dateString = e.target.date.year+"-"+(e.target.date.month+1)+"-"+e.target.date.day;
					window.cal_target.value = dateString;
					calPopup();
				})
			}
			row.appendChild(td);
		}
		tbody.appendChild(row);
	}
}
function calPopup(e){
	var root = document.getElementById('cal-root');
	if(root.style.display == "block"){
		document.getElementById('cal-root').style.display = "none";
		window.cal_target = null;
		return;
	}else{
		var bodyRect = document.body.getBoundingClientRect(),
			target = document.getElementById(e.target.dataset.target);
			elemRect = target.getBoundingClientRect(),
			offsetTop   = elemRect.top - bodyRect.top,
			offsetLeft  = elemRect.left- bodyRect.left;
		window.cal_target = target;
		setTimeout(function(root){root.style.display = "block";}, 101, root);
		root.style.left = offsetLeft+"px";
		root.style.top = offsetTop+target.clientHeight+"px";
		buildCalendar(buildMonthArray(new Date()));
	}
}