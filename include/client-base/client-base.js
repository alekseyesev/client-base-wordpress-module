;(function($, document, window) {

	'use strict';

	$(function() {

        /* ClientBase */

        var birthdayList = $('.birthday-list'),
			customers = $('.customers'),
			customerFilterForm = $('.customer-filter-form'),
			customerActivityPeriod = $('.customer-activity-period'),
			customersAction = $('.customers-action'),
			clientBaseProperties = $.extend({}, ClientBase),
            clientBase;

        ClientBase = function () {
            var self = this,
                __construct = function () {
					
                    $.extend(self, clientBaseProperties);
					
					$('[type=tel]', customers)
						.inputmask('+7 (999) 999-99-99');
			
                };
            __construct();
        };
		
		ClientBase.prototype.onToggleDeals = function(event) {

			var self = this,
				toggleLink = $(event.currentTarget),
				toggle = toggleLink.closest('.customer-form__toggle-deals'),
				icon = toggleLink.prev('.fa'),
				customerDeals = toggle.prev('.customer__deals');
				
				if (toggle.hasClass('customer-form__toggle-deals_close')) {
					
					toggleLink
						.text('Скрыть сделки');
						
					customerDeals
						.slideDown(300);
					
				}
				else if (toggle.hasClass('customer-form__toggle-deals_open')) {
					
					toggleLink
						.text('Показать сделки');
						
					customerDeals
						.slideUp(300);
					
				}
				
				toggle
					.toggleClass('customer-form__toggle-deals_close customer-form__toggle-deals_open');
					
				icon
					.toggleClass('fa-folder-open-o fa-folder-o');
			
		};
		
		ClientBase.prototype.deleteBirthdayReminder = function(event) {	
		
			var self = this,
				currentCloseButton = $(event.currentTarget),
				_wpnonce = currentCloseButton.data('wp-nonce'),
				userId = parseInt(currentCloseButton.data('birthday-id')),
				data = new FormData(),
				birthday;
				
			if (userId) {
				
				birthday = $('#birthday-' + userId);

				data.append('action', 'client_base_delete_birthday_reminder');
				data.append('_wpnonce', _wpnonce);
				data.append('id', userId);
					
				$.ajax({
					url: self.ajaxurl,
					data: data,
					dataType: 'json',
					method: 'POST',
					cache: false,
					contentType: false,
					processData: false,
					success: function(result) {
						if (result.error) {
							
							if (typeof result.message !== 'undefined') {
								jAlert(result.message, 'Ошибка');
							}
							
						}
						else {
							
							if (typeof result.message !== 'undefined') {
								jAlert(result.message, 'Сообщение');
							}
							
							birthday
								.fadeOut(300, function() {
									birthday
										.remove();
									$('.birthday-list__exclude-count')
										.text(parseInt($('.birthday-list__exclude-count').text()) + 1);
								});
						}
					},
					error: function() {
						jAlert('При отправке запроса на сервер произошла ошибка.', 'Ошибка');
					}
				});
			
			}

		};
		
		ClientBase.prototype.scrollToCard = function(event) {	
		
			var currentAnchor = $(event.currentTarget),
				destinationElement = $(currentAnchor.prop('hash')),
				offsetTop = parseInt(destinationElement.offset().top);
				
				$('html, body')
					.animate({
						scrollTop: offsetTop
					}, (offsetTop / 1000) * 400);

		};
		
		ClientBase.prototype.recalculateBirthdayCount = function() { 
		
			var birthdayListCount = $('.birthday-list__count'),
				birthdayCount = birthdayList.children('.birthday-list__item').length;
				
			birthdayListCount
				.text(birthdayCount);

		};
		
		ClientBase.prototype.onBeforeSubmit = function(event) {
			
			var self = this,
				currentSubmit = $(event.currentTarget),
				currentSubmitValue = currentSubmit.val();
				
			self.customerAction = currentSubmitValue ? currentSubmitValue : '';
			
		};
		
		ClientBase.prototype.onSubmit = function(event) { 
			
			var self = this,
				currentForm = $(event.currentTarget),
				currentCard = $(event.currentTarget).closest('.customer'),
				data = new FormData(),
				birthday;

			if (self.customerAction) {
				data.append('action', 'client_base_' + self.customerAction);
			}
			else {
				return false;
			}
			
            $.each(currentForm.serializeArray(), function (i, object) {
				
				var objectName = /customer\[\d+\]\[([_a-z]+)\]/.exec(object['name']);
				
				if (objectName !== null && typeof objectName[1] !== 'undefined') {
					
					objectName = objectName[1];
					
					if (self.customerAction === 'delete') {
						
						if (objectName !== 'id' && objectName !== '_wpnonce') {
						
							return true;
						
						}
						
					}

				}
				else {
					
					return true;
					
				}
				
                data.append(objectName, object['value']);
				
            });
			
			$.ajax({
                url: self.ajaxurl,
                data: data,
                dataType: 'json',
                method: 'POST',
                cache: false,
                contentType: false,
                processData: false,
				success: function(result) {
					if (result.error) {
						
						if (typeof result.message !== 'undefined') {
							jAlert(result.message, 'Ошибка');
						}
						
					}
					else {
						
						if (typeof result.message !== 'undefined') {
							jAlert(result.message, 'Сообщение');
						}
						
						if (self.customerAction === 'delete') {
							
							currentCard
								.fadeOut(300, function() {
									currentCard
										.remove();
								});
							
						}
						else if (self.customerAction === 'update') {
							
							if (birthdayList.data('birthday-date').substr(4) === data.get('birthday').substr(4)) { // Если ДР сегодня
								
								$('<div id="birthday-' + data.get('id') + '" class="birthday-list__item">' + 
									'<div class="birthday-list__name">' + 
										'<i class="fa fa-birthday-cake"></i>&nbsp;&nbsp;&nbsp;<a class="birthday-list__anchor" href="#customer-' + data.get('id') + '" title="Перейти к карточке клиента">' + data.get('name') + '</a>' + 
									'</div>' + 
									'<div class="birthday-list__phone">' + data.get('phone') + '</div>' + 
									'<button class="birthday-list__close" type="button" title="Удалить уведомление" data-birthday-id="' + data.get('id') + '" data-wp-nonce="a024d4094d"><i class="fa fa-times"></i></button>' + 
								'</div>')
									.appendTo(birthdayList);
									
									
								self.recalculateBirthdayCount();
								
							}
							else {
								
								birthday = $('#birthday-' + data.get('id'));
								
								birthday
									.fadeOut(300, function() {
										birthday
											.remove();
										self.recalculateBirthdayCount();
									});
								
							}

						}
						
					}
				},
				error: function() {
					jAlert('При отправке запроса на сервер произошла ошибка.', 'Ошибка');
				}
			});
			
		};
		
		ClientBase.prototype.onFilterChange = function(event) {
			
			var self = this,
				filterForm = $(event.delegateTarget),
				filterFormSelect = $(event.currentTarget),
				filterFormAction = $('.customer-filter-form__action'),
				filterType = filterFormSelect.val(),
				validation = '',
				date = new Date();
			
			if (filterType === 'tel') {
			
				validation = ' pattern="\\+7\\s\\(\\d{3}\\)\\s\\d{3}-\\d{2}-\\d{2}"';
				
			}
			else if (filterType === 'date') {
				
				validation = ' min="1900-01-01" max="' + ( date.getFullYear() + '-' + ( date.getMonth() < 9 ? '0' : '' ) + ( date.getMonth() + 1 ) + '-' + ( date.getDate() < 10 ? '0' : '' ) + ( date.getDate() + 1 ) ) + '"';
				
			}
			
			if (filterType === 'reset') {
				
				$('select[name="customer_activity_period"]', customerActivityPeriod)
					.removeAttr('disabled')
					.removeProp('disabled');
				
				filterFormAction
					.html('');
					
				self.onFilterSubmit({currentTarget: event.delegateTarget});
					
			}
			else {
				
				$('select[name="customer_activity_period"]', customerActivityPeriod)
					.attr('disabled', 'disabled')
					.prop('disabled', 'disabled');
				
				filterFormAction
					.html( 
						'<input class="customer-filter-form__input" type="' + filterType + '" name="customer_filter_value" required="required"' + validation + ' />' + 
						'<button class="customer-filter-form__submit" type="submit">Показать</button>' 
					);
				
			}
			
			if (filterType === 'tel') {
				
				$('.customer-filter-form__input', filterForm)
					.inputmask('+7 (999) 999-99-99');
				
			}
			
		};
		
		ClientBase.prototype.getCustomerMarkup = function(customer, currentTime, nonce) { 
		
			var self = this;
		
			return typeof customer === 'undefined' ? '' : 
				'<div style="display:none" id="customer-' + customer.id +  '" class="customer' + (customer.deals_count ? (parseInt(customer.deals_count) >= 5 ? ' customer_regular' : '') : '') + '">' + 
				  '<form class="customer-form">' + 
					'<div class="customer__id">' + 
						'<strong>ID:</strong> ' + customer.id + 
					'</div>' + 
					'<div class="customer__registration-datetime">' + 
						'<strong>Зарегистрирован(а) на сайте:</strong> ' + customer.registration_datetime + 
					'</div>' + 
					'<div class="customer__name">' + 
						'<strong>Имя</strong> <input type="text" name="customer[' + customer.id +  '][name]" value="' + customer.name + '" required="required" />' + 
					'</div>' + 
					'<div class="customer__phone">' + 
						'<strong>Телефон</strong> <input type="tel" name="customer[' + customer.id +  '][phone]" value="' + ( customer.phone ? customer.phone : '' ) + '" required="required" />' + 
					'</div>' + 
					'<div class="customer__additional-phone">' + 
						'<strong>Дополнительный телефон</strong> <input type="tel" name="customer[' + customer.id +  '][additional_phone]" value="' + ( customer.additional_phone ? customer.additional_phone : '' ) + '" />' + 
					'</div>' + 
					'<div class="customer__email">' + 
						'<strong>Email</strong> <input type="email" name="customer[' + customer.id +  '][email]" value="' + ( customer.email ? customer.email : '' ) + '">' + 
					'</div>' + 
					'<div class="customer__additional-email">' + 
						'<strong>Дополнительный  email</strong> <input type="email" name="customer[' + customer.id +  '][additional_email]" value="' + ( customer.additional_email ? customer.additional_email : '' ) + '">' + 
					'</div>' + 
					'<div class="customer__birthday">' + 
						'<strong>День рождения</strong> <input type="date" name="customer[' + customer.id +  '][birthday]" value="' + ( customer.birthday ? customer.birthday : '' ) + '" min="1900-01-01" max="' + currentTime + '">' + 
					'</div>' + 
					'<div class="customer__admin-notes">' + 
						'<strong>Заметки администратора</strong> <textarea name="customer[' + customer.id +  '][admin_notes]" rows="6">' + ( customer.admin_notes ? customer.admin_notes : '' ) + '</textarea>' +
					'</div>' + 
					self.getDealsMarkup(customer.deals_list, customer.deals_count) + 
					'<div class="customer-action">' + 
						'<input type="hidden" id="customer[' + customer.id +  '][_wpnonce]" name="customer[' + customer.id +  '][_wpnonce]" value="' + nonce + '">' + 
						'<input type="hidden" name="customer[' + customer.id +  '][id]" value="' + customer.id +  '">' + 
						'<input title="Сохранить изменения" type="submit" name="customer[' + customer.id +  '][action]" value="update">' + 
						'<input title="Удалить" type="submit" name="customer[' + customer.id +  '][action]" value="delete">' + 
					'</div>' + 
				  '</form>' + 
				'</div>';
		
		};
		
		ClientBase.prototype.getDealsMarkup = function(dealsEncoded, dealsCount) { 
		
			var deals = [],
				dealIndexes = [],
				dealsList = '',
				dealsDecoded,
				deal,
				dealId,
				datetime,
				datetimeFormat,
				date,
				time,
				title;
				
			if (!dealsEncoded) return dealsList; 
			
			dealsDecoded = dealsEncoded.split(';');
			
			while (deal = dealsDecoded.shift()) {
				
				deal = deal.split(',');
				
				if (deal.length === 3) { 
					
					dealId   = deal[0];					
					datetime = new Date(deal[1]);
					datetimeFormat = (datetime.getDate() < 10 ? '0' + datetime.getDate() : datetime.getDate()) + '.' +  (datetime.getMonth() < 9 ? '0' + (datetime.getMonth() + 1) : datetime.getMonth() + 1)  + '.' +   datetime.getFullYear() + ' ' + (datetime.getHours() < 10 ? '0' + datetime.getHours() : datetime.getHours()) +':' + (datetime.getMinutes() < 10 ? '0' + datetime.getMinutes() : datetime.getMinutes());
					title    = deal[2];
					
					deals[datetime.getTime()] = 
						'<li class="deal">' + 
							'<strong class="deal__datetime">' + datetimeFormat + '</strong><a class="deal__link" href="/deals?id=' + dealId + '" target="_blank">' + title + '</a>' + 
						'</li>';
					
				}
				
			}

			if (!deals) return dealsList; 

			for (var dealIndex in deals) 
				dealIndexes.push(dealIndex);
			
			dealIndexes.sort().reverse(); 
			
			for (var n in dealIndexes) 
				dealsList += deals[dealIndexes[n]];
		
			return  '<ul style="display:none" class="customer__deals">' + 
						dealsList + 
					'</ul>' + 
					'<div class="customer-form__toggle-deals customer-form__toggle-deals_close"><i class="fa fa-folder-open-o"></i><span class="customer-form__toggle-deals-title">Показать сделки</span><span class="customer-form__toggle-deals-count">(' + (dealsCount ? dealsCount : deals.length) + ')</span></div>';
		
		};
		
		ClientBase.prototype.onFilterSubmit = function(event) { 
			
			var self = this,
				filterForm = $(event.currentTarget),
                data = filterForm.serialize(),
				isReset = /customer_filter_type=reset/.test(data);
				
            data += '&action=client_base_filter';
			
			$.ajax({
                url: self.ajaxurl,
                data: data,
                dataType: 'json',
                method: 'POST',
				success: function(result) {
					
					var customerCards = [],
						client;
					
					if (result.error) {
						
						if (typeof result.message !== 'undefined') {
							jAlert(result.message, 'Внимание');
						}
						
					}
					else {
						
						if (typeof result.message !== 'undefined') {
							
							console.log(result.message);
							
						}
						
						if (typeof result.clients.length !== 'undefined' && result.clients.length > 0) {
								
							$('.customer', customers)
								.remove();
									
							while (client = result.clients.shift()) {
										
								customerCards.push(
									$(self.getCustomerMarkup(client, result.current_time, result.nonce))
										.find('[type="tel"]')
										.prop('pattern', '\\+7\\s\\(\\d{3}\\)\\s\\d{3}-\\d{2}-\\d{2}')
										.attr('pattern', '\\+7\\s\\(\\d{3}\\)\\s\\d{3}-\\d{2}-\\d{2}')
										.inputmask('+7 (999) 999-99-99')
										.end()
								);
										
							}
								
							$.each(customerCards, function(cardIndex, card) {
									
								customers
									.append(card);
									
							});
									
							$('.customer', customers)
								.fadeIn(300);
								
							if (isReset) {

								$('.show-all-customers', customersAction)
									.css({display: 'inline-block'});

							}
							else {
								
								$('.show-all-customers', customersAction)
									.css({display: 'none'});
								
							}

						}
						
					}
					
				},
				error: function() {
					jAlert('При отправке запроса на сервер произошла ошибка.', 'Ошибка');
				}
			});
			
		};
		
		ClientBase.prototype.onPeriodChange = function(event) { 
			
			var self = this,
				periodForm = $(event.delegateTarget),
                data = periodForm.serialize();
				
            data += '&action=client_base_period';
			
			$.ajax({
                url: self.ajaxurl,
                data: data,
                dataType: 'json',
                method: 'POST',
				success: function(result) {
					
					var customerCards = [],
						client;
					
					if (result.error) {
						
						if (typeof result.message !== 'undefined') {
							jAlert(result.message, 'Внимание');
						}
						
					}
					else {
						
						if (typeof result.message !== 'undefined') {
							
							console.log(result.message);
							
						}
						
						if (typeof result.clients.length !== 'undefined' && result.clients.length > 0) {
								
							$('.customer', customers)
								.remove();
									
							while (client = result.clients.shift()) {
										
								customerCards.push(
									$(self.getCustomerMarkup(client, result.current_time, result.nonce))
										.find('[type="tel"]')
										.prop('pattern', '\\+7\\s\\(\\d{3}\\)\\s\\d{3}-\\d{2}-\\d{2}')
										.attr('pattern', '\\+7\\s\\(\\d{3}\\)\\s\\d{3}-\\d{2}-\\d{2}')
										.inputmask('+7 (999) 999-99-99')
										.end()
								);
										
							}
								
							$.each(customerCards, function(cardIndex, card) {
									
								customers
									.append(card);
									
							});
									
							$('.customer', customers)
								.fadeIn(300);

							$('.show-all-customers', customersAction)
								.css({display: 'inline-block'});

						}
						
					}
					
				},
				error: function() {
					jAlert('При отправке запроса на сервер произошла ошибка.', 'Ошибка');
				}
			});
			
		};
		
		ClientBase.prototype.onShowAllCustomers = function(event) { 
			
			var self = this,
				showAllCustomersLink = $(event.currentTarget),
				loader = $('.loader-facebook', event.delegateTarget),
                data = {
					_wpnonce: showAllCustomersLink.data('nonce'),
					action: 'show_all_clients'
				},
				period = $('[name="customer_activity_period"]', customerActivityPeriod).val();

			if (!isNaN(period)) {
				
				period = parseInt(period);
				
				if (period > 1000 && period < 9999) {
					
					data['period'] = period;				
					
				}
				
			}
				
			showAllCustomersLink
				.css({display: 'none'});
				
			loader
				.css({display: 'inline-flex'});
			
			$.ajax({
                url: self.ajaxurl,
                data: data,
                dataType: 'json',
                method: 'POST',
				success: function(result) {
					
					var customerCards = [],
						client;
					
					if (result.error) {
						
						if (typeof result.message !== 'undefined') {
							jAlert(result.message, 'Внимание');
						}
						
					}
					else {
						
						if (typeof result.message !== 'undefined') {
							
							console.log(result.message);
							
						}
						
						if (typeof result.clients.length !== 'undefined' && result.clients.length > 0) {
									
							while (client = result.clients.shift()) {
										
								customerCards.push(
									$(self.getCustomerMarkup(client, result.current_time, result.nonce))
										.find('[type="tel"]')
										.prop('pattern', '\\+7\\s\\(\\d{3}\\)\\s\\d{3}-\\d{2}-\\d{2}')
										.attr('pattern', '\\+7\\s\\(\\d{3}\\)\\s\\d{3}-\\d{2}-\\d{2}')
										.inputmask('+7 (999) 999-99-99')
										.end()
								);
										
							}
								
							$.each(customerCards, function(cardIndex, card) {
									
								customers
									.append(card);
									
							});
									
							$('.customer:not(:visible)', customers)
								.fadeIn(300);
								
							loader
								.css({display: 'none'});

						}
						
					}
					
				},
				error: function() {
					jAlert('При отправке запроса на сервер произошла ошибка.', 'Ошибка');
				}
			});
			
		};

		clientBase = new ClientBase();
		
		birthdayList
			.on('click', '.birthday-list__anchor', function (e) {

				clientBase
					.scrollToCard(e);

				return false;
				
			})
			.on('click', '.birthday-list__close', function (e) {

				clientBase
					.deleteBirthdayReminder(e);

				return false;
				
			})
			.on('click', '.birthday-list__close-all', function (e) {

				clientBase
					.deleteAllBirthdayReminders(e);

				return false;
				
			});
	
		customers
			.on('submit', '.customer-form', function (e) {
				
				clientBase
					.onSubmit(e);

				return false;

			})
			.on('click', '[type=submit]', function (e) {
				
				clientBase
					.onBeforeSubmit(e);

				return true;

			})
			.on('click', '.customer-form__toggle-deals-title', function (e) {
				
				clientBase
					.onToggleDeals(e);

				return true;

			});
			
		customerFilterForm
			.on('change', '[name="customer_filter_type"]', function (e) {
				
				clientBase
					.onFilterChange(e);

				return true;

			})
			.on('submit', function (e) {
				
				clientBase
					.onFilterSubmit(e);

				return false;

			});
			
		customersAction
			.on('click', '.show-all-customers', function (e) {
				
				clientBase
					.onShowAllCustomers(e);

				return true;

			});
			
		customerActivityPeriod
			.on('change', '[name="customer_activity_period"]', function (e) {
				
				clientBase
					.onPeriodChange(e);

				return true;
				
			});

	});

})(jQuery, document, window)