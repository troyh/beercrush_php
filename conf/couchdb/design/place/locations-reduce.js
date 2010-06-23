function(keys,values,rereduce) {
	var total=0;
	var total_lat=0;
	var total_lon=0;
	for (var i=0;i<values.length;++i) {
		total+=values[i][0];
		total_lat+=values[i][1] * values[i][0];
		total_lon+=values[i][2] * values[i][0];
	}
	return [total,total_lat/total,total_lon/total];
}
