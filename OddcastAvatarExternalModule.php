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
			<script type="text/javascript" src="//vhss-d.oddcast.com/vhost_embed_functions_v2.php?acc=6267283&js=1"></script><script type="text/javascript">AC_VHost_Embed(6267283,300,400,'',1,1, 2556887, 2864965,1,0,'c01a5c706842a2c4fb1d468701b73ff6',0);</script>
		</div>

		<script>
			$(function(){
				var voice = <?=json_encode(explode(',', $this->getProjectSetting('voice')))?>;
				if(voice == ''){
					voice = [1,1]
				}

				var engine = voice[0]
				var person = voice[1]

				console.log(voice)

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
					if(welcomeMessage != ''){
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
