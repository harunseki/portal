/**
* Start SadeceRakam()
* Coded By Mustafa OZCAN
* For more information visit to www.mustafaozcan.net
* Version 1.5.1 Release Date Time :23.03.2009 16:30
* Sample Usage Keypress Event:  onkeypress="return SadeceRakam(event,['-']);"
* -First parameter is event, 
* -Second parameter is optional chars array. 
* -if you want to allow special chars , you must use code like this onkeypress="return SadeceRakam(event,['-','/']); 
* Sample Usage Blur Event: onblur="SadeceRakamBlur(event,true)" 
* Blur Event Second Parameter : Clear [Enter Keys] and [WhiteSpaces] in Value
* For Input:  <input type="text" id="txtInput" onkeypress="return SadeceRakam(event);" onblur="SadeceRakamBlur(event,false)" />
* For TextArea : <textarea cols="50" rows="10" id="txtArea" onkeypress="return SadeceRakam(event,['-']);" onblur="SadeceRakamBlur(event,true)" ></textarea>
*/
function SadeceRakam(e,allowedchars){var key=e.charCode==undefined?e.keyCode:e.charCode;if((/^[0-9]+$/.test(String.fromCharCode(key)))||key==0||key==13||isPassKey(key,allowedchars)){return true;}else{return false;}}
function isPassKey(key,allowedchars){if(allowedchars!=null){for(var i=0;i<allowedchars.length;i++){if(allowedchars[i]==String.fromCharCode(key))return true;}}return false;}
function SadeceRakamBlur(e,clear){var nesne=e.target?e.target:e.srcElement;var val=nesne.value;val=val.replace(/^\s+|\s+$/g,"");if(clear)val=val.replace(/\s{2,}/g," ");nesne.value=val;}


function HarfKontrol(e) {
	olay = document.all ? window.event : e;
	tus = document.all ? olay.keyCode : olay.which;
	if(tus>=48&&tus<=57) {
		if(document.all) { olay.returnValue = false; } else { olay.preventDefault(); }
	}
}