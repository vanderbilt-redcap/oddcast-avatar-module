OddcastAvatarExternalModule.addProperties({
	scenedLoaded: false,
	showId: null,
	timeoutVerificationValue: '',
	iFrameLoaded: false,
	isPaused: false,
	initializeParent: function(){
		OddcastAvatarExternalModule.loadIFrame()

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
			textIntroModal.find('.modal-dialog').css('max-width', '625px')

			textIntroModal.modal('show')
		})

		OddcastAvatarExternalModule.getPageMessageButton().click(function () {
			OddcastAvatarExternalModule.afterSceneLoaded(function () {
				OddcastAvatarExternalModule.sayPageMessage('page message played manually')
			})
		})

		OddcastAvatarExternalModule.getPlayButton().click(function(){
			if(OddcastAvatarExternalModule.isPaused){
				freezeToggle()
				OddcastAvatarExternalModule.isPaused = false
				OddcastAvatarExternalModule.togglePlayAndPauseButtons(true)
			}
			else{
				// Replay the last played message
				vhsshtml5_clickPlayButton(0)
			}
		})

		OddcastAvatarExternalModule.getPauseButton().click(function(){
			freezeToggle()
			OddcastAvatarExternalModule.isPaused = true
			OddcastAvatarExternalModule.togglePlayAndPauseButtons(false)
		})

		$('.oddcast-character').click(function () {
			if(!OddcastAvatarExternalModule.isAudioPrimed){
				if(!OddcastAvatarExternalModule.scenedLoaded){
					// We need to wait until the scene is loaded before trying to respond to a click event by playing audio.
					// If we don't, iOS & Android may not allow audio playback at all.
					return
				}
	
				// Android & iOS require a user action (not within an iframe) to trigger the initial playing of audio.
				// In case there is not page message on the first page, we must prime audio so that the next page message will play.
				// We prime before showing the avatar initially so users can't see it's mouth move.
				// The text said doesn't matter because it will get replaced with silence,
				// but we might as well go with a short message to make priming as fast as possible.
				// We must call oddcast's method directly since the avatar isn't really enabled by our definition yet.
				sayText('a', 7, 1, 2)
			}

			// Hide the modal, but leave the background in place (poor man's loading indicator).
			OddcastAvatarExternalModule.getTextIntroModal().hide()

			var showId = $(this).data('show-id')
			OddcastAvatarExternalModule.until(
				function(){
					return OddcastAvatarExternalModule.isAudioPrimed
				},
				function(){
					OddcastAvatarExternalModule.showId = showId
					
					OddcastAvatarExternalModule.maximizeAvatar()
		
					// Hide the play/pause buttons since they won't work again yet if there's not a page message.
					OddcastAvatarExternalModule.getPlayButton().hide()
					OddcastAvatarExternalModule.getPauseButton().hide()
		
					OddcastAvatarExternalModule.log('character selected', {
						'show id': showId
					})
				}
			)
		})

		OddcastAvatarExternalModule.getTextIntroModal().find('button').click(function () {
			OddcastAvatarExternalModule.getTextIntroModal().modal('hide')
			OddcastAvatarExternalModule.minimizeAvatar()
		})

		$('#pagecontainer').hide()
		var wrapper = OddcastAvatarExternalModule.getWrapper()
		$('body').prepend(wrapper)

		wrapper.removeClass('hidden')

		OddcastAvatarExternalModule.initTimeout()
		OddcastAvatarExternalModule.handleViewHeight()

		// Prevent the user from scrolling the avatar and iframe container elements
		$('body').append('<script src="https://cdnjs.cloudflare.com/ajax/libs/inobounce/0.1.6/inobounce.min.js" integrity="sha256-8Lv10+9kieliYluA+S5z1+KwnqLTX4J0FiDyx8FWM2s=" crossorigin="anonymous"></script>')
	},
	// This method accounts for the view height changes on mobile
	// due to the address bar (and bottom bar on iOS) sometimes
	// being visible and sometimes not.
	handleViewHeight: function(){
		var setViewHeight = function(){
			$('#oddcast-wrapper').css('height', window.innerHeight + 'px')
		}

		setViewHeight()
		window.addEventListener('resize', function(){
			setViewHeight()
		})
	},
	loadIFrame: function(){
		var iFrameUrl = location.href.replace('&vorlon', '')

		// This field name is hard coded because it is undocumented/unsupported.
		// If it ever changes, we'd prefer the duplicate log entry deletion to stop workingm
		// rather than a syntax error breaking the entire module.
		var parentTemporaryRecordId = $('input[name=external-modules-temporary-record-id]').val()
		if(parentTemporaryRecordId){
			iFrameUrl += '&' + OddcastAvatarExternalModule.settings.temporaryRecordIdFieldName + '=' + parentTemporaryRecordId
		}
		else{
			console.error('The temporary record id for the parent window could not be detected.  This will likely result in an extraneous survey page load log entry.')

			// In older versions of the framework ExternalModules::isSurveyPage() does not work correctly
			// for REDCap installs that are hosted in a subdirectory instead of directly at a domain url.
			// This breaks temporary recording id generation, which in turn prevents the iframe from
			// loading in IE (and sometimes in Firefox) because the parent url is identical to the iframe url.
			// The following parameter is appended to allow the avatar module to work in that scenario.
			iFrameUrl += '&iframe-matching-parent-url-fix'
		}

		var iFrame = $("<iframe></iframe>")
		$('#oddcast-content').append(iFrame)
		iFrame.attr('src', iFrameUrl) // The src is set separately from the iFrame definition/append to prevent an extra cancelled request in Chrome.
	},
	sayPageMessage: function(logMessage){
		var settings = OddcastAvatarExternalModule.settings
		OddcastAvatarExternalModule.sayText(settings.pageMessage)
		OddcastAvatarExternalModule.log(logMessage, {
			page: settings.currentPageNumber
		})
	},
	startAvatar: function () {
		OddcastAvatarExternalModule.callOnIFrame('hideLoadingOverlay')	

		if(!OddcastAvatarExternalModule.settings.avatarDisabled && !OddcastAvatarExternalModule.introModalInitiallyDisplayed){
			// This is the first time the avatar has been started.  Show the intro modal.
			OddcastAvatarExternalModule.introModalInitiallyDisplayed = true
			OddcastAvatarExternalModule.getTextIntroModal().modal('show')
		}
		else{
			// The user clicked "no thanks", the avatar is disabled via settings, or the intro modal has been displayed before (perhaps on a prior instrument in the same window).
		}
	},
	getWrapper: function () {
		return $('#oddcast-wrapper')
	},
	getTextIntroModal: function () {
		return OddcastAvatarExternalModule.getWrapper().find('.modal.text-intro')
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
		var showTimeoutModal = function () {
			triesRemaining = 5

			if (OddcastAvatarExternalModule.timeoutVerificationValue == '') {
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

		OddcastAvatarExternalModule.updateLastActivity()
		OddcastAvatarExternalModule.onActivity(function(){
			OddcastAvatarExternalModule.updateLastActivity()
		})

		setInterval(function(){
			if (OddcastAvatarExternalModule.isModalDisplayed(OddcastAvatarExternalModule.getTextIntroModal()) && !OddcastAvatarExternalModule.showId) {
				// The intro modal is still displayed for the first time.  Do not constantly reload the page.
				return
			}

			var minutesIdle = (Date.now() - OddcastAvatarExternalModule.lastActivity) / 1000 / 60
			if (!isTimeoutModalDisplayed()) {
				if (minutesIdle >= settings.timeout) {
					showTimeoutModal()
					OddcastAvatarExternalModule.updateLastActivity()
				}
			}
			else if (minutesIdle >= settings.restartTimeout) {
				// The timeout modal is displayed.
				openNewPublicSurvey()
			}
		}, 1000)

		modal.find('button.restart').click(openNewPublicSurvey)

		modal.find('button.continue').click(function () {
			var enteredValue = input.val().trim().toLowerCase()
			if (enteredValue == OddcastAvatarExternalModule.timeoutVerificationValue) {
				modal.modal('hide')

				// Clear the value entered, in case the timeout modal is displayed again.
				input.val('')

				// If we had to hide a REDCap dialog (like a required fields message), re-display it.
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
	updateLastActivity: function(){
		OddcastAvatarExternalModule.lastActivity = Date.now()
	},
	updateTimeoutVerificationValue: function(value){
		if(!value){
			value = ''
		}

		OddcastAvatarExternalModule.timeoutVerificationValue = value.trim().toLowerCase()
	},
	updateSidebarVisibility: function () {
		var visibility
		if (OddcastAvatarExternalModule.settings.avatarDisabled) {
			visibility = 'hidden'
		}
		else{
			visibility = 'visible'
		}

		$('#oddcast-sidebar').css('visibility', visibility)
	},
	onIFrameInitialized: function(settings){
		OddcastAvatarExternalModule.iFrameLoaded = true
		
		// Update all the settings for the current page
		OddcastAvatarExternalModule.settings = settings
		
		OddcastAvatarExternalModule.updateSidebarVisibility()

		// Even if the sidebar visibility hasn't changed, we need to Make sure the iFrame's enabled flag is up to date
		// in case the iFrame wasn't loaded when it last changed.  This can be reproduced by simulating a "Slow 3G" connection
		// in Chrome's developer tools and immediately selecting an avatar when the dialog is displayed.
		OddcastAvatarExternalModule.setEnabledOnIFrame()

		OddcastAvatarExternalModule.handlePageMessage()

		OddcastAvatarExternalModule.callOnIFrame('initReviewMode')

		// Disable the multilingual module in the parent so users don't have to select their language twice.
		$('#p1000Overlay').hide()

		OddcastAvatarExternalModule.hideLoadingOverlay()
	},
	onIFrameUnLoad: function(){
		OddcastAvatarExternalModule.iFrameLoaded = false
	},
	handlePageMessage: function(){
		OddcastAvatarExternalModule.afterSceneLoaded(function(){
			if(!OddcastAvatarExternalModule.isEnabled()){
				return
			}

			var pageMessageButton = OddcastAvatarExternalModule.getPageMessageButton()
			if(OddcastAvatarExternalModule.settings.pageMessage){
				pageMessageButton.show()
				OddcastAvatarExternalModule.sayPageMessage('page message played automatically')
			}
			else{
				pageMessageButton.hide()
			}
		})
	},
	isModalDisplayed: function (modal) {
		return modal.hasClass('show')
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

		// Speech rates are only fully supported in engine #3.
		if(OddcastAvatarExternalModule.engine === '3') {
			var speechRate = OddcastAvatarExternalModule.settings.speechRate
			if (speechRate && speechRate != 100) {
				speechRate = 100 - speechRate

				// Only certain percentage values are accepted (not just any value).
				// They do not appear in the documentation, so trial and error was used to find the list in the settings.
				text = "<PROSODY RATE='-" + speechRate + "%'>" + text + "</PROSODY>"
			}
		}

		stopSpeech()
		sayText(text, OddcastAvatarExternalModule.person, 1, OddcastAvatarExternalModule.engine)
	},
	loadShowByID: function (showId) {
		OddcastAvatarExternalModule.scenedLoaded = false

		// loadShow() is designed to load by index, but we don't want to do that since index is affected by adding/removing shows in the list.
		// Setting this window var then omitting the show index parameter will effectively load by show ID instead of index.
		window.vhsshtml5_ss_var = showId

		// Oddcast didn't used to require this to be set, but now they require it for loadShow() to trigger the vh_sceneLoaded() call.
		vhsshtml5_vh_sceneLoaded = 0

		loadShow()
	},
	onSceneLoaded: function () {
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
		return OddcastAvatarExternalModule.getAvatar().is(':visible') && $('#oddcast-sidebar').css('visibility') === 'visible'
	},
	getPageMessageButton: function () {
		return $('#oddcast-controls .fa-comment')
	},
	log: function (message, parameters) {
		OddcastAvatarExternalModule.callOnIFrame('log', message, parameters)
	},
	minimizeAvatar: function () {
		OddcastAvatarExternalModule.stopSpeech();
		OddcastAvatarExternalModule.getAvatar().fadeOut(OddcastAvatarExternalModule.fadeDuration, function(){
			OddcastAvatarExternalModule.setEnabledOnIFrame()
		});

		$('#oddcast-minimize-avatar').hide();
		$('#oddcast-maximize-avatar').show();

		OddcastAvatarExternalModule.log('avatar disabled')
	},
	maximizeAvatar: function () {
		var settings = OddcastAvatarExternalModule.settings
		var textIntroModal = OddcastAvatarExternalModule.getTextIntroModal()

		// Hide the dialog but leave the backdrop displayed until the scene is loaded.
		textIntroModal.hide()

		// Wait until the avatar is loaded in the background initially, or we could see a flash of the wrong character.
		OddcastAvatarExternalModule.afterSceneLoaded(function () {
			// The initial show is loaded, but we may not want to use this one so we load our own show later.

			// Wait to hide the modal until a scene has been loaded to prevent the survey from shifting down on slow connections.
			textIntroModal.modal('hide')

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

			OddcastAvatarExternalModule.getAvatar().fadeIn(OddcastAvatarExternalModule.fadeDuration, function(){
				OddcastAvatarExternalModule.setEnabledOnIFrame()
			});

			// Handle the page message every time the avatar is maximized, in case the user hasn't heard the message for the current page
			OddcastAvatarExternalModule.handlePageMessage()

			$('#oddcast-minimize-avatar').show();
			$('#oddcast-maximize-avatar').hide();
		})
	},
	setEnabledOnIFrame: function(){
		if(OddcastAvatarExternalModule.iFrameLoaded){
			OddcastAvatarExternalModule.callOnIFrame('setEnabled', OddcastAvatarExternalModule.isEnabled())
		}
	},
	callOnIFrame: function(){
		// Any number of issues can occur if we attempt to access the iFrame when it is not yet fully loaded.
		// This can be reproduced by using setTimeout() to delay the loading of the iFrame for a second or so in initializeParent(),
		// then by selecting an avatar character before the iFrame is fully loaded.
		// The 'character selected' message will attempt to be logged before the iFrame is initialized.
		// This problem is much more common on slower connections.
		if(!OddcastAvatarExternalModule.iFrameLoaded){
			var argumentsArray = Array.prototype.slice.call(arguments)

			// console.log('An iFrame call could not be completed because the iFrame is not yet loaded.  It will be reattempted shortly.  Here are the arguments: ', argumentsArray)

			setTimeout(function(){
				OddcastAvatarExternalModule.callOnIFrame.apply(OddcastAvatarExternalModule, argumentsArray)
			}, 50)

			return
		}

		OddcastAvatarExternalModule.callOnTarget($('#oddcast-content > iframe')[0].contentWindow, arguments)
	},
	togglePlayAndPauseButtons: function(isPlaying){
		var playButton = this.getPlayButton()
		var pauseButton = this.getPauseButton()

		if(isPlaying){
			playButton.hide()
			pauseButton.show()
		}
		else{
			pauseButton.hide()
			playButton.show()
		}
	},
	getPlayButton: function(){
		return $('#oddcast-controls .fa-play-circle')
	},
	getPauseButton: function(){
		return $('#oddcast-controls .fa-pause-circle')
	}
})

// Defining these global functions is the standard Oddcast way of hooking into events as documented here:
// http://www.oddcast.com/support/docs/vhost_API_Reference.pdf

function vh_sceneLoaded() {
	OddcastAvatarExternalModule.until(
		function(){
			// Oddcast is not fully initialized until the following flags are both set.
			// We must wait or audio sometimes won't prime correctly on mobile.
			return window.vhsshtml5_characterLoaded && window.mobile_events
		},
		OddcastAvatarExternalModule.onSceneLoaded
	)
}

function vh_talkStarted() {
	if(!OddcastAvatarExternalModule.isAudioPrimed){
		return // don't show the pause/play buttons if we're just priming audio
	}

	OddcastAvatarExternalModule.togglePlayAndPauseButtons(true)
}

function vh_talkEnded() {
	if(!OddcastAvatarExternalModule.isAudioPrimed){
		OddcastAvatarExternalModule.isAudioPrimed = true

		return // don't show the pause/play buttons if we're just priming audio
	}

	OddcastAvatarExternalModule.togglePlayAndPauseButtons(false)
}

OddcastAvatarExternalModule.initializeParent()
