function(doc) { 
	if (doc.type=='review' && doc._id.substring(0,12)=='review:beer:') 
		emit(doc.beer_id,null); 
}