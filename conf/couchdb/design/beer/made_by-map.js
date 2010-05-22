function (doc) { 
	if (doc.type=='beer') 
		emit(doc.brewery_id,doc.name); 
}