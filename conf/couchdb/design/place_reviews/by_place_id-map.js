function(doc) {
	if (doc.type=='review' && doc._id.substring(0,13)=='review:place:') 
		emit(doc.place_id,doc);
}
