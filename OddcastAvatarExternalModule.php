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
