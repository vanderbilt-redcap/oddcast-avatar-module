<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Exception;

const REVIEW_MODE = 'review-mode';
const TURNING_OFF = 'turning-off';
const TEMPORARY_RECORD_ID_TO_DELETE = 'temporary-record-id-to-delete';

class OddcastAvatarExternalModule extends AbstractExternalModule
{
	const SHOWS = [
		2560288 => 'female',
		2560294 => 'female',
		2613244 => 'male',
		2613247 => 'male',
		2613251 => 'male',
		2613248 => 'female',
		2613253 => 'female',
		2613256 => 'male',
		2613261 => 'female',
		2613263 => 'male',
		2613264 => 'female',
		2613267 => 'male',
	];

	function redcap_survey_page($project_id, $record, $instrument)
	{
		$this->loadAvatar($project_id, $record, $instrument);
		$this->removeParentWindowLogEntry();
	}

	// The iFrame created by this module causes the analytics module to insert duplicate survey page load logs,
	// so we remove the survey page load log for the temporary record id used by the parent window.
	// We only want to keep the version of this log saved by the iFrame.
	function removeParentWindowLogEntry()
	{
		$temporaryRecordIdToRemove = db_escape($_GET[TEMPORARY_RECORD_ID_TO_DELETE]);

		if(empty($temporaryRecordIdToRemove)){
			return;
		}

		$parts = explode('-', $temporaryRecordIdToRemove);
		$time = $parts[5];

		if($time < time()-120){
			// Do not respect requests to delete older logs.  This may be a malicious request.
			return;
		}
		else if($time > time()){
			// Ignore requests to delete future logs.  Something must have gone wrong
			return;
		}

		$this->query("delete from redcap_external_modules_log where record = '$temporaryRecordIdToRemove' limit 1");
	}

	function redcap_survey_complete($project_id, $record, $instrument)
	{
		$this->loadAvatar($project_id, $record, $instrument);
	}

	function loadAvatar($project_id, $record, $instrument)
	{
		$initializeJavascriptMethodName = 'initializeJavascriptModuleObject';
		$loggingSupported = method_exists($this, $initializeJavascriptMethodName);
		if ($loggingSupported) {
			$this->{$initializeJavascriptMethodName}();
		}

		?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" integrity="sha256-NuCn4IvuZXdBaFKJOAcsU2Q3ZpwbdFisd5dux4jkQ5w=" crossorigin="anonymous" />
		<style>
			.fa{
				font-family: FontAwesome !important; /* Override the REDCap style that prevents FontAwesome from working */
			}
		</style>

		<script src="//cdn.jsdelivr.net/npm/mobile-detect@1.4.1/mobile-detect.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.2.0/js.cookie.min.js" integrity="sha256-9Nt2r+tJnSd2A2CRUvnjgsD+ES1ExvjbjBNqidm9doI=" crossorigin="anonymous"></script>
		<?php
			if(isset($_GET['vorlon'])){
				?><script src="http://<?=$_SERVER['HTTP_HOST']?>:1337/vorlon.js"></script><?php
			}
		?>

		<link rel="stylesheet" href="<?=$this->getUrl('css/style.css')?>">

		<div id="oddcast-wrapper">
			<div class="modal fade text-intro" data-backdrop="static">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-body">
							<p class="top-section">Hello!  Thank you for your interest in volunteering for a research study.  At any time during the consent you can ask a study coordinator for help.  We also have our eStaff team members to guide you through the consent.  Please select an eStaff team member to take you through the consent:</p>
							<div id="oddcast-character-list" class="text-center">
								<?php
								foreach (OddcastAvatarExternalModule::SHOWS as $id => $gender) {
									?><img src="<?=$this->getUrl("images/$id.png")?>" data-show-id="<?=$id?>" class="oddcast-character" /><?php
								}
								?>
							</div>
							<div class="bottom-section">
								<p>If you don't want eStaff help, click the button below.  If you decide later that you want to use eStaff, you can press the <b>Enable eStaff</b> button in the top left corner to bring them back.</p>
								<div class="text-center">
									<button>No thanks, I don’t want eStaff help.</button>
								</div>
							</div>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
			<div class="modal fade timeout" data-backdrop="static">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-body">
							<p>Do you want to continue?  If so, please re-enter the following:</p>
							<div class="text-center">
								<label><?=$this->getTimeoutVerificationLabel($project_id)?></label>
								<input>
							</div>
							<div>
								<button class="restart">Open a New Survey</button>
								<button class="continue">Continue</button>
							</div>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
			<div id="oddcast-sidebar">
				<button id="oddcast-minimize-avatar">Disable eStaff</button>
				<button id="oddcast-maximize-avatar">Enable eStaff</button>
				<div id='oddcast-avatar' >
					<div id="oddcast-controls">
						<i class="fa fa-play-circle tippy" title="Click this icon to play the<br>message for this page." data-tippy-arrow="true" data-tippy-trigger="manual" data-tippy-offset="-50, 0" data-tippy-theme="light inline-popups"></i>
						<i class="fa fa-user"></i>
					</div>
					<script type="text/javascript" src="//vhss-d.oddcast.com/vhost_embed_functions_v2.php?acc=6267283&js=1"></script>
					<script type="text/javascript">
						(function(){
							if(window.frameElement){
								// We're inside an iFrame.  Return without initializing the avatar since it is already displayed in the parent page.
								return;
							}

							AC_VHost_Embed(6267283, 300, 400, '', 1, 1, <?=array_keys(OddcastAvatarExternalModule::SHOWS)[0]?>, 0, 1, 0, '709e320dba1a392fa4e863ef0809f9f1', 0);
						})()
					</script>
				</div>
			</div>
			<div id="oddcast-content"></div>
			<div id="oddcast-overlay">
				Please rotate the screen!
			</div>
		</div>

		<script type="text/javascript" src="<?=$this->getUrl('js/OddcastAvatarExternalModule.base.js')?>"></script>

		<script>
			$(function(){
				<?php
				$currentPageNumber = $_GET['__page__'];

				$femaleVoice = $this->getProjectSetting('voice');
				if(empty($femaleVoice)){
					$femaleVoice = '3,3';
				}

				$maleVoice = $this->getProjectSetting('male-voice');
				if(empty($maleVoice)){
					$maleVoice = '3,2';
				}
				?>

				OddcastAvatarExternalModule.settings = <?=json_encode([
					'voices' => [
						'female' => explode(',', $femaleVoice),
						'male' => explode(',', $maleVoice),
					],
					'shows' => OddcastAvatarExternalModule::SHOWS,
					'isInitialLoad' => $_SERVER['REQUEST_METHOD'] == 'GET',
					'avatarDisabled' => $this->getProjectSetting('disable'),
					'reviewModeEnabled' => $this->isReviewModeEnabled($instrument),
					'reviewModeCookieName' => REVIEW_MODE,
					'reviewModeTurningOffValue' => TURNING_OFF,
					'pageMessage' => $this->getPageMessage($instrument, $currentPageNumber),
					'currentPageNumber' => $currentPageNumber,
					'messagesForValues' => $this->getSubSettings('messages-for-field-values'),
					'instrument' => $instrument,
					'publicSurveyUrl' => $this->getPublicSurveyUrl(),
					'timeout' => $this->getProjectSetting('timeout'),
					'restartTimeout' => $this->getProjectSetting('restart-timeout'),
					'timeoutVerificationFieldName' => $this->getTimeoutVerificationFieldName(),
					'loggingSupported' => $loggingSupported,
					'temporaryRecordIdFieldName' => TEMPORARY_RECORD_ID_TO_DELETE,
					'speechRate' => $this->getProjectSetting('speech-rate')
				])?>

				var jsObjectUrl
				if(window.frameElement){
					jsObjectUrl = <?=json_encode($this->getUrl('js/OddcastAvatarExternalModule.iframe.js'))?>
				}
				else{
					jsObjectUrl = <?=json_encode($this->getUrl('js/OddcastAvatarExternalModule.parent.js'))?>
				}

				$.getScript(jsObjectUrl)
			})
		</script>
		<?php
	}

	private function getPageMessage($instrument, $currentPageNumber)
	{
		foreach($this->getSubSettings('page-messages') as $settingGroup){
			$forms = $settingGroup['page-message-forms'];

			// Remove the empty string form if the user hasn't selected anything.
			$forms = array_filter($forms);

			$formMatches = empty($forms) || in_array($instrument, $forms);

			$pageNumberMatches = $settingGroup['page-number'] == $currentPageNumber;

			if($formMatches && $pageNumberMatches){
				return $settingGroup['page-message'];
			}
		}

		return '';
	}

	function redcap_every_page_before_render()
	{
		if (!$this->isSurveyPage()) {
			return false;
		}

		?>
		<style>
			/*
				This loading overlay prevents some confusing "flashes" of partially loaded content.
				Simulate slower connections via Chrome's developer tools to reproduce this.
				It will be most noticeable on initial page load when the content is replaced with the iframe,
				and when exiting review mode from page three or greater.
		    */
			#oddcast-loading-overlay{
				background: #e8e8e8; /* This color is used to make transitions between pages less harsh */
				height: 100vh;
				width: 100vw;
				position: fixed;
				top: 0px;
				left: 0px;
				z-index: 9999999;
			}
		</style>
		<div id="oddcast-loading-overlay"></div>
		<?php

		$reviewMode = @$_COOKIE[REVIEW_MODE];
		if (!$this->isReviewModeEnabled() || is_null($reviewMode)) {
			return;
		}

		global $Proj;
		foreach ($_POST as $fieldName => $value) {
			$checkboxPrefix = '__chk__';
			if (strpos($fieldName, $checkboxPrefix) === 0) {
				$fieldName = substr($fieldName, strlen($checkboxPrefix));

				$parts = explode('_', $fieldName);
				$parts = array_slice($parts, 0, -2);  // Remove the "_RC_#" suffix
				$fieldName = implode('_', $parts);
			}

			if (!isset($Proj->metadata[$fieldName])) {
				continue;
			}

			$field = &$Proj->metadata[$fieldName];
			if ($field) {
				// Trick REDCap into thinking this field is hidden, just for this request.
				// This will allow us the skip required fields and navigate freely between pages.
				$field['misc'] = '@HIDDEN';
			}
		}
	}

	private function isReviewModeEnabled($instrument = null)
	{
		if (!$this->getProjectSetting('enable-review-mode')) {
			return false;
		}

		$reviewModeForms = array_filter($this->getProjectSetting('review-mode-forms'));
		if (!empty($instrument) && !empty($reviewModeForms)) {
			if (!in_array($instrument, $reviewModeForms)) {
				return false;
			}
		}

		return true;
	}

	private function getTimeoutVerificationFieldName(){
		return $this->getProjectSetting('timeout-verification-field');
	}

	private function getTimeoutVerificationLabel($project_id){
		$fieldName = $this->getTimeoutVerificationFieldName();
		return $this->getFieldLabel($project_id, $fieldName);
	}

	// This method now exists in the External Modules core code, but is duplicated here for compatibility with older REDCap versions.
	public function getFieldLabel($project_id, $fieldName){
		$dictionary = \REDCap::getDataDictionary($project_id, 'array', false, [$fieldName]);
		return $dictionary[$fieldName]['field_label'];
	}
	
	// This method now exists in the External Modules core code, but is duplicated here for compatibility with older REDCap versions.
	public function getPublicSurveyUrl(){
		$instrumentNames = \REDCap::getInstrumentNames();
		$formName = db_real_escape_string(key($instrumentNames));

		$sql ="
			select h.hash from redcap_surveys s join redcap_surveys_participants h on s.survey_id = h.survey_id
			where form_name = '$formName' and participant_email is null
		";

		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		$hash = @$row['hash'];

		return APP_PATH_SURVEY_FULL . "?s=$hash";
	}

	public function validateSettings($settings){
		$pageNumbers = $settings['page-number'];
		$uniquePageNumbers = array_unique($pageNumbers);

		if(count($pageNumbers) != count($uniquePageNumbers)){
			return "Multiple page messages for the same page number are not currently supported.";
		}
	}

	public function isSurveyPage()
	{
		$url = $_SERVER['REQUEST_URI'];

		return strpos($url, '/surveys/') === 0 &&
			strpos($url, '__passthru=DataEntry%2Fimage_view.php') === false; // Prevent hooks from firing for survey logo URLs (and breaking them).
	}

	public function getQueryLogsSql($sql)
	{
		$sql = parent::getQueryLogsSql($sql);

		// Remove the current module restriction.
		// On 9/21/18 the ability to override the module id clause was added to the framework.
		// We should make sure whatever REDCap version that change makes it into is deployed on UAB's servers before refactoring this to use this new feature.
		$sql = str_replace("redcap_external_modules_log.external_module_id = (SELECT external_module_id FROM redcap_external_modules WHERE directory_prefix = 'vanderbilt_oddcast-avatar') and", '', $sql);

		return $sql;
	}

	public function getShowDetails($desiredShowId)
	{
		$showNumber = 1;
		foreach(OddcastAvatarExternalModule::SHOWS as $showId=>$showDetails){
			if($showId == $desiredShowId){
				return [$showNumber, $showDetails];
			}

			$showNumber++;
		}

		throw new Exception("Show ID not found: $desiredShowId");
	}

	public function getTimePeriodString($seconds)
	{
		$minutes = (int)($seconds/60);

		$suffix = '';
		if($minutes > 0){
			$timePeriodNumber = $minutes;
			$timePeriodWord = 'minute';
			$suffix = ', ' . $this->getTimePeriodString($seconds%60);
		}
		else{
			$timePeriodNumber = $seconds;
			$timePeriodWord = 'second';
		}

		if($timePeriodNumber == 0 || $timePeriodNumber > 1){
			$timePeriodWord .= 's';
		}

		return "$timePeriodNumber $timePeriodWord" . $suffix;
	}

	private function testGetTimePeriodString()
	{
		$assert = function($expected, $seconds){
			$actual = $this->getTimePeriodString($seconds);
			if($expected !== $actual){
				throw new Exception("Expected '$expected' but got '$actual'!");
			}
		};

		$assert('0 seconds', 0);
		$assert('1 second', 1);
		$assert('2 seconds', 2);
		$assert('59 seconds', 59);
		$assert('1 minute, 0 seconds', 60);
		$assert('1 minute, 1 second', 61);
		$assert('1 minute, 2 seconds', 62);
		$assert('1 minute, 59 seconds', 60+59);
		$assert('2 minutes, 0 seconds', 60*2);
	}

	public function runReportUnitTests()
	{
		$this->testGetTimePeriodString();
	}

	public function analyzeSurvey($record, $instrument)
	{
		$getLogs = function ($whereClauses) use ($record, $instrument){
			$sql = "
				select
					log_id,
					" . TIMESTAMP_COLUMN . "
				where
					record = '$record'
					and instrument = '$instrument'
					and $whereClauses
				order by log_id asc
			";

			$result = $this->queryLogs($sql);

			$logs = [];
			while($row = db_fetch_assoc($result)){
				$logs[] = $row;
			}

			return $logs;
		};

		$pageOneLogs = $getLogs("
			message = 'survey page loaded'
			and page = 1
		");

		$firstSurveyLog = $pageOneLogs[0];

		$surveyCompleteLogs = $getLogs("
			message = 'survey complete'
		");

		$count = count($surveyCompleteLogs);
		if($count !== 1){
			throw new Exception("Expected 1 but found $count survey complete logs for record $record and instrument $instrument.");
		}

		$surveyCompleteLog = $surveyCompleteLogs[0];

		$sql = $this->getQueryLogsSql("
			select
				" . TIMESTAMP_COLUMN . ",
				message,
				page,
				`show id`
			where
				record = '$record'
				and log_id >= {$firstSurveyLog['log_id']}
				and log_id <= {$surveyCompleteLog['log_id']}
		");

		// The table name prefix is required until EM framework commit a386287 is in place on UAB's servers.
		$sql .= " order by redcap_external_modules_log.log_id asc ";

		$results = $this->query($sql);

		$firstReviewModeLog = null;
		$currentAvatar = [];
		$avatarUsagePeriods = [];
		while($log = db_fetch_assoc($results)){
			$this->handleAvatarMessages($log, $currentAvatar, $avatarUsagePeriods);

			if($log['message'] === 'review mode exited'){
				$firstReviewModeLog = $firstSurveyLog;
				$firstSurveyLog = $log;
			}
		}

		return [
			$firstReviewModeLog,
			$firstSurveyLog,
			$surveyCompleteLog,
			$avatarUsagePeriods
		];
	}

	private function handleAvatarMessages($log, &$currentAvatar, &$avatarUsagePeriods)
	{
		$timestamp = $log['timestamp'];
		$message = $log['message'];

		$characterSelected = $message === 'character selected';
		$avatarDisabled = $message === 'avatar disabled';
		$avatarEnabled = $message === 'avatar enabled';

		if($characterSelected || $avatarEnabled){
			if($characterSelected){
				$showId = $log['show id'];
				if($showId === $currentAvatar['show id']) {
					// The same avatar that was already displayed was selected.  Ignore this event.
					return;
				}

				if(empty($currentAvatar)){
					// This is when the avatar was selected initially.  There is no previous avatar, so no need to set an end time.
				}
				else{
					$currentAvatar['end'] = $timestamp;
				}
			}
			else{ // avatar enabled
				// We're re-enabling the avatar that was previously displayed, so use the same show id.
				$showId = $currentAvatar['show id'];
			}

			unset($currentAvatar); // Prevent references from being mixed up
			$currentAvatar = [
				'show id' => $showId,
				'start' => $timestamp,
			];

			$avatarUsagePeriods[] = &$currentAvatar;
		}
		else if($avatarDisabled){
			$currentAvatar['end'] = $timestamp;
		}
	}
}
