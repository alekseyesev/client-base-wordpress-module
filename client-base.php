<?php /* Template Name: База клиентов */ ?>

<?php get_header(); ?>

<?php if ( ClientBase::access_granted() ) : ?>

<div id="dialogs_block">
	<div class="inner_block">
	
		<div class="customer-filter">
			<h1>База клиентов</h1>
			<form class="customer-filter-form">
				<div class="customer-filter-form__action"></div>
				<label for="customer_filter_type">Фильтр:</label>
				<select name="customer_filter_type">
					<option value="reset">Без фильтра</option>
					<option value="text">По имени</option>
					<option value="email">По email</option>
					<option value="tel">По телефону</option>
					<option value="date">По дате рождения</option>
				</select>
				<?php wp_nonce_field( 'client_base', '_wpnonce', false ); ?>
			</form>
			<form class="customer-activity-period">
				<label for="customer_filter_type">Период:</label>
				<select name="customer_activity_period">
					<option value="all">За все время</option>
					<?php if ( $start_datetime = @new DateTime( ClientBase::get_start_datetime() ) ) : ?>
					
					<?php
					
					$year = ( int ) $start_datetime->format( 'Y' );
					
					$current_year = ( $current_year = new DateTime() ) ? ( int ) $current_year->format( 'Y' ) : $year;
					
					?>
					
					<?php while ( $year <= $current_year ) : ?>
					
					<option value="<?php echo $year; ?>"><?php echo sprintf( __( '%d год', ClientBase::LANG_DOMAIN ), $year ); ?></option>
					
					<?php $year++; endwhile; ?>
					
					<?php endif; ?>
				</select>
				<?php wp_nonce_field( 'client_base_period_selection', 'period_nonce', false ); ?>
			</form>
		</div>
		
		<?php if ( $customers = ClientBase::get_list() ) : ?>
		
		<div class="container_block">
			
			<?php $birthday = ClientBase::get_list( true ); ?>
			<div class="birthday-list__title">Сегодня <?php echo date_i18n( 'j F' ); ?> дней рождений: <span class="birthday-list__count"><?php echo count( $birthday ); ?></span></div>
			<div class="birthday-list" data-birthday-date="<?php echo current_time( 'Y-m-d' ); ?>">
				<?php $exclude_count = 0; ?>
				<?php $current_date = current_time( 'Ymd' ); ?>
				<?php $birthday_exclude = get_user_meta( get_current_user_id(), 'client_base_birthday_exclude', true ); ?>
				<?php foreach ( $birthday as $birthday_meta ) : ?>
				<?php if ( ! empty( $birthday_exclude ) && isset( $birthday_exclude[$current_date] ) && in_array( $birthday_meta->id, $birthday_exclude[$current_date] ) ) { $exclude_count++; continue; } ?>
				<div id="birthday-<?php echo $birthday_meta->id; ?>" class="birthday-list__item">
					<div class="birthday-list__name">
						<i class="fa fa-birthday-cake"></i>&nbsp;&nbsp;&nbsp;<a class="birthday-list__anchor" href="#customer-<?php echo $birthday_meta->id; ?>" title="<?php echo __( 'Перейти к карточке клиента', ClientBase::LANG_DOMAIN ); ?>"><?php echo $birthday_meta->name; ?></a>
					</div>
					<div class="birthday-list__phone">
						<?php echo $birthday_meta->phone; ?>
					</div>
					<button class="birthday-list__close" type="button" title="<?php echo __( 'Удалить уведомление', ClientBase::LANG_DOMAIN ); ?>" data-birthday-id="<?php echo $birthday_meta->id; ?>" data-wp-nonce="<?php echo wp_create_nonce( 'client_base' ); ?>"><i class="fa fa-times"></i></button>
				</div>
				<?php endforeach; ?>
			</div>
			<div class="birthday-list__message">
				<?php echo sprintf( __( 'Удалено уведомлений: <span class="birthday-list__exclude-count birthday-list__exclude-count_small">%d</span> из <span class="birthday-list__count birthday-list__count_small">%d</span>', ClientBase::LANG_DOMAIN ), $exclude_count, count( $birthday ) ); ?>
			</div>
		
			<div class="customers">
		
			<?php foreach ( $customers as $customer ) : ?>
			
				<div id="customer-<?php echo $customer->id; ?>" class="customer<?php if ( ( int ) $customer->deals_count >= 5 ) : ?> customer_regular<?php endif; ?>">
				  <form class="customer-form">
					<div class="customer__id">
						<strong>ID:</strong> <?php echo $customer->id; ?>
					</div>
					<div class="customer__registration-datetime">
						<strong>Зарегистрирован(а) на сайте:</strong> <?php echo $customer->registration_datetime; ?>
					</div>
					<div class="customer__name">
						<strong>Имя</strong> <input type="text" name="customer[<?php echo $customer->id; ?>][name]" value="<?php echo $customer->name; ?>" required>
					</div>
					<div class="customer__phone">
						<strong>Телефон</strong> <input type="tel" name="customer[<?php echo $customer->id; ?>][phone]" value="<?php echo $customer->phone; ?>" required pattern="\+7\s\(\d{3}\)\s\d{3}-\d{2}-\d{2}">
					</div>
					<div class="customer__additional-phone">
						<strong>Дополнительный телефон</strong> <input type="tel" name="customer[<?php echo $customer->id; ?>][additional_phone]" value="<?php echo ( $customer_additional_phone = get_user_meta( $customer->id, 'customer_additional_phone', true ) ) ? $customer_additional_phone : ''; ?>" pattern="\+7\s\(\d{3}\)\s\d{3}-\d{2}-\d{2}">
					</div>
					<div class="customer__email">
						<strong>Email</strong> <input type="email" name="customer[<?php echo $customer->id; ?>][email]" value="<?php echo $customer->email; ?>">
					</div>
					<div class="customer__additional-email">
						<strong>Дополнительный  email</strong> <input type="email" name="customer[<?php echo $customer->id; ?>][additional_email]" value="<?php echo ( $customer_additional_email = get_user_meta( $customer->id, 'customer_additional_email', true ) ) ? $customer_additional_email : ''; ?>">
					</div>
					<div class="customer__birthday">
						<strong>День рождения</strong> <input type="date" name="customer[<?php echo $customer->id; ?>][birthday]" value="<?php echo ( $customer_birthday = get_user_meta( $customer->id, 'customer_birthday', true ) ) ? $customer_birthday : ''; ?>" min="1900-01-01" max="<?php echo current_time( 'Y-m-d' ); ?>">
					</div>
					<div class="customer__admin-notes">
						<strong>Заметки администратора</strong> <textarea name="customer[<?php echo $customer->id; ?>][admin_notes]" rows="6"><?php echo ( $customer_admin_notes = get_user_meta( $customer->id, 'customer_admin_notes', true ) ) ? $customer_admin_notes : ''; ?></textarea>
					</div>
					
					<?php

					$deals = array();
					
					$deals_list = explode( ';', $customer->deals_list );
					
					if ( ( int ) $customer->deals_count > 0 ) {
					
						foreach ( $deals_list as $raw_deal ) {
							
							$deal = explode( ',', $raw_deal );
							
							$deals[strtotime( $deal[1] )] = array( 
							
								'link'  => site_url( '/deals?id=' . $deal[0] ),
								'title' => $deal[2],
								
							);
							
						}
					
					}

					if ( ! empty( $deals ) ) : krsort( $deals, SORT_NUMERIC ); ?>
					
					<ul style="display:none" class="customer__deals">
					
					<?php $deals_count = 0; foreach ( $deals as $deal_datetime => $deal ) : ?>
					
						<?php if ( empty( $deal['title'] ) ) continue; else $deals_count++; ?>
						
						<?php 
						
						$datetime = new DateTime();
						
						$datetime->setTimestamp( $deal_datetime );
						
						$deal_datetime = $datetime->format( 'd.m.Y H:i' ); 
						
						?>
					
						<li class="deal">
						
							<strong class="deal__datetime"><?php echo $deal_datetime; ?></strong><a class="deal__link" href="<?php echo $deal['link']; ?>" target="_blank"><?php echo $deal['title']; ?></a>
						
						</li>
					
					<?php endforeach; ?>
					
					</ul>
					
					<div class="customer-form__toggle-deals customer-form__toggle-deals_close"><i class="fa fa-folder-open-o"></i><span class="customer-form__toggle-deals-title">Показать сделки</span><span class="customer-form__toggle-deals-count">(<?php echo $deals_count; ?>)</span></div>
					
					<?php endif; ?>
					
					<div class="customer-action">
						<?php wp_nonce_field( 'client_base', "customer[{$customer->id}][_wpnonce]", false ); ?>
						<input type="hidden" name="customer[<?php echo $customer->id; ?>][id]" value="<?php echo $customer->id; ?>">
						<input title="Сохранить изменения" type="submit" name="customer[<?php echo $customer->id; ?>][action]" value="update">
						<input title="Удалить"  type="submit" name="customer[<?php echo $customer->id; ?>][action]" value="delete">
					</div>
					
				  </form>
				</div>
			
			<?php endforeach; ?>
		
			</div>
			
			<div class="customers-action">
				<div style="display:none" class="loader-facebook"><div></div><div></div><div></div></div>
				<button class="show-all-customers" type="button" data-nonce="<?php echo wp_create_nonce( 'show-all-customers' ); ?>">Показать всех клиентов</button>
			</div>
			
		</div>
		
		<?php endif; ?>
		
	</div>
</div>

<?php else : ?>

<div id="dialogs_block">
	<div class="inner_block">
		<h1>Страница доступна только для администратора</h1>
	</div>
</div>

<?php endif; ?>

<?php get_footer(); ?>