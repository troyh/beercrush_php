function(doc) { 
	if (doc.type=='user' && doc.email) 
		emit(doc.email,doc.password);    
}
