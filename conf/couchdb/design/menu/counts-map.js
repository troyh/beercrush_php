function(doc) { 
	if (doc.type=='menu') { 
		emit(1,1);
		emit(2,doc.items.length); 
	}
}
