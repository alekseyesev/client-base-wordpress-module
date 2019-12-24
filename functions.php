<?php 

/* Include ClientBase module */

include get_template_directory() . '/include/client-base/client-base.php';

if ( class_exists( 'ClientBase' ) ) {

	new ClientBase;

}