function(doc) {
	if (doc.type=='review' && doc._id.substring(0,13)=='review:place:' && doc.rating) 
		emit(doc.place_id,doc.rating);
}
