<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

const REVIEW_MODE = 'review-mode';
const TURNING_OFF = 'turning-off';

class OddcastAvatarExternalModule extends AbstractExternalModule
{
	function redcap_survey_page($project_id, $record, $instrument)
	{
		$initializeJavascriptMethodName = 'initializeJavascriptModuleObject';
		$loggingSupported = method_exists($this, $initializeJavascriptMethodName);
		if ($loggingSupported) {
			$this->{$initializeJavascriptMethodName}();
		}

		$shows = [
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

		?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" integrity="sha256-NuCn4IvuZXdBaFKJOAcsU2Q3ZpwbdFisd5dux4jkQ5w=" crossorigin="anonymous" />
		<style>
			.fa{
				font-family: FontAwesome !important; /* Override the REDCap style that prevents FontAwesome from working */
			}
		</style>

		<script src="//cdn.jsdelivr.net/npm/mobile-detect@1.4.1/mobile-detect.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.2.0/js.cookie.min.js" integrity="sha256-9Nt2r+tJnSd2A2CRUvnjgsD+ES1ExvjbjBNqidm9doI=" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/jquery.idle@1.2.6/jquery.idle.min.js" integrity="sha256-RFOvLffDBWTRL2yzD1Atxv6t+G3Rd73IYdbmGO3IOzM=" crossorigin="anonymous"></script>
		<?php
			$vorlonIPAddress = '10.151.18.178';
			if(false && $_SERVER['HTTP_HOST'] == $vorlonIPAddress){
				?><script src="http://<?=$vorlonIPAddress?>:1337/vorlon.js"></script><?php
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
								foreach ($shows as $id => $gender) {
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
						AC_VHost_Embed(6267283, 300, 400, '', 1, 1, <?=array_keys($shows)[0]?>, 0, 1, 0, '709e320dba1a392fa4e863ef0809f9f1', 0);
						setTimeout(function () {
							// For some stupid reason AC_VHost_Embed *sometimes* attaches an event listener to our submit
							// button during the next iteration of the event loop that causes submits to fail.
							// We effectively remove it by replace the submit button with a clone of itself.
							var submitButton = $('button[name=submit-btn-saverecord]')
							var newButton = submitButton.clone()
							submitButton.replaceWith(newButton)

							newButton.click(function () {
								// Prevent the "are you sure" prompt when leaving the page from triggering in IE & Chrome.
								dataEntryFormValuesChanged = false
							})
						}, 0)
					</script>
				</div>
			</div>
			<div id="oddcast-content"></div>
			<div id="oddcast-overlay">
				Please rotate the screen!
			</div>
		</div>

		<script type="text/javascript" src="<?=$this->getUrl('js/OddcastAvatarExternalModule.js')?>"></script>

		<script>
			$(function(){
				<?php
				$currentPageNumber = $_GET['__page__'];
				$pageMessage = '';
				foreach($this->getSubSettings('page-messages') as $settingGroup){
					if($settingGroup['page-number'] == $currentPageNumber){
						$pageMessage = $settingGroup['page-message'];
						break;
					}
				}
				?>

				OddcastAvatarExternalModule.initialize(<?=json_encode([
					'voices' => [
						'female' => explode(',', $this->getProjectSetting('voice')),
						'male' => explode(',', $this->getProjectSetting('male-voice')),
					],
					'shows' => $shows,
					'isInitialLoad' => $_SERVER['REQUEST_METHOD'] == 'GET',
					'avatarDisabled' => $this->getProjectSetting('disable'),
					'reviewModeEnabled' => $this->isReviewModeEnabled($instrument),
					'reviewModeCookieName' => REVIEW_MODE,
					'reviewModeTurningOffValue' => TURNING_OFF,
					'pageMessage' => $pageMessage,
					'currentPageNumber' => $currentPageNumber,
					'messagesForValues' => $this->getSubSettings('messages-for-field-values'),
					'publicSurveyUrl' => $this->getPublicSurveyUrl(),
					'timeout' => $this->getProjectSetting('timeout'),
					'restartTimeout' => $this->getProjectSetting('restart-timeout'),
					'timeoutVerification' => [
						'fieldName' => $this->getTimeoutVerificationFieldName(),
						'value' => $this->getTimeoutVerificationValue($project_id, $record)
					],
					'loggingSupported' => $loggingSupported
				])?>)
			})
		</script>
		<?php
	}

	function redcap_every_page_before_render()
	{
		if (!$this->isSurveyPage()) {
			return false;
		}

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

		if ($reviewMode === TURNING_OFF) {
			?>
			<style>
				body {
					visibility: hidden; /* poor man's loading indicator */
				}
			</style>
			<?php
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

	private function getTimeoutVerificationValue($project_id, $record){
		$fieldName = $this->getTimeoutVerificationFieldName();
		return @json_decode(\REDCap::getData($project_id, 'json', [$record], [$fieldName]), true)[0][$fieldName];
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
}
