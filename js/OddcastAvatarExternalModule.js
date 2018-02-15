// This object is defined globally so it can be used in other modules (like Inline Descriptive Pop-ups).
var OddcastAvatarExternalModule = {
	scenedLoaded: false,
	initialize: function(settings){
		var wrapper = OddcastAvatarExternalModule.getWrapper()
		var sidebar = $('#oddcast-sidebar')
		var avatar = OddcastAvatarExternalModule.getAvatar()
		var textIntroModal = OddcastAvatarExternalModule.getTextIntroModal()

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

			$('#oddcast-controls .fa-user').click(function(){
				textIntroModal.find('.top-section').html('Select an eStaff member:').css('font-weight', 'bold')
				textIntroModal.find('.bottom-section').hide()
				textIntroModal.find('.modal-dialog').width('350px')

				textIntroModal.modal('show')
			})

			$('#oddcast-controls .fa-play-circle').click(function(){
				OddcastAvatarExternalModule.afterSceneLoaded(function () {
					OddcastAvatarExternalModule.sayText(settings.welcomeMessage)
				})
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

				// If a timeout was active, remove it.
				Cookies.remove('timeout-active')

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
			OddcastAvatarExternalModule.initTimeout(settings)
		})
	},
	getWrapper: function(){
		return $('#oddcast-wrapper')
	},
	getTextIntroModal: function(){
		return OddcastAvatarExternalModule.getWrapper().find('.modal.text-intro')
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
	initTimeout: function(settings){
		var modal = OddcastAvatarExternalModule.getWrapper().find('.modal.timeout')
		var input = modal.find('input')

		var openNewPublicSurvey = function(){
			window.location.href = settings.publicSurveyUrl
		}

		var isTimeoutModalDisplayed = function(){
			return OddcastAvatarExternalModule.isModalDisplayed(modal)
		}

		var redcapDialog
		var timeoutVerificationValue
		var showTimeoutModal = function(){
			timeoutVerificationValue = OddcastAvatarExternalModule.getTimeoutVerificationValue(settings)
			if(timeoutVerificationValue == ''){
				// There's nothing to verify, so just restart the survey.
				openNewPublicSurvey()
				return
			}

			$('.modal').modal('hide') // hide any other modals

			modal.modal('show')
			redcapDialog = $('.ui-dialog:visible')
			if(redcapDialog.length > 0){
				// Hide any REDCap dialogs (like required fields messages) because they steal focus from inputs in bootstrap dialogs.
				redcapDialog.hide()
			}

			Cookies.set('timeout-active', true)
		}

		if(Cookies.get('timeout-active')){
			// setTimeout() was required here to make sure this happened AFTER any REDCap dialogs were displayed (like required field messages).
			setTimeout(showTimeoutModal, 0)
		}

		var lastActive = Date.now()
		$(document).idle({
			idle: 1000,
			recurIdleCall: true,
			onIdle: function(){
				if(OddcastAvatarExternalModule.isModalDisplayed(OddcastAvatarExternalModule.getTextIntroModal())){
					return
				}

				var minutesIdle = (Date.now() - lastActive)/1000/60
				if(!isTimeoutModalDisplayed()){
					if(minutesIdle >= settings.timeout){
						showTimeoutModal()
						lastActive = Date.now()
					}
				}
				else if(minutesIdle >= settings.restartTimeout){
					// The timeout modal is displayed.
					openNewPublicSurvey()
				}
			},
			onActive: function(){
				lastActive = Date.now()
			},
		})

		modal.find('button.restart').click(openNewPublicSurvey)

		var triesRemaining = 5
		modal.find('button.continue').click(function(){
			var enteredValue = input.val().trim().toLowerCase()
			if(enteredValue == timeoutVerificationValue){
				modal.modal('hide')

				// Clear the value entered, in case the timeout modal is displayed again.
				input.val('')

				// If we had to hide a REDCap dialog (like a required fields message), re-dislpay it.
				if(redcapDialog.length > 0){
					redcapDialog.show()
				}

				Cookies.remove('timeout-active')
			}
			else{
				triesRemaining--
				if(triesRemaining > 0){
					alert("You did not enter the correct value.  You have " + triesRemaining + " tries left.")
				}
				else{
					alert("You did not enter the correct value.  You must start the survey from the beginning.")
					openNewPublicSurvey()
				}
			}
		})
	},
	getTimeoutVerificationValue: function(settings){
		var value

		var verificationField = $('input[name=' + settings.timeoutVerification.fieldName + ']')
		if(verificationField.length > 0){
			value = verificationField.val()
		}
		else{
			value = settings.timeoutVerification.value
			if(!value){
				value = ''
			}
		}

		return value.trim().toLowerCase()
	},
	isModalDisplayed: function(modal){
		return modal.hasClass('in')
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
