function (doc) {
	if (doc.type=='place' && doc.address.latitude && doc.address.longitude) 
		emit(doc.name,{
			"lat":doc.address.latitude,
			"lon":doc.address.longitude
		});
}
