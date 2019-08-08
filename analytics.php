<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use REDCap;

require_once 'header.php';

$module->runReportUnitTests();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.0/dist/loadingoverlay.min.js" integrity="sha384-MySkuCDi7dqpbJ9gSTKmmDIdrzNbnjT6QZ5cAgqdf1PeAYvSUde3uP8MGnBzuhUx" crossorigin="anonymous"></script>

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
	<label>Start Date:</label>
	<input class="flatpickr" name="start-date" value="<?=$module->getStartDate()?>">
	<br>
	<label>End Date:</label>
	<input class="flatpickr" name="end-date" value="<?=$module->getEndDate()?>">
</form>

<table id="analytics-table" class="table table-striped table-bordered"></table>

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
			data: <?=json_encode($module->getRecords())?>,
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
							"url": <?=json_encode($module->getUrl('csv.php'))?> + '&start-date=' + startDate + '&end-date' + endDate,
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

		table.on('click', 'tbody tr', function(){
			var recordId = $(this).find('.cell-record').html()
			var instrument = $(this).find('.cell-instrument').html()

			location = <?=json_encode($module->getUrl('analytics-instrument.php'))?> + '&record=' + recordId + '&instrument=' + instrument
		})

		flatpickr('input.flatpickr')

		var customControlsForm = $('#custom-controls')
		customControlsForm.find('input').change(function(){
			customControlsForm.submit()
			$('body').fadeOut() // poor man's loading indicator
		})
	})
</script>
