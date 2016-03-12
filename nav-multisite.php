<?php 
/*
Plugin Name:    Menus Multisite Picker
Version:        1.0
Description:    A multisite metabox in Menus of Wordpress Appearance
Author:         Vũ Ngọc Tùng | Ngoc Tung Vu (Mr)
Author URI:     http://tungvn.info/
Plugin URI:     
Text Domain:    menu-multisite-picker
License:        GPLv2

Copyright 2016  Vũ Ngọc Tùng
*/

// Add a meta box for multisite at nav menus
add_action( 'admin_init', 'add_nav_menu_meta_boxes' );
function add_nav_menu_meta_boxes() {
	// Check multisite enable
	if( is_multisite() ) {
		add_meta_box(
			'tvn_add_metabox_multisite',
			__( 'Multisite' ),
			'tvn_add_metabox_multisite_cb',
			'nav-menus',
			'side',
			'high'
		);
	}
}

// Content of metabox
function tvn_add_metabox_multisite_cb() {
	global $wpdb;
	$args = array(
		'network_id' => $wpdb->siteid,
		'public' => true,
		'limit' => 10000,
	);
	$sites = wp_get_sites( $args );
	$current_blog_id = get_current_blog_id();

	if( !empty( $sites ) ): ?>

		<div id="multisite" class="posttypediv">
			<!-- Tabs -->
			<ul id="posttype-page-tabs" class="posttype-tabs add-menu-item-tabs">
				<li class="tabs">
					<a class="nav-tab-link" data-type="multisite-all" href="/wp-admin/nav-menus.php?page-tab=all#multisite-all">View All</a>
				</li>
				<li>
					<a class="nav-tab-link" data-type="tabs-panel-multisite-search" href="/wp-admin/nav-menus.php?page-tab=#tabs-panel-multisite-search">Search</a>
				</li>
			</ul>

			<!-- Tabs Content -->
			<div id="multisite-all" class="tabs-panel tabs-panel-active">
				<ul id ="multisite-checklist" class="categorychecklist form-no-clear">

				<?php foreach ($sites as $skey => $site):
					$site_details = get_blog_details( $site['blog_id'] );

					$site_name = $site_details->blogname;
					$site_url = $site_details->siteurl; ?>

					<li>
						<label class="menu-item-title">
							<input type="checkbox" class="menu-item-checkbox" name="menu-item[-10][menu-item-object-id]" value="-10"> <?php echo $site_name; ?>
						</label>
						<input type="hidden" class="menu-item-type" name="menu-item[-10][menu-item-type]" value="custom">
						<input type="hidden" class="menu-item-title" name="menu-item[-10][menu-item-title]" value="<?php echo $site_name; ?>">
						<input type="hidden" class="menu-item-url" name="menu-item[-10][menu-item-url]" value="<?php echo $site_url; ?>">
						<input type="hidden" class="menu-item-target" name="menu-item[-10][menu-item-target]" value="">
						<input type="hidden" class="menu-item-attr_title" name="menu-item[-10][menu-item-attr_title]" value="">
						<input type="hidden" class="menu-item-classes" name="menu-item[-10][menu-item-classes]" value="">
						<input type="hidden" class="menu-item-xfn" name="menu-item[-10][menu-item-xfn]" value="">
					</li>

				<?php endforeach;
				switch_to_blog( $current_blog_id ); ?>
				</ul>
			</div>

			<div id="tabs-panel-multisite-search" class="tabs-panel tabs-panel-inactive">
				<p class="quick-search-wrap">
					<input autocomplete="off" class="quick-search input-with-default-title" title="Search" value="" name="quick-search-multisite" type="search">
					<span class="spinner"></span>
					<input name="submit" id="submit-quick-search-multisite" class="button button-small quick-search-submit hide-if-js" value="Search" type="submit">
				</p>

				<ul id="multisite-search-checklist" data-wp-lists="list:multisite" class="categorychecklist form-no-clear"></ul>
			</div>

			<p class="button-controls">
				<span class="list-controls">
					<a href="/wp-admin/nav-menus.php?page-tab=all&amp;selectall=1#multisite-all" class="select-all">Select All</a>
				</span>
				<span class="add-to-menu">
					<input type="submit" class="button-secondary submit-add-to-menu right" value="Add to Menu" name="add-post-type-menu-item" id="submit-multisite">
					<span class="spinner"></span>
				</span>
			</p>
		</div>
	<?php endif;
}

// Custom XHR for search multisite
add_action( 'wp_ajax_menu-quick-search', 'tvn_custom_menu_quick_search_cb', 1 );
function tvn_custom_menu_quick_search_cb() {
	if ( ! current_user_can( 'edit_theme_options' ) )
		wp_die( -1 );

	require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

	$request = $_POST;

	$args = array();
	$type = isset( $request['type'] ) ? $request['type'] : '';
	$object_type = isset( $request['object_type'] ) ? $request['object_type'] : '';
	$query = isset( $request['q'] ) ? $request['q'] : '';
	$response_format = isset( $request['response-format'] ) && in_array( $request['response-format'], array( 'json', 'markup' ) ) ? $request['response-format'] : 'json';

	if ( 'markup' == $response_format ) {
		$args['walker'] = new Walker_Nav_Menu_Checklist;
	}

	if ( 'get-post-item' == $type ) {
		if ( post_type_exists( $object_type ) ) {
			if ( isset( $request['ID'] ) ) {
				$object_id = (int) $request['ID'];
				if ( 'markup' == $response_format ) {
					echo walk_nav_menu_tree( array_map('wp_setup_nav_menu_item', array( get_post( $object_id ) ) ), 0, (object) $args );
				} elseif ( 'json' == $response_format ) {
					echo wp_json_encode(
						array(
							'ID' => $object_id,
							'post_title' => get_the_title( $object_id ),
							'post_type' => get_post_type( $object_id ),
						)
					);
					echo "\n";
				}
			}
		} elseif ( taxonomy_exists( $object_type ) ) {
			if ( isset( $request['ID'] ) ) {
				$object_id = (int) $request['ID'];
				if ( 'markup' == $response_format ) {
					echo walk_nav_menu_tree( array_map('wp_setup_nav_menu_item', array( get_term( $object_id, $object_type ) ) ), 0, (object) $args );
				} elseif ( 'json' == $response_format ) {
					$post_obj = get_term( $object_id, $object_type );
					echo wp_json_encode(
						array(
							'ID' => $object_id,
							'post_title' => $post_obj->name,
							'post_type' => $object_type,
						)
					);
					echo "\n";
				}
			}

		}

	} elseif ( preg_match('/quick-search-(posttype|taxonomy)-([a-zA-Z_-]*\b)/', $type, $matches) ) {
		if ( 'posttype' == $matches[1] && get_post_type_object( $matches[2] ) ) {
			query_posts(array(
				'posts_per_page' => 10,
				'post_type' => $matches[2],
				's' => $query,
			));
			if ( ! have_posts() )
				return;
			while ( have_posts() ) {
				the_post();
				if ( 'markup' == $response_format ) {
					$var_by_ref = get_the_ID();
					echo walk_nav_menu_tree( array_map('wp_setup_nav_menu_item', array( get_post( $var_by_ref ) ) ), 0, (object) $args );
				} elseif ( 'json' == $response_format ) {
					echo wp_json_encode(
						array(
							'ID' => get_the_ID(),
							'post_title' => get_the_title(),
							'post_type' => get_post_type(),
						)
					);
					echo "\n";
				}
			}
		} elseif ( 'taxonomy' == $matches[1] ) {
			$terms = get_terms( $matches[2], array(
				'name__like' => $query,
				'number' => 10,
			));
			if ( empty( $terms ) || is_wp_error( $terms ) )
				return;
			foreach ( (array) $terms as $term ) {
				if ( 'markup' == $response_format ) {
					echo walk_nav_menu_tree( array_map('wp_setup_nav_menu_item', array( $term ) ), 0, (object) $args );
				} elseif ( 'json' == $response_format ) {
					echo wp_json_encode(
						array(
							'ID' => $term->term_id,
							'post_title' => $term->name,
							'post_type' => $matches[2],
						)
					);
					echo "\n";
				}
			}
		}
	}
	// Custom for multisite
	elseif( 'quick-search-multisite' == $type ) {
		global $wpdb;
		$sites = wp_get_sites(
			array(
				'network_id' => $wpdb->siteid,
				'public' => true,
				'limit' => 10000,
			)
		);
		$current_blog_id = get_current_blog_id();

		if( empty( $sites ) )
			return;

		foreach ($sites as $skey => $site) {
			$returns = array();
			$site_details = get_blog_details( $site['blog_id'] );

			$site_name = $site_details->blogname;
			$site_url = $site_details->siteurl;

			if( strpos( strtolower( $site_name ), strtolower( $query ) ) !== false ) {
				$returns = array(
					'ID' => $site['blog_id'],
					'title' => $site_name,
					'url' => $site_url,
				);

				if ( 'markup' == $response_format ) {
					$html = '<li>';
					$html .= '<label class="menu-item-title">';
					$html .= '<input type="checkbox" class="menu-item-checkbox" name="menu-item[-10][menu-item-object-id]" value="'. $returns['ID'] .'" /> ';
					$html .= $site_name;
					$html .= '</label>';
					$html .= '<input type="hidden" class="menu-item-type" name="menu-item[-10][menu-item-type]" value="custom" />';
					$html .= '<input type="hidden" class="menu-item-title" name="menu-item[-10][menu-item-title]" value="'. $site_name .'" />';
					$html .= '<input type="hidden" class="menu-item-url" name="menu-item[-10][menu-item-url]" value="'. $site_url .'" />';
					$html .= '<input type="hidden" class="menu-item-target" name="menu-item[-10][menu-item-target]" value="" />';
					$html .= '<input type="hidden" class="menu-item-attr_title" name="menu-item[-10][menu-item-attr_title]" value="" />';
					$html .= '<input type="hidden" class="menu-item-classes" name="menu-item[-10][menu-item-classes]" value="" />';
					$html .= '<input type="hidden" class="menu-item-xfn" name="menu-item[-10][menu-item-xfn]" value="" />';
					$html .= '</li>';
					echo $html;
				}
				elseif ( 'json' == $response_format ) {
					echo wp_json_encode( $returns );
					echo "\n";
				}
			}
		}
	}

	wp_die();
}
