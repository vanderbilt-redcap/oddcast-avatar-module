// This object is defined globally so it can be used in other modules (like Inline Descriptive Pop-ups).
var OddcastAvatarExternalModule = {
	initialize: function(settings){
		var wrapper = $('#oddcast-wrapper')
		var sidebar = $('#oddcast-sidebar')
		var textIntroModal = wrapper.find('.modal.text-intro')

		$(function(){
			var voice = settings.voice
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

				followCursor(0);
				setIdleMovement(20,10);
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
	},
	sayText: function(text){
		if(!OddcastAvatarExternalModule.engine){
			// The initialize function hasn't run yet.
			return
		}

		stopSpeech()
		sayText(text, OddcastAvatarExternalModule.person, 1, OddcastAvatarExternalModule.engine)
	}
}