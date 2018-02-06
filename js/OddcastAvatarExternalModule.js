// This object is defined globally so it can be used in other modules (like Inline Descriptive Pop-ups).
var OddcastAvatarExternalModule = {
	scenedLoaded: false,
	initialize: function(settings){
		var wrapper = $('#oddcast-wrapper')
		var sidebar = $('#oddcast-sidebar')
		var avatar = OddcastAvatarExternalModule.getAvatar()
		var textIntroModal = wrapper.find('.modal.text-intro')

		$(function(){
			var voice = settings.voice
			if(!voice){
				voice = [1,1];
			}

			OddcastAvatarExternalModule.engine = voice[0];
			OddcastAvatarExternalModule.person = voice[1];

			var fadeDuration = 200

			var minimizeAvatar = function() {
				OddcastAvatarExternalModule.stopSpeech();
				avatar.fadeOut(fadeDuration);
				$('#oddcast-minimize-avatar').hide();
				$('#oddcast-maximize-avatar').show();

				Cookies.set('oddcast-avatar-maximized', 'false')
			}

			var firstMaximize = true
			var maximizeAvatar = function() {
				textIntroModal.modal('hide')
				
				// Wait until the avatar is loaded in the background initially, or we could see a flash of the wrong character.
				OddcastAvatarExternalModule.afterSceneLoaded(function () {
					oddcastPlayer.find('.character').remove()

					var showIndex = Cookies.get('oddcast-show-index')
					OddcastAvatarExternalModule.loadShow(showIndex)

					avatar.fadeIn(fadeDuration);
					$('#oddcast-minimize-avatar').show();
					$('#oddcast-maximize-avatar').hide();

					if (settings.isInitialLoad && firstMaximize) {
						// This call MUST be made from within a timeout scheduled by the click event, since Android and iOS require a user event to trigger media playback.
						// We call afterSceneLoaded() again to make sure the call loadShow() above has completed first.
						OddcastAvatarExternalModule.afterSceneLoaded(function () {
							OddcastAvatarExternalModule.sayText(settings.welcomeMessage)
						})
					}

					firstMaximize = false

					Cookies.set('oddcast-avatar-maximized', 'true')
				})
			}

			$('#oddcast-maximize-avatar').click(maximizeAvatar)
			$('#oddcast-minimize-avatar').click(minimizeAvatar)

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

			$('.oddcast-character').click(function(){
				var showIndex = $(this).data('show-index')
				Cookies.set('oddcast-show-index', showIndex)
				maximizeAvatar()
			})

			textIntroModal.find('button').click(function(){
				textIntroModal.modal('hide')
				minimizeAvatar()
			})

			$('body').prepend(wrapper)
			$('#pagecontainer').appendTo($('#oddcast-content'))

			if(settings.isInitialLoad){
				// Forget the show/character chosen from the last survey
				Cookies.remove('oddcast-show-index')

				textIntroModal.modal('show')
			}
			else if(Cookies.get('oddcast-avatar-maximized') === 'true'){
				maximizeAvatar()
			}
			else{
				$('#oddcast-maximize-avatar').show()
			}

			OddcastAvatarExternalModule.initPortraitDialog()
			OddcastAvatarExternalModule.initMessagesForValues(settings.messagesForValues)
		})
	},
	initPortraitDialog: function(){
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
	},
	initMessagesForValues: function(messagesForValues){
		var fieldMap = {}
		$.each(messagesForValues, function(i, item){
			if(fieldMap[item.field] == undefined){
				fieldMap[item.field] = {}
			}

			fieldMap[item.field][item.value.toLowerCase()] = item.message
		})

		$.each(fieldMap, function(fieldName, valueMap){
			var fields = $('[name=' + fieldName + ']')
			if(fields.length == 0){
				// Assume this is a set of checkbox fields.
				fields = $('[name=__chkn__' + fieldName + ']')
			}
			else if(fields.hasClass('hiddenradio')){
				fields = $('[name=' + fieldName + '___radio]')
			}

			fields.change(function(){
				var field = $(this)
				var type = field.attr('type')
				if($.inArray(type, ['checkbox', 'radio']) !== -1){
					if(!field.is(':checked')){
						return
					}
				}

				var value
				if(type == 'checkbox'){
					value = field.attr('code')
				}
				else{
					value = field.val().toLowerCase()
				}

				var message = valueMap[value]
				if(message){
					OddcastAvatarExternalModule.sayText(message)
				}
			})
		})
	},
	stopSpeech: function(){
		// Only respsect this request if the Oddcast libraries have already loaded.
		if(typeof stopSpeech != 'undefined'){
			stopSpeech()
		}
	},
	until: function(condition, then){
		var timeoutFunction = function(){
			if(!condition()){
				setTimeout(timeoutFunction, 100)
				return
			}

			then()
		}

		timeoutFunction()
	},
	afterSceneLoaded: function(callback){
		OddcastAvatarExternalModule.until(
			function(){
				return OddcastAvatarExternalModule.scenedLoaded
			},
			callback
		)
	},
	sayText: function(text){
		if(!OddcastAvatarExternalModule.engine){
			// The initialize function hasn't run yet.
			return
		}

		stopSpeech()
		sayText(text, OddcastAvatarExternalModule.person, 1, OddcastAvatarExternalModule.engine)
	},
	loadShow: function(showIndex){
		OddcastAvatarExternalModule.scenedLoaded = false
		loadShow(showIndex)
	},
	onSceneLoaded: function(){
		window.mobile_events = 1 // Required for sayText() to work on iOS/Android

		followCursor(0)
		setIdleMovement(0,0)

		var avatar = OddcastAvatarExternalModule.getAvatar()
		OddcastAvatarExternalModule.scenedLoaded = true
	},
	getAvatar: function(){
		return $('#oddcast-avatar')
	}
}

// Defining a global function is the standard Oddcast way of hooking into the scene loaded event...
function vh_sceneLoaded(){
	OddcastAvatarExternalModule.onSceneLoaded()
}
