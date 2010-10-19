function(doc) { 
	if (doc.type=='review') 
		emit(doc.user_id,doc); 
}
