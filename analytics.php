<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use REDCap;

require_once 'header.php';

$module->runReportUnitTests();
?>

<div class="projhdr">Avatar Analytics</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.0/dist/loadingoverlay.min.js" integrity="sha384-MySkuCDi7dqpbJ9gSTKmmDIdrzNbnjT6QZ5cAgqdf1PeAYvSUde3uP8MGnBzuhUx" crossorigin="anonymous"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>

<style>
	#center{
		/* prevent datatables page selector from hanging off to the side */
		flex-grow: 0;
		min-width: 650px;
	}

	table.stats{
		width: auto;
	}
	
	#analytics-table_wrapper{
		max-width: 900px;
	}

	#analytics-table_wrapper button{
		border: 1px solid gray;
    	border-radius: 4px;
	}

	#analytics-table_wrapper .cell-timestamp{
		width: 150px;
	}

	label{
		display: inline-block;
		min-width: 75px;
		text-align: right;
		margin-right: 5px;
	}

	input.flatpickr{
		width: 97px;
		padding: 0px 5px;
	}

	.flatpickr-current-month{
		padding-top: 4px;
	}

	.flatpickr-current-month .flatpickr-monthDropdown-months{
		height: 27px;
	}

	.dataTables_length label{
		margin-top: 3px;
		margin-left: 10px;
	}
	
	#characters-modal .modal-dialog{
		max-width: 650px;
		text-align: center;
		font-size: 16px;
		font-weight: 500;
	}

	#characters-modal .character-wrapper{
		display: inline-block;
		margin: 10px 0px;
	}

	#characters-modal .oddcast-character{
		width: 125px;
		margin: 5px 5px;
		display: inline-block;
		border: 1px solid #dedede;
		border-radius: 4px;
		box-shadow: 0px 1px 4px -1px #cacaca;
	}
</style>

<div class="modal" tabindex="-1" role="dialog" id="characters-modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">List of Avatar Characters With IDs</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?php
		foreach (OddcastAvatarExternalModule::$SHOWS as $id => $gender) {
			?>
			<div class="character-wrapper">
				<img src="<?=$module->getUrl("images/$id.png")?>" data-show-id="<?=$id?>" class="oddcast-character" />
				<div><?=$id?></div>
			</div>
			<?php
		}
		?>
      </div>
    </div>
  </div>
</div>

<br>

<form id="custom-controls" method="post">
	<label><b>Start Date:</b></label>
	<input class="flatpickr" name="start-date" value="<?=$module->getStartDate()?>">
	<br>
	<label><b>End Date:</b></label>
	<input class="flatpickr" name="end-date" value="<?=$module->getEndDate()?>">
</form>

<?php

$sessions = $module->getSessionsForDateParams();
$stats = $module->getAggregateStats($sessions);

$echoTableCells = function($items, $header = false){
	if($header){
		$tagName = 'th';
	}
	else{
		$tagName = 'td';
	}

	foreach($items as $item){
		echo "<$tagName>" . $item . "</$tagName>";
	}
};

$echoTableHeaders = function($headers, $data) use ($echoTableCells){
	$echoTableCells($headers, true);
	if(empty($data)){
		$headerCount = count($headers);
		echo "<tr>";
		echo "<td colspan='$headerCount'>None</td>";
		echo "</tr>";
	}
}

?>

<br>
<p><?=count($stats['records'])?> record(s) were found in the specified date range.</p>
<br>
<h6>Instrument & Page Metrics</h6>
<table class="table table-striped table-bordered stats">
	<tr>
		<?php
		$echoTableHeaders([
			'Instrument',
			'Page Number(s)',
			'Record Count',
			'Average Time<br>Spent Per Record',
			'Average Time<br>An Avatar Was Enabled',
		], $stats['instruments']);
		?>
	</tr>
	<?php

	foreach($stats['instruments'] as $instrumentName=>$instrument){
		$instrumentDisplayName = \REDCap::getInstrumentNames($instrumentName);
		$instrumentRowData = [];

		foreach($instrument['pages'] as $pageNumber=>$page){
			$pageRecordCount = count($page['records']);
			$instrumentRowData[] = [
				$pageNumber,
				$pageRecordCount,
				$module->getTimePeriodString($page['seconds']/$pageRecordCount),
				$module->getTimePeriodString($page['avatarSeconds']/$pageRecordCount),
			];
		}

		$instrumentRecordCount = count($instrument['records']);
		$instrumentRowData[] = [
			'All',
			$instrumentRecordCount,
			$module->getTimePeriodString($instrument['seconds']/$instrumentRecordCount),
			$module->getTimePeriodString($instrument['avatarSeconds']/$instrumentRecordCount)
		];

		$rowCount = count($instrumentRowData);
		for($i=0; $i<$rowCount; $i++){
			echo "<tr>";

			if($i === 0){
				echo "<td rowspan='$rowCount'>$instrumentDisplayName</td>";
			}

			$lastRow = $i === $rowCount-1;
			$echoTableCells($instrumentRowData[$i], $lastRow);

			echo "</tr>";
		}
	}
	?>
</table>
<br>
<h6>Video Metrics</h6>
<table id='stats' class="table table-striped table-bordered stats">
	<tr>
		<?php
		$echoTableHeaders([
			'Field Name',
			'Record Count',
			'Average Play Time',
			'Average Number of Times Played'
		], $stats['videos']);
		?>
	</tr>
	<?php

	foreach($stats['videos'] as $fieldName=>$details){
		echo "<tr>";
		$recordCount = count($details['records']);
		$echoTableCells([
			$fieldName,
			$recordCount,
			$module->getTimePeriodString($details['playTime']/$recordCount),
			round($details['playCount']/$recordCount, 2),
		]);
		echo "</tr>";
	}
	?>
</table>
<br>
<br>
<h6>Popup Metrics</h6>
<table id='stats' class="table table-striped table-bordered stats">
	<tr>
		<?php
		$echoTableHeaders([
			'Popup Link Text',
			'Total View Count',
			'Average Views Per Record',
		], $stats['popups']);
		?>
	</tr>
	<?php

	foreach($stats['popups'] as $text=>$details){
		echo "<tr>";
		$recordCount = count($details['records']);
		$viewCount = $details['viewCount'];
		$echoTableCells([
			$text,
			$viewCount,
			round($viewCount/$recordCount, 2),
		]);
		echo "</tr>";
	}
	?>
</table>
<br>
<br>
<h6>Sessions</h6>
<table id="analytics-table" class="table table-striped table-bordered"></table>

<script>
	$(function() {
		var table = $('#analytics-table')
		var columns = [
			{
				title: "Session Start<br>Date & Time",
				data: 'timestamp',
				render: function(data){
					return moment(data*1000).format('YYYY-MM-DD HH:mm:ss')
				}
			},
			{
				title: "Record ID",
				data: 'record',
			},
			{
				title: "Instrument",
				data: 'instrument',
			},
			{
				title: "Actions",
				data: 'logs',
				orderable: false,
				render: function(logs){
					var firstLog = logs[0]
					var lastLog = logs.slice(-1)[0]
					return "<a href='<?=$module->getUrl('analytics-session.php')?>&first-log-id=" + firstLog.log_id + "&last-log-id=" + lastLog.log_id + "' target='_blank'><button>View Session Report</button></a>"
				}
			}
		]

		columns.forEach(function(column){
			column.sClass = 'cell-' + column.data
		})

		table.DataTable({
			columns: columns,
			order: [[0, 'desc']],
			data: <?=json_encode($sessions)?>,
			searching: false,
			dom: 'Blftip',
			buttons: [
				{
					text: 'Export In Repository Format',
					action: function (e, dt, node, config) {
						var startDate = $('input[name=start-date]').val()
						var endDate = $('input[name=end-date]').val();

						$.LoadingOverlay('show')

						$.ajax({
							"url": <?=json_encode($module->getUrl('csv.php'))?> + '&start-date=' + startDate + '&end-date=' + endDate,
							"data": dt.ajax.params(),
							"success": function(res, status, xhr) {
								var csvData = new Blob([res], {type: 'text/csv;charset=utf-8;'});
								var csvURL = window.URL.createObjectURL(csvData);
								var tempLink = document.createElement('a');
								var filename = <?=json_encode(REDCap::getProjectTitle())?> + " - Avatar Analytics - " + startDate + " to " + endDate + '.csv'
								tempLink.href = csvURL;
								tempLink.setAttribute('download', filename);
								tempLink.click();

								$.LoadingOverlay('hide')
							}
						});
					}
				},
				{
					text: 'View Avatar Character IDs',
					action: function (e, dt, node, config) {
						$('#characters-modal').modal('show');
					}
				}
			]
		})

		flatpickr('input.flatpickr')

		var customControlsForm = $('#custom-controls')
		customControlsForm.find('input').change(function(){
			customControlsForm.submit()
			$('body').fadeOut() // poor man's loading indicator
		})
	})
</script>

<?php

require_once 'footer.php';