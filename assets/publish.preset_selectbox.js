/**
 * @author Deux Huit Huit | fhamon
 * @license MIT
 */
(function ($, Symphony) {

	'use strict';

	var sels = {
		toggle: '.js-preset-selectbox-toggle',
		content: '.js-preset-selectbox-content',
		ctn: '.field-preset_selectbox',
		inputs: 'input[type="checkbox"]'
	};

	var inputsRestoreState = {};

	var STATE_CLASS = 'is-hidden';

	var computeInitialState = function () {
		$(sels.ctn).each(function () {
			var ctn = $(this);
			var toggle = ctn.find(sels.toggle);
			var content = ctn.find(sels.content);
			var inputs = content.find(sels.inputs);
			var fx = 'addClass';

			inputs.each(function () {
				var t = $(this);
				var id = t.attr('id');
				var isChecked = !!t.prop('checked');

				if (isChecked) {
					fx = 'removeClass';
					inputsRestoreState[id] = isChecked;
				}
			});

			if (!!toggle.length) {
				content[fx](STATE_CLASS);
			}
		});
	};

	var onToggleChange = function () {
		var t = $(this);
		var ctn = t.closest(sels.ctn);
		var content = ctn.find(sels.content);
		var inputs = content.find(sels.inputs);
		var fx = !!t.prop('checked') ? 'removeClass' : 'addClass';
		content[fx](STATE_CLASS);

		if (!t.prop('checked')) {
			inputs.prop('checked', false);
		}
		else {
			inputs.each(function () {
				var input = $(this);
				var id = input.attr('id');
				var isChecked = inputsRestoreState[id];
				input.prop('checked', isChecked);
			});
		}
	};

	var onInputsChange = function () {
		var t = $(this);
		var content = t.closest(sels.content);
		var inputs = content.find(sels.inputs).not(t);

		if (!content.attr('data-multiple')) {
			inputs.prop('checked', false);
		}
	};

	var init = function () {
		computeInitialState();
		$(sels.toggle).on('change', onToggleChange);
		$(sels.content + ' ' + sels.inputs).on('change', onInputsChange);
	};

	$(init);

})(jQuery, window.Symphony);
