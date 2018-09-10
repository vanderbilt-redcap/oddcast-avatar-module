OddcastAvatarExternalModule = {
	settings: OddcastAvatarExternalModule.settings,
	initialize: function () {
		// The following improves scrolling on iPad.
		// For unknown reasons this line doesn't work when added via style.css.
		$('body').css('-webkit-overflow-scrolling', 'touch')

		OddcastAvatarExternalModule.initMessagesForValues()
	},
	initMessagesForValues: function () {
		var messagesForValues = OddcastAvatarExternalModule.settings.messagesForValues
		var fieldMap = {}
		$.each(messagesForValues, function (i, item) {
			if (!item.field || !item.value || !item.message) {
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
					OddcastAvatarExternalModule.log('message for value played', {
						field: fieldName,
						value: value
					})
				}
			})
		})
	}
}

OddcastAvatarExternalModule.initialize()