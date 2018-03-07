<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class OddcastAvatarExternalModule extends AbstractExternalModule
{
	function redcap_survey_page($project_id, $record)
	{
		$showIds = [2560288, 2560294];

		?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" integrity="sha256-NuCn4IvuZXdBaFKJOAcsU2Q3ZpwbdFisd5dux4jkQ5w=" crossorigin="anonymous" />
		<style>
			.fa{
				font-family: FontAwesome !important; /* Override the REDCap style that prevents FontAwesome from working */
			}
		</style>

		<link rel="stylesheet" href="https://unpkg.com/tippy.js@2.2.2/dist/tippy.css" integrity="sha384-wSlyG10EXV8zWqE9v9lzWCfOPiVQB5p5/9xT/zfpYn4yxqLooKBko44huGddKjAT" crossorigin="anonymous">
		<link rel="stylesheet" href="https://unpkg.com/tippy.js@2.2.2/dist/themes/light.css" integrity="sha384-L67GFzFvXzI/emFX7zfRPrrglAGTl08iybyk/gP2LdDEaY77xQ2GwBjiUglPhEQw" crossorigin="anonymous">
		<script src="https://unpkg.com/tippy.js@2.2.2/dist/tippy.all.min.js" integrity="sha384-PZHY4QRH2Yg34/USJTSmg+oXlrrxxxOHITDLz+TERu3KS9JbUpnsp0JrhT/F1Hmc" crossorigin="anonymous"></script>

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
								foreach($showIds as $id){
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
						<i class="fa fa-play-circle tippy" title="Click this icon to play<br>a welcome message." data-tippy-arrow="true" data-tippy-trigger="manual" data-tippy-offset="-50, 0" data-tippy-theme="light inline-popups"></i>
						<i class="fa fa-user"></i>
					</div>
					<script type="text/javascript" src="//vhss-d.oddcast.com/vhost_embed_functions_v2.php?acc=6267283&js=1"></script>
					<script type="text/javascript">AC_VHost_Embed(6267283,300,400,'',1,1, <?=$showIds[0]?>, 0,1,0,'709e320dba1a392fa4e863ef0809f9f1',0);</script>
				</div>
			</div>
			<div id="oddcast-content"></div>
			<div id="oddcast-overlay">
				Please rotate the screen!
			</div>
		</div>

		<script type="text/javascript" src="<?=$this->getUrl('js/OddcastAvatarExternalModule.js')?>"></script>

		<script>
			OddcastAvatarExternalModule.log('main script block')

			$(function(){
				OddcastAvatarExternalModule.log('before disable setting check')

				<?php
				if($this->getProjectSetting('disable')){
					echo 'return';
				}
				?>

				OddcastAvatarExternalModule.log('before initialize()')

				OddcastAvatarExternalModule.initialize(<?=json_encode([
					'voice' => explode(',', $this->getProjectSetting('voice')),
					'isInitialLoad' => $_SERVER['REQUEST_METHOD'] == 'GET',
					'welcomeMessage' => trim($this->getProjectSetting('welcome-message')),
					'messagesForValues' => $this->getSubSettings('messages-for-field-values'),
					'publicSurveyUrl' => $this->getPublicSurveyUrl(),
					'timeout' => $this->getProjectSetting('timeout'),
					'restartTimeout' => $this->getProjectSetting('restart-timeout'),
					'timeoutVerification' => [
						'fieldName' => $this->getTimeoutVerificationFieldName(),
						'value' => $this->getTimeoutVerificationValue($project_id, $record)
					]
				])?>)
			})
		</script>
		<?php
	}

	private function getTimeoutVerificationFieldName(){
		return $this->getProjectSetting('timeout-verification-field');
	}

	private function getTimeoutVerificationLabel($project_id){
		$fieldName = $this->getTimeoutVerificationFieldName();
		return $this->getFieldLabel($fieldName);
	}

	private function getTimeoutVerificationValue($project_id, $record){
		$fieldName = $this->getTimeoutVerificationFieldName();
		return @json_decode(\REDCap::getData($project_id, 'json', [$record], [$fieldName]), true)[0][$fieldName];
	}
}
