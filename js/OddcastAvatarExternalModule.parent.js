OddcastAvatarExternalModule.addProperties({
	scenedLoaded: false,
	showId: null,
	fadeDuration: 200,
	initializeParent: function(){
		$('#pagecontainer').hide()

		var url = location.href.replace('&vorlon', '')
		$('#oddcast-content').html("<iframe src='" + url + "'></iframe>")

		if (OddcastAvatarExternalModule.settings.voices.female == '') {
			OddcastAvatarExternalModule.settings.voices.female = [3, 3]
		}

		if (OddcastAvatarExternalModule.settings.voices.male == '') {
			OddcastAvatarExternalModule.settings.voices.male = [3, 2]
		}

		$('#oddcast-maximize-avatar').click(function () {
			OddcastAvatarExternalModule.maximizeAvatar()
			OddcastAvatarExternalModule.log('avatar enabled')
		})

		$('#oddcast-minimize-avatar').click(function(){
			OddcastAvatarExternalModule.minimizeAvatar()
		})

		var oddcastPlayer = OddcastAvatarExternalModule.getPlayer()
		oddcastPlayer.click(function (e) {
			// Oddcast sets a touch start handler that prevents our controls from working consistently, and causes exceptions in the mobile Safari console.
			// Luckily we don't need this touch event, so we can just remove it.
			oddcastPlayer.find('.main_container').removeAttr('ontouchstart')
		})

		$('#oddcast-controls .fa-user').click(function () {
			var textIntroModal = OddcastAvatarExternalModule.getTextIntroModal()
			textIntroModal.find('.top-section').html('Select an eStaff member:').css('font-weight', 'bold')
			textIntroModal.find('.bottom-section').hide()
			textIntroModal.find('.modal-dialog').width('625px')

			textIntroModal.modal('show')
		})

		OddcastAvatarExternalModule.getPlayButton().click(function () {
			OddcastAvatarExternalModule.afterSceneLoaded(function () {
				OddcastAvatarExternalModule.sayPageMessage('page message played manually')
			})
		})

		$('.oddcast-character').click(function () {
			var showId = $(this).data('show-id')
			OddcastAvatarExternalModule.showId = showId

			OddcastAvatarExternalModule.maximizeAvatar()

			OddcastAvatarExternalModule.log('character selected', {
				'show id': showId
			})
		})

		OddcastAvatarExternalModule.getTextIntroModal().find('button').click(function () {
			OddcastAvatarExternalModule.getTextIntroModal().modal('hide')
			OddcastAvatarExternalModule.minimizeAvatar()
		})

		// Make the wrapper visible.
		var wrapper = OddcastAvatarExternalModule.getWrapper()
		$('body').prepend(wrapper)
		wrapper.css('display', 'table')

		OddcastAvatarExternalModule.initPortraitDialog()
		OddcastAvatarExternalModule.initTimeout()
		OddcastAvatarExternalModule.initReviewMode()
	},
	sayPageMessage: function(logMessage){
		var settings = OddcastAvatarExternalModule.settings
		OddcastAvatarExternalModule.sayText(settings.pageMessage)
		OddcastAvatarExternalModule.log(logMessage, {
			page: settings.currentPageNumber
		})
	},
	initReviewMode: function () {
		var settings = OddcastAvatarExternalModule.settings

		var cookieName = settings.reviewModeCookieName
		var onValue = 'on'
		var turningOffValue = settings.reviewModeTurningOffValue

		var getSubmitButton = function () {
			// This was switched to a function instead of a variable since the submit button is replaced when the avatar is loaded.
			return $('button[name=submit-btn-saverecord]:contains("Submit")')
		}

		getSubmitButton().prop('disabled', true)

		var setCookie = function (value) {
			Cookies.set(cookieName, value, {expires: 1})
		}

		var startAvatar = function (isInitialLoad) {
			getSubmitButton().prop('disabled', false)
			OddcastAvatarExternalModule.startAvatar(isInitialLoad)
		}

		var clickPreviousButton = function () {
			var previousButton = $('button[name=submit-btn-saveprevpage]')
			if (previousButton.length == 0) {
				Cookies.remove(cookieName)
				$('body').css('visibility', 'visible') // poor man's loading indicator

				startAvatar(true)

				OddcastAvatarExternalModule.log('review mode exited')
			}
			else {
				previousButton.click()
			}
		}

		if (!settings.reviewModeEnabled) {
			// Make sure Review Mode is disabled if a cookie is left over from when Review Mode was enabled previously (perhaps on a different instrument).
			Cookies.remove(cookieName)
		}
		else if (settings.isInitialLoad) {
			// Start Review Mode
			setCookie(onValue)
		}

		var value = Cookies.get(cookieName)
		if (value == onValue) {
			var reviewModeFooter = $('<div id="review-mode-footer">You are currently in Review Mode.<br><button	>Click here to begin consent</button></div>')
			reviewModeFooter.insertBefore($('#footer'))
			reviewModeFooter.find('button').click(function () {
				setCookie(turningOffValue)
				reviewModeFooter.remove() // Important if we're already on the first page
				clickPreviousButton()
			})
		}
		else if (value == turningOffValue) {
			clickPreviousButton()
		}
		else {
			// Either we're done reviewing, or review mode is disabled.
			startAvatar(settings.isInitialLoad)
		}
	},
	startAvatar: function (isInitialLoad) {
		if (OddcastAvatarExternalModule.settings.avatarDisabled) {
			$('#oddcast-sidebar').hide()
			return
		}

		if (isInitialLoad) {
			OddcastAvatarExternalModule.getTextIntroModal().modal('show')
		}
		else if (Cookies.get('oddcast-avatar-maximized') === 'true') {
			OddcastAvatarExternalModule.maximizeAvatar()
		}
		else {
			$('#oddcast-maximize-avatar').show()
		}
	},
	getWrapper: function () {
		return $('#oddcast-wrapper')
	},
	getTextIntroModal: function () {
		return OddcastAvatarExternalModule.getWrapper().find('.modal.text-intro')
	},
	initPortraitDialog: function () {
		var checkOrientation = function () {
			var md = new MobileDetect(window.navigator.userAgent);
			if (!md.mobile() && !md.tablet()) {
				return
			}

			var overlay = $('#oddcast-overlay');
			if (window.innerHeight > window.innerWidth) {
				overlay.fadeIn()
			}
			else {
				overlay.fadeOut()
			}
		}

		checkOrientation()
		window.addEventListener('orientationchange', checkOrientation)
	},
	initTimeout: function () {
		var settings = OddcastAvatarExternalModule.settings

		if (!settings.timeout) {
			settings.timeout = 5
		}

		if (!settings.restartTimeout) {
			settings.restartTimeout = 60
		}

		var modal = OddcastAvatarExternalModule.getWrapper().find('.modal.timeout')
		var input = modal.find('input')

		var openNewPublicSurvey = function () {
			window.location.href = settings.publicSurveyUrl
		}

		var isTimeoutModalDisplayed = function () {
			return OddcastAvatarExternalModule.isModalDisplayed(modal)
		}

		var redcapDialog
		var triesRemaining
		var timeoutVerificationValue
		var showTimeoutModal = function () {
			triesRemaining = 5
			timeoutVerificationValue = OddcastAvatarExternalModule.getTimeoutVerificationValue(settings)
			if (timeoutVerificationValue == '') {
				// There's nothing to verify, so just restart the survey.
				openNewPublicSurvey()
				return
			}

			$('.modal').modal('hide') // hide any other modals

			modal.modal('show')
			redcapDialog = $('.ui-dialog:visible')
			if (redcapDialog.length > 0) {
				// Hide any REDCap dialogs (like required fields messages) because they steal focus from inputs in bootstrap dialogs.
				redcapDialog.hide()
			}
		}

		var lastActive = Date.now()
		$(document).idle({
			idle: 1000,
			recurIdleCall: true,
			onIdle: function () {
				if (OddcastAvatarExternalModule.isModalDisplayed(OddcastAvatarExternalModule.getTextIntroModal())) {
					return
				}

				var minutesIdle = (Date.now() - lastActive) / 1000 / 60
				if (!isTimeoutModalDisplayed()) {
					if (minutesIdle >= settings.timeout) {
						showTimeoutModal()
						lastActive = Date.now()
					}
				}
				else if (minutesIdle >= settings.restartTimeout) {
					// The timeout modal is displayed.
					openNewPublicSurvey()
				}
			},
			onActive: function () {
				lastActive = Date.now()
			},
		})

		modal.find('button.restart').click(openNewPublicSurvey)

		modal.find('button.continue').click(function () {
			var enteredValue = input.val().trim().toLowerCase()
			if (enteredValue == timeoutVerificationValue) {
				modal.modal('hide')

				// Clear the value entered, in case the timeout modal is displayed again.
				input.val('')

				// If we had to hide a REDCap dialog (like a required fields message), re-dislpay it.
				if (redcapDialog.length > 0) {
					redcapDialog.show()
				}
			}
			else {
				triesRemaining--
				if (triesRemaining > 0) {
					alert("You did not enter the correct value.  You have " + triesRemaining + " tries left.")
				}
				else {
					alert("You did not enter the correct value.  You must start the survey from the beginning.")
					openNewPublicSurvey()
				}
			}
		})
	},
	getTimeoutVerificationValue: function (settings) {
		var value

		var verificationField = $('input[name=' + settings.timeoutVerification.fieldName + ']')
		if (verificationField.length > 0) {
			value = verificationField.val()
		}
		else {
			value = settings.timeoutVerification.value
			if (!value) {
				value = ''
			}
		}

		return value.trim().toLowerCase()
	},
	isModalDisplayed: function (modal) {
		return modal.hasClass('in')
	},
	// This method is referenced by the Analytics module.
	stopSpeech: function () {
		// Only respsect this request if the Oddcast libraries have already loaded.
		if (typeof stopSpeech != 'undefined') {
			stopSpeech()
		}
	},
	until: function (condition, then) {
		var timeoutFunction = function () {
			if (!condition()) {
				setTimeout(timeoutFunction, 100)
				return
			}

			then()
		}

		timeoutFunction()
	},
	afterSceneLoaded: function (callback) {
		OddcastAvatarExternalModule.until(
			function () {
				return OddcastAvatarExternalModule.scenedLoaded
			},
			callback
		)
	},
	sayText: function (text) {
		if (!OddcastAvatarExternalModule.engine) {
			// The initialize function hasn't run yet.
			return
		}
		else if (!OddcastAvatarExternalModule.isEnabled()) {
			return
		}

		stopSpeech()
		sayText(text, OddcastAvatarExternalModule.person, 1, OddcastAvatarExternalModule.engine)
	},
	loadShowByID: function (showId) {
		OddcastAvatarExternalModule.scenedLoaded = false

		// loadShow() is designed to load by index, but we don't want to do that since index is affected by adding/removing shows in the list.
		// Setting this window var then omitting the show index parameter will effectively load by show ID instead of index.
		window.vhsshtml5_ss_var = showId
		loadShow()
	},
	onSceneLoaded: function () {
		window.mobile_events = 1 // Required for sayText() to work on iOS/Android

		OddcastAvatarExternalModule.scenedLoaded = true

		var postInit = function (runCount) {
			// In IE the scene is apparently not fully initialized yet.
			// It will become initialized at some point in the next few hundred milliseconds.
			// We keep setting the following methods repeatedly for a while to make sure they're
			// applied as soon as the avatar is fully initialized.
			// Without this, the followCursor() method was not working consistently in IE.

			followCursor(0) // Prevents the avatar's eyes from following the mouse cursor
			setIdleMovement(0, 0)

			if (runCount < 20) {
				setTimeout(function () {
					postInit(runCount + 1)
				}, 50)
			}
		}

		postInit(0)
	},
	getAvatar: function () {
		return $('#oddcast-avatar')
	},
	getPlayer: function(){
		return $('._html5Player')
	},
	isEnabled: function () {
		return OddcastAvatarExternalModule.getAvatar().is(':visible')
	},
	getPlayButton: function () {
		return $('#oddcast-controls .fa-play-circle')
	},
	log: function (message, parameters) {
		if (OddcastAvatarExternalModule.settings.loggingSupported) {
			ExternalModules.Vanderbilt.OddcastAvatarExternalModule.log(message, parameters)
		}
	},
	minimizeAvatar: function () {
		OddcastAvatarExternalModule.stopSpeech();
		OddcastAvatarExternalModule.getAvatar().fadeOut(OddcastAvatarExternalModule.fadeDuration);
		OddcastAvatarExternalModule.sendToIFrame('setEnabled', false)

		$('#oddcast-minimize-avatar').hide();
		$('#oddcast-maximize-avatar').show();

		Cookies.set('oddcast-avatar-maximized', 'false')

		OddcastAvatarExternalModule.log('avatar disabled')
	},
	maximizeAvatar: function () {
		var settings = OddcastAvatarExternalModule.settings
		var textIntroModal = OddcastAvatarExternalModule.getTextIntroModal()
		textIntroModal.modal('hide')

		// Wait until the avatar is loaded in the background initially, or we could see a flash of the wrong character.
		OddcastAvatarExternalModule.afterSceneLoaded(function () {
			// The initial show is loaded, but we may not want to use this one so we load our own show later.

			OddcastAvatarExternalModule.getPlayer().find('.character').remove()

			var showId = OddcastAvatarExternalModule.showId
			if (!showId) {
				// The user has not yet selected a character. Show the intro modal again instead.
				textIntroModal.modal('show')
				return
			}

			var gender = settings.shows[showId]
			var voice = settings.voices[gender]

			OddcastAvatarExternalModule.engine = voice[0];
			OddcastAvatarExternalModule.person = voice[1];

			// Load the show we want instead.
			OddcastAvatarExternalModule.loadShowByID(showId)

			OddcastAvatarExternalModule.getAvatar().fadeIn(OddcastAvatarExternalModule.fadeDuration);
			OddcastAvatarExternalModule.sendToIFrame('setEnabled', true)

			$('#oddcast-minimize-avatar').show();
			$('#oddcast-maximize-avatar').hide();

			Cookies.set('oddcast-avatar-maximized', 'true')

			OddcastAvatarExternalModule.afterSceneLoaded(function () {
				// The show we want has been loaded.
				if (!settings.pageMessage) {
					return
				}

				OddcastAvatarExternalModule.getPlayButton().show()
				OddcastAvatarExternalModule.sayPageMessage('page message played automatically')
			})
		})
	},
	sendToIFrame: function(){
		OddcastAvatarExternalModule.sendTo($('#oddcast-content > iframe')[0].contentWindow, arguments)
	}
})

// Defining a global function is the standard Oddcast way of hooking into the scene loaded event...
function vh_sceneLoaded() {
	OddcastAvatarExternalModule.onSceneLoaded()
}

OddcastAvatarExternalModule.initializeParent()