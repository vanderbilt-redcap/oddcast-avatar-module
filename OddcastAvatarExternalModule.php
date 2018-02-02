<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class OddcastAvatarExternalModule extends AbstractExternalModule
{
	function hook_survey_page()
	{
		?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" integrity="sha256-NuCn4IvuZXdBaFKJOAcsU2Q3ZpwbdFisd5dux4jkQ5w=" crossorigin="anonymous" />
		<style>
			.fa{
				font-family: FontAwesome !important; /* Override the REDCap style that prevents FontAwesome from working */
			}
		</style>

		<script src="//cdn.jsdelivr.net/npm/mobile-detect@1.4.1/mobile-detect.min.js"></script>
		<?php
			$vorlonIPAddress = '10.151.18.178';
			if($_SERVER['HTTP_HOST'] == $vorlonIPAddress){
				?><script src="http://<?=$vorlonIPAddress?>:1337/vorlon.js"></script><?php
			}
		?>

		<link rel="stylesheet" href="<?=$this->getUrl('style.css')?>">

		<div id="oddcast-wrapper">
			<div class="modal fade text-intro" data-backdrop="static">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-body">
							<p class="top-section">Hello!  Thank you for your interest in volunteering for a research study.  At any time during the consent you can ask a study coordinator for help.  We also have our eStaff team members to guide you through the consent.  Please select an eStaff team member to take you through the consent:<p>
							<div id="oddcast-character-list" class="text-center">
								<img src="<?=$this->getUrl('images/4.png')?>" data-show-id="4" class="oddcast-character" />
								<img src="<?=$this->getUrl('images/5.png')?>" data-show-id="5" class="oddcast-character" />
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
			<div id="oddcast-sidebar">
				<button id="oddcast-minimize-avatar">Disable eStaff</button>
				<button id="oddcast-maximize-avatar">Enable eStaff</button>
				<div id='oddcast-avatar' >
					<div id="oddcast-controls">
						<i class="fa fa-user" id="choose-avatar"></i>
					</div>

					<?php
					$show = $this->getProjectSetting('character');
					if($show == 'Amy'){
						?>
						<script type="text/javascript" src="//vhss-d.oddcast.com/vhost_embed_functions_v2.php?acc=6267283&js=1"></script><script type="text/javascript">AC_VHost_Embed(6267283,300,400,'',1,1, 2560294, 0,1,0,'709e320dba1a392fa4e863ef0809f9f1',0);</script>
						<?php
					}
					else if($show == 'Paul'){
						?>
						<script type="text/javascript" src="//vhss-d.oddcast.com/vhost_embed_functions_v2.php?acc=6267283&js=1"></script><script type="text/javascript">AC_VHost_Embed(6267283,300,400,'',1,1, 2556887, 0,1,0,'1ecd277ef756782795cfe78231031c9a',0);</script>
						<?php
					}
					else{
						?>
						<script type="text/javascript" src="//vhss-d.oddcast.com/vhost_embed_functions_v2.php?acc=6267283&js=1"></script><script type="text/javascript">AC_VHost_Embed(6267283,300,400,'',1,1, 2560288, 0,1,0,'357ac6967e845ef95e8aed802148866f',0);</script>
						<?php
					}
					?>
				</div>
			</div>
			<div id="oddcast-content"></div>
			<div id="oddcast-overlay">
				Please rotate the screen!
			</div>
		</div>

		<script>
			<?php
			if($this->getProjectSetting('disable')){
//				echo 'return';
			}
			?>

			var wrapper = $('#oddcast-wrapper')
			var sidebar = $('#oddcast-sidebar')
			var textIntroModal = wrapper.find('.modal.text-intro')

			var pageNumber = <?php echo $_GET['__page__'] != 0 ? $_GET['__page__'] : 0; ?>;
			var enableOddcastSpeech = false;

			// This object is defined globally so it can be used in other modules (like Inline Descriptive Pop-ups).
			var OddcastAvatarExternalModule = {
				sayText: function(text){
					if(!OddcastAvatarExternalModule.engine){
						// The initialize function hasn't run yet.
						return
					}

					stopSpeech()
					sayText(text, OddcastAvatarExternalModule.person, 1, OddcastAvatarExternalModule.engine)
				}
			}

			$(function(){
				var voice = <?=json_encode(explode(',', $this->getProjectSetting('voice')))?>;
				if(!voice){
					voice = [1,1];
				}

				OddcastAvatarExternalModule.engine = voice[0];
				OddcastAvatarExternalModule.person = voice[1];

				var initialize = function(){
					if(typeof sayText == 'undefined'){
						// The oddcast code hasn't been loaded yet.
						setTimeout(initialize, 100)
						return
					}

					window.mobile_events = 1 // Required for sayText() to work on iOS/Android

					var welcomeMessage = <?=json_encode($this->getProjectSetting('welcome-message'))?>;
					var pageList = <?=json_encode($this->getProjectSetting('message-page'))?>;
					if(!pageList){
						pageList = []
					}

					for(var i = 0; i < pageList.length; i++) {
						if(welcomeMessage[i] && (pageNumber == pageList[i])){
							OddcastAvatarExternalModule.sayText(welcomeMessage[i]);
							break;
						}
					}

					followCursor(0);
					setIdleMovement(20,10);

					$('input, select, textarea, .ui-slider-handle').focus(function(){
						oddcastFocusSpeech(this)
					})
				};

				var fadeDuration = 200

				$('#oddcast-minimize-avatar').click(function() {
					stopSpeech();
					$('#oddcast-avatar').fadeOut(fadeDuration);
					$('#oddcast-minimize-avatar').hide();
					$('#oddcast-maximize-avatar').show();
				});

				var maximizeAvatar = function() {
					$('#oddcast-avatar').fadeIn(fadeDuration);
					$('#oddcast-minimize-avatar').show();
					$('#oddcast-maximize-avatar').hide();
				}

				$('#oddcast-maximize-avatar').click(maximizeAvatar);

				var oddcastPlayer = $('._html5Player')
				oddcastPlayer.click(function(e){
					// Oddcast sets a touch start handler that prevents our controls from working consistently, and causes exceptions in the mobile Safari console.
					// Luckily we don't need this touch event, so we can just remove it.
					oddcastPlayer.find('.main_container').removeAttr('ontouchstart')
				})

				$('#choose-avatar').click(function(){
					textIntroModal.find('.top-section').html('Select an eStaff member:').css('font-weight', 'bold')
					textIntroModal.find('.bottom-section').hide()
					textIntroModal.find('.modal-dialog').width('350px')

					textIntroModal.modal('show')
				})

				$('.oddcast-character').click(function(link){
					oddcastPlayer.find('.character').remove()
					var id = $(this).data('show-id')
					loadShow(id)
					textIntroModal.modal('hide')
					maximizeAvatar()
				})

				var oddcastFocusSpeech = function(element) {
					if(enableOddcastSpeech) {
						var row = $(element).closest('tr');

						if(row.closest('table').hasClass('sldrparent')){
							row = row.parent().closest('tr')
						}

						var text = row.find('> td:nth-child(2)').text().trim()

						OddcastAvatarExternalModule.sayText('You just clicked the ' + text + ' field.')
					}
				};

				initialize()
			})
			
			$(function(){
				var checkOrientation = function(){
					var md = new MobileDetect(window.navigator.userAgent);
					if(!md.mobile() && !md.tablet()){
						return
					}

					var overlay = $('#oddcast-overlay');
					if(window.innerHeight > window.innerWidth){
						overlay.fadeIn()
					}
					else{
						overlay.fadeOut()
					}
				}

				checkOrientation()
				window.addEventListener('orientationchange', checkOrientation)
			})

			$(function(){
				$('body').prepend(wrapper)

				$('#pagecontainer').appendTo($('#oddcast-content'))

				textIntroModal.modal('show')

				textIntroModal.find('button').click(function(){
					textIntroModal.modal('hide')
				})
			})
		</script>
		<?php
	}
}
