checked=false;
function myselectcb(thisobj, thisclass, var1){
	var o = document.getElementById(thisobj).getElementsByTagName('INPUT');
        if (checked == false){
           checked = true;
        } else {
          checked = false;
        }
	if(o){
		for (i=0; i<o.length; i++){
			if ((o[i].type == 'checkbox') ){
				o[i].checked = checked;
			}
		}
	}
}
