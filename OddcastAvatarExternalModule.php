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

		<style>
			/* The following two blocks prevent the user from attempting to scroll the sidebar area on iPad. */
			html {
				position: fixed;
				height: 100%;
				overflow: hidden;
			}
			body {
				width: 100vw;
				height: 100vh;
			}

			#oddcast-wrapper{
				display: table;
				margin: auto;
				background: #f3f3f3;
			}

			#oddcast-wrapper > *{
				display: table-cell;
				vertical-align: top;
			}

			#oddcast-sidebar{
				position: relative;
			}

			#pagecontainer{
				height: 100vh;
				overflow-y: scroll;
				-webkit-overflow-scrolling: touch;
			}

			#oddcast-avatar{
				background: white;
				width: 300px;
				overflow: hidden;
				border-bottom: 1px solid #cccccc;
			}

			#oddcast-avatar ._html5Player,
			#oddcast-avatar ._html5Player .character{
				margin-left: -40px;
			}

			#oddcast-avatar .button_holder{
				display: none !important;
			}

			#oddcast-sidebar{
				width: 300px;
			}

			#oddcast-sidebar .fa{
				position: absolute;
				font-size: 25px;
				color: #ececec;
				text-shadow: 0px 0px 2px black;
				top: 5px;
				z-index: 10000; /* Above the avatar character */
				cursor: pointer;
			}

			#oddcast-controls{
				z-index: 10000; /* Above the avatar character */
				position: absolute;
				width: 100%;
			}

			#oddcast-sidebar .fa-minus-circle,
			#oddcast-sidebar .fa-plus-circle{
				left: 8px;
			}

			#oddcast-sidebar .fa-plus-circle{
				display: none;
			}

			#oddcast-sidebar .fa-user{
				left: 276px;
			}

			#oddcast-overlay{
				display: none;
			    position: fixed;
			    height: 100vh;
			    width: 100vw;
			    background: rgba(239, 239, 239, 0.96);
				top: 0px;
				left: 0px;
				z-index: 9999; /* Above the avatar character */
				text-align: center;
				padding-top: 50%;
				font-size: 40px;
				font-weight: 700;
				letter-spacing: 1px;
				color: #4e4e4e;
				font-family: sans-serif !important;
			}

			#oddcast-character-picker{
				top: 100px;
				left: 67px;
				box-shadow: 0px 0px 3px #bdb2b2;
				position: absolute;
				background: white;
				display: none;
				border-radius: 8px;
				padding: 0px 10px;
			}

			#oddcast-controls .character{
				width: 60px;
				margin: 10px 5px;
				display: inline-block;
				border-bottom: 1px solid #f1eeee;
				cursor: pointer;
				transition: 200ms all;
			}

			#oddcast-controls .character:hover{
				width: 65px;
				margin: 7px 2.5px;
			}
		</style>

		<div id="oddcast-wrapper">
			<div id="oddcast-sidebar">
				<i class="fa fa-minus-circle" id="minimize-avatar"></i>
				<i class="fa fa-plus-circle" id="maximize-avatar"></i>
				<div id='oddcast-avatar' >
					<div id="oddcast-controls">
						<i class="fa fa-user" id="choose-avatar"></i>
						<div id="oddcast-character-picker">
							<img src="<?=$this->getUrl('images/4.png')?>" data-show-id="4" class="character" />
							<img src="<?=$this->getUrl('images/5.png')?>" data-show-id="5" class="character" />
						</div>
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
					$('#oddcast-avatar').hide();
					$('#minimize-avatar').hide();
					$('#maximize-avatar').show();
				});

				$('#maximize-avatar').click(function() {
					$('#oddcast-avatar').removeClass("minimize");
					$('#oddcast-avatar').show();
					$('#minimize-avatar').show();
					$('#maximize-avatar').hide();
					freezeToggle();
				});

				var oddcastPlayer = $('._html5Player')
				var characterPicker = $('#oddcast-character-picker')

				var toggleCharacterPicker = function(){
					if(characterPicker.is(':visible')){
						characterPicker.fadeOut(100)
						oddcastPlayer.css('filter', '')
					}
					else{
						characterPicker.fadeIn(200)
						oddcastPlayer.css('filter', 'blur(5px)')
					}
				}

				$('#choose-avatar').click(toggleCharacterPicker)

				characterPicker.find('.character').click(function(link){
					toggleCharacterPicker()
					oddcastPlayer.find('.character').remove()
					var id = $(this).data('show-id')
					loadShow(id)
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
				var sidebar = $('#oddcast-sidebar');
				$('body').prepend($('#oddcast-wrapper'))

				$('#pagecontainer').appendTo($('#oddcast-content'))
			})
		</script>
		<?php
	}
}
