/*!
 *  jQuery selectBox - A cosmetic, styleable replacement for SELECT elements
 *
 *  Copyright 2012 Cory LaViska for A Beautiful Site, LLC.
 *
 *  https://github.com/claviska/jquery-selectBox
 *
 *  Licensed under both the MIT license and the GNU GPLv2 (same as jQuery: http://jquery.org/license)
 *
 *
 *  Note: Modified for vBulletin. Grep for 'vBulletin' to see the changes.
 */
if(jQuery) (function($) {

	$.extend($.fn, {

		selectBox: function(method, data) {

			var typeTimer,
				typeSearch = '',
				isMac = navigator.platform.match(/mac/i);

			//
			// Private methods
			//

			var init = function(select, data) {

				var options;

				// Disable for iOS devices (their native controls are more suitable for a touch device)
				if( navigator.userAgent.match(/iPad|iPhone|Android|IEMobile|BlackBerry/i) ) return false;

				// Element must be a select control
				if( select.tagName.toLowerCase() !== 'select' ) return false;

				select = $(select);
				if( select.data('selectBox-control') ) return false;

				//added check for hidden select boxes for vBulletin
				if( select.is(':hidden') && (!data || !data.allowHidden) ) return false;

				var control = $('<a class="selectBox" />'),
					inline = select.attr('multiple') || parseInt(select.attr('size')) > 1;

				var settings = data || {};

				control
					.addClass(select.attr('class'))
					.attr('title', select.attr('title') || '')
					.attr('tabindex', parseInt(select.attr('tabindex')))
					.css('display', 'inline-block')
					.on('focus.selectBox', function() {
						if( this !== document.activeElement && document.body !== document.activeElement ) $(document.activeElement).blur();
						if( control.hasClass('selectBox-active') ) return;
						control.addClass('selectBox-active');
						select.trigger('focus');
					})
					.on('blur.selectBox', function() {
						if( !control.hasClass('selectBox-active') ) return;
						control.removeClass('selectBox-active');
						select.trigger('blur');
					});

				if( !$(window).data('selectBox-bindings') ) {
					$(window)
						.data('selectBox-bindings', true)
						.on('scroll.selectBox', hideMenus)
						.on('resize.selectBox', hideMenus);
				}

				if( select.attr('disabled') ) control.addClass('selectBox-disabled');

				// Focus on control when label is clicked
				select.on('click.selectBox', function(event) {
					control.focus();
					event.preventDefault();
				});

				// Generate control
				if( inline ) {

					//
					// Inline controls
					//
					options = getOptions(select, 'inline');

					control
						.append(options)
						.data('selectBox-options', options)
						.addClass('selectBox-inline selectBox-menuShowing')
						.on('keydown.selectBox', function(event) {
							handleKeyDown(select, event);
						})
						.on('keypress.selectBox', function(event) {
							handleKeyPress(select, event);
						})
						.on('mousedown.selectBox', function(event) {
							if( $(event.target).is('A.selectBox-inline') ) event.preventDefault();
							if( !control.hasClass('selectBox-focus') ) control.focus();
						})
						.insertAfter(select);

					//added for vBulletin
					//moved setting of width here and considered font size to get more accurate select width
					control.width(select.css('fontSize', control.css('fontSize')).outerWidth());

					// Auto-height based on size attribute
					if( !select[0].style.height ) {

						var size = select.attr('size') ? parseInt(select.attr('size')) : 5;

						// Draw a dummy control off-screen, measure, and remove it
						var tmp = control
							.clone()
							.removeAttr('id')
							.css({
								position: 'absolute',
								top: '-9999em'
							})
							.show()
							.appendTo('body');
						tmp.find('.selectBox-options').html('<li><a>\u00A0</a></li>');
						var optionHeight = parseInt(tmp.find('.selectBox-options A:first').html('&nbsp;').outerHeight());
						tmp.remove();

						control.height(optionHeight * size);

					}

					disableSelection(control);

				} else {

					//
					// Dropdown controls
					//
					var label = $('<span class="selectBox-label" />'),
						arrow = $('<span class="selectBox-arrow" />');
						// modified for vBulletin -- add the fontawesome icon classes.
						// doing it here instead of in global.js allows for us to calculate
						// the element widths properly if we need to in the future.
						// extraClass = select.data('vb-arrow-extra'),
						// arrow = $('<span class="selectBox-arrow ' + extraClass + '" />');

					// Update label
					label
						.attr('class', getLabelClass(select))
						.text(getLabelText(select));

					options = getOptions(select, 'dropdown');
					options.appendTo('BODY');

					control
						.data('selectBox-options', options)
						.addClass('selectBox-dropdown')
						.append(label)
						.append(arrow)
						.on('mousedown.selectBox', function(event) {
							if( control.hasClass('selectBox-menuShowing') ) {
								hideMenus();
							} else {
								event.stopPropagation();
								// Webkit fix to prevent premature selection of options
								options.data('selectBox-down-at-x', event.screenX).data('selectBox-down-at-y', event.screenY);
								showMenu(select);
							}
						})
						.on('keydown.selectBox', function(event) {
							handleKeyDown(select, event);
						})
						.on('keypress.selectBox', function(event) {
							handleKeyPress(select, event);
						})
						.on('open.selectBox', function(event, triggerData) {
							if(triggerData && triggerData._selectBox === true) return;
							showMenu(select);
						})
						.on('close.selectBox', function(event, triggerData) {
							if(triggerData && triggerData._selectBox === true) return;
							hideMenus();
						})
						.insertAfter(select);

					// added for vBulletin
					// Set the fontsize (& any horizontal padding) of the selectBox style to the original select for a more accurate
					// select width.
					// Note that the .h-pre-selectbox CSS sets the fontsize & paddings as well as accounts for the arrow's width &
					// its effective margin/spacing for the most accurate expected width.
					// Note that the helper class is selectively set currently, as to avoid unintended changes. If/once we tag ALL
					// selects affected by selectBox with this helper class in one way or another, we won't have to add the class
					// via JS here. Note that the JS addition here does NOT prevent reflow, it only makes the resulting width consistent
					// between selects that already have the helper class (which does mitigate reflow) & ones that do not yet.
					let selectWidth = select.addClass('h-pre-selectbox').outerWidth();
					control.width(selectWidth);

					// Set label width
					var labelWidth = control.width() - arrow.outerWidth() - parseInt(label.css('paddingLeft')) - parseInt(label.css('paddingRight'));
					label.width(labelWidth);

					disableSelection(control);

				}

				// Removing this, as it seems to be logic to account for any padding. This is handled via the .h-pre-select logic above. I thought
				// it *may* do something for the multi-select, which is NOT handled via the .h-pre-select logic, but the few places we have that
				// (channel filter multi-select in advanced search, search widget config) do not seem affected by this (they do not seem to pass the
				// controlWidth <= parent.innerWidth .. check).
				/*
				//added for vBulletin
				//increase width of control based on the css padding of the options and the approximate 4px "padding" of the native select options
				//(control's width is set the same as the native select's outerWidth)
				let option = $('.selectBox-label', control),
					adjustmentWidth = parseInt(option.css('paddingLeft')) + parseInt(option.css('paddingRight')) - 4,
					controlWidth = control.width() + adjustmentWidth;


				//if <select>'s width is set to 100%, the custom dropdown's width will be wider than the <select>'s (because the arrow button width of the custom dropdown is wider than the <select>'s).
				//so we only apply the adjustment width when there is room (the option label text *may* be cut-off but this will be rare)
				if (controlWidth <= control.parent().innerWidth())
				{
					control.width(Math.max(controlWidth, 50));
					option.width(option.width() + adjustmentWidth);
				}
				*/


				// Store data for later use and show the control
				select
					.addClass('selectBox')
					.data('selectBox-control', control)
					.data('selectBox-settings', settings)
					.hide();

			};


			var getOptions = function(select, type) {
				var options;

				// Private function to handle recursion in the getOptions function.
				var _getOptions = function(select, options) {
					// Loop through the set in order of element children.
					select.children('OPTION, OPTGROUP').each( function() {
						// If the element is an option, add it to the list.
						if ($(this).is('OPTION')) {
							// Check for a value in the option found.
							if($(this).length > 0) {
								// Create an option form the found element.
								generateOptions($(this), options);
							}
							else {
								// No option information found, so add an empty.
								options.append('<li>\u00A0</li>');
							}
						}
						else {
							// If the element is an option group, add the group and call this function on it.
							var optgroup = $('<li class="selectBox-optgroup" />');
							optgroup.text($(this).attr('label'));
							options.append(optgroup);
							options = _getOptions($(this), options);
						}
					});
					// Return the built strin
					return options;
				};

				switch( type ) {

					case 'inline':

						options = $('<ul class="selectBox-options" />');
						options = _getOptions(select, options);

						options
							.find('A')
								.on('mouseover.selectBox', function(event) {
									addHover(select, $(this).parent());
								})
								.on('mouseout.selectBox', function(event) {
									removeHover(select, $(this).parent());
								})
								.on('mousedown.selectBox', function(event) {
									event.preventDefault(); // Prevent options from being "dragged"
									if( !select.selectBox('control').hasClass('selectBox-active') ) select.selectBox('control').focus();
								})
								.on('mouseup.selectBox', function(event) {
									hideMenus();
									selectOption(select, $(this).parent(), event);
								});

						disableSelection(options);

						return options;

					case 'dropdown':
						options = $('<ul class="selectBox-dropdown-menu selectBox-options" />');
						options = _getOptions(select, options);

						options
							.data('selectBox-select', select)
							.css('display', 'none')
							.appendTo('BODY')
							.find('A')
								.on('mousedown.selectBox', function(event) {
									event.preventDefault(); // Prevent options from being "dragged"
									if( event.screenX === options.data('selectBox-down-at-x') && event.screenY === options.data('selectBox-down-at-y') ) {
										options.removeData('selectBox-down-at-x').removeData('selectBox-down-at-y');
										hideMenus();
									}
								})
								.on('mouseup.selectBox', function(event) {
									if( event.screenX === options.data('selectBox-down-at-x') && event.screenY === options.data('selectBox-down-at-y') ) {
										return;
									} else {
										options.removeData('selectBox-down-at-x').removeData('selectBox-down-at-y');
									}
									selectOption(select, $(this).parent());
									hideMenus();
								}).on('mouseover.selectBox', function(event) {
									addHover(select, $(this).parent());
								})
								.on('mouseout.selectBox', function(event) {
									removeHover(select, $(this).parent());
								});

						// Inherit classes for dropdown menu
						var classes = select.attr('class') || '';
						if( classes !== '' ) {
							classes = classes.split(' ');
							for( var i in classes ) options.addClass(classes[i] + '-selectBox-dropdown-menu');
						}

						disableSelection(options);

						return options;

				}

			};


			var getLabelClass = function(select) {
				var selected = $(select).find('OPTION:selected');
				return ('selectBox-label ' + (selected.attr('class') || '')).replace(/\s+$/, '');
			};


			var getLabelText = function(select) {
				var selected = $(select).find('OPTION:selected');
				// fix for vBulletin VBV-20334 (minification replaces '\u00A0' with something weird)
				return selected.text() || String.fromCharCode(0x00A0);
			};


			var setLabel = function(select) {
				select = $(select);
				var control = select.data('selectBox-control');
				if( !control ) return;
				control.find('.selectBox-label').attr('class', getLabelClass(select)).text(getLabelText(select));
			};


			var destroy = function(select) {

				select = $(select);
				var control = select.data('selectBox-control');
				if( !control ) return;
				var options = control.data('selectBox-options') || $();//added fallback object for safety as .data() could return undefined (added for vBulletin)

				options.remove();
				control.remove();
				select
					.removeClass('selectBox')
					.removeData('selectBox-control').data('selectBox-control', null)
					.removeData('selectBox-settings').data('selectBox-settings', null)
					.show();

			};


			var refresh = function(select) {
				select = $(select);
				select.selectBox('options', select.html());
			};


			var showMenu = function(select) {

				select = $(select);
				var control = select.data('selectBox-control') || $(), //added fallback object for safety as .data() could return undefined (added for vBulletin)
					settings = select.data('selectBox-settings') || {}, //added fallback object for safety as .data() could return undefined (added for vBulletin)
					options = control.data('selectBox-options') || $(); //added fallback object for safety as .data() could return undefined (added for vBulletin)
				if( control.hasClass('selectBox-disabled') ) return false;

				hideMenus();

				//original code (always returns 0 because isNaN check will always return true as the border width value contains a unit (e.g. '1px')
				//var borderBottomWidth = isNaN(control.css('borderBottomWidth')) ? 0 : parseInt(control.css('borderBottomWidth'));

				//fixed for vBulletin
				var borderBottomWidth = parseInt(control.css('borderBottomWidth')) || 0;

				// Menu position
				options
					.width(control.innerWidth())
					.css({
						top: control.offset().top + control.outerHeight() - borderBottomWidth,
						left: control.offset().left
					});

				if( select.triggerHandler('beforeopen') ) return false;
				//Added the isScrolledIntoView how we should display the select (up or down of the select)
				var dispatchOpenEvent = function() {
					select.triggerHandler('open', { _selectBox: true });
					if (!isScrolledIntoView(options)) {
						options.css('top', parseFloat(options.css('top')) - (control.outerHeight() + options.outerHeight()));
					}
				};

				// Show menu
				switch( settings.menuTransition ) {

					case 'fade':
						options.fadeIn(settings.menuSpeed, dispatchOpenEvent);
						break;

					case 'slide':
						options.slideDown(settings.menuSpeed, dispatchOpenEvent);
						break;

					default:
						options.show(settings.menuSpeed, dispatchOpenEvent);
						break;

				}

				if( !settings.menuSpeed ) dispatchOpenEvent();

				// Center on selected option
				var li = options.find('.selectBox-selected:first');
				keepOptionInView(select, li, true);
				addHover(select, li);

				control.addClass('selectBox-menuShowing');

				$(document).on('mousedown.selectBox', function(event) {
					if( $(event.target).parents().addBack().hasClass('selectBox-options') ) return;
					hideMenus();
				});

			};


			var hideMenus = function() {

				if( $(".selectBox-dropdown-menu:visible").length === 0 ) return;
				$(document).off('mousedown.selectBox');

				$(".selectBox-dropdown-menu").each( function() {

					var options = $(this),
						select = options.data('selectBox-select') || $(),
						control = select.data('selectBox-control') || $(),
						settings = select.data('selectBox-settings') || {};

					if( select.triggerHandler('beforeclose') ) return false;

					var dispatchCloseEvent = function() {
						select.triggerHandler('close', { _selectBox: true });
					};

					switch( settings.menuTransition ) {

						case 'fade':
							options.fadeOut(settings.menuSpeed, dispatchCloseEvent);
							break;

						case 'slide':
							options.slideUp(settings.menuSpeed, dispatchCloseEvent);
							break;

						default:
							options.hide(settings.menuSpeed, dispatchCloseEvent);
							break;

					}

					if( !settings.menuSpeed ) dispatchCloseEvent();

					control.removeClass('selectBox-menuShowing');

				});

			};


			var selectOption = function(select, li, event) {

				select = $(select);
				li = $(li);
				var control = select.data('selectBox-control') || $(),
					settings = select.data('selectBox-settings') || {};

				if( control.hasClass('selectBox-disabled') ) return false;
				if( li.length === 0 || li.hasClass('selectBox-disabled') ) return false;

				if( select.attr('multiple') ) {

					// If event.shiftKey is true, this will select all options between li and the last li selected
					if( event.shiftKey && control.data('selectBox-last-selected') ) {

						li.toggleClass('selectBox-selected');

						var affectedOptions;
						if( li.index() > control.data('selectBox-last-selected').index() ) {
							affectedOptions = li.siblings().slice(control.data('selectBox-last-selected').index(), li.index());
						} else {
							affectedOptions = li.siblings().slice(li.index(), control.data('selectBox-last-selected').index());
						}

						affectedOptions = affectedOptions.not('.selectBox-optgroup, .selectBox-disabled');

						if( li.hasClass('selectBox-selected') ) {
							affectedOptions.addClass('selectBox-selected');
						} else {
							affectedOptions.removeClass('selectBox-selected');
						}

					} else if( (isMac && event.metaKey) || (!isMac && event.ctrlKey) ) {
						li.toggleClass('selectBox-selected');
					} else {
						li.siblings().removeClass('selectBox-selected');
						li.addClass('selectBox-selected');
					}

				} else {
					li.siblings().removeClass('selectBox-selected');
					li.addClass('selectBox-selected');
				}

				if( control.hasClass('selectBox-dropdown') ) {
					control.find('.selectBox-label').text(li.text());
				}

				// Update original control's value
				var i = 0, selection = [];
				if( select.attr('multiple') ) {
					control.find('.selectBox-selected A').each( function() {
						selection[i++] = $(this).attr('rel');
					});
				} else {
					selection = li.find('A').attr('rel');
				}

				// Remember most recently selected item
				control.data('selectBox-last-selected', li);

				// Change callback
				if( select.val() !== selection ) {
					select.val(selection);
					setLabel(select);
					select.trigger('change');
				}

				return true;

			};


			var addHover = function(select, li) {
				select = $(select);
				li = $(li);
				var control = select.data('selectBox-control') || $(), //added fallback object for safety as .data() could return undefined (added for vBulletin)
					options = control.data('selectBox-options') || $(); //added fallback object for safety as .data() could return undefined (added for vBulletin)

				options.find('.selectBox-hover').removeClass('selectBox-hover');
				li.addClass('selectBox-hover');
			};


			var removeHover = function(select, li) {
				select = $(select);
				li = $(li);
				var control = select.data('selectBox-control') || $(), //added fallback object for safety as .data() could return undefined (added for vBulletin)
					options = control.data('selectBox-options') || $(); //added fallback object for safety as .data() could return undefined (added for vBulletin)

				options.find('.selectBox-hover').removeClass('selectBox-hover');
			};


			var keepOptionInView = function(select, li, center) {

				if( !li || li.length === 0 ) return;

				select = $(select);
				var control = select.data('selectBox-control') || $(), //added fallback object for safety as .data() could return undefined (added for vBulletin)
					options = control.data('selectBox-options') || $(), //added fallback object for safety as .data() could return undefined (added for vBulletin)
					scrollBox = control.hasClass('selectBox-dropdown') ? options : options.parent(),
					top = parseInt(li.offset().top - scrollBox.position().top),
					bottom = parseInt(top + li.outerHeight());

				if( center ) {
					scrollBox.scrollTop( li.offset().top - scrollBox.offset().top + scrollBox.scrollTop() - (scrollBox.height() / 2) );
				} else {
					if( top < 0 ) {
						scrollBox.scrollTop( li.offset().top - scrollBox.offset().top + scrollBox.scrollTop() );
					}
					if( bottom > scrollBox.height() ) {
						scrollBox.scrollTop( (li.offset().top + li.outerHeight()) - scrollBox.offset().top + scrollBox.scrollTop() - scrollBox.height() );
					}
				}

			};


			var handleKeyDown = function(select, event) {

				//
				// Handles open/close and arrow key functionality
				//

				select = $(select);
				var control = select.data('selectBox-control') || $(), //added fallback object for safety as .data() could return undefined (added for vBulletin)
					options = control.data('selectBox-options') || $(), //added fallback object for safety as .data() could return undefined (added for vBulletin)
					settings = select.data('selectBox-settings') || {}, //added fallback object for safety as .data() could return undefined (added for vBulletin)
					totalOptions = 0,
					i = 0;

				if( control.hasClass('selectBox-disabled') ) return;

				switch( event.keyCode ) {

					case 8: // backspace
						event.preventDefault();
						typeSearch = '';
						break;

					case 9: // tab
					case 27: // esc
						hideMenus();
						removeHover(select);
						break;

					case 13: // enter
						if( control.hasClass('selectBox-menuShowing') ) {
							selectOption(select, options.find('LI.selectBox-hover:first'), event);
							if( control.hasClass('selectBox-dropdown') ) hideMenus();
						} else {
							showMenu(select);
						}
						break;

					case 38: // up
					case 37: // left

						event.preventDefault();

						if( control.hasClass('selectBox-menuShowing') ) {

							var prev = options.find('.selectBox-hover').prev('LI');
							totalOptions = options.find('LI:not(.selectBox-optgroup)').length;
							i = 0;

							while( prev.length === 0 || prev.hasClass('selectBox-disabled') || prev.hasClass('selectBox-optgroup') ) {
								prev = prev.prev('LI');
								if( prev.length === 0 ) {
									if (settings.loopOptions) {
										prev = options.find('LI:last');
									} else {
										prev = options.find('LI:first');
									}
								}
								if( ++i >= totalOptions ) break;
							}

							addHover(select, prev);
							selectOption(select, prev, event);
							keepOptionInView(select, prev);

						} else {
							showMenu(select);
						}

						break;

					case 40: // down
					case 39: // right

						event.preventDefault();

						if( control.hasClass('selectBox-menuShowing') ) {

							var next = options.find('.selectBox-hover').next('LI');
							totalOptions = options.find('LI:not(.selectBox-optgroup)').length;
							i = 0;

							while( next.length === 0 || next.hasClass('selectBox-disabled') || next.hasClass('selectBox-optgroup') ) {
								next = next.next('LI');
								if( next.length === 0 ) {
									if (settings.loopOptions) {
										next = options.find('LI:first');
									} else {
										next = options.find('LI:last');
									}
								}
								if( ++i >= totalOptions ) break;
							}

							addHover(select, next);
							selectOption(select, next, event);
							keepOptionInView(select, next);

						} else {
							showMenu(select);
						}

						break;

				}

			};


			var handleKeyPress = function(select, event) {

				//
				// Handles type-to-find functionality
				//

				select = $(select);
				var control = select.data('selectBox-control') || $(), //added fallback object for safety as .data() could return undefined (added for vBulletin)
					options = control.data('selectBox-options') || $(); //added fallback object for safety as .data() could return undefined (added for vBulletin)

				if( control.hasClass('selectBox-disabled') ) return;

				switch( event.keyCode ) {

					case 9: // tab
					case 27: // esc
					case 13: // enter
					case 38: // up
					case 37: // left
					case 40: // down
					case 39: // right
						// Don't interfere with the keydown event!
						break;

					default: // Type to find

						if( !control.hasClass('selectBox-menuShowing') ) showMenu(select);

						event.preventDefault();

						clearTimeout(typeTimer);
						typeSearch += String.fromCharCode(event.charCode || event.keyCode);

						options.find('A').each( function() {
							if( $(this).text().substr(0, typeSearch.length).toLowerCase() === typeSearch.toLowerCase() ) {
								addHover(select, $(this).parent());
								keepOptionInView(select, $(this).parent());
								return false;
							}
						});

						// Clear after a brief pause
						typeTimer = setTimeout( function() { typeSearch = ''; }, 1000);

						break;

				}

			};


			var enable = function(select) {
				select = $(select);
				select.attr('disabled', false);
				var control = select.data('selectBox-control');
				if( !control ) return;
				control.removeClass('selectBox-disabled');
			};


			var disable = function(select) {
				select = $(select);
				select.attr('disabled', true);
				var control = select.data('selectBox-control');
				if( !control ) return;
				control.addClass('selectBox-disabled');
			};


			var setValue = function(select, value) {
				select = $(select);
				select.val(value);
				value = select.val();
				//if we didn't select an actual value, set to first option. this doesn't play nice with
				//multi-select boxes which don't need a value.
				if(value === null && !select.attr('multiple'))
				{
					select[0].selectedIndex = 0;
					value = select.val();
				}

				var control = select.data('selectBox-control');
				if( !control ) return;
				var settings = select.data('selectBox-settings') || {}, //added fallback object for safety as .data() could return undefined (added for vBulletin)
					options = control.data('selectBox-options') || $(); //added fallback object for safety as .data() could return undefined (added for vBulletin)

				// Update label
				setLabel(select);

				// Update control values
				options.find('.selectBox-selected').removeClass('selectBox-selected');
				options.find('A').each( function() {
					if( typeof(value) === 'object' ) {
						for( var i = 0; i < value.length; i++ ) {
							if( $(this).attr('rel') == value[i] ) {
								$(this).parent().addClass('selectBox-selected');
							}
						}
					} else {
						if( $(this).attr('rel') == value ) {
							$(this).parent().addClass('selectBox-selected');
						}
					}
				});

				if( settings.change ) settings.change.call(select);

			};


			var setOptions = function(select, options) {

				select = $(select);
				var control = select.data('selectBox-control') || $(),
					settings = select.data('selectBox-settings') || {};

				switch( typeof(data) ) {

					case 'string':
						select.html(data);
						break;

					case 'object':
						select.html('');
						for( var i in data ) {
							if( data[i] === null ) continue;
							if( typeof(data[i]) === 'object' ) {
								var optgroup = $('<optgroup label="' + i + '" />');
								for( var j in data[i] ) {
									optgroup.append('<option value="' + j + '">' + data[i][j] + '</option>');
								}
								select.append(optgroup);
							} else {
								var option = $('<option value="' + i + '">' + data[i] + '</option>');
								select.append(option);
							}
						}
						break;

				}

				if( !control ) return;

				// Remove old options
				control.data('selectBox-options').remove();

				// Generate new options
				var type = control.hasClass('selectBox-dropdown') ? 'dropdown' : 'inline';
				options = getOptions(select, type);
				control.data('selectBox-options', options);

				switch( type ) {
					case 'inline':
						control.append(options);
						break;
					case 'dropdown':
						// Update label
						setLabel(select);
						$("BODY").append(options);
						break;
				}

			};


			var disableSelection = function(selector) {
				$(selector)
					.css('MozUserSelect', 'none')
					.on('selectstart', function(event) {
						event.preventDefault();
					});
			};

			var generateOptions = function(self, options){
				var li = $('<li />'),
				a = $('<a />');
				li.addClass( self.attr('class') );
				li.data( self.data() );
				a.attr('rel', self.val()).text( self.text() );
				li.append(a);
				if( self.attr('disabled') ) li.addClass('selectBox-disabled');

				//changed from attr to prop for vBulletin jquery 2.x compatibility
				if( self.prop('selected') ) li.addClass('selectBox-selected');
				options.append(li);
			};

			/***
			* Checks if the specified element is completely visible from the browser viewport.
			*
			* @param	elem			The element to check. Can be any selector that jQuery accepts for $() to reference an element. Can also be a jQuery object too. (Required)
			* @return	boolean			Returns true if the element is completely visible from the browser viewport, false if not.
			*/
			var isScrolledIntoView = function(elem)
			{
				var docViewTop = $(window).scrollTop();
				var docViewBottom = docViewTop + $(window).height();

				var elemTop = elem.offset().top;
				var elemBottom = elemTop + elem.outerHeight();

				var elemRelativeTop = elemTop - docViewTop;
				var elemRelativeBottom = docViewBottom - elemBottom;

				return (elemRelativeTop >= 0 && elemRelativeBottom >= 0);
			};


			//
			// Public methods
			//

			switch( method ) {

				case 'control':
					return $(this).data('selectBox-control');

				case 'settings':
					if( !data ) return $(this).data('selectBox-settings');
					$(this).each( function() {
						$(this).data('selectBox-settings', $.extend(true, $(this).data('selectBox-settings'), data));
					});
					break;

				case 'options':
					// Getter
					if( data === undefined ) return $(this).data('selectBox-control').data('selectBox-options');
					// Setter
					$(this).each( function() {
						setOptions(this, data);
					});
					break;

				case 'value':
					// Empty string is a valid value
					if( data === undefined ) return $(this).val();
					$(this).each( function() {
						setValue(this, data);
					});
					break;

				case 'refresh':
					$(this).each( function() {
						refresh(this);
					});
					break;

				case 'enable':
					$(this).each( function() {
						enable(this);
					});
					break;

				case 'disable':
					$(this).each( function() {
						disable(this);
					});
					break;

				case 'destroy':
					$(this).each( function() {
						destroy(this);
					});
					break;

				default:
					$(this).each( function() {
						init(this, method);
					});
					break;

			}

			return $(this);

		}

	});

})(jQuery);
