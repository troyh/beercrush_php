function(doc) { 
	if (doc.type=='brewery_meta') 
		emit(doc._id,null); 
}
