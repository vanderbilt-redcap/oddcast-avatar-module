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

<script src="https://cdn.jsdelivr.net/npm/js-cookie@rc/dist/js.cookie.min.js"></script>

<style>
	#center{
		flex-basis: auto;
		flex-grow: 0;
		width: auto;
	}

	table.stats{
		width: auto;
	}

	table.stats th{
		vertical-align: bottom;
	}
	
	#analytics-table_wrapper button{
		border: 1px solid gray;
    	border-radius: 4px;
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
list($stats, $errors) = $module->getAggregateStats($sessions);

if(!empty($errors)){
	$errorHtml = '<p>' . implode('</p><p>', $errors) . '</p>';
	?>
	<br>
	<div class='red'>
		Page stats were empty for some sessions.
		This is likely caused by inconsistent data, perhaps because the Analytics module was not enabled when it should have been.
		<span>
			<a href='#' style='text-decoration: underline'>Click here</a> to see the problematic sessions.
			<script>
				$(document.currentScript.parentElement).find('a').click(function(){
					simpleDialog(<?=json_encode($errorHtml)?>, 'Sessions With Empty Page Stats')
				})
			</script>
		</span>
		If you do not know the cause for this, please report this error.
	</div>
	<?php
}

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
			'Average Avatar Enabled Time',
		], $stats['instruments']);
		?>
	</tr>
	<?php

	foreach($stats['instruments'] as $instrumentName=>$instrument){
		$instrumentDisplayName = $module->getInstrumentDisplayName($instrumentName);

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

<?php

$sessionTableData = [];
foreach($sessions as $session){
	$logs = $session['logs'];
	$firstLog =$logs[0];
	$lastLog = $logs[count($logs)-1];

	$sessionTime = $lastLog['timestamp'] - $firstLog['timestamp'];
	
	list($sessionStats, $errors) = $module->getAggregateStats([$session]);
	$instrument = array_keys($sessionStats['instruments'])[0];

	$timePerPage = '';
	$avatarEnabledTime = 0;
	foreach($sessionStats['instruments'][$instrument]['pages'] as $pageNumber=>$details){
		$timePerPage .= "Page $pageNumber - " . $module->getTimePeriodString($details['seconds']) . '<br>';
		$avatarEnabledTime += $details['avatarSeconds'];
	}

	$videoTime = 0;
	foreach($sessionStats['videos'] as $fieldName=>$details){
		$videoTime +=  $details['playTime'];
	}

	$popupViewCount = 0;
	foreach($sessionStats['popups'] as $fieldName=>$details){
		$popupViewCount +=  $details['viewCount'];
	}

	$session['instrument'] = $module->getInstrumentDisplayName($session['instrument']);
	
	$sessionTableData[] = array_merge($session, [
		'sessionTime' => [
			'display' => $module->getTimePeriodString($sessionTime),
			'sort' => $sessionTime,
		],
		'timePerPage' => $timePerPage, 
		'avatarEnabledTime' => [
			'display' => $module->getTimePeriodString($avatarEnabledTime),
			'sort' => $avatarEnabledTime,
		],
		'videoTime' => [
			'display' => $module->getTimePeriodString($videoTime),
			'sort' => $videoTime,
		],
		'popupViewCount' => $popupViewCount,
	]);
}

?>

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
				title: 'Record',
				data: 'record'
			},
			{
				title: 'Instrument',
				data: 'instrument',
			},
			{
				title: 'Session Time',
				data: 'sessionTime',
				type: 'num',
				render: {
					_: 'display',
					sort: 'sort'
				}
			},
			{
				title: 'Time Per Page',
				data: 'timePerPage',
				orderable: false
			},
			{
				title: 'Avatar Enabled Time',
				data: 'avatarEnabledTime',
				type: 'num',
				render: {
					_: 'display',
					sort: 'sort'
				}
			},
			{
				title: 'Video Play Time',
				data: 'videoTime',
				type: 'num',
				render: {
					_: 'display',
					sort: 'sort'
				}
			},
			{
				title: 'Popup<br>View Count',
				data: 'popupViewCount',
			},
			{
				title: 'Actions',
				data: 'logs',
				orderable: false,
				render: function(logs){
					var firstLogId = ''
					var lastLogId = ''
					var firstLogEventId = ''
					var lastLogEventId = ''

					logs.forEach((log) => {
						var parts = log.log_id.split('.')
						var log_id = parts[0]
						var log_event_id = parts[1]

						if(log_event_id){
							if(firstLogEventId === ''){
								firstLogEventId = log_event_id
							}

							lastLogEventId = log_event_id
						}
						else{
							if(firstLogId === ''){
								firstLogId = log_id
							}

							lastLogId = log_id
						}
					})
					
					return "<a href='<?=$module->getUrl('analytics-session.php')?>&first-log-id=" + firstLogId + "&last-log-id=" + lastLogId + "&first-log-event-id=" + firstLogEventId + "&last-log-event-id=" + lastLogEventId + "' target='_blank'><button>View Session Report</button></a>"
				}
			}
		]

		columns.forEach(function(column){
			column.sClass = 'cell-' + column.data
		})

		table.DataTable({
			columns: columns,
			order: [[0, 'desc']],
			data: <?=json_encode($sessionTableData)?>,
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

		var alternateEventsCookieName = <?=json_encode(ALTERNATE_EVENTS_COOKIE_NAME)?>;
		var alternateEventsChecked = ''
		if(Cookies.get(alternateEventsCookieName) === 'true'){
			alternateEventsChecked = 'checked'
		}

		var alternateEventsCheckbox = $('<input type="checkbox" style="vertical-align: -1px;" ' + alternateEventsChecked + '>')
		alternateEventsCheckbox.change(function(){
			$('body').fadeOut()
			Cookies.set(alternateEventsCookieName, alternateEventsCheckbox.is(':checked'))
			location.reload()
		})

		var wrapper = $('<div style="clear: both; margin-bottom: 9px; margin-left: 5px" />')
		wrapper.append
		wrapper.append(alternateEventsCheckbox)
		wrapper.append('<label style="display: inline"> <b>Alternate Analytics Event Detection</b> - This setting does not support all features and should only be used on time periods when the Analytics module was accidentally left disabled.</label>')
		table.before(wrapper)
	})
</script>

<?php

require_once 'footer.php';