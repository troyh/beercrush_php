function(doc) {
	if (doc.type=='place') 
		emit(doc.name,null); 
}
