<?php
namespace ExternalModules;
require_once dirname(__FILE__) . '/../../external_modules/classes/ExternalModules.php';

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
				width: 350px;
				height: 300px;
			}

			#oddcast-avatar .character{
				margin-left: -40px;
				margin-top: 5px; /* hide the bottom of the avatar, since it has some rendering issues */
			}

			#oddcast-avatar #_play{
				display: none;
			}
		</style>

		<div id='oddcast-avatar' >
			<?php
			$show = $this->getProjectSetting('character');
			if($show == 'Amy'){
				?>
				<script type="text/javascript" src="//vhss-d.oddcast.com/vhost_embed_functions_v2.php?acc=6267283&js=1"></script><script type="text/javascript">AC_VHost_Embed(6267283,300,400,'',1,1, 2557406, 2865485,1,0,'19b9de5dfbb6523d8d0aa02e20f42102',0);</script>
				<?php
			}
			else{
				?>
				<script type="text/javascript" src="//vhss-d.oddcast.com/vhost_embed_functions_v2.php?acc=6267283&js=1"></script><script type="text/javascript">AC_VHost_Embed(6267283,300,400,'',1,1, 2556887, 2864965,1,0,'c01a5c706842a2c4fb1d468701b73ff6',0);</script>
				<?php
			}
			?>
		</div>

		<script>
			$(function(){
				var voice = <?=json_encode(explode(',', $this->getProjectSetting('voice')))?>;
				if(!voice){
					voice = [1,1]
				}

				var engine = voice[0]
				var person = voice[1]

				var mySayText = function(text){
					sayText(text, person, 1, engine)
				}

				var initialize = function(){
					if(typeof sayText == 'undefined'){
						// The oddcast code hasn't been loaded yet.
						setTimeout(initialize, 100)
						return
					}

					var welcomeMessage = <?=json_encode($this->getProjectSetting('welcome-message'))?>;
					if(welcomeMessage){
						mySayText(welcomeMessage)
					}

					$('input, select, textarea, .ui-slider-handle').focus(function(){
						var row = $(this).closest('tr')

						if(row.closest('table').hasClass('sldrparent')){
							row = row.parent().closest('tr')
						}

						var text = row.find('> td:nth-child(2)').text().trim()

						stopSpeech()
						mySayText('You just clicked the ' + text + ' field.')
					})
				}

				initialize()
			})
		</script>
		<?php
	}
}
