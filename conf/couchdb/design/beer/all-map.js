function(doc) { 
	if (doc.type=='beer') 
		emit(doc.name,null); 
}