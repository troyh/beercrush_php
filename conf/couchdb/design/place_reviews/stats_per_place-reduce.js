function(keys,values,rereduce) {
	var stddev=0.0;
	var total=0;
	var count=0;
	var square_total=0.0;

	values.forEach(function(n) {
		if (rereduce) {
			total+=n.count * n.avg;
			count+=n.count;
			square_total += n.square_total;
		}
		else {
			total+=n;
			count+=1;
			square_total += n * n;
		}
	});

	var variance=(square_total - ((total * total)/count)) / count;
	stddev=Math.sqrt(variance);

	return {
		"count": count,
		"square_total": square_total,
		"avg": total / count,
		"stddev": stddev
	};
}