<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

require_once __DIR__ . '/classes/MockMySQLResult.php';
require_once __DIR__ . '/classes/OddcastAvatarExternalModuleTest.php';

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Exception;
use stdClass;

const REVIEW_MODE = 'review-mode';
const TURNING_OFF = 'turning-off';
const TEMPORARY_RECORD_ID_TO_DELETE = 'temporary-record-id-to-delete';

class OddcastAvatarExternalModule extends AbstractExternalModule
{
	const TIMESTAMP_COLUMN = 'UNIX_TIMESTAMP(timestamp) as timestamp';

	const SECONDS_PER_MINUTE = 60;
	const SECONDS_PER_HOUR = self::SECONDS_PER_MINUTE*60;
	const SECONDS_PER_DAY = self::SECONDS_PER_HOUR*24;

	const SESSION_TIMEOUT = self::SECONDS_PER_HOUR;

	static $SHOWS = [
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

	private $spoofedPrecedingAvatarLogs = null;
	private $debugLogging = null;

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

		<link rel="stylesheet" href="<?=$this->getUrl('css/style.css')?>">

		<div id="oddcast-wrapper" class="hidden">
			<div class="modal text-intro" data-backdrop="static">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-body">
							<p class="top-section">Hello!  Thank you for your interest in volunteering for a research study.  At any time during the consent you can ask a study coordinator for help.  We also have our eStaff team members to guide you through the consent.  Please select an eStaff team member to take you through the consent:</p>
							<div id="oddcast-character-list" class="text-center">
								<?php
								foreach (OddcastAvatarExternalModule::$SHOWS as $id => $gender) {
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
						<i class="fa fa-play-circle"></i>
						<i class="fa fa-pause-circle"></i>
						<i class="fa fa-comment" title="Click this icon to play the message for this page."></i>
						<i class="fa fa-user"></i>
					</div>
					<script type="text/javascript" src="//vhss-d.oddcast.com/vhost_embed_functions_v2.php?acc=6267283&js=1"></script>
					<script type="text/javascript">
						(function(){
							if(window.frameElement){
								// We're inside an iFrame.  Return without initializing the avatar since it is already displayed in the parent page.
								return;
							}

							// In order to prime audio on iOS, be must hook into the play() method BEFORE Oddcast's internal <audio> element is created
							var originalPlay = Audio.prototype.play
							Audio.prototype.play = function(){
								if(!OddcastAvatarExternalModule.isAudioPrimed){
									this.src =  <?=json_encode($this->getUrl('empty.mp3'))?>;
								}

								return originalPlay.apply(this, arguments)
							}

							AC_VHost_Embed(6267283, 300, 400, '', 1, 1, <?=array_keys(OddcastAvatarExternalModule::$SHOWS)[0]?>, 0, 1, 0, '709e320dba1a392fa4e863ef0809f9f1', 0);
						})()
					</script>
				</div>
			</div>
			<div id="oddcast-content"></div>
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

				$avatarDisabled = $this->getProjectSetting('disable');
				if(!$avatarDisabled){
					// Filter out the default null value.
					$avatarForms = array_filter($this->getProjectSetting('avatar-forms'));
					if(!empty($avatarForms)){
						$avatarDisabled = !in_array($instrument, $avatarForms);
					}
				}
				?>

				OddcastAvatarExternalModule.settings = <?=json_encode([
					'voices' => [
						'female' => explode(',', $femaleVoice),
						'male' => explode(',', $maleVoice),
					],
					'shows' => OddcastAvatarExternalModule::$SHOWS,
					'isInitialLoad' => $_SERVER['REQUEST_METHOD'] == 'GET',
					'avatarDisabled' => $avatarDisabled,
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

				OddcastAvatarExternalModule.addVorlon(<?=json_encode(file_get_contents(__DIR__ . '/.vorlon-url'))?>)

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

	function redcap_every_page_top($project_id)
	{
		// The following check has been disabled since PHP 5.4 is buggy (hangs) in UniServer on Windows.
		if(false && $_SERVER['HTTP_HOST'] === 'localhost' && (PHP_MAJOR_VERSION !== 5 || PHP_MINOR_VERSION !== 4)){
			?>
			<script>
				alert("Please test the <?=$this->getModuleName()?> module in PHP 5.4 for STRIDE, since UMass (and maybe UAB) are currently on 5.4.")
			</script>
			<?php
		}

		if (!$this->isSurveyPage()) {
			return;
		}

		if(isset($_GET['__return'])){
			// This is the page where return codes are entered.
			// The redcap_survey_page hook will not get called on this page, so call loadAvatar() now.
			// This page could be loaded both in and outside of the iframe.
			// We have to initialize the avatar in case it's loaded outside the iframe.
			$this->loadAvatar($project_id, null, 'return_code_page');
			return;
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
		<script>
			if(window.frameElement){
				// We're in an iframe.  Go ahead and hide the loading indicator.
				$('#oddcast-loading-overlay').fadeOut(200)
			}
		</script>
		<?php
	}

	function redcap_every_page_before_render()
	{
		// No content can be echo'ed here, or it will break REDCap's TTS features in surveys.

		$reviewMode = @$_COOKIE[REVIEW_MODE];
		if (!$this->isSurveyPage() || !$this->isReviewModeEnabled() || is_null($reviewMode)) {
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
		$allForms = $settings['page-message-forms'];

		$messagesSet = [];
		for($i=0; $i<count($pageNumbers); $i++){
			$pageNumber = $pageNumbers[$i];
			$forms = $allForms[$i];

			foreach($forms as $form){
				$existingForms = @$messagesSet[$pageNumber];
				if($existingForms){
					foreach($existingForms as $existingForm){
						if(empty($form) || empty($existingForm) || $form === $existingForm){
							return "Multiple page messages are set on the same form for page $pageNumber.  This is not allowed.";
						}
					}
				}

				$messagesSet[$pageNumber][] = $form;
			}
		}
	}

	public function isSurveyPage()
	{
		$url = $_SERVER['REQUEST_URI'];

		return strpos($url, '/surveys/') === 0 &&
			strpos($url, '__passthru=DataEntry%2Fimage_view.php') === false; // Prevent hooks from firing for survey logo URLs (and breaking them).
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
		// Disable debug logging during testing.
		$this->debugLogging = false;

		$testInstance = new OddcastAvatarExternalModuleTest($this);
		$testInstance->runReportUnitTests();

		$this->testGetTimePeriodString();
		$this->testAnalyzeLogEntries_basics();
		$this->testAnalyzeLogEntries_avatar();
		$this->testAnalyzeLogEntries_video();
		$this->testGetSessionsFromLogs();
		$this->testSetAvatarAnalyticsFields();

		// Reset debug logging to null so it gets re-initialized from the DB.
		$this->debugLogging = null;
	}

	public function dump($var, $label)
	{
		echo "<pre>$label\n";
		var_dump($var);
		echo "</pre>";
	}

	private function testAnalyzeLogEntries_basics()
	{
		$instrument = 'instrument1';

		$logs = [
			['message' => 'survey page loaded', 'instrument' => $instrument],
			['message' => 'survey page loaded', 'instrument' => $instrument],
		];

		$logs = $this->flushOutMockLogs($logs);

		list(
			$firstReviewModeLog,
			$firstSurveyLog,
			$lastSurveyLog,
			$avatarUsagePeriods
		) = $this->analyzeLogEntries($logs, $instrument);

		$this->assertSame(1, $lastSurveyLog['timestamp'] - $firstSurveyLog['timestamp']);
	}

	function assertSame($expected, $actual){
		if($expected !== $actual){
			$this->dump($expected, '$expected');
			$this->dump($actual, '$actual');
			throw new Exception("The expected and actual values are not the same (or not the same type).");
		}
	}

	private function testAnalyzeLogEntries_avatar()
	{
		$showId = rand();
		$showId2 = rand();

		// basic usage
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'survey complete'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				]
			]
		);

		// basic usage without survey complete event
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				]
			]
		);

		// different character selections
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'character selected', 'show id' => $showId2],
				['message' => 'survey complete'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				],
				[
					'show id' => $showId2,
					'startIndex' => 2,
					'endIndex' => 3
				]
			]
		);

		// repeated character selections
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'survey complete'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				],
				[
					'show id' => $showId,
					'startIndex' => 2,
					'endIndex' => 3
				]
			]
		);

		// disabling and enabling
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'avatar disabled'],
				['message' => 'avatar enabled'],
				['message' => 'survey complete'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				],
				[
					'show id' => $showId,
					'startIndex' => 2,
					'endIndex' => 3,
					'disabled' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 3,
					'endIndex' => 4
				]
			]
		);

		// disabling to begin with
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'avatar disabled'],
				['message' => 'avatar enabled'], // the selection dialog will be displayed again here
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'startIndex' => 1,
					'endIndex' => 2,
					'disabled' => true
				],
				[
					'startIndex' => 2,
					'endIndex' => 3,
				],
				[
					'show id' => $showId,
					'startIndex' => 3,
					'endIndex' => 4,
				]
			]
		);

		// second of two instruments, and avatar left enabled from first
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument2'],
				['message' => 'this message creates a gap between the surrounding logs so that the following event is not assumed to be the initial character selection dialog'],
				['message' => 'character selected', 'show id' => $showId2],
				['message' => 'survey complete'],
			],
			[
				[
					'show id' => $showId,
					'startIndex' => 0,
					'endIndex' => 2
				],
				[
					'show id' => $showId2,
					'startIndex' => 2,
					'endIndex' => 3
				]
			],
			[
				['message' => 'character selected', 'show id' => $showId]
			]
		);

		// second of two instruments, and avatar left disabled from first
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument2'],
				['message' => 'avatar enabled'],
				['message' => 'survey complete'],
			],
			[
				[
					'show id' => $showId,
					'startIndex' => 0,
					'endIndex' => 1,
					'disabled' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				]
			],
			[
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'avatar disabled'],
			]
		);

		// second of two instruments opened at different times via direct links from participant list
		// avatar was left open on first instrument, but that avatar period should not be included on the second instrument
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument2'],
				['message' => 'character selected', 'show id' => $showId2],
				['message' => 'some action to simulate the user staying on the survey after selecting the character'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId2,
					'startIndex' => 1,
					'endIndex' => 2
				]
			],
			[
				['message' => 'character selected', 'show id' => $showId],
			]
		);
	}

	function flushOutMockLogs($logs, $lastLog = null)
	{
		if($logs === null){
			return null;
		}

		if($lastLog){
			$lastId = $lastLog['log_id'];
			$lastTimestamp = $lastLog['timestamp'];
		}
		else{
			$lastId = 0;
			$lastTimestamp = time();
		}

		$nextId = $lastId+1;

		$createLog = function($params) use (&$nextId, &$lastTimestamp){
			$isVideoMessage = strpos($params['message'], 'video ') === 0;
			if($isVideoMessage){
				if(!isset($params['seconds'])){
					$params['seconds'] = $nextId - 1;
				}

				if(!isset($params['field'])){
					$params['field'] = 'video_1';
				}
			}

			$params['log_id'] = $nextId;
			$nextId++;

			$params['timestamp'] = $lastTimestamp;
			$lastTimestamp++;

			return $params;
		};

		for($i=0; $i<count($logs); $i++){
			$logs[$i] = $createLog($logs[$i]);
		}

		return $logs;
	}

	private function assertAvatarUsagePeriods($logs, $expectedPeriods, $spoofedPrecedingAvatarLogs = null)
	{
		$this->spoofedPrecedingAvatarLogs = $spoofedPrecedingAvatarLogs = $this->flushOutMockLogs($spoofedPrecedingAvatarLogs);
		$logs = $this->flushOutMockLogs($logs, end($spoofedPrecedingAvatarLogs));

		list(
			$firstReviewModeLog,
			$firstSurveyLog,
			$lastSurveyLog,
			$avatarUsagePeriods
		) = $this->analyzeLogEntries($logs, $logs[0]['instrument']);

		if(count($avatarUsagePeriods) !== count($expectedPeriods)){
			$this->dump($expectedPeriods, '$expected');
			$this->dump($avatarUsagePeriods, '$actual');
			throw new Exception("Expected " . count($expectedPeriods) . " usage period(s), but found " . count($avatarUsagePeriods));
		}

		// Used to specifically order keys such that the triple equals check works as expected below.
		$moveKeyToEnd = function(&$array, $key){
			if(isset($array[$key])){
				$value = $array[$key];
			}
			else{
				// Make values default to false
				$value = false;
			}

			unset($array[$key]);
			$array[$key] = $value;
		};

		for($i=0; $i<count($expectedPeriods); $i++){
			$expected = $expectedPeriods[$i];
			$actual = $avatarUsagePeriods[$i];

			if(!isset($expected['show id'])){
				$expected['show id'] = null;
			}

			$moveKeyToEnd($expected, 'initialSelectionDialog');

			$startIndex = $expected['startIndex'];
			$endIndex = $expected['endIndex'];

			$expected['start'] = $logs[$startIndex]['timestamp'];

			$moveKeyToEnd($expected, 'disabled');

			$expected['end'] = $logs[$endIndex]['timestamp'];

			unset($expected['startIndex']);
			unset($expected['endIndex']);

			if($expected['start'] >= $expected['end']){
				$this->dump($expected, '$expected');
				throw new Exception("The expected start is not before the expected end for index $i");
			}

			if($expected !== $actual){
				$this->dump($expected, '$expected');
				$this->dump($actual, '$actual');
				throw new Exception("The expected and actual periods did not match!");
			}
		}

		$this->spoofedPrecedingAvatarLogs = null;
	}


	private function testAnalyzeLogEntries_video()
	{
		// test no video messages
		$this->assertVideoStats(
			[],
			[]
		);

		// test all messages that stop play
		$this->assertVideoStats(
			[
				// survey page loaded messages will be added and tested automatically
				['message' => 'video played'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video ended'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video popup closed'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);

		// make sure review mode events are included
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'review mode exited'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 1,
				]
			]
		);

		// assert that page loads are considered to stop video playback
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);

		// test two play events in a row
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video played'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 1,
				]
			]
		);

		// test play time
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played'],
				['message' => 'video ended'],
			],
			[
				'video_1' => [
					'playTime' => 3,
					'playCount' => 1,
				]
			]
		);

		// test repeats after end
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video ended'],
				['message' => 'video played', 'seconds' => 0],
				['message' => 'video ended'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 2,
				]
			]
		);

		// test repeats after seeking back to the beginning
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played', 'seconds' => 0],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 2,
				]
			]
		);

		// test repeats after seeking back near to the beginning
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played', 'seconds' => 1],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 2,
				]
			]
		);

		// make sure pausing and playing early in the video doesn't count as an extra play
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 1,
				]
			]
		);

		// test repeats after going forward and backward a page
		$this->assertVideoStats(
			[
				['message' => 'video played'],

				// Add some message to put us past the threshold considered a new play.
				['message' => 'foo'],
				['message' => 'foo'],
				['message' => 'foo'],
				['message' => 'foo'],
				['message' => 'foo'],

				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'video played', 'seconds' => 0],
				['message' => 'video paused'],
			],
			[
				'video_1' => [
					'playTime' => 7,
					'playCount' => 2,
				]
			]
		);

		// test multiple videos
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video ended'],
				['message' => 'video played', 'field' => 'video_2'],
				['message' => 'video ended', 'field' => 'video_2'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				],
				'video_2' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);

		// test multiple videos playing when page changes
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video played', 'field' => 'video_2'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 1,
				],
				'video_2' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);
	}

	private function assertVideoStats($logs, $expectedStats, $wrapInPageLoadLogs = true)
	{
		$instrument = 'instrument1';

		if($wrapInPageLoadLogs){
			$pageLoadLog = ['message' => 'survey page loaded', 'instrument' => $instrument];
			array_unshift($logs, $pageLoadLog);
			array_push($logs, $pageLoadLog);
		}

		$logs = $this->flushOutMockLogs($logs);

		list(
			$firstReviewModeLog,
			$firstSurveyLog,
			$lastSurveyLog,
			$avatarUsagePeriods,
			$videoStats
		) = $this->analyzeLogEntries($logs, $instrument);

		foreach($videoStats as &$stats){
			// Remove unused temporary stats that we don't want to compare.
			unset($stats['currentPlayLog']);
			unset($stats['lastPositionInSeconds']);
		}

		if($expectedStats !== $videoStats){
			$this->dump($expectedStats, 'expected stats');
			$this->dump($videoStats, 'actual stats');
			throw new Exception("Actual video stats did not match expected");
		}
	}

	public function analyzeLogEntries($logsToAnalyze, $instrument)
	{
//		$this->dump('analyzeLogEntries');

		// The instrument used to be detected from the first log entry,
		// but we changed it to a parameter to support sessions where the instrument is carried over from a previous session
		// and is not necessarily present on the first log entry in the current session.
		if(!$instrument){
			throw new Exception("An instrument is required to analyze log entries.  Here is the first log entry being analyzed: " . json_encode($logsToAnalyze[0]));
		}

		$firstSurveyLog = $logsToAnalyze[0];
		$logsToAnalyze = array_merge($this->getPrecedingAvatarRecordLogs($firstSurveyLog), $logsToAnalyze);

		$lastPageLoadLog = null;
		$lastSurveyLog = null;
		$firstReviewModeLog = null;
		$previousLog = null;
		$avatarUsagePeriods = [];
		$videoStats = [];
		$popupStats = [];
		$pageStats = [];

		foreach($logsToAnalyze as $log){
//			$this->dump($log, '$log');
			
			$message = $log['message'];
			$isPageLoad = $message === 'survey page loaded';
			if($isPageLoad || $message === 'survey complete'){
				if($lastPageLoadLog){
					$pageStats[$lastPageLoadLog['page']]['seconds'] += $log['timestamp'] - $lastPageLoadLog['timestamp'];
				}

				$lastPageLoadLog = $log;
			}

			if(!$lastSurveyLog && $isPageLoad && $log['instrument'] !== $instrument){
				$lastSurveyLog = $previousLog;
			}

			$logId = $log['log_id'];
			if($lastSurveyLog && $logId > $lastSurveyLog['log_id']){
				// This log is for a later instrument on this same record.
				continue;
			}

			// Handle avatar messages for the requested instrument as well as all prior instruments on this record, to make sure we detect avatars still enabled from previous instruments.
			$this->handleAvatarMessages($log, $previousLog, $firstSurveyLog, $lastSurveyLog, $avatarUsagePeriods);

			if(!$firstSurveyLog){
				// This log is for a earlier instrument on this same record.
				continue;
			}

			$this->handleVideoMessages($log, $videoStats);
			$this->handlePopupMessages($log, $popupStats);

// Include stats from review mode (at least for now, we may want to change that and uncomment the following in the future).
//			if($log['message'] === 'review mode exited'){
//				$firstReviewModeLog = $firstSurveyLog;
//				$firstSurveyLog = $log;
//
//				// Ignore any stats from review mode
//				$videoStats = [];
//				$popupStats = [];
//			}

			$previousLog = $log;
		}

		if(!$lastSurveyLog){
			// The user must have just dropped off.  Consider whatever the last log was to be the last one.
			$lastSurveyLog = $previousLog;
		}

		// Re-handle the last survey log message now that we know it's the last one
		$this->handleAvatarMessages($lastSurveyLog, $previousLog, $firstSurveyLog, $lastSurveyLog, $avatarUsagePeriods);

		// Remove avatar periods outside the range of logs analyzed
		// We must analyze outside the range to start so that we can account for avatars previously left enabled.
		$newAvatarUsagePeriods = [];
		foreach($avatarUsagePeriods as $period){
			if($period['end'] > $firstSurveyLog['timestamp']){
				// This period is within the range of logs
				if($period['start'] < $firstSurveyLog['timestamp']){
					// This period started before the log range.  Adjust it to start at the first log
					$period['start'] = $firstSurveyLog;
				}

				$newAvatarUsagePeriods[] = $period;
			}
		}
		$avatarUsagePeriods = $newAvatarUsagePeriods;

		return [
			$firstReviewModeLog,
			$firstSurveyLog,
			$lastSurveyLog,
			$avatarUsagePeriods,
			$videoStats,
			$popupStats,
			$pageStats
		];
	}

	private function getPrecedingAvatarRecordLogs($firstSurveyLog)
	{
		$logs = $this->spoofedPrecedingAvatarLogs;
		if($logs){
			return $logs;
		}

		$recordId = db_real_escape_string($firstSurveyLog['record']);
		$firstLogId = db_real_escape_string($firstSurveyLog['log_id']);

		$results = $this->queryLogsForWhereClause("
			record = '$recordId'
			and log_id < '$firstLogId'
			and message in ('character selected', 'avatar disabled', 'avatar enabled')
		");

		$logs = [];
		while($row = $results->fetch_assoc()){
			$logs[] = $row;
		}

		return $logs;
	}

	private function handleVideoMessages($log, &$allVideoStats)
	{
		$message = $log['message'];

		$onPlayStopped = function(&$currentVideoStats) use ($log){
			if(isset($currentVideoStats['currentPlayLog'])){
				$currentVideoStats['playTime'] += $log['timestamp'] - $currentVideoStats['currentPlayLog']['timestamp'];
				unset($currentVideoStats['currentPlayLog']);
			}
		};

		$isVideoMessage = strpos($log['message'], 'video ') === 0;
		if($isVideoMessage) {
			$field = $log['field'];
			$seconds = $log['seconds'];

			if(isset($allVideoStats[$field])){
				$currentVideoStats = &$allVideoStats[$field];
			}
			else{
				$currentVideoStats = [];
			}

			if ($message === 'video played') {
				if (!isset($allVideoStats[$field])) {
					$currentVideoStats = [
						'playTime' => 0,
						'playCount' => 0
					];

					$allVideoStats[$field] = &$currentVideoStats;
				}

				if ($currentVideoStats['currentPlayLog']) {
					// The last video message was also a play message.  They must have seeked.
					// Keep the old timestamp since we never stopped playing.
				} else {
					$currentVideoStats['currentPlayLog'] = $log;
				}

				if ($seconds < 5) {
					$previousSeconds = @$currentVideoStats['lastPositionInSeconds'];
					if ($previousSeconds !== null && $previousSeconds < $seconds) {
						// The user either paused then played the video, or seeked forward a little.
						// Either way, don't consider this a repeat.
					} else {
						// Either this is the first play, or the video was rewound to a point near the beginning of the video.
						// Consider this a new play regardless.
						$currentVideoStats['playCount']++;
					}
				}
			}
			else if (in_array($message, ['video paused', 'video ended', 'video popup closed'])) {
				$onPlayStopped($currentVideoStats);
			}

			$currentVideoStats['lastPositionInSeconds'] = $seconds;
		}
		else if (in_array($message, ['survey page loaded', 'survey complete'])) {
			foreach($allVideoStats as $field=>&$currentVideoStats) {
				$onPlayStopped($currentVideoStats);
			}
		}
	}

	private function handlePopupMessages($log, &$popupStats){
		$message = $log['message'];
		$term = @$log['link text'];

		if($message === 'popup opened') {
			if (!isset($popupStats[$term])) {
				$popupStats[$term] = 0;
			}

			$popupStats[$term]++;
		}
	}

	private function handleAvatarMessages($log, $previousLog, $firstSurveyLog, $lastSurveyLog, &$avatarUsagePeriods)
	{
//		$this->dump($log, '$log');

		if(!$log['log_id']){
			throw new Exception('A log id must be specified, even if this is a unit test: ' . json_encode($log));
		}

		$currentAvatar = null;
		if(!empty($avatarUsagePeriods)){
			$currentAvatar = &$avatarUsagePeriods[count($avatarUsagePeriods)-1];
		}

		$timestamp = $log['timestamp'];
		$message = $log['message'];

		$characterSelected = $message === 'character selected';
		$avatarDisabled = $message === 'avatar disabled';
		$avatarEnabled = $message === 'avatar enabled';

		$isFirstSurveyLog = $log['log_id'] === @$firstSurveyLog['log_id'];
		$isLastSurveyLog = $log['log_id'] === @$lastSurveyLog['log_id'];

		if(!($isFirstSurveyLog || $characterSelected || $avatarDisabled || $avatarEnabled || $isLastSurveyLog)){
			return;
		}

		$showId = @$currentAvatar['show id'];
		$initialSelectionDialog = false;

		if ($isFirstSurveyLog){
			// Remove usage periods from previous instruments.
			$avatarUsagePeriods = [];

			if($currentAvatar){
				$avatarDisabled = $currentAvatar['disabled'];
			}
			else{
				// No avatar period was added from a previous instrument.
			    // This must be the first instrument and the initial avatar selection popup must be displayed.
				$initialSelectionDialog = true;
			}
		}
		else if($characterSelected){
			$showId = $log['show id'];

			if($previousLog === $firstSurveyLog && $currentAvatar){
				// Assume the current period is the initial character selection dialog
				// We also detect the initial character selection dialog here to cover the case where
				// an avatar is left enabled on the first instrument and the second instrument is opened via a participant list link
				// instead of within the iframe where the first instrument was loaded (where the avatar could still be enabled).
				// If no other events occur between the first survey log and the character selection event, this will incorrectly detect
				// the reused window/iframe case as displaying an initial character selection dialog, but that's an acceptable compromise.
				$currentAvatar['initialSelectionDialog'] = true;
				$currentAvatar['show id'] = null;
			}
		}

		if($currentAvatar){
			$currentAvatar['end'] = $timestamp;
		}

		if(!$isLastSurveyLog ){
			$avatarUsagePeriods[] = [
				'show id' => $showId,
				'initialSelectionDialog' => $initialSelectionDialog,
				'start' => $timestamp,
				'disabled' => $avatarDisabled
			];
		}

//		$this->dump($avatarUsagePeriods, '$avatarUsagePeriods end of handleAvatarMessages');
	}

	public function displayAvatarStats($avatarUsagePeriods)
	{
		$avatarUsageTotals = [];
		foreach($avatarUsagePeriods as $avatar) {
			if($avatar['initialSelectionDialog'] || $avatar['disabled']){
				continue;
			}

			$showId = $avatar['show id'];
			$avatarUsageTotals[$showId] += $avatar['end'] - $avatar['start'];
		}

		?>
		<h5>Avatar</h5>
		<?php

		if(empty($avatarUsagePeriods)){
			echo "None";
		}
		else{
			?>
			<style>
				table.avatar{
					margin-bottom: 50px
				}

				table.avatar tr:not(:first-child),
				table.avatar td.character img{
					height: 100px;
				}

				td.character{
					padding: 0px;
				}

				td.character img{
					border-top: 4px solid white;
				}
			</style>
			<table class="table table-striped table-bordered avatar">
				<tr>
					<th>Character</th>
					<th>Gender</th>
					<th>Length of Time Used</th>
				</tr>
				<?php
				foreach($avatarUsageTotals as $showId=>$total){
					if(!$showId){
						continue;
					}
					?>
					<tr>
						<td class="character"><img src="<?=$this->getUrl("images/$showId.png")?>"</td>
						<td class="align-middle"><?=ucfirst(OddcastAvatarExternalModule::$SHOWS[$showId])?></td>
						<td class="align-middle"><?=$this->getTimePeriodString($total)?></td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php
		}
	}

	public function displayPageStats($pageStats)
	{
		?>
		<h5>Page Details</h5>
		<table class="table table-striped table-bordered">
			<tr>
				<th>Page</th>
				<th>Time Spent</th>
			</tr>
			<?php
			foreach($pageStats as $page=>$stats){
				$timePeriodString = $this->getTimePeriodString($stats['seconds']);
				echo "
					<tr>
						<td>$page</td>
						<td>$timePeriodString</td>
					</tr>
				";
			}
			?>
		</table>
		<?php
	}

	public function displayVideoStats($videoStats)
	{
		?>
		<h5>Videos (in order played)</h5>

		<?php
		if(empty($videoStats)){
			?><div>No videos were played.</div><?php
		}
		else{
			?>
			<table class="table table-striped table-bordered">
				<tr>
					<th>Field Name</th>
					<th>Time Spent Playing</th>
					<th>Number of Plays</th>
				</tr>
				<?php
				foreach($videoStats as $field=>$stats){
					?>
					<tr>
						<td><?=$field?></td>
						<td class="text-right"><?=$this->getTimePeriodString($stats['playTime'])?></td>
						<td class="text-right"><?=$stats['playCount']?></td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php
		}
	}

	public function displayPopupStats($popupStats)
	{
		?>
		<h5>Inline Descriptive Popups</h5>

		<?php
		if(empty($popupStats)){
			?><div>No inline popups were used.</div><?php
		}
		else{
			?>
			<table class="table table-striped table-bordered">
				<tr>
					<th>Term</th>
					<th>Number of Views</th>
				</tr>
				<?php
				foreach($popupStats as $term=>$views){
					?>
					<tr>
						<td><?=$term?></td>
						<td class="text-right"><?=$views?></td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php
		}
	}

	function formatDate($time){
		return date('Y-m-d', $time);
	}

	function getStartDate(){
		return $this->getParam('start-date', $this->formatDate(time() - self::SECONDS_PER_DAY*30));
	}

	function getEndDate(){
		return $this->getParam('end-date', $this->formatDate(time()));
	}

	function getParam($name, $defaultValue = null){
		$value = \db_real_escape_string(@$_REQUEST[$name]);
		if($value){
			return $value;
		}
		else{
			return $defaultValue;
		}
	}

	function getSessionsForDateParams(){
		$startDate = $this->getStartDate();
		$endDate = $this->getEndDate();

		// Bump the end date to the next day so all events on the day specified are include
		$endDate = $this->formatDate(strtotime($endDate) + self::SECONDS_PER_DAY);

		return $this->getSessionsForWhereClause("timestamp >= '$startDate' and timestamp < '$endDate'");
	}

	function getSessionsForLogIdParams(){
		$firstLogId = $this->getParam('first-log-id');
		$lastLogId = $this->getParam('last-log-id');

		return $this->getSessionsForWhereClause("log_id >= '$firstLogId' and log_id <= '$lastLogId'");
	}

	private function queryLogsForWhereClause($whereClause){
		$sql = "
			select
				log_id,
				" . self::TIMESTAMP_COLUMN . ",
				timestamp as timestamp_raw,
				message,
				record,
				instrument,
				page,
				`show id`,
				field,
				seconds,
				`link text`
			where
				record not like 'external-modules-temporary-record-id-%'
				and $whereClause
				and external_module_id is not null " // return logs from all modules
			. " order by log_id
		";

		if($this->isDebugLoggingEnabled()){
			$this->log('log query', [
				'pseudo sql' => $sql,
				'sql' => $this->getQueryLogsSql($sql)
			]);
		}

		return $this->queryLogs($sql);
	}

	private function getSessionsForWhereClause($whereClause){
		$results = $this->queryLogsForWhereClause($whereClause);
		return array_reverse($this->getSessionsFromLogs($results));
	}

	private function isDebugLoggingEnabled(){
		if($this->debugLogging === null){
			$this->debugLogging = $this->getProjectSetting('enable-debug-logging') === true;
		}

		return $this->debugLogging;
	}

	private function getSessionsFromLogs($logQueryResults){
		$lastSessionByRecordId = [];
		$sessions = [];
		while($log = $logQueryResults->fetch_assoc()){
			if($this->isDebugLoggingEnabled()){
				$this->log('processing session log', [
					'log json' => json_encode($log)
				]);
			}

			$recordId = $log['record'];
			$instrument = $log['instrument'];

			$currentSession = &$lastSessionByRecordId[$recordId];

			$addNewSession = false;
			if(!$currentSession){
				if(!$instrument){
					// The logs must have started in the middle of a session.
					// Continue until we hit the next log that defines an instrument so we can actually start the session.
					continue;
				}

				// echo "Starting first session<br>\n";
				$addNewSession = true;
			}
			else if($instrument && $instrument != $currentSession['lastInstrument']){
				// echo "Starting new session because instrument changed<br>\n";
				$addNewSession = true;
			}
			else {
				$lastLog = end($currentSession['logs']);
				$timeSinceLastLog = $log['timestamp'] - $lastLog['timestamp'];
				if($timeSinceLastLog >= self::SESSION_TIMEOUT){
					// echo "Starting new session because the last one timed out<br>\n";
					$addNewSession = true;

					if(!$instrument){
						// New sessions must have an instrument set.
						// Use the one from the current (soon to be previous) session.
						// echo "Using instrument from previous session<br>\n";
						$instrument = $currentSession['lastInstrument'];
					}
				}
			}

			if($addNewSession){
				unset($currentSession); // this is required to break the old reference before reusing this variable

				if(empty($instrument)){
					throw new Exception("Log {$log['log_id']} started a session, but an instrument for the session couldn't be detected.");
				}

				$currentSession = [
					'timestamp' => $log['timestamp'],
					'record' => $log['record'],
					'instrument' => $instrument,
					'logs' => []
				];

				$sessions[] = &$currentSession;
				$lastSessionByRecordId[$recordId] = &$currentSession;
			}

			$currentSession['logs'][] = $log;

			if($instrument){
				$currentSession['lastInstrument'] = $instrument;
			}
		}

		if($this->isDebugLoggingEnabled()){
			$sessionSummaries = [];
			foreach($sessions as $summary){
				$newIds = [];
				foreach($summary['logs'] as $log){
					$newIds[] = (int)$log['log_id'];
				}

				$summary['logs'] = $newIds;
				$sessionSummaries[] = $summary;
			}

			$this->log('sessions', [
				'session json' => json_encode($sessionSummaries)
			]);
		}

		return $sessions;
	}

	private function testGetSessionsFromLogs(){
		$assert = function($logs){
			$expectedSessions = [];
			for($i=0; $i<count($logs); $i++){
				$log = &$logs[$i];
				$log['log_id'] = $i;

				$sessionIndex = @$log['session'];
				if($sessionIndex === null){
					continue; // don't include this log in a session
				}

				$instrument = @$log['instrument'];
				if(@$log['copy-previous-instrument']){
					$instrument = $expectedSessions[$sessionIndex-1]['instrument'];
				}

				$session = &$expectedSessions[$sessionIndex];
				if(!$session){
					$session = [
						'timestamp' => $log['timestamp'],
						'record' => $log['record'],
						'instrument' => $instrument
					];

					$expectedSessions[$sessionIndex] = &$session;
				}

				// This value is only used to generate the expected sessions for testing.
				// It should not be included in the log data passed to getSessionsFromLogs() since sessions should be determined independently (the point of this test).
				unset($log['session']);

				$session['logs'][] = $log;
				if($instrument){
					$session['lastInstrument'] = $instrument;
				}
			}

			$results = new MockMySQLResult($logs);
			$actualSessions = $this->getSessionsFromLogs($results);

			$this->assertSame($expectedSessions, $actualSessions);
		};

		// two basic sessions
		$assert(
			[
				['session' => 0, 'record' => 1, 'instrument' => 'a'],
				['session' => 0, 'record' => 1], // simulate a message without an instrument set
				['session' => 1, 'record' => 1, 'instrument' => 'b'],
			]
		);

		// two overlapped records
		$assert(
			[
				['session' => 0, 'record' => 1, 'instrument' => 'a'],
				['session' => 0, 'record' => 1],
				['session' => 1, 'record' => 2, 'instrument' => 'a'],
				['session' => 0, 'record' => 1],
				['session' => 1, 'record' => 2],
			]
		);

		// sessions split by a timeout
		$time1 = 1;
		$time2 = $time1 + self::SESSION_TIMEOUT -1;
		$time3 = $time2 + self::SESSION_TIMEOUT;
		$assert(
			[
				['session' => 0, 'record' => 1, 'timestamp' => $time1, 'instrument' => 'a'],
				['session' => 0, 'record' => 1, 'timestamp' => $time2],
				['session' => 1, 'record' => 1, 'timestamp' => $time3, 'copy-previous-instrument' => true],
			]
		);

		// logs start in the middle of a session
		// The first session log must have an instrument set for analyzeLogEntries() to work properly.
		$assert(
			[
				[], // simulate a record from the middle of a session that doesn't have an instrument
				['session' => 0, 'record' => 1, 'instrument' => 'a'],
				['session' => 0, 'record' => 1],
			]
		);
	}

	function setAvatarAnalyticsFields($avatarUsagePeriods, &$data){
		$data['avatar_disabled'] = 0;

		$secondsByShowId = [];
		foreach($avatarUsagePeriods as $period){
			if($period['initialSelectionDialog']){
				continue;
			}

			if($period['disabled']){
				$data['avatar_disabled'] = 1;
				continue;
			}

			$showId = $period['show id'];
			if(!$showId){
				// This is subsequent selection dialog
				continue;
			}

			$secondsByShowId[$showId] += $period['end'] - $period['start'];
		}

		arsort($secondsByShowId);

		$avatarNumber = 1;
		foreach($secondsByShowId as $showId=>$seconds){
			if($avatarNumber <= 3){
				$data["avatar_id_$avatarNumber"] = $showId;
				$data["avatar_seconds_$avatarNumber"] = $seconds;
			}
			else{
				$data["avatar_seconds_other"] += $seconds;
			}

			$avatarNumber++;
		}
	}

	private function testSetAvatarAnalyticsFields(){
		$assert = function($periods, $expectedData){
			if(!isset($expectedData['avatar_disabled'])){
				$expectedData = ['avatar_disabled' => 0] + $expectedData;
			}

			$actualData = [];
			$this->setAvatarAnalyticsFields($periods, $actualData);
			$this->assertSame($expectedData, $actualData);
		};

		// basic
		$assert(
			[
				['show id' => 1, 'start' => 1, 'end' => 2],
			],
			[
				'avatar_id_1' => 1,
				'avatar_seconds_1' => 1
			]
		);

		// assert multiple period times combined
		$assert(
			[
				['show id' => 1, 'start' => 1, 'end' => 2],
				['show id' => 2, 'start' => 1, 'end' => 2],
				['show id' => 1, 'start' => 1, 'end' => 2],
			],
			[
				'avatar_id_1' => 1,
				'avatar_seconds_1' => 2,
				'avatar_id_2' => 2,
				'avatar_seconds_2' => 1
			]
		);

		// assert disabled works
		$assert(
			[
				['disabled' => true],
			],
			[
				'avatar_disabled' => 1
			]
		);

		// assert avatars ordered by most used
		$assert(
			[
				['show id' => 1, 'start' => 1, 'end' => 2],
				['show id' => 2, 'start' => 1, 'end' => 3],
			],
			[
				'avatar_id_1' => 2,
				'avatar_seconds_1' => 2,
				'avatar_id_2' => 1,
				'avatar_seconds_2' => 1
			]
		);

		// assert other works as expected
		$assert(
			[
				['show id' => 1, 'start' => 1, 'end' => 2],
				['show id' => 2, 'start' => 1, 'end' => 2],
				['show id' => 3, 'start' => 1, 'end' => 2],
				['show id' => 4, 'start' => 1, 'end' => 3],
				['show id' => 5, 'start' => 1, 'end' => 4],
				['show id' => 6, 'start' => 1, 'end' => 5],
			],
			[
				'avatar_id_1' => 6,
				'avatar_seconds_1' => 4,
				'avatar_id_2' => 5,
				'avatar_seconds_2' => 3,
				'avatar_id_3' => 4,
				'avatar_seconds_3' => 2,
				'avatar_seconds_other' => 3,
			]
		);

		// assert initialSelectionDialog skipped
		$assert(
			[
				['initialSelectionDialog' => true],
			],
			[]
		);

		// assert subsequent selection dialogs are skipped
		$assert(
			[
				[
					'initialSelectionDialog' => false,
					'disabled' => false,
					'show id' => null
				]
			],
			[]
		);
	}

	function getVideoUrl($fieldName){
		$result = $this->query("
			select video_url
			from redcap_metadata
			where
				project_id = " . $this->getProjectId() . "
				and field_name = '$fieldName'
		");

		$row = $result->fetch_assoc();

		return @$row['video_url'];
	}

	function isInstrumentComplete($recordId, $instrument){
		$result = $this->query("
			select value from redcap_data
			where
				project_id = " . $this->getProjectId() . "
				and record = $recordId
				and field_name = '{$instrument}_complete'
		");

		$row = $result->fetch_assoc();

		return $row['value'] == 2;
	}
}
