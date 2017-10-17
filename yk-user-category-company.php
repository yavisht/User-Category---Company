<?php
/**
 * User Category - Company
 *
 * @package     YK-User-Category-Company
 * @author      Yavisht Katgara
 * @copyright   2017 YKAT.COM.AU
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: User Category - Company
 * Plugin URI:  http://www.ykat.com.au
 * Description: Adds a category called Company to Users
 * Version:     1.0.0
 * Author:      Yavisht Katgara
 * Author URI:  http://www.ykat.com.au
 * Text Domain: yk-user-category-company
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_action( 'init', 'yk_register_company_taxonomy', 0 );

/**
 * Registers the 'company' taxonomy for users.  This is a taxonomy for the 'user' object type rather than a 
 * post being the object type.
 */
function yk_register_company_taxonomy() {

	 register_taxonomy(
		'company',
		'user',
		array(
			'public' => true,
			'labels' => array(
				'name' => __( 'Companies' ),
				'singular_name' => __( 'Company' ),
				'menu_name' => __( 'Companies' ),
				'search_items' => __( 'Search Companies' ),
				'popular_items' => __( 'Popular Companies' ),
				'all_items' => __( 'All Companies' ),
				'edit_item' => __( 'Edit Company' ),
				'update_item' => __( 'Update Company' ),
				'add_new_item' => __( 'Add New Company' ),
				'new_item_name' => __( 'New Company Name' ),
				'separate_items_with_commas' => __( 'Separate companies with commas' ),
				'add_or_remove_items' => __( 'Add or remove companies' ),
				'choose_from_most_used' => __( 'Choose from the most popular companies' ),
			),
			'rewrite' => array(
				'with_front' => true,
				'slug' => 'author/company' // Use 'author' (default WP user slug).
			),
			'capabilities' => array(
				'manage_terms' => 'edit_users', // Using 'edit_users' cap to keep this simple.
				'edit_terms'   => 'edit_users',
				'delete_terms' => 'edit_users',
				'assign_terms' => 'read',
			),
			'update_count_callback' => 'yk_update_company_count' // Use a custom function to update the count.
		)
	);
}

/**
 * Function for updating the 'company' taxonomy count.  What this does is update the count of a specific term 
 * by the number of users that have been given the term.  We're not doing any checks for users specifically here. 
 * We're just updating the count with no specifics for simplicity.
 *
 * See the _update_post_term_count() function in WordPress for more info.
 *
 * @param array $terms List of Term taxonomy IDs
 * @param object $taxonomy Current taxonomy object of terms
 */
function yk_update_company_count( $terms, $taxonomy ) {
	global $wpdb;

	foreach ( (array) $terms as $term ) {

		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );

		do_action( 'edit_term_taxonomy', $term, $taxonomy );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
		do_action( 'edited_term_taxonomy', $term, $taxonomy );
	}
}


/* Adds the taxonomy page in the admin. */
add_action( 'admin_menu', 'yk_add_company_admin_page' );

/**
 * Creates the admin page for the 'company' taxonomy under the 'Users' menu.  It works the same as any 
 * other taxonomy page in the admin.  However, this is kind of hacky and is meant as a quick solution.  When 
 * clicking on the menu item in the admin, WordPress' menu system thinks you're viewing something under 'Posts' 
 * instead of 'Users'.  We really need WP core support for this.
 */
function yk_add_company_admin_page() {

	$tax = get_taxonomy( 'company' );

	add_users_page(
		esc_attr( $tax->labels->menu_name ),
		esc_attr( $tax->labels->menu_name ),
		$tax->cap->manage_terms,
		'edit-tags.php?taxonomy=' . $tax->name
	);
}

/* Create custom columns for the manage company page. */
add_filter( 'manage_edit-company_columns', 'yk_manage_company_user_column' );

/**
 * Unsets the 'posts' column and adds a 'users' column on the manage company admin page.
 *
 * @param array $columns An array of columns to be shown in the manage terms table.
 */
function yk_manage_company_user_column( $columns ) {

	unset( $columns['posts'] );

	$columns['users'] = __( 'Users' );

	return $columns;
}

/* Customize the output of the custom column on the manage company's page. */
add_action( 'manage_company_custom_column', 'yk_manage_company_column', 10, 3 );

/**
 * Displays content for custom columns on the manage professions page in the admin.
 *
 * @param string $display WP just passes an empty string here.
 * @param string $column The name of the custom column.
 * @param int $term_id The ID of the term being displayed in the table.
 */
function yk_manage_company_column( $display, $column, $term_id ) {

	if ( 'users' === $column ) {
		$term = get_term( $term_id, 'company' );
		echo $term->count;
	}
}

/* Add section to the edit user page in the admin to select company. */
add_action( 'show_user_profile', 'my_edit_user_company_section' );
add_action( 'edit_user_profile', 'my_edit_user_company_section' );

/**
 * Adds an additional settings section on the edit user/profile page in the admin.  This section allows users to 
 * select a company from a checkbox of terms from the company taxonomy.  This is just one example of 
 * many ways this can be handled.
 *
 * @param object $user The user object currently being edited.
 */
function my_edit_user_company_section( $user ) {

	$tax = get_taxonomy( 'company' );

	/* Make sure the user can assign terms of the company taxonomy before proceeding. */
	if ( !current_user_can( $tax->cap->assign_terms ) )
		return;

	/* Get the terms of the 'company' taxonomy. */
	$terms = get_terms( 'company', array( 'hide_empty' => false ) ); ?>

	<h3><?php _e( 'Company' ); ?></h3>

	<table class="form-table">

		<tr>
			<th><label for="company"><?php _e( 'Select Company' ); ?></label></th>

			<td><?php

			/* If there are any company terms, loop through them and display checkboxes. */
			if ( !empty( $terms ) ) {

				foreach ( $terms as $term ) { ?>
					<input type="radio" name="company" id="company-<?php echo esc_attr( $term->slug ); ?>" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( true, is_object_in_term( $user->ID, 'company', $term ) ); ?> /> <label for="company-<?php echo esc_attr( $term->slug ); ?>"><?php echo $term->name; ?></label> <br />
				<?php }
			}

			/* If there are no company terms, display a message. */
			else {
				_e( 'There are no companies available.' );
			}

			?></td>
		</tr>

	</table>
<?php }

/* Update the company terms when the edit user page is updated. */
add_action( 'personal_options_update', 'yk_save_user_company_terms' );
add_action( 'edit_user_profile_update', 'yk_save_user_company_terms' );

/**
 * Saves the term selected on the edit user/profile page in the admin. This function is triggered when the page 
 * is updated.  We just grab the posted data and use wp_set_object_terms() to save it.
 *
 * @param int $user_id The ID of the user to save the terms for.
 */
function yk_save_user_company_terms( $user_id ) {

	$tax = get_taxonomy( 'company' );

	/* Make sure the current user can edit the user and assign terms before proceeding. */
	if ( !current_user_can( 'edit_user', $user_id ) && current_user_can( $tax->cap->assign_terms ) )
		return false;

	$term = esc_attr( $_POST['company'] );

	/* Sets the terms (we're just using a single term) for the user. */
	wp_set_object_terms( $user_id, array( $term ), 'company', false);

	clean_object_term_cache( $user_id, 'company' );
}

/**
 * Function for outputting the correct text in a tag cloud.  Use as the 'update_topic_count_callback' argument 
 * when calling wp_tag_cloud().  Instead of 'topics' it displays 'users'.
 *
 * @param int $count The count of the objects for the term.
 */
function yk_profession_count_text( $count ) {
	return sprintf( _n('%s user', '%s users', $count ), number_format_i18n( $count ) );
}

/* Filter the 'sanitize_user' to disable username. */
add_filter( 'sanitize_user', 'yk_disable_username' );

/**
 * Disables the 'company' username when someone registers.  This is to avoid any conflicts with the custom 
 * 'author/company' slug used for the 'rewrite' argument when registering the 'company' taxonomy.  This
 * will cause WordPress to output an error that the username is invalid if it matches 'company'.
 *
 * @param string $username The username of the user before registration is complete.
 */
function yk_disable_username( $username ) {

	if ( 'company' === $username )
		$username = '';

	return $username;
}

/**
*
* To make sure that the right hand side menu highlights company when you are on the companies tax admin page
*
*/

add_filter( 'parent_file', 'yk_fix_user_tax_page' );

function yk_fix_user_tax_page( $parent_file = '' ) {
	
	global $pagenow;

	if ( ! empty( $_GET[ 'taxonomy' ] ) && $_GET[ 'taxonomy' ] == 'company' && $pagenow == 'edit-tags.php' ) {
		$parent_file = 'users.php';
	}

	return $parent_file;
}


/**
*
* Show company as a column on the User's page
*
*/

function yk_add_company_column( $column ) {
    
    $column['company'] = 'Company';
    
    return $column;

}

add_filter( 'manage_users_columns', 'yk_add_company_column' );


function yk_add_company_column_row( $val, $column_name, $user_id ) {

	$yk_get_user_company_name = wp_get_object_terms( $user_id, 'company');
	
	$yk_get_user_company_name = $yk_get_user_company_name[0]->name;
    
    switch ($column_name) {

        case 'company' :

            return $yk_get_user_company_name;

            break;

        default:

    }

    return $val;

}

add_filter( 'manage_users_custom_column', 'yk_add_company_column_row', 10, 3 );

/**
*
* In order to delete user object to term relations upon user deletion use this function on the ‘delete_user’ action hook
*
*/

add_action( 'delete_user', 'yk_delete_user_object_term_relationships' );

function yk_delete_user_object_term_relationships( $user_id ) {

	wp_delete_object_term_relationships( $user_id, 'company' );

}
