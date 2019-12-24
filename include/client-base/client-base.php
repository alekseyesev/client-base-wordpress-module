<?php

class ClientBase {
	
	const LANG_DOMAIN = 'example';
	
	private $ajaxurl;
	
	public function __construct() {

        $this->ajaxurl = admin_url( 'admin-ajax.php' );
		
        add_action( 'wp_footer', array( $this, 'custom_scripts' ), 99 );
		
		add_action( 'deleted_user', array( $this, 'after_user_has_deleted' ) );
		
		add_action( 'wp_ajax_show_all_clients', array( $this, 'show_all_clients' ) );
		
		add_action( 'wp_ajax_client_base_delete', array( $this, 'remove' ) );
		
		add_action( 'wp_ajax_client_base_update', array( $this, 'update' ) );
		
		add_action( 'wp_ajax_client_base_delete_birthday_reminder', array( $this, 'delete_reminder' ) );
		
		add_action( 'wp_ajax_client_base_filter', array( $this, 'filter' ) );
		
		add_action( 'wp_ajax_client_base_period', array( $this, 'change_period' ) );
		
	}
	
	public function access_granted() {
		
		return current_user_can( 'level_10' );
		
	}
	
	public function custom_scripts() {
		
		if ( is_page_template( 'client-base.php' ) && $this->access_granted() ) { 
		
			$style_uri = get_template_directory_uri() . '/inc/client-base/client-base.css';
			$script_uri = get_template_directory_uri() . '/inc/client-base/client-base.js';
			
			echo <<<HTML
			
			<link href="{$style_uri}" rel="stylesheet" />
			<script>window['ClientBase'] = {ajaxurl: '{$this->ajaxurl}'};</script>
			<script defer src="{$script_uri}"></script>
				
HTML;

		}
		
	}
	
	public function show_all_clients() {
		
		$result = array( 'error' => true );
		
		if ( 
			is_admin() && 
			( 
				isset( $_POST['_wpnonce'] ) && 
				wp_verify_nonce( $_POST['_wpnonce'], 'show-all-customers' ) 
			) && 
			ClientBase::access_granted() 
		) {	
		
			if ( 
				empty( $_POST['period'] ) && 
				(  
					! is_numeric( $_POST['period'] ) ||  
					1000 > ( int ) $_POST['period'] || 
					9999 < ( int ) $_POST['period']
				)
			) {
				
				$filter = array();
				
			}
			else {
				
				$filter = array( 'period' => ( int ) $_POST['period'] );
				
			}
		
			if ( $client_base = ClientBase::get_list( false, $filter, 'fickle' ) ) {

				$result['error']        = false;
				$result['message']      = sprintf( __( 'Найдено непостоянных клиентов: %d.', self::LANG_DOMAIN ), count( $client_base ) );
				$result['clients']      = $client_base;
				$result['current_time'] = current_time( 'Y-m-d' );
				$result['nonce']        = wp_create_nonce( 'client_base' );
					
			}
			else { 
					
				$result['message'] =  __( 'По данному запросу клиенты не найдены.', self::LANG_DOMAIN );
					
			}
		
		}
		
		echo json_encode( $result, JSON_UNESCAPED_UNICODE );
		
		wp_die();
		
	}
	
	public function filter() {
		
		$result = array( 'error' => true );
		
		if ( 
			is_admin() && 
			( 
				isset( $_POST['_wpnonce'] ) && 
				wp_verify_nonce( $_POST['_wpnonce'], 'client_base' ) 
			) && 
			ClientBase::access_granted() 
		) {
			
			global $wpdb;
			
			if ( empty( $_POST['customer_filter_type'] ) || ( $_POST['customer_filter_type'] !== 'reset' && empty( $_POST['customer_filter_value'] ) ) ) {
				
				$result['message'] = __( 'Не заполнены обязательные поля.', self::LANG_DOMAIN );
				
			}
			elseif ( $_POST['customer_filter_type'] === 'tel' && ! preg_match( '/7\s\(\d{3}\)\s\d{3}-\d{2}-\d{2}/', $_POST['customer_filter_value'] ) ) {
				
				$result['message'] = __( 'Неверный формат телефона клиента.', self::LANG_DOMAIN );
				
			}
			elseif ( $_POST['customer_filter_type'] === 'email' && ! filter_var( $_POST['customer_filter_value'], FILTER_VALIDATE_EMAIL ) ) {
				
				$result['message'] = __( 'Неверный формат эл. почты клиента.', self::LANG_DOMAIN );
				
			}
			elseif ( $_POST['customer_filter_type'] === 'date' && strlen( $_POST['customer_filter_value'] ) === 10 && ! strtotime( $_POST['customer_filter_value'] ) ) {
				
				$result['message'] =  __( 'Неверный формат дня рождения клиента.', self::LANG_DOMAIN );
				
			}
			else {
				
				$filter_type_names = array(
					
					'reset' => __( 'Без фильтра', self::LANG_DOMAIN ),
					'text'  => __( 'Имя', self::LANG_DOMAIN ),
					'email' => __( 'Email', self::LANG_DOMAIN ),
					'tel'   => __( 'Телефон', self::LANG_DOMAIN ),
					'date'  => __( 'Дата рождения', self::LANG_DOMAIN ),

				);
				
				if ( $client_base = ClientBase::get_list( false, array( 'key' => $_POST['customer_filter_type'], 'value' => $_POST['customer_filter_type'] === 'reset' ? '' : $_POST['customer_filter_value'] ), $_POST['customer_filter_type'] === 'reset' ? 'regular' : 'all' ) ) {

					$result['error']        = false;
					$result['message']      = sprintf( __( 'Список клиентов успешно отфильтрован по параметру «%s».', self::LANG_DOMAIN ), $filter_type_names[$_POST['customer_filter_type']] );
					$result['clients']      = $client_base;
					$result['current_time'] = current_time( 'Y-m-d' );
					$result['nonce']        = $_POST['_wpnonce'];
					
				}
				else { 
					
					$result['message'] =  __( 'По данному запросу клиенты не найдены.', self::LANG_DOMAIN );
					
				}
			
			}

		}
		
		echo json_encode( $result, JSON_UNESCAPED_UNICODE );
		
		wp_die();
		
	}
	
	public function change_period() {
		
		$result = array( 'error' => true );
		
		if ( 
			is_admin() && 
			( 
				isset( $_POST['period_nonce'] ) && 
				wp_verify_nonce( $_POST['period_nonce'], 'client_base_period_selection' ) 
			) && 
			ClientBase::access_granted() 
		) {
			
			global $wpdb;
			
			if ( 
				empty( $_POST['customer_activity_period'] ) && 
				(
					$_POST['customer_activity_period'] !== 'all' ||   
					! is_numeric( $_POST['customer_activity_period'] ) ||  
					1000 > ( int ) $_POST['customer_activity_period'] || 
					9999 < ( int ) $_POST['customer_activity_period']
				)
			) {
				
				$result['message'] = __( 'Не указан период.', self::LANG_DOMAIN );
				
			}
			else {
				
				if ( $client_base = ClientBase::get_list( false, ( $_POST['customer_activity_period'] === 'all' ? array() : array( 'key' => 'period', 'value' => $_POST['customer_activity_period'] ) ) ) ) {

					$result['error']        = false;
					$result['message']      = sprintf( __( 'Найдено клиентов: %d.', self::LANG_DOMAIN ), count( $client_base ) );
					$result['clients']      = $client_base;
					$result['current_time'] = current_time( 'Y-m-d' );
					$result['nonce']        = wp_create_nonce( 'client_base' );
					
				}
				else { 
					
					$result['message'] =  __( 'По данному запросу клиенты не найдены.', self::LANG_DOMAIN );
					
				}
			
			}

		}
		
		echo json_encode( $result, JSON_UNESCAPED_UNICODE );
		
		wp_die();
		
	}
	
	public function update() {
		
		$result = array( 'error' => true );
		
		if ( 
			is_admin() && 
			( 
				isset( $_POST['_wpnonce'] ) && 
				wp_verify_nonce( $_POST['_wpnonce'], 'client_base' ) 
			) && 
			ClientBase::access_granted() 
		) {
			
			global $wpdb;
			
			if ( ! isset( $_POST['id'] ) || ! is_numeric( $_POST['id'] ) ) {
				
				$result['message'] = __( 'Неверный идентифиактор клиента.', self::LANG_DOMAIN );
				
			}
			elseif ( empty( $_POST['name'] ) ) {
				
				$result['message'] = __( 'Имя клиента обязательно для заполнения.', self::LANG_DOMAIN );
				
			}
			elseif ( empty( $_POST['phone'] ) || ! preg_match( '/7\s\(\d{3}\)\s\d{3}-\d{2}-\d{2}/', $_POST['phone'] ) ) {
				
				$result['message'] = __( 'Телефон клиента обязателен для заполнения.', self::LANG_DOMAIN );
				
			}
			elseif ( ! empty( $_POST['additional_phone'] ) && ! preg_match( '/7\s\(\d{3}\)\s\d{3}-\d{2}-\d{2}/', $_POST['additional_phone'] ) ) {
				
				$result['message'] = __( 'Неверный формат дополнительного телефона клиента.', self::LANG_DOMAIN );
				
			}
			elseif ( ! empty( $_POST['email'] ) && ! filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL ) ) {
				
				$result['message'] = __( 'Неверный формат эл. почты клиента.', self::LANG_DOMAIN );
				
			}
			elseif ( ! empty( $_POST['additional_email'] ) && ! filter_var( $_POST['additional_email'], FILTER_VALIDATE_EMAIL ) ) {
				
				$result['message'] =  __( 'Неверный формат дополнительной эл. почты клиента.', self::LANG_DOMAIN );
				
			}
			elseif ( ! empty( $_POST['birthday'] ) && strlen( $_POST['birthday'] ) === 10 && ! strtotime( $_POST['birthday'] ) ) {
				
				$result['message'] =  __( 'Неверный формат дня рождения клиента.', self::LANG_DOMAIN );
				
			}
			else {
				
				if ( $wpdb->update( 
					$wpdb->users, 
					array( 
						'display_name' => $_POST['name'],
						'user_phone'   => $_POST['phone'],
						'user_email'   => $_POST['email'],
					), 
					array( 'ID' => $_POST['id'] ), 
					array( 
						'%s',
						'%s',
						'%s',
					), 
					array( '%d' ) 
				) !== false ) {
					
					if ( isset( $_POST['additional_phone'] ) ) {
						
						update_user_meta( $_POST['id'], 'customer_additional_phone', $_POST['additional_phone'] );
					
					}
					
					if ( isset( $_POST['additional_email'] ) ) {
						
						update_user_meta( $_POST['id'], 'customer_additional_email', $_POST['additional_email'] );
					
					}
					
					if ( isset( $_POST['birthday'] ) ) {
						
						update_user_meta( $_POST['id'], 'customer_birthday', $_POST['birthday'] );
					
					}
					
					if ( isset( $_POST['admin_notes'] ) ) {
						
						update_user_meta( $_POST['id'], 'customer_admin_notes', $_POST['admin_notes'] );
					
					}
					
					$result['error'] = false;
					$result['message'] = __( 'Данные успнешно сохранены в базу.', self::LANG_DOMAIN );
					
				}
				else {
					
					$result['message'] =  __( 'Во время записи данных в базу произошла ошибка.', self::LANG_DOMAIN );
					
				}
			
			}

		}
		
		echo json_encode( $result, JSON_UNESCAPED_UNICODE );
		
		wp_die();
		
	}
	
	public function remove() { 
	
		$result = array( 'error' => true );
		
		if ( 
			is_admin() && 
			( 
				isset( $_POST['_wpnonce'] ) && 
				wp_verify_nonce( $_POST['_wpnonce'], 'client_base' ) 
			) && 
			ClientBase::access_granted() 
		) {
			
			global $wpdb;
			
			if ( ! isset( $_POST['id'] ) || ! is_numeric( $_POST['id'] ) ) {
				
				$result['message'] = __( 'Неверный идентифиактор клиента.', self::LANG_DOMAIN );
				
			}
			else {
				
				if ( wp_delete_user( $_POST['id'] ) ) {
					
					
					$result['error'] = false;
					$result['message'] = __( 'Данные клиента успешно удалены из базы.', self::LANG_DOMAIN );
					
				}
				else {
					
					$result['message'] =  __( 'Во время удаления данных из базы произошла ошибка.', self::LANG_DOMAIN );
					
				}
			
			}

		}
		
		echo json_encode( $result, JSON_UNESCAPED_UNICODE );
		
		wp_die();
	
	}
	
	public function delete_reminder() { 
	
		$result = array( 'error' => true );
		
		if ( 
			is_admin() && 
			( 
				isset( $_POST['_wpnonce'] ) && 
				wp_verify_nonce( $_POST['_wpnonce'], 'client_base' ) 
			) && 
			ClientBase::access_granted() 
		) {
			
			global $wpdb;
			
			$current_date = current_time( 'Ymd' );
			
			if ( ! isset( $_POST['id'] ) || ! is_numeric( $_POST['id'] ) ) {
				
				$result['message'] = __( 'Неверный идентифиактор клиента.', self::LANG_DOMAIN );
				
			}
			else {
				
				$user_id = (int) $_POST['id'];
				
				$admin_id = get_current_user_id();
				
				if ( $client_base_birthday_exclude = get_user_meta( $admin_id, 'client_base_birthday_exclude', true ) ) {
				
					if ( isset( $client_base_birthday_exclude[$current_date] ) ) {
						
						if ( ! in_array( $user_id, $client_base_birthday_exclude[$current_date] ) ) {
							
							$client_base_birthday_exclude[$current_date][] = $user_id;
							
						}
						
						$result['error'] = false;
						$result['message'] = __( 'Уведомление успешно удалено.', self::LANG_DOMAIN );
						
					}
					else {
						
						$client_base_birthday_exclude[$current_date] = array( $user_id );
						
						if ( update_user_meta( $admin_id, 'client_base_birthday_exclude', $client_base_birthday_exclude ) ) {
							
							$result['error'] = false;
							$result['message'] = __( 'Уведомление успешно удалено.', self::LANG_DOMAIN );
							
						}
						else {
							
							$result['message'] = __( 'Во время удаления уведомления произошла ошибка.', self::LANG_DOMAIN );
							
						}
						
					}

				}
				else {
					
					if ( update_user_meta( $admin_id, 'client_base_birthday_exclude', array( $current_date => array( $user_id ) ) ) ) {
						
						$result['error'] = false;
						$result['message'] = __( 'Уведомление успешно удалено.', self::LANG_DOMAIN );
						
					}
					else {
						
						$result['message'] = __( 'Во время удаления уведомления произошла ошибка.', self::LANG_DOMAIN );
						
					}
					
				}
			
			}

		}
		
		echo json_encode( $result, JSON_UNESCAPED_UNICODE );
		
		wp_die();
	
	}
	
	public function after_user_has_deleted( $user_id ) {
		
		global $wpdb;
		
		if ( $comments_to_delete = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT 
				GROUP_CONCAT(comment_ID)
			FROM 
				{$wpdb->comments} 
			WHERE 
				user_id = %d
			GROUP BY 
				user_id
			",
			$user_id
		) ) ) {
			
			$comments_to_delete = explode( ',', $comments_to_delete );
			
			foreach ( $comments_to_delete as $comment_id ) {
				
				wp_delete_comment( $comment_id, true );
				
			}
			
		}
		
		$wpdb->query( $wpdb->prepare( 
			"
			DELETE FROM 
				{$wpdb->prefix}deals
			WHERE 
				user_id = %d
			",
			$user_id
		) );
		
		$wpdb->query( $wpdb->prepare( 
			"
			DELETE FROM 
				{$wpdb->prefix}notifications
			WHERE 
				user_id = %d
			",
			$user_id
		) );
		
	}
	
	public static function get_count() {
		
		if ( ! ClientBase::access_granted() ) 
			
			return false;
		
		global $wpdb;
		
		return  $wpdb->get_var(	
			"
			SELECT 
				COUNT(*)
			FROM 
				{$wpdb->users} AS users,
				{$wpdb->usermeta} AS usermeta
			WHERE 
				users.ID = usermeta.user_id 
			AND 
				usermeta.meta_key = 'wp_user_level'
			AND 
				usermeta.meta_value != '10'
			"		
		);
		
	}
	
	public static function get_start_datetime() { 
		
		if ( ! ClientBase::access_granted() ) 
			
			return false;
		
		global $wpdb;
		
		return $wpdb->get_var( "SELECT MIN( time ) FROM {$wpdb->prefix}deals WHERE time <> '0000-00-00 00:00:00'" );

	}
	
	public static function get_list( $birthday = false, $filter = array(), $only_clients = 'regular' ) { 
		
		if ( ! ClientBase::access_granted() ) 
			
			return false;
		
		global $wpdb, $_TYPE_DOGOVOR2;
		
		if ( $birthday ) {
			
			$current_month_day = current_time( '-m-d' );
			
			$clients = $wpdb->get_results( 
				"
				SELECT 
					clients.*
				FROM 
					(SELECT 
						users.ID AS id,
						users.display_name AS name,
						users.user_phone AS phone
					FROM 
						{$wpdb->users} AS users,
						{$wpdb->usermeta} AS usermeta
					WHERE 
						users.ID = usermeta.user_id 
					AND 
						usermeta.meta_key = 'wp_user_level'
					AND 
						usermeta.meta_value <> '10'
					ORDER BY 
						id 
					ASC) AS clients,
					{$wpdb->usermeta} AS clientmeta
				WHERE 
					clients.id = clientmeta.user_id
				AND 
					clientmeta.meta_key = 'customer_birthday'
				AND 
					clientmeta.meta_value LIKE '%{$current_month_day}'
				"
			);
			
		}
		else {
			
			if ( ! empty( $filter ) && ! empty( $filter['key'] ) && ! empty( $filter['value'] ) ) {
				
				$filter_type_queries = array(
					
					'reset' => "",
					'text'  => "
						AND 
							(users.display_name LIKE '%{$filter['value']}%')
					",
					'email' => "
						AND 
							(users.user_email LIKE '%{$filter['value']}%' OR users.customer_additional_email LIKE '%{$filter['value']}%')
					",
					'tel'   => "
						AND 
							(users.user_phone = '{$filter['value']}' OR users.customer_additional_phone = '{$filter['value']}')
					",
					'date'  => "
						AND 
							(users.customer_birthday = '{$filter['value']}')
					",
					'period'  => "
						AND 
							(YEAR( deals.datetime ) = '{$filter['value']}')
					",
					

				);
				
				$filter_condition = $filter_type_queries[$filter['key']];
				
			}
			else {
				
				$filter_condition = "";
				
			}

			$mysqli_connect = $wpdb->__get( "dbh" );
			
			mysqli_query( $mysqli_connect, "SET SESSION group_concat_max_len = 65536" );
			
			mysqli_query( $mysqli_connect, "SET SQL_BIG_SELECTS = 1" );

			$query = 
				"SELECT 
					users.ID AS id,
					users.display_name AS name,
					DATE_FORMAT(users.user_registered, '%H:%i %d.%m.%Y') AS registration_datetime,
					users.user_phone AS phone,
					users.customer_additional_phone AS additional_phone,
					users.user_email AS email,
					users.customer_additional_email AS additional_email,
					users.customer_birthday AS birthday,
					users.customer_admin_notes AS admin_notes,
                    GROUP_CONCAT( DISTINCT CONCAT_WS( ',', deals.deal_id, deals.datetime, deals.title ) SEPARATOR ';' ) AS deals_list,
					COUNT( DISTINCT deals.datetime ) AS deals_count
				FROM 
					(
						SELECT 
							*
						FROM 
							{$wpdb->users} AS users
						LEFT JOIN 
							(
							SELECT
								user_id as ID,
								meta_value as customer_additional_email
							FROM 
								{$wpdb->usermeta}
							WHERE 
								meta_key = 'customer_additional_email'
							) as tmp_additional_email
						USING(ID)
						LEFT JOIN 
							(
							SELECT
								user_id as ID,
								meta_value as customer_additional_phone
							FROM 
								{$wpdb->usermeta}
							WHERE 
								meta_key = 'customer_additional_phone'
							) as tmp_additional_phone
						USING(ID)
						LEFT JOIN 
							(
							SELECT
								user_id as ID,
								meta_value as customer_birthday
							FROM 
								{$wpdb->usermeta}
							WHERE 
								meta_key = 'customer_birthday'
							) as tmp_birthday
						USING(ID)
						LEFT JOIN 
							(
							SELECT
								user_id as ID,
								meta_value as customer_admin_notes
							FROM 
								{$wpdb->usermeta}
							WHERE 
								meta_key = 'customer_admin_notes'
							) as tmp_admin_notes
						USING(ID)
						LEFT JOIN 
							(
							SELECT
								user_id as ID,
								meta_value as wp_user_level
							FROM 
								{$wpdb->usermeta}
							WHERE 
								meta_key = 'wp_user_level'
							) as tmp_user_level
						USING(ID)
						WHERE 
							tmp_user_level.wp_user_level <> '10'
						ORDER BY 
							ID 
						ASC
					) AS users 
				LEFT JOIN 
					(
						SELECT 
							tmp_deals.user_id AS ID,
							tmp_deals.post_id AS deal_id,  
							tmp_deals.time AS datetime,
							CONCAT( 'Сделка ', ( CASE WHEN tmp_deals.dogovor = 0 THEN LOWER('{$_TYPE_DOGOVOR2[0]} недвижимости') WHEN tmp_deals.dogovor = 1 THEN LOWER('{$_TYPE_DOGOVOR2[1]} недвижимости') ELSE LOWER('{$_TYPE_DOGOVOR2[2]}') END ) ) AS title
						FROM 
							{$wpdb->prefix}deals AS tmp_deals 
						WHERE 
							tmp_deals.time <> '0000-00-00 00:00:00'
						ORDER BY 
							tmp_deals.user_id 
						ASC 
					) AS deals 
				USING(ID) 
				WHERE 
					1 = 1  
				{$filter_condition}
				GROUP BY 
					id 
				ORDER BY 
					deals_count
				DESC";

			if ( $result = mysqli_query( $mysqli_connect, $query ) ) {
				
				while ( $client = mysqli_fetch_object( $result ) ) {
					
					switch ( $only_clients ) {
						
						case 'regular':
						
							$continue = ( int ) $client->deals_count < 2;
						
						break;
						
						case 'fickle':
						
							$continue = ( int ) $client->deals_count > 1;
						
						break;
						
						default:
						
							$continue = false;
						
						break;
						
						
					}
					
					if ( $continue ) continue;
					
					$clients[] = $client;
					
				}
				
				mysqli_free_result( $result );
	
			} 
			
			unset( $mysqli_connect );
		
		}
		
		return $clients; 
		
	}
	
}
	
?>