<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class OddcastAvatarExternalModule extends AbstractExternalModule
{
	function hook_survey_page()
	{
		?>
		<script src="//cdn.jsdelivr.net/npm/mobile-detect@1.4.1/mobile-detect.min.js"></script>

		<style>
			#oddcast-wrapper{
				display: table;
				margin: auto;
			}

			#oddcast-wrapper > *{
				display: table-cell;
			}

			#pagecontainer{
				height: 100vh;
				overflow-y: scroll;
				-webkit-overflow-scrolling: touch;
			}

			#oddcast-avatar{
				background: white;
				width: 300px;
			}

			#oddcast-avatar ._html5Player,
			#oddcast-avatar .character{
				margin-left: -40px;
			}

			#oddcast-avatar .button_holder{
				display: none !important;
			}

			#oddcast-avatar.minimize{
				width: 100px;
				height:30px;
			}

			#oddcast-overlay{
				display: none;
			    position: fixed;
			    height: 100vh;
			    width: 100vw;
			    background: rgba(239, 239, 239, 0.96);
				top: 0px;
				left: 0px;
				z-index: 1;
				text-align: center;
				padding-top: 50%;
				font-size: 40px;
				font-weight: 700;
				letter-spacing: 1px;
				color: #4e4e4e;
				font-family: sans-serif !important;
			}
		</style>

		<div id="oddcast-overlay">
			Please rotate the screen!
		</div>

		<div id="oddcast-wrapper">
			<div id="oddcast-sidebar">
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
			</div>
			<div id="oddcast-content"></div>
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
				var sidebar = $('#oddcast-sidebar');
				$('body').prepend($('#oddcast-wrapper'))

				$('#pagecontainer').appendTo($('#oddcast-content'))
			})
		</script>
		<?php
	}
}
