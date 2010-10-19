function (doc) { 
	if (doc.type=='beer') 
		emit(doc.meta.timestamp,doc.name); 
}
