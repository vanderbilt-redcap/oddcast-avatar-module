<?php
namespace Vanderbilt\OddcastAvatarExternalModule;
require_once 'header.php';
?>

<style>
	#analytics-table_wrapper{
		margin-top: 30px;
		max-width: 700px;
	}

	#analytics-table_wrapper tbody tr:hover{
		background: #c6e4f9;
		cursor: pointer;
	}

	#analytics-table_wrapper .cell-timestamp{
		width: 150px;
	}
</style>

<table id="analytics-table" class="table table-striped table-bordered"></table>

<?php

$sql = "
	select timestamp, record, instrument
	where
		record not like 'external-modules-temporary-record-id-%'
		and message = 'survey complete'
		and instrument is not null
";

$results = $module->queryLogs($sql);

$data = [];
while($row = db_fetch_assoc($results)){
	$data[] = $row;
}

?>

<script>
	$(function() {
		var table = $('#analytics-table')
		var columns = [
			{
				title: "Date & Time",
				data: 'timestamp'
			},
			{
				title: "Record ID",
				data: 'record',
				orderable: false
			},
			{
				title: "Instrument",
				data: 'instrument',
				orderable: false
			}
		]

		columns.forEach(function(column){
			column.sClass = 'cell-' + column.data
		})

		table.DataTable({
			columns: columns,
			order: [[0, 'desc']],
			data: <?=json_encode($data)?>
		})

		table.on('click', 'tbody tr', function(){
			var recordId = $(this).find('.cell-record').html()
			var instrument = $(this).find('.cell-instrument').html()

			location = <?=json_encode($module->getUrl('analytics-instrument.php'))?> + '&record=' + recordId + '&instrument=' + instrument
		})
	})
</script>
