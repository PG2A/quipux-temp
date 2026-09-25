
function markAll() {
    if(document.form1.elements['checkAll'].checked){
        for(i=1;i<document.form1.elements.length;i++){
            document.form1.elements[i].checked=1;
        }
    } else {
        for(i=1;i<document.form1.elements.length;i++) {
            document.form1.elements[i].checked=0;
        }
    }
}
