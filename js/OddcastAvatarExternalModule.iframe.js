OddcastAvatarExternalModule.addProperties({
	initializeIFrame: function () {
		// The following improves scrolling on iPad.
		// For unknown reasons this line doesn't work when added via style.css.
		$('body').css('-webkit-overflow-scrolling', 'touch')

		OddcastAvatarExternalModule.initMessagesForValues()
		OddcastAvatarExternalModule.initTimeout()
		OddcastAvatarExternalModule.initTTS()

		$(window).on('beforeunload', function(){
			OddcastAvatarExternalModule.callOnParent('onIFrameUnLoad')
		})
		
		// This must occur before review mode is initialized, so the correct page message is in place when avatar is initialized.
		OddcastAvatarExternalModule.callOnParent('onIFrameInitialized', OddcastAvatarExternalModule.settings)
	},
	initTTS: function () {
		var originalPlayAudioObject = window.playAudioObject
		window.playAudioObject = function(ob,iconob,event){
			var originalIsMobileDevice = isMobileDevice
			var originalIsIOS = isIOS
			var originalAgt = agt

			// REDCap normally doesn't support TTS on mobile/tablet.
			// Override these flags to trick it into allowing this call anyway, since the avatar will be reading the message instead.
			isMobileDevice = false
			isIOS = false
			agt = ''

			var speakerIconUrl = iconob.src
			var argumentArray = Array.prototype.slice.call(arguments)
			originalPlayAudioObject.apply(null, argumentArray)
			iconob.src = speakerIconUrl

			isMobileDevice = originalIsMobileDevice
			isIOS = originalIsIOS
			agt = originalAgt
		}

		window.playAudio = function(text, iconob){
			OddcastAvatarExternalModule.sayText(text)
		}
	},
	initMessagesForValues: function () {
		var messagesForValues = OddcastAvatarExternalModule.settings.messagesForValues
		var instrument = OddcastAvatarExternalModule.settings.instrument
		var fieldMap = {}
		$.each(messagesForValues, function (i, item) {
			if (!item.field || !item.value || !item.message) {
				// The setting hasn't been fully configured.
				return
			}

			var forms = item['messages-for-field-values-forms']
			if(forms === undefined || forms.length === 1 && !forms[0]){
				// The form value is null or empty string, meaning no form was selected.  Remove this empty value.
				forms = []
			}

			if(forms.length > 0 && forms.indexOf(instrument) === -1){
				// This message isn't configured for this form.
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
					OddcastAvatarExternalModule.log('message for value played', {
						field: fieldName,
						value: value
					})
				}
			})
		})
	},
	initTimeout: function(){
		OddcastAvatarExternalModule.onActivity(function(){
			OddcastAvatarExternalModule.callOnParent('updateLastActivity')
		})

		var field = $('input[name=' + OddcastAvatarExternalModule.settings.timeoutVerificationFieldName + ']')
		field.change(function(){
			OddcastAvatarExternalModule.callOnParent('updateTimeoutVerificationValue', field.val())
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

		var startAvatar = function () {
			getSubmitButton().prop('disabled', false)
			OddcastAvatarExternalModule.callOnParent('startAvatar')
		}

		var reviewModeFooter = $('<div id="oddcast-review-mode-footer">You are currently in Review Mode.<br><button>Click here to begin consent</button></div>')

		var clickPreviousButton = function () {
			var previousButton = $('button[name=submit-btn-saveprevpage]')
			if (previousButton.length == 0) {
				Cookies.remove(cookieName)
				reviewModeFooter.remove() // Important if we're already on the first page
				OddcastAvatarExternalModule.hideLoadingOverlay()

				startAvatar()

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
			reviewModeFooter.find('button').click(function () {
				setCookie(turningOffValue)
				clickPreviousButton()
			})

			$('#pagecontent').append(reviewModeFooter)

			// Remove the econsent checkbox's id (if present) so it can't be initialized.  Clicking this checkbox would re-enable the submit button.
			$('#econsent_confirm_checkbox').removeAttr('id')

			OddcastAvatarExternalModule.hideLoadingOverlay()
		}
		else if (value == turningOffValue) {
			clickPreviousButton()
		}
		else{
			// Review mode has been exited, and we're on a page other than one.
			OddcastAvatarExternalModule.hideLoadingOverlay()
		}
	},
	// This method is referenced by the Inline Popups module.
	sayText: function(message){
		OddcastAvatarExternalModule.callOnParent('sayText', message)
	},
	// This method is referenced by the Analytics module.
	stopSpeech: function () {
		OddcastAvatarExternalModule.callOnParent('stopSpeech')
	},
	setEnabled: function(value){
		OddcastAvatarExternalModule.enabled = value
	},
	// This method is referenced by the Inline Popups module.
	isEnabled: function(){
		return OddcastAvatarExternalModule.enabled
	},
	callOnParent: function(){
		OddcastAvatarExternalModule.callOnTarget(window.parent, arguments)
	},
	log: function (message, parameters) {
		if (OddcastAvatarExternalModule.settings.loggingSupported) {
			ExternalModules.Vanderbilt.OddcastAvatarExternalModule.log(message, parameters)
		}
	}
})

OddcastAvatarExternalModule.initializeIFrame()