function (doc) {
	if ((doc.type=='place' || doc.type=='brewery') && doc.address.country && doc.address.state && doc.address.city) { 
		emit([doc.address.country,doc.address.state,doc.address.city],1);
	}
}
