(function($) {
	Monthpicker = function() {};
	$.extend(Monthpicker.prototype, {
		/**
		 * Highjacks the datepicker.
		 * Remove the default day selection and replaces it with month selection.
		 * Removes the default month selection.
		 * Highjacks also the adjustDate functionality of datepicker, so switch between years, not months
		 * @param {Object} inst Current instance of datepicker
		 */
		_hiJackThis: function(inst) {
			// get the datepicker container
			var datepicker = $('#ui-datepicker-div');
			// prevent calendar from flicker
			$('.ui-datepicker-calendar').css('visibility', 'hidden');
			// remove month selector if available
			$('.ui-datepicker-month').remove();
			// highjack prev and next button in header
			// override onclick event to switch only years
			this._reassignOnClick($('.ui-datepicker-prev'));
			this._reassignOnClick($('.ui-datepicker-next'));
			// overwrite html
			$('.ui-datepicker-calendar').html(this._generateHTML(inst));
			// show calendar again
			$('.ui-datepicker-calendar').css('visibility', 'visible');
		},
		/**
		 * Used to highjack the cached hardcoded onclick attribute of elements.
		 * Its copys the onclick function to the data array of the element and removes the onclick attribute in html.
		 * After that, the function value will be altered to call the new function
		 * and a new click event will be bound to the element, to call the cached and altered function.
		 * @param {Object} element 	JQuery element
		 * @param {String} search  	The string to search for in the function (e.g. '.datepicker')
		 * @param {String} replace	The string for the replacement
		 */
		_reassignOnClick: function(element, search, replace) {
			// check if element has onclick
			if(element.attr('onclick')) {
				// save cached handler to data array of object
				$.data(element.get(0), 'onclickmethod', element.attr('onclick').toString());
				// remove old onclick
				element.removeAttr('onclick');
				
				var methodstringPrev = element.data('onclickmethod')
														   .replace('.datepicker', '.monthpicker');
				// assign onclick event to element
				element.click(function(e) {
					var clickMethodPrev = eval("(" + methodstringPrev + ")");
					clickMethodPrev(e);
				});
			}
		},
		_generateHTML: function(inst) {
			var tbody = $('<tbody/>');
			var tr,
				td,
				currentDate;
			
			var minDate = $.datepicker._getMinMaxDate(inst, 'min');
			var maxDate = $.datepicker._getMinMaxDate(inst, 'max');
			
			// loop over 4 rows, each row 3 months
			for(var row = 0; row < 4; row++) {
				tr = $('<tr/>');
				// loop up to 3 months per row
				for(var cell = row*3; cell < row*3+3; cell++) {
					currentDate = new Date(inst.selectedYear, cell, 1);
					
					// check if month is selectedable
					if(currentDate >= maxDate || currentDate <= minDate) {
						td = $('<td/>', {'class': 'ui-datepicker-unselectable ui-state-disabled'});
						// create a link and append to td
						$('<span/>', {'style': 'text-align: center; display:block; line-height: 30px;','class': 'ui-state-default'}).html($.datepicker._defaults.monthNamesShort[cell]).appendTo(td);
					}
					else
					{
						td = $('<td/>', {'style': 'text-align:center'});
						// create a link and append to td
						$('<a/>', {'style': 'text-align:center; display:block; line-height: 30px;', 'href': '#', 'class': 'ui-state-default'}).html($.datepicker._defaults.monthNamesShort[cell]).appendTo(td);
						
						td.click(function(e) {
							e.preventDefault();
							$.monthpicker._selectMonth(this, inst);
						});
					}
					tr.append(td);
				}
				tbody.append(tr);
			}
			
			// reassign mouseover/-out events
			$('td a', tbody)
				.mouseover(function() {
					$(this).addClass('ui-state-hover');
				})
				.mouseout(function() {
					$(this).removeClass('ui-state-hover');
			});
			
			return tbody;
		},
		_adjustDate: function(id, offset, period) {
			jQuery.datepicker._adjustDate(id, offset, 'Y');
		},
		/**
		 * Selects a month
		 * @param {int} month selected month
		 * @param {Object} inst The current datepicker instance
		 */
		_selectMonth: function(element, inst) {
			var month = $.datepicker._defaults.monthNamesShort.indexOf($('a', element).html());
			// create a fake td
			$.datepicker._selectDay('#'+inst.id, month, inst.selectedYear, 1);
		}
	});
	
	$.monthpicker = new Monthpicker();
	
	// override rendering of datepicker
	// after rendering, highjack the html code
	$.datepicker = $.extend($.datepicker, {
		_updateDatepicker: function(inst) {
			var self = this;
			var borders = $.datepicker._getBorders(inst.dpDiv);
			inst.dpDiv.empty().append(this._generateHTML(inst))
				.find('iframe.ui-datepicker-cover') // IE6- only
					.css({left: -borders[0], top: -borders[1],
						width: inst.dpDiv.outerWidth(), height: inst.dpDiv.outerHeight()})
				.end()
				.find('button, .ui-datepicker-prev, .ui-datepicker-next, .ui-datepicker-calendar td a')
					.bind('mouseout', function(){
						$(this).removeClass('ui-state-hover');
						if(this.className.indexOf('ui-datepicker-prev') != -1) $(this).removeClass('ui-datepicker-prev-hover');
						if(this.className.indexOf('ui-datepicker-next') != -1) $(this).removeClass('ui-datepicker-next-hover');
					})
					.bind('mouseover', function(){
						if (!self._isDisabledDatepicker( inst.inline ? inst.dpDiv.parent()[0] : inst.input[0])) {
							$(this).parents('.ui-datepicker-calendar').find('a').removeClass('ui-state-hover');
							$(this).addClass('ui-state-hover');
							if(this.className.indexOf('ui-datepicker-prev') != -1) $(this).addClass('ui-datepicker-prev-hover');
							if(this.className.indexOf('ui-datepicker-next') != -1) $(this).addClass('ui-datepicker-next-hover');
						}
					})
				.end()
				.find('.' + this._dayOverClass + ' a')
					.trigger('mouseover')
				.end();
			var numMonths = this._getNumberOfMonths(inst);
			var cols = numMonths[1];
			var width = 17;
			if (cols > 1)
				inst.dpDiv.addClass('ui-datepicker-multi-' + cols).css('width', (width * cols) + 'em');
			else
				inst.dpDiv.removeClass('ui-datepicker-multi-2 ui-datepicker-multi-3 ui-datepicker-multi-4').width('');
			inst.dpDiv[(numMonths[0] != 1 || numMonths[1] != 1 ? 'add' : 'remove') +
				'Class']('ui-datepicker-multi');
			inst.dpDiv[(this._get(inst, 'isRTL') ? 'add' : 'remove') +
				'Class']('ui-datepicker-rtl');
			if (inst == $.datepicker._curInst && $.datepicker._datepickerShowing && inst.input &&
					inst.input.is(':visible') && !inst.input.is(':disabled'))
				inst.input.focus();
			
			// hijack
			$.monthpicker._hiJackThis(inst);
		},
		_selectDay: function(id, month, year, day) {
			var target = $(id);
			var inst = this._getInst(target[0]);
			inst.selectedDay = inst.currentDay = day;
			inst.selectedMonth = inst.currentMonth = month;
			inst.selectedYear = inst.currentYear = inst.drawYear = year;
			this._selectDate(id, this._formatDate(inst,
				inst.currentDay, inst.currentMonth, inst.currentYear));
		},
		/* Determines if we should allow a "next/prev" year display change. */
		_canAdjustMonth: function(inst, offset, curYear, curMonth) {
			// fix offset
			var date = this._daylightSavingAdjust(new Date(curYear + offset, curMonth));
			return this._isInRange(inst, date);
		}
});
})(jQuery);