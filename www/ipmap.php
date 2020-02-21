<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
	google.load("visualization", "1", {packages:["geochart"]});
	google.setOnLoadCallback(drawChart);
	function drawChart() {

		var data = new google.visualization.DataTable();
		<?php include_once("mapdata.php") ?>

		var container = document.getElementById('map_div');
		var geochart = new google.visualization.GeoChart(container);
		google.visualization.events.addListener(geochart, 'select', function() {
			var selectionIdx = geochart.getSelection()[0].row;
			var countryName = data.getValue(selectionIdx, 0);
			window.open('./search.php?search=' + countryName, '_self');
		});
		geochart.draw(data, {
			colors: ['#ffe6e6','#ff0000'],
			legend: 0
		  });
	}	
</script>