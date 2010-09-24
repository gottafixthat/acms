function setCaretToPos (textarea, pos) {
  setSelectionRange(textarea, pos, pos);
}
function insertAt(elementId, replaceString) {
  //inserts given text at selection or cursor position
  textarea = document.getElementById(elementId);
  var toBeReplaced = /text|page|area_name/;//substrings in replaceString to be replaced by the selection if a selection was done
  if (textarea.setSelectionRange) {
    //Mozilla UserAgent Gecko-1.4
    var selectionStart = textarea.selectionStart;
    var selectionEnd = textarea.selectionEnd;
    if (selectionStart != selectionEnd) { // has there been a selection
	var newString = replaceString.replace(toBeReplaced, textarea.value.substring(selectionStart, selectionEnd));
    	textarea.value = textarea.value.substring(0, selectionStart)
                  + newString
                  + textarea.value.substring(selectionEnd);
      setSelectionRange(textarea, selectionStart, selectionStart + newString.length);
    }
    else  {// set caret
       textarea.value = textarea.value.substring(0, selectionStart)
                  + replaceString
                  + textarea.value.substring(selectionEnd);
      setCaretToPos(textarea, selectionStart + replaceString.length);
    }
  }
  else if (document.selection) {
    //UserAgent IE-6.0
    textarea.focus();
    var range = document.selection.createRange();
    if (range.parentElement() == textarea) {
      var isCollapsed = range.text == '';
      if (! isCollapsed)  {
        range.text = replaceString.replace(toBeReplaced, range.text);
        range.moveStart('character', -range.text.length);
        range.select();
      }
	else {
		range.text = replaceString;
	}
    }
  }
  else { //UserAgent Gecko-1.0.1 (NN7.0)
    setSomeElement(elementId, replaceString)
    //alert("don't know yet how to handle insert" + document);
	}
}
function setSomeElement(fooel, foo1) {
	document.getElementById(fooel).value = document.getElementById(fooel).value + foo1;
}
