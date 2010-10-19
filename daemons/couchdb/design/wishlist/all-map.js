function(doc) { 
	if (doc.type=='wishlist') 
		emit(doc._id,null); 
}

