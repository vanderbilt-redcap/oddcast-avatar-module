<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class OddcastAvatarExternalModule extends AbstractExternalModule
{
	function hook_survey_page()
	{
		?>
		<style>
			#oddcast-avatar{
				position: fixed;
				top: 25px;
				right: 25px;
				background: white;
				border-radius: 5px;
				border: 1px solid black;
				width: 350px;
				height: 302px;
			}

			#oddcast-avatar .character{
				margin-left: -40px;
				margin-top: 5px; /* hide the bottom of the avatar, since it has some rendering issues */
			}

			#oddcast-avatar #_play{
				display: none;
			}

			#oddcast-avatar.minimize{
				width: 100px;
				height:30px;
			}
		</style>

		<div id='oddcast-avatar' >
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
			<img id='maximize-avatar' style='position:absolute;top:5px;left:5px;display:none' src='<?=APP_PATH_IMAGES?>plus.png' />
			<img id='minimize-avatar' style='position:absolute;top:5px;left:5px;' src='<?=APP_PATH_IMAGES?>minus.png' />
		</div>

		<script>
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

					var welcomeMessage = <?=json_encode($this->getProjectSetting('welcome-message'))?>;
					var pageList = <?=json_encode($this->getProjectSetting('message-page'))?>;

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

				$('#minimize-avatar').click(function() {
					freezeToggle();
					$('#oddcast-avatar').addClass("minimize");
					$('.character').hide();
					$('#minimize-avatar').hide();
					$('#maximize-avatar').show();
				});

				$('#maximize-avatar').click(function() {
					$('#oddcast-avatar').removeClass("minimize");
					$('.character').show();
					$('#minimize-avatar').show();
					$('#maximize-avatar').hide();
					freezeToggle();
				});

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
		</script>
		<?php
	}
}
