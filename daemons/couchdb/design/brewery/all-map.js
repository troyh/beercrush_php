function(doc) { 
	if (doc.type=='brewery') 
		emit(doc.name,null); 
}
