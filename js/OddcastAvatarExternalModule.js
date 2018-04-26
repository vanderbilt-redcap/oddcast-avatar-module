// This object is defined globally so it can be used in other modules (like Inline Descriptive Pop-ups).
var OddcastAvatarExternalModule = {
	scenedLoaded: false,
	initialize: function (settings) {
		var wrapper = OddcastAvatarExternalModule.getWrapper()
		var avatar = OddcastAvatarExternalModule.getAvatar()
		var textIntroModal = OddcastAvatarExternalModule.getTextIntroModal()

		var fades = $('[id=fade]')
		if (fades.length == 2) {
			// A quirk of e-Consent and the oddcast-wrapper cause two fade divs to be created on the e-Consent preview/confirmation page.
			// We replace the inner one with the outer one to make sure it covers the oddcast-sidebar.
			$(fades[1]).replaceWith($(fades[0]))
		}

		if (settings.pageMessage == '') {
			OddcastAvatarExternalModule.getPlayButton().hide()
		}

		$(function () {
			var voice = settings.voice
			if (!voice) {
				voice = [1, 1];
			}

			OddcastAvatarExternalModule.engine = voice[0];
			OddcastAvatarExternalModule.person = voice[1];

			var fadeDuration = 200

			var minimizeAvatar = function () {
				OddcastAvatarExternalModule.stopSpeech();
				avatar.fadeOut(fadeDuration);
				$('#oddcast-minimize-avatar').hide();
				$('#oddcast-maximize-avatar').show();

				Cookies.set('oddcast-avatar-maximized', 'false')
			}

			var maximizeAvatar = function () {
				textIntroModal.modal('hide')

				// Wait until the avatar is loaded in the background initially, or we could see a flash of the wrong character.
				OddcastAvatarExternalModule.afterSceneLoaded(function () {
					oddcastPlayer.find('.character').remove()

					var showId = Cookies.get('oddcast-show-id')
					if (!showId) {
						// The user has not yet selected a character. Show the intro modal again instead.
						textIntroModal.modal('show')
						return
					}

					OddcastAvatarExternalModule.loadShowByID(showId)

					avatar.fadeIn(fadeDuration);
					$('#oddcast-minimize-avatar').show();
					$('#oddcast-maximize-avatar').hide();

					Cookies.set('oddcast-avatar-maximized', 'true')

					if (OddcastAvatarExternalModule.getPlayButton().is(':visible')) {
						// This is the first time a character was picked.  Show the play button tooltip.
						OddcastAvatarExternalModule.afterSceneLoaded(function () {
							var playButton = OddcastAvatarExternalModule.getPlayButton()
							if (playButton.is(':visible')) {
								playButton = playButton[0]
								tippy(playButton)
								playButton._tippy.show()
							}
						})
					}
				})
			}

			$('#oddcast-maximize-avatar').click(maximizeAvatar)
			$('#oddcast-minimize-avatar').click(minimizeAvatar)

			var oddcastPlayer = $('._html5Player')
			oddcastPlayer.click(function (e) {
				// Oddcast sets a touch start handler that prevents our controls from working consistently, and causes exceptions in the mobile Safari console.
				// Luckily we don't need this touch event, so we can just remove it.
				oddcastPlayer.find('.main_container').removeAttr('ontouchstart')
			})

			$('#oddcast-controls .fa-user').click(function () {
				textIntroModal.find('.top-section').html('Select an eStaff member:').css('font-weight', 'bold')
				textIntroModal.find('.bottom-section').hide()
				textIntroModal.find('.modal-dialog').width('350px')

				textIntroModal.modal('show')
			})

			OddcastAvatarExternalModule.getPlayButton().click(function () {
				OddcastAvatarExternalModule.afterSceneLoaded(function () {
					OddcastAvatarExternalModule.sayText(settings.pageMessage)
				})
			})

			$('.oddcast-character').click(function () {
				var showId = $(this).data('show-id')
				Cookies.set('oddcast-show-id', showId)
				maximizeAvatar()
			})

			textIntroModal.find('button').click(function () {
				textIntroModal.modal('hide')
				minimizeAvatar()
			})

			$('body').prepend(wrapper)
			$('#pagecontainer').appendTo($('#oddcast-content'))

			OddcastAvatarExternalModule.initPortraitDialog()
			OddcastAvatarExternalModule.initMessagesForValues(settings.messagesForValues)
			OddcastAvatarExternalModule.initTimeout(settings)

			OddcastAvatarExternalModule.startAvatar = function (isInitialLoad) {
				if (settings.avatarDisabled) {
					return
				}

				if (isInitialLoad) {
					// Forget the show/character chosen from the last survey
					Cookies.remove('oddcast-show-id')

					// If a timeout was active, remove it.
					Cookies.remove('timeout-active')

					textIntroModal.modal('show')
				}
				else if (Cookies.get('oddcast-avatar-maximized') === 'true') {
					maximizeAvatar()
				}
				else {
					$('#oddcast-maximize-avatar').show()
				}
			}

			OddcastAvatarExternalModule.initReviewMode(settings)
		})
	},
	initReviewMode: function (settings) {
		var cookieName = settings.reviewModeCookieName
		var onValue = 'on'
		var turningOffValue = settings.reviewModeTurningOffValue

		var setCookie = function (value) {
			Cookies.set(cookieName, value, {expires: 1})
		}

		var clickPreviousButton = function () {
			var previousButton = $('button[name=submit-btn-saveprevpage]')
			if (previousButton.length == 0) {
				Cookies.remove(cookieName)
				$('body').css('visibility', 'visible') // poor man's loading indicator

				OddcastAvatarExternalModule.startAvatar(true)
			}
			else {
				previousButton.click()
			}
		}

		if (!settings.reviewModeEnabled) {
			Cookies.remove(cookieName)
		}
		else if (settings.isInitialLoad) {
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
			OddcastAvatarExternalModule.startAvatar(settings.isInitialLoad)
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
	initMessagesForValues: function (messagesForValues) {
		var fieldMap = {}
		$.each(messagesForValues, function (i, item) {
			if (!item.field) {
				// The setting hasn't been fully configured.
				return
			}

			if (fieldMap[item.field] == undefined) {
				fieldMap[item.field] = {}
			}

			fieldMap[item.field][item.value.toLowerCase()] = item.message
		})

		$.each(fieldMap, function (fieldName, valueMap) {
			var fields = $('[name=' + fieldName + ']')
			if (fields.length == 0) {
				// Assume this is a set of checkbox fields.
				fields = $('[name=__chkn__' + fieldName + ']')
			}
			else if (fields.hasClass('hiddenradio')) {
				fields = $('[name=' + fieldName + '___radio]')
			}

			fields.change(function () {
				var field = $(this)
				var type = field.attr('type')
				if ($.inArray(type, ['checkbox', 'radio']) !== -1) {
					if (!field.is(':checked')) {
						return
					}
				}

				var value
				if (type == 'checkbox') {
					value = field.attr('code')
				}
				else {
					value = field.val().toLowerCase()
				}

				var message = valueMap[value]
				if (message) {
					OddcastAvatarExternalModule.sayText(message)
				}
			})
		})
	},
	initTimeout: function (settings) {
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

			Cookies.set('timeout-active', true)
		}

		if (Cookies.get('timeout-active')) {
			// setTimeout() was required here to make sure this happened AFTER any REDCap dialogs were displayed (like required field messages).
			setTimeout(showTimeoutModal, 0)
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

				Cookies.remove('timeout-active')
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
		console.log('showId', showId)
		OddcastAvatarExternalModule.scenedLoaded = false

		// loadShow() is designed to load by index, but we don't want to do that since index is affected by adding/removing shows in the list.
		// Setting this window var then omitting the show index parameter will effectively load by show ID instead of index.
		window.vhsshtml5_ss_var = showId
		loadShow()
	},
	onSceneLoaded: function () {
		window.mobile_events = 1 // Required for sayText() to work on iOS/Android

		followCursor(0)
		setIdleMovement(0, 0)

		var avatar = OddcastAvatarExternalModule.getAvatar()
		OddcastAvatarExternalModule.scenedLoaded = true
	},
	getAvatar: function () {
		return $('#oddcast-avatar')
	},
	isEnabled: function () { // This function is used by the inline popups module.
		return OddcastAvatarExternalModule.getAvatar().is(':visible')
	},
	getPlayButton: function () {
		return $('#oddcast-controls .fa-play-circle')
	}
}

// Defining a global function is the standard Oddcast way of hooking into the scene loaded event...
function vh_sceneLoaded() {
	OddcastAvatarExternalModule.onSceneLoaded()
}
