<?php
/*
Plugin Name: xili-tidy-tags
Plugin URI: http://dev.xiligroup.com/xili-tidy-tags/
Description: xili-tidy-tags is a tool for grouping tags by language or semantic group. Initially developed to enrich xili-language plugin and usable in all sites (CMS) and bbPress forum or others custom taxonomies.
Author: dev.xiligroup.com - MS
Version: 1.11.1
Author URI: http://dev.xiligroup.com
License: GPLv2
Text Domain: xili_tidy_tags
Domain Path: /languages/
*/

# 1.11.1 - 150705 - more tests with WP 4.3-beta
# 1.11.0 - 150703 - datatables js updated to 1.10.7 (for jQuery 1.11.3 WP 4.3)
# 1.10.3 - 150408 - fixes notice with constante line 61 of admin
# 1.10.2 - 150322 - Update datatables js css - tested WP 4.2-beta2
# 1.10.1 - 150228 - Tested WP 4.2-alpha - rewrite selected(), checked() - new js pour dropdown function (updated - see end)

# 1.10.0 - 140313 - Tested WP 4.1 - fixes editing alias tags
# 1.9.3 - 141128 - Fixes $news_id pointer in admin and WP_LANG for 4.x
# 1.9.2 - 140313 - First tests with WP 3.9rc - es_ES and sr_RS translation added (contribution of http://firstsiteguide.com/)

# 1.9.1 - 131229 - Deeper tests with WP 3.8 -
# 1.9.0 - 130922 - Introduces of tags multilingual grouping (using alias feature existing in WP taxonomy)
# 1.8.6 - 130903 - tests 3.6 final - fixes Strict Standards message (class Walker) - new icons - dropdown template tag for tags : xili_tidy_tags_dropdown
# 1.8.5 - 130524 - tests 3.6 - fixes Strict Standards message (class Walker) - fixes rare situation when one or more plugin desactivated - fixes
# 1.8.4 - 130508 - tests 3.6 - clean $wp_roles on deactivating
# 1.8.3 - 130421 - tests 3.6 - better errors management for external instancing
# 1.8.2 - 130127 - tests 3.5.1 - fixes prepare warning - fixes support (s)

# 1.8.1 - 120925 - fixes - ready for bbPress topic-tag
# 1.8.0 - 120819 - 120728 - class admin in separated file - new icons
# 1.7.0 - 120528 - language info in tags list - fixes in assign list display - cloud of other site if multisite in widget (further dev)
# 1.6.5 - 120405 - pre-tests WP3.4: fixes metaboxes columns

# 1.6.3 - 111210, 120122 - warning fixes, Notices
# 1.6.2 - 111008 - fixes no groups for custom post tags - clean source warnings - tag edit + hierarchy
# 1.6.1 - 110628 - fixes url and messages, new folder organization, fixes
# 1.6.0 - 110603 - ready for custom taxonomy and custom post
# 1.5.5 - 110602 - source code cleaned - possible multiple instantiation
# 1.5.4 - 110320 - 2 new template tags, posts series of group tag and examples, support email metabox
# 1.5.3.1 - 110209 - add option to desactivate javascript list

# 1.5.3 - 101217 - add options to select unchecked tags only and to exclude one group and include unchecked.
# 1.5.2 - 101205 - some cache issues fixed
# 1.5.1 - 101128 - popup for groups in widget
# 1.5.0 - 101107 - add DOM datatables js library - widget as extends class - fixe cache pb with get_terms - contextual help
# 1.4.3 - 101007 - fixes add_action for admin taxonomies of custom post type
# 1.4.2 - 100930 - fixes "warning" when xili-language is not present and no groups created ar first activation. More comments in source
# 1.4.1 - 100728 - fixes before published as current version
# 1.4.0 - 100727 - some source lines rewritten, new messages window, capabilities setting added in settings
# 1.3.4 - 100424 - special add for wpmu as superadmin
# 1.3.3 - 100416 - Compatible with xili-language 1.5.2
# 1.3.2 - 100411 - Optimizations for WMPU 3.0
# 1.3.1 - 100407 - minor modifications for WPMU 3.0
# 1.3.0 - 100218 - add sub-selection by tags belonging to a group (suggestion of David) - Now uses Walker class to sort groups in UI.

# 1.2.1 - 091129 - fix quick-edit tag error (thanks to zarban)
# 1.2 - 091122 - fix subselection sort in get_terms_of_groups (thanks to zarban)
# 1.1 - 091012 - new xili_the_tags() for the loop
# 1.0.1 - 090718 - new icon in admin menu - some fixes in php code for some servers (Thanks to Giannis)
# 1.0   - 090611 - add shortcode to include a cloud of a group of tags inside a post - compatible with WP 2.8
# 0.9.6 - 090602 <- # 0.8.1 - 090331 - see history in readme.txt -
# first public release 090329 - 0.8.0 - beta version

# This plugin is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This plugin is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this plugin; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

define('XILITIDYTAGS_VER','1.11.1'); /* used in admin UI */

class xili_tidy_tags {

	//var $is_metabox = false; /* for tests of special box in post */
	//var $is_post_ajax = false; /* for tests using ajax in UI */
	var $langgroupid = 0; /* group of langs*/


	var $post_tag = 'post_tag'; /* by default group post_tag 1.5.5 */
	var $post_tag_post_type = 'post';
	var $tidy_taxonomy = '' ; // defined according tag


	// 1.8 - class admin in separate file
	var $file_file = ''; // see in construct below
	var $file_basename = '';
	var $plugin_basename = '';
	var $plugin_url = '';
	var $plugin_path = ''; // The path to this plugin - see construct

	public function __construct( $post_tag = 'post_tag', $post_tag_post_type = 'post', $class_admin = false ) { // default values - 1.5.5

		// 1.8 - class admin in separate file
		$this->file_file = __FILE__ ; // see in construct below
		$this->file_basename = basename(__FILE__) ;
		$this->plugin_basename = plugin_basename(__FILE__) ;
		$this->plugin_url = plugins_url('', __FILE__) ;
		$this->plugin_path = plugin_dir_path(__FILE__) ;


		if ( '' != $post_tag ) $this->post_tag = $post_tag ;
		if ( '' != $post_tag_post_type ) $this->post_tag_post_type = $post_tag_post_type ;

		/* activated when first activation of plug or automatic upgrade */
		register_activation_hook( __FILE__, array( &$this,'xili_tidy_tags_activate' ) );
		register_deactivation_hook( __FILE__, array(&$this, 'remove_capabilities') ); //2.3.7

		/* get current settings - name of taxonomy - name of query-tag - 0.9.8 new taxonomy taxolangsgroup */
		$this->xili_tidy_tags_activate();

		if ( $this->xili_settings['version'] < '0.5' ) { /* updating value by default 0.9.5 */
			$this->xili_settings['version'] = '0.5';
		}
		if ( $this->xili_settings['version'] == '0.5' ) {
			$this->xili_settings['editor_caps'] = 'no_caps' ;
			$this->xili_settings['version'] = '0.6';
		}
		if ( $this->xili_settings['version'] == '0.6' ) {
			$this->xili_settings['datatable_js'] = '' ; // 1.5.3.1
			$this->xili_settings['version'] = '0.7';
		}
		update_option('xili_tidy_tags_settings', $this->xili_settings);


		if ( 'post_tag' == $this->post_tag )
			$this->tidy_taxonomy = $this->xili_settings['taxonomy'] ; // replace previous TAXOTIDYTAGS
		else
			$this->tidy_taxonomy = $this->xili_settings['taxonomy'].'_'.$this->post_tag ; // for new taxonomy

		if ( ! $class_admin ) { // 1.8.1
			if ( !defined( 'TAXOTIDYTAGS')) define( 'TAXOTIDYTAGS', $this->xili_settings['taxonomy'] ) ; // for use in widget or elsewhere 1.5.5
			//if ( !defined( 'LANGSTAGSGROUPSLUG')) define( 'LANGSTAGSGROUPSLUG', $this->xili_settings['tidylangsgroup'] );
			//if ( !defined( 'LANGSTAGSGROUPNAME')) define( 'LANGSTAGSGROUPNAME', $this->xili_settings['tidylangsgroupname'] );
		}

		/* hooks */
		add_action('wp_head', array(&$this,'head_insert_metas') );

		/* admin settings taxonomy and roles*/
		add_action( 'init', array( &$this, 'init_plugin'), 10 ); /* text domain and caps of admin*/

	}

	function xili_tidy_tags_activate() {

		$this->xili_settings = get_option( 'xili_tidy_tags_settings' );
		if ( empty( $this->xili_settings ) ) {
			$this->xili_settings = array(
			    'taxonomy'			=> 'xili_tidy_tags',
			    'tidylangsgroup'	=> 'tidy-languages-group',
			    'tidylangsgroupname' => 'All lang.',
			    'editor_caps'		=> 'no_caps',
			    'datatable_js'		=> '',
			    'version' 			=> '0.7'
		    );
			update_option('xili_tidy_tags_settings', $this->xili_settings);
		}
	}

	function init_plugin() {
		/*multilingual for admin pages and menu*/
		load_plugin_textdomain( 'xili_tidy_tags', false, 'xili-tidy-tags/languages' ); // 1.5.5

		/* add new taxonomy in available taxonomies - move here for wpmu and wp 3.0*/
		register_taxonomy( $this->tidy_taxonomy, 'term', array(
			'hierarchical' => true,
			'label'=>false,
			'rewrite' => false,
			'update_count_callback' => '',
			'show_ui' => false,
			'show_in_nav_menus' => false // WP 4.3
			) );

		$res = term_exists ( $this->xili_settings['tidylangsgroupname'] , $this->tidy_taxonomy );
		if ($res) $this->langgroupid = $res ['term_id'];

		/* since 0.9.5 new default caps for admnistrator - updated 1.4.0 */
		if ( is_admin() ) {

			$role = get_role ( 'administrator' ) ;
			if ( current_user_can ('activate_plugins') ) {

				$role->add_cap ( 'xili_tidy_admin_set' );
				$role->add_cap ( 'xili_tidy_editor_set' );
				$role->add_cap ( 'xili_tidy_editor_group' );

			} elseif ( current_user_can ( 'edit_others_pages' ) ) {
				$role = get_role ( 'editor' ) ;
				switch ( $this->xili_settings['editor_caps'] ) {
					case 'caps_grouping';
						$role->remove_cap ( 'xili_tidy_editor_set' );
						$role->add_cap ( 'xili_tidy_editor_group' );
						break;
					case 'caps_setting_grouping';
						$role->add_cap ( 'xili_tidy_editor_set' );
						$role->add_cap ( 'xili_tidy_editor_group' );
						break;
					case 'no_caps';
						$role->remove_cap ( 'xili_tidy_editor_set' );
						$role->remove_cap ( 'xili_tidy_editor_group' );
						break;
				}
			}
		}
	}

	// when desactivating - 1.8.4
	function remove_capabilities () {

		global $wp_roles;

		$wp_roles->remove_cap ( 'administrator', 'xili_tidy_admin_set' );
		$wp_roles->remove_cap ( 'administrator', 'xili_tidy_editor_set' );
		$wp_roles->remove_cap ( 'administrator', 'xili_tidy_editor_group' );

		$wp_roles->remove_cap ('editor', 'xili_tidy_editor_set'); // reset
		$wp_roles->remove_cap ('editor', 'xili_tidy_editor_group');

	}


	function head_insert_metas() {
		echo "<!-- for tag ". $this->post_tag .", website powered with xili-tidy-tags v.".XILITIDYTAGS_VER.", a WP plugin by dev.xiligroup.com -->\n";
	}

	function get_WPLANG () {
		global $wp_version;
		if ( version_compare($wp_version, '4.0', '<') ) {
			if ( defined('WPLANG') )
				return WPLANG;
			else
				return '';
		} else {
			return get_option( 'WPLANG', '' );
		}
	}

	/**
	 * for further dev.
	 *
	 */
	function xili_manage_tax_action ( $actions, $tag ) {
		return $actions;
	}

	/**
	* return array of languages assigned to a tag
	* called by filter in class instancing
	*
	*/
	function return_lang_of_tag ( $id, $tidy_taxonomy = '' ) {

		if ( $tidy_taxonomy == '' )
			$tidy_taxonomy = $this->tidy_taxonomy; // if called inside class

		$langs = array();

		if ( ! class_exists ( 'xili_language' ) )  // need xili-language
				return false;

		$listlanguages = get_terms( TAXONAME, array('hide_empty' => false ) );
		foreach ( $listlanguages as $lang) {
				if ( is_object_in_term( $id, $tidy_taxonomy, $lang->slug. '-'. $this->xili_settings['tidylangsgroup'] ) ) {
					$langs[] = $lang ;
				}
		}
		if ( $langs == array() ) {
			return false;
		} else {
			return $langs;
		}
	}


} /* end class */

/**
 * Display tidy tag cloud. (adapted form wp_tag_cloud - category-template)
 *
 * The text size is set by the 'smallest' and 'largest' arguments, which will
 * use the 'unit' argument value for the CSS text size unit. The 'format'
 * argument can be 'flat' (default), 'list', or 'array'. The flat value for the
 * 'format' argument will separate tags with spaces. The list value for the
 * 'format' argument will format the tags in a UL HTML list. The array value for
 * the 'format' argument will return in PHP array type format.
 *
 * The 'orderby' argument will accept 'name' or 'count' and defaults to 'name'.
 * The 'order' is the direction to sort, defaults to 'ASC' and can be 'DESC'.
 *
 * The 'number' argument is how many tags to return. By default, the limit will
 * be to return the top 45 tags in the tag cloud list.
 *
 * The 'topic_count_text_callback' argument is a function, which, given the count
 * of the posts  with that tag, returns a text for the tooltip of the tag link.
 *
 * The 'exclude' and 'include' arguments are used for the {@link get_tags()}
 * function. Only one should be used, because only one will be used and the
 * other ignored, if they are both set.
 *
 * @since 0.8.0
 * @updated 0.8.2, 1.2, 1.6.2
 *
 * @param array|string $args Optional. Override default arguments.
 * @return array Generated tag cloud, only if no failures and 'array' is set for the 'format' argument.
 */
function xili_tidy_tag_cloud( $args = '' ) {
	if ( is_array($args) )
		$r = &$args;
	else
		parse_str( $args, $r );

	$defaults = array(
		'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
		'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC',
		'exclude' => '', 'include' => '', 'link' => 'view', 'tagsgroup' => '', 'tagsallgroup' => '',
		'tidy_post_tag' => 'post_tag', 'echo' => true
	);
	$r = array_merge( $defaults, $r );

	extract($r); /* above changed because new args */

	$tidy_taxonomy = ( $tidy_post_tag == 'post_tag' ) ? 'xili_tidy_tags' : 'xili_tidy_tags_'.$tidy_post_tag ; // 1.6.2

	if ( ( $tagsgroup == '' && $tagsallgroup == '' ) || !function_exists('xtt_get_terms_of_groups_new' ) ) {
		// 1.6.2
		$tags = get_terms( $tidy_post_tag, array_merge( $r, array( 'orderby' => 'count', 'order' => 'DESC'  ) ) ); // Always query top tags

	} else {
		if ($tagsgroup !='') {
			$groupterm = term_exists( $tagsgroup, $tidy_taxonomy );
			$group_id[] = $groupterm['term_id'];
		}
		if ($tagsallgroup !='') {
			$groupterm = term_exists( $tagsallgroup, $tidy_taxonomy );
			$group_id[] = $groupterm['term_id'];
		}
 		$tags = null;
 		if ( taxonomy_exists ( $tidy_taxonomy ) )
			$tags = xtt_get_terms_of_groups_new ( $group_id, $tidy_taxonomy, $tidy_post_tag, array_merge( $r, array( 'orderby' => 'count', 'order' => 'DESC' ) ) );
	}

	if ( !taxonomy_exists ( $tidy_taxonomy ) || is_wp_error( $tags ) ) // error treatment 1.6.2
		{
			return;
		}

	foreach ( $tags as $key => $tag ) {
		if ( 'edit' == $r['link'] )
			$link = get_edit_tag_link( $tag->term_id, $tidy_post_tag ); // 1.5.5
		else
			$link = get_term_link( intval( $tag->term_id ), $tidy_post_tag  );
		//if ( is_wp_error( $link ) )
			//return false;

		$tags[ $key ]->link = $link;
		$tags[ $key ]->id = $tag->term_id;
	}

	$cloud = wp_generate_tag_cloud( $tags, $r ); // Here's where those top tags get sorted according to $args

	//$return = apply_filters( 'wp_tag_cloud', $return, $r );

	if ( 'array' == $format ) {
		return $cloud;
	}
	if ( $echo ) {		// 2.8.1 to improve...
 		echo $cloud;
	} else {
 		return $cloud;
	}
}

/**
 * the tags for each post in loop
 * (not in class for general use)
 *
 * @since 1.1 -
 * @same params as the default the_tags() and and array as fourth param (see [xili_] get_object_terms for details)
 *
 * @updated 1.5.5 for custom taxonomy - 'tidy_post_tag' in array for custom taxonomy
 * example : xili_the_tags('Actors: ' ,' | ', ' - ',array('sub_groups' => 'french-actors' , "tidy_post_tag" => "actors"));
 */
function xili_the_tags( $before = null, $sep = ', ', $after = '', $args = array() ) {
	if ( null === $before )
		$before = __('Tags: ');
	if ($args == array()) {
		echo get_the_tag_list($before, $sep, $after);
	} else {
		echo xili_get_the_term_list($before, $sep, $after, $args); /* no filter tag_list*/
	}
}
/**
 * get_the tag_list for each post in loop $xili_tidy_tags
 * (not in class for general use)
 *
 * @since 1.1 -
 * @same params as the default the_tags() and and array as fourth param
 * @updated 1.5.5 for custom taxonomy
 */
function xili_get_the_term_list( $before, $sep, $after, $args ) {
 	global $post;
 	$id = (int) $post->ID;

 	/* args analysis */
 	$defaults = array(
		'sub_groups' => '',
		'tidy_post_tag' => 'post_tag' // 1.5.5
	);
	$r = array_merge($defaults, $args);
	extract($r);
 	if ($sub_groups == '') {
		 $terms = get_the_terms( $id, $tidy_post_tag );
 	} else {
 		if (!is_array($sub_groups)) $sub_groups = explode(',',$sub_groups);
 		/* xili - search terms in sub groups */
 		$terms = get_object_term_cache( $id, $tidy_post_tag.implode('-',$sub_groups));
		if ( false === $terms ) {
			if ( $tidy_post_tag ==  'post_tag')
 				$terms = xtt_get_subgroup_terms_in_post ( $id, $tidy_post_tag, $sub_groups);
 			else
 				$terms = xtt_get_subgroup_terms_in_post ( $id, $tidy_post_tag, $sub_groups, TAXOTIDYTAGS.'_'.$tidy_post_tag );
		}

 	}
 	if ( is_wp_error( $terms ) )
		return $terms;

	if ( empty( $terms ) )
		return false;

	foreach ( $terms as $term ) {
		$link = get_term_link( $term, $tidy_post_tag );
		if ( is_wp_error( $link ) )
			return $link;
		$term_links[] = '<a href="' . $link . '" rel="tag">' . $term->name . '</a>';
	}

	$term_links = apply_filters( "term_links-$tidy_post_tag", $term_links );

	return $before . join( $sep, $term_links ) . $after;
}

function xtt_get_subgroup_terms_in_post ( $id, $taxonomy, $sub_groups, $tidy_taxonomy = TAXOTIDYTAGS ) {

	return xili_get_object_terms ($id, $taxonomy, array('tidy_tags_taxo'=>$tidy_taxonomy, 'sub_groups' => $sub_groups));
}

/**** Functions that improve taxinomy.php ****/

/**
 * get the terms of subgroups of the series objects
 * (not in class for general use)
 *
 * @since 1.1 -
 *
 */

function xili_get_object_terms($object_ids, $taxonomies, $args = array()) {

	global $wpdb;

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	foreach ( (array) $taxonomies as $taxonomy ) {
		if ( ! is_taxonomy($taxonomy) )
			return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));
	}

	if ( !is_array($object_ids) )
		$object_ids = array($object_ids);
	$object_ids = array_map('intval', $object_ids);

	$defaults = array('orderby' => 'name',
		'order' => 'ASC', 'fields' => 'all',
		'tidy_tags_taxo' => TAXOTIDYTAGS ,

		);
	$args = array_merge ( $defaults, $args );
	extract ($args);


	if (!is_array($sub_groups)) $sub_groups = array($sub_groups);
	foreach ($sub_groups as $tagsgroup) {
		if ($tagsgroup !='') {
			$groupterm = term_exists($tagsgroup, $tidy_tags_taxo); //echo '----'.$tagsgroup;
			$group_ids[] = $groupterm['term_id'];
		}
	}
	$group_ids = array_map('intval', $group_ids);
		$group_ids = implode(', ', $group_ids); /* the terms ID of subgroups are now in list */

	$terms = array();
	if ( count($taxonomies) > 1 ) {
		foreach ( $taxonomies as $index => $taxonomy ) {
			$t = get_taxonomy($taxonomy);
			if ( isset($t->args) && is_array($t->args) && $args != array_merge($args, $t->args) ) {
				unset($taxonomies[$index]);
				$terms = array_merge($terms, wp_get_object_terms($object_ids, $taxonomy, array_merge($args, $t->args)));
			}
		}
	} else {
		$t = get_taxonomy($taxonomies[0]);
		if ( isset($t->args) && is_array($t->args) )
			$args = array_merge($args, $t->args);
	}

	extract($args, EXTR_SKIP);

	if ( 'count' == $orderby )
		$orderby = 'tt.count';
	else if ( 'name' == $orderby )
		$orderby = 't.name';
	else if ( 'slug' == $orderby )
		$orderby = 't.slug';
	else if ( 'term_group' == $orderby )
		$orderby = 't.term_group';
	else if ( 'term_order' == $orderby )
		$orderby = 'tr.term_order';
	else if ( 'none' == $orderby ) {
		$orderby = '';
		$order = '';
	} else {
		$orderby = 't.term_id';
	}

	// tt_ids queries can only be none or tr.term_taxonomy_id
	if ( ('tt_ids' == $fields) && !empty($orderby) )
		$orderby = 'tr.term_taxonomy_id';

	if ( !empty($orderby) )
		$orderby = "ORDER BY $orderby";

	$taxonomies = "'" . implode("', '", $taxonomies) . "'";
	$object_ids = implode(', ', $object_ids);

	$select_this = '';
	if ( 'all' == $fields )
		$select_this = 't.*, tt.*';
	else if ( 'ids' == $fields )
		$select_this = 't.term_id';
	else if ( 'names' == $fields )
		$select_this = 't.name';
	else if ( 'all_with_object_id' == $fields )
		$select_this = 't.*, tt.*, tr.object_id';

	$subselect = $wpdb->prepare( "SELECT st.term_id FROM $wpdb->term_relationships AS str INNER JOIN $wpdb->term_taxonomy AS stt ON str.term_taxonomy_id = stt.term_taxonomy_id INNER JOIN $wpdb->terms AS st ON st.term_id = str.object_id INNER JOIN $wpdb->term_taxonomy AS stt2 ON stt2.term_id = str.object_id WHERE stt.taxonomy IN ('".$tidy_tags_taxo."') AND stt2.taxonomy = ".$taxonomies." AND stt.term_id IN (".$group_ids.") ", 'd' );
	// dummy param added 3.5
	$query = "SELECT $select_this FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tr.object_id IN ($object_ids) AND t.term_id IN ($subselect) $orderby $order"; //echo $query;

	if ( 'all' == $fields || 'all_with_object_id' == $fields ) {
		$terms = array_merge( $terms, $wpdb->get_results( $wpdb->prepare( $query, 'd' ) ) );
		update_term_cache($terms);
	} else if ( 'ids' == $fields || 'names' == $fields ) {
		$terms = array_merge( $terms, $wpdb->get_col( $wpdb->prepare( $query, 'd' ) ) );
	} else if ( 'tt_ids' == $fields ) {
		$terms = $wpdb->get_col( $wpdb->prepare( "SELECT tr.term_taxonomy_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tr.object_id IN ($object_ids) AND tt.taxonomy IN ($taxonomies) $orderby $order", 'd' ) );
	}

	if ( ! $terms )
		$terms = array();

	return $terms;
}

/**
 * _deprecated_function since 1.8.0
 *
 */

function get_terms_of_groups_new ( $group_ids, $taxonomy, $taxonomy_child, $order = '', $not = false, $uncheckedtags = false ) {
	_deprecated_function( __FUNCTION__, 3.4, 'xtt_get_terms_of_groups_new()' );
	return xtt_get_terms_of_groups_new ( $group_ids, $taxonomy, $taxonomy_child, $order, $not, $uncheckedtags );

}


/**
 *
 * @updated 1.8.0
 */
function xtt_get_terms_of_groups_new ( $group_ids, $taxonomy, $taxonomy_child, $order = '', $not = false, $uncheckedtags = false ) {
		global $wpdb;
		if ( !is_array($group_ids) )
			$group_ids = array($group_ids);
		$group_ids = array_map('intval', $group_ids);
		$group_ids = implode(', ', $group_ids);
		$theorderby = '';
		$where = '';
		$defaults = array('orderby' => 'term_order', 'order' => 'ASC',
		'hide_empty' => true, 'exclude' => '', 'exclude_tree' => '', 'include' => '',
		'number' => '', 'slug' => '', 'parent' => '',
		'name__like' => '', 'hierarchical' => false,
		'pad_counts' => false, 'offset' => '', 'search' => '');

		if (is_array($order)) { // for back compatibility
			$r = &$order;
			$r = array_merge($defaults, $r);
			extract($r);

			if ($order == 'ASC' || $order == 'DESC') {
				if ('term_order'== $orderby) {
					$theorderby = ' ORDER BY tr.'.$orderby.' '.$order ;
				} elseif ('count'== $orderby || 'parent'== $orderby) {
					$theorderby = ' ORDER BY tt2.'.$orderby.' '.$order ;
				} elseif ('term_id'== $orderby || 'name'== $orderby) {
					$theorderby = ' ORDER BY t.'.$orderby.' '.$order ;
				}
			}

			if ( !empty($name__like) )
			$where .= " AND t.name LIKE '{$name__like}%'";

			if ( '' != $parent ) {
				$parent = (int) $parent;
				$where .= " AND tt2.parent = '$parent'";
			}

			if ( $hide_empty && !$hierarchical )
				$where .= ' AND tt2.count > 0';
			// don't limit the query results when we have to descend the family tree
			if ( ! empty($number) && '' == $parent ) {
				if( $offset )
					$limit = ' LIMIT ' . $offset . ',' . $number;
				else
					$limit = ' LIMIT ' . $number;

			} else {
				$limit = '';
			}

			if ( !empty($search) ) {
				$search = like_escape($search);
				$where .= " AND (t.name LIKE '%$search%')";
			}

			$groupby = " GROUP BY t.term_id ";

		} else { // for back compatibility
			if ($order == 'ASC' || $order == 'DESC') $theorderby = ' ORDER BY tr.term_order '.$order ;
		}


		if ( $not === false ) {
		$query = "SELECT t.*, tt2.term_taxonomy_id, tt2.description,tt2.parent, tt2.count, tt2.taxonomy, tr.term_order FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->terms AS t ON t.term_id = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt2 ON tt2.term_id = tr.object_id WHERE tt.taxonomy IN ('".$taxonomy."') AND tt2.taxonomy = '".$taxonomy_child."' AND tt.term_id IN (".$group_ids.") ".$where.$groupby.$theorderby.$limit;
		} else {
			if ( $uncheckedtags ) { // current query + not in
		 		$query = "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('".$taxonomy_child."') AND (t.term_ID) NOT IN ("."SELECT t.term_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->terms AS t ON t.term_id = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt2 ON tt2.term_id = tr.object_id WHERE tt.taxonomy IN ('".$taxonomy."') AND tt2.taxonomy = '".$taxonomy_child."' AND tt.term_id IN (".$group_ids.") ".") ".$where.$groupby.$theorderby.$limit;
			} else {
				$query = "SELECT DISTINCT t.*, tt2.term_taxonomy_id, tt2.description,tt2.parent, tt2.count, tt2.taxonomy, tr.term_order FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->terms AS t ON t.term_id = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt2 ON tt2.term_id = tr.object_id WHERE tt.taxonomy IN ('".$taxonomy."') AND tt2.taxonomy = '".$taxonomy_child."' AND (t.term_ID) NOT IN ("."SELECT t.term_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->terms AS t ON t.term_id = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt2 ON tt2.term_id = tr.object_id WHERE tt.taxonomy IN ('".$taxonomy."') AND tt2.taxonomy = '".$taxonomy_child."' AND tt.term_id IN (".$group_ids.") ".") ".$where.$groupby.$theorderby.$limit;
			}
		}
		//echo $query;
		$listterms = $wpdb->get_results( $query  ); // pb with wpdb->prepare echo $query ;
		if ( ! $listterms )
			return array();

		return $listterms;
	}


/**
 * Create HTML check row (select) content for Tidy Tag Group List.
 *
 * @package xili-tidy-tags
 * @since 1.3.0
 * @uses Walker
 */
class Walker_TagGroupList_row extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 1.3.0
	 * @var string
	 */
	var $tree_type = 'tidytaggroup';

	/**
	 * @see Walker::$db_fields
	 * @since 1.3.0
	 * @todo Decouple this
	 * @var array
	 */
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	/**
	 * @see Walker::start_el()
	 * @since 1.3.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $term term data object.
	 * @param int $depth Depth of category. Used for padding.
	 */
	function start_el(&$output, $term, $depth = 0, $args = array(), $current_object_id = 0 ) {
		/*$pad = str_repeat('&nbsp;', $depth * 3);*/
		if ($depth > 0) {
			$pad = str_repeat('– ', $depth);
			$term_name = $term->name;
		} else {
			$pad = '';
			$term_name = '<strong>'.$term->name.'</strong>';
		}
		// fixes 1.7
		$output .= '<input type="checkbox" id="line-%1$s-'.$term->term_id.'" name="line-%1$s-'.$term->term_id.'" value="'.$term->term_id.'" "checked'.$term->term_id.'" />'.$pad.$term_name.'&nbsp;&nbsp;';
	}
	/**
	 * @see Walker::end_lvl()
	 * @since 1.3.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of category. Used for tab indentation.
	 */
	function end_lvl(&$output, $depth = 0, $args = Array()  ) {
		$output .= "<br />";
	}
}

/**
 * Retrieve HTML check row (select) content for Tag Group List.
 *
 * @uses Walker_TagGroupList_row to create HTML  content line.
 * @since 1.3.0
 * @see Walker_TagGroupList_row::walk() for parameters and return description.
 */
function walk_TagGroupList_tree_row() {
	$args = func_get_args();
	// the user's options are the third parameter
	if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') ) {
		$walker = new Walker_TagGroupList_row;
	} else {
		$walker = $args[2]['walker'];
	}
	return call_user_func_array(array( &$walker, 'walk' ), $args );
}

/**
 * Create Sorted array of Tags from Group List.
 *
 * @since 1.3.0
 *
 */
class Walker_TagGroupList_sorted extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 1.3.0
	 * @var string
	 */
	var $tree_type = 'tidytaggroup';

	/**
	 * @see Walker::$db_fields
	 * @since 1.3.0
	 * @todo Decouple this
	 * @var array
	 */
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id' );

	/**
	 * @see Walker::start_el()
	 * @since 1.3.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $term term data object.
	 * @param int $depth Depth of category. Used for padding.
	 */
	function start_el( &$output, $term, $depth = 0, $args = array(), $current_object_id = 0 ) {
		/*$pad = str_repeat('&nbsp;', $depth * 3);*/
		$output[] = $term;
	}
}
/**
 * Retrieve Sorted array of Tags from Group List.
 *
 * @uses Walker_TagGroupList_sorted to sort.
 * @since 1.3.0
 * @see Walker_TagGroupList_sorted::walk() for parameters and return description.
 */
function walk_TagGroupList_sorted() {
	$args = func_get_args();
	// the user's options are the third parameter
	if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') ) {
		$walker = new Walker_TagGroupList_sorted;
	} else {
		$walker = $args[2]['walker'];
	}
	return call_user_func_array(array( &$walker, 'walk' ), $args );
}

/**
 * class for multiple tidy tags cloud widgets
 * @since 1.3.3
 * @updated 1.5.0 rewritten as extends
 * @updated 1.5.5 able to display custom taxonomies
 * @updated 1.6.5 multisite
 */

class xili_tidy_tags_cloud_multiple_widgets extends WP_Widget {

	function xili_tidy_tags_cloud_multiple_widgets() {
		load_plugin_textdomain('xili_tidy_tags', false, 'xili-tidy-tags/languages' );
		$widget_ops = array('classname' => 'xili_tdtc_widget', 'description' => __( "Cloud of grouped tags by xili-tidy-tags plugin",'xili_tidy_tags' ).' - v.'.XILITIDYTAGS_VER );
		parent::__construct('xili_tidy_tags_cloud_widget', '[©xili] ' .__("Tidy tags cloud", 'xili_tidy_tags'), $widget_ops);
		$this->alt_option_name = 'xili_tidy_tags_cloud_widgets_options';
	}

	function widget( $args, $instance ) {

		extract($args, EXTR_SKIP);

		$thecondition = trim( $instance['thecondition'],'!' ) ;

		if ( '' != $instance['thecondition'] && function_exists( $thecondition ) ) {
			$not = ( $thecondition == $instance['thecondition'] ) ? false : true ;
			$arr_params = ('' != $instance['theparams']) ? array(explode( ',', $instance['theparams'] )) : array();
 			$condition_ok = ($not) ? !call_user_func_array ( $thecondition, $arr_params ) : call_user_func_array ( $thecondition, $arr_params );
		} else {
 			$condition_ok = true;
 		}
		if ( $condition_ok ) {
			$title = apply_filters( 'widget_title', $instance['title'] );
			echo $before_widget.$before_title.$title.$after_title;

			$cloudsargs = array();

			if ('the_curlang' == $instance['tagsgroup'] && class_exists( 'xili_language' ) ) { // if xl temporary desactivate
				$cloudsargs[] = 'tagsgroup='.the_curlang();
			} elseif ('the_category' == $instance['tagsgroup'])  {
				$cloudsargs[] = 'tagsgroup='.single_cat_title('',false);
			} else {
				$cloudsargs[] = 'tagsgroup='.$instance['tagsgroup'];
			}
			$cloudsargs[] = 'tagsallgroup='.$instance['tagsallgroup'];

			if ( abs( (int) $instance['smallest'] ) > 0 ) $cloudsargs[] = 'smallest='.abs((int) $instance['smallest']);
			if ( abs( (int) $instance['largest'] ) > 0  ) $cloudsargs[] = 'largest='.abs((int) $instance['largest']);
			if ( abs( (int) $instance['quantity'] ) > 0 ) $cloudsargs[] = 'number='.abs((int) $instance['quantity']); // fixe number

			if ('no' != $instance['orderby'] ) $cloudsargs[] = 'orderby='.$instance['orderby'];
			if ('no' != $instance['order'] ) $cloudsargs[] = 'order='.$instance['order'];

			$cloudsargs[] = 'format='.$instance['displayas'];

			// 'tidy_taxonomy' => 'xili_tidy_tags', 'tidy_post_tag' => 'post_tag' - by default -
			// $cloudsargs[] = 'tidy_taxonomy='.$instance['tidy_taxonomy']; // set in cloud 1.6.2
			$cloudsargs[] = ( $instance['tidy_taxonomy'] == 'xili_tidy_tags' ) ? 'tidy_post_tag=post_tag' : 'tidy_post_tag='.str_replace ( TAXOTIDYTAGS .'_', '', $instance['tidy_taxonomy']) ;


			echo '<div class="xilitidytagscloud">';

			if ( is_multisite() ) { // 1.7 - only for current clouds
				global $blog_id ;
				$targetsite = (isset ( $instance['targetsite'] ) &&  $instance['targetsite'] != 0 ) ? $instance['targetsite'] : $blog_id ;
				$targetsite = (int)$targetsite;
				$switch_to = ( $blog_id  !=  $targetsite ) ? true : false ; // if other
			} else {
				$switch_to = false;
			}

			if ( $switch_to ) {
				switch_to_blog( $targetsite );
			}

			xili_tidy_tag_cloud( implode ( '&', $cloudsargs ) );

			if ( $switch_to ) {
				restore_current_blog();
			}
			echo '</div>';
			echo $after_widget;
		}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);

		$instance['tagsgroup'] = strip_tags(stripslashes($new_instance['tagsgroup']));
		$instance['tagsallgroup'] = strip_tags(stripslashes($new_instance['tagsallgroup']));
		$instance['smallest'] = strip_tags(stripslashes($new_instance['smallest']));
		$instance['largest'] = strip_tags(stripslashes($new_instance['largest']));
		$instance['quantity'] = strip_tags(stripslashes($new_instance['quantity']));
		$instance['orderby'] = strip_tags(stripslashes($new_instance['orderby']));
		$instance['order'] = strip_tags(stripslashes($new_instance['order']));
		$instance['displayas'] = strip_tags(stripslashes($new_instance['displayas']));
		$instance['tidy_taxonomy'] = strip_tags($new_instance['tidy_taxonomy']);

		$instance['thecondition'] = strip_tags(stripslashes($new_instance['thecondition'])); // 1.6.0
		$instance['theparams'] = strip_tags(stripslashes($new_instance['theparams']));

		if ( is_multisite() ) {
			$instance['targetsite'] = strip_tags(stripslashes($new_instance['targetsite']));
		}
		return $instance;
	}

	function form( $instance ) {

		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$tagsgroup = isset($instance['tagsgroup']) ? esc_attr($instance['tagsgroup']) : '' ;
		$tagsallgroup = isset($instance['tagsallgroup']) ? esc_attr($instance['tagsallgroup']) : '';
		$smallest = isset($instance['smallest']) ? esc_attr($instance['smallest']): '';
		$largest = isset($instance['largest']) ? esc_attr($instance['largest']) : '';
		$quantity = isset($instance['quantity']) ? esc_attr($instance['quantity']): '';
		$orderby = isset($instance['orderby']) ? $instance['orderby']: '';
		$order = isset($instance['order']) ? $instance['order']: '';
		$displayas = isset($instance['displayas']) ? $instance['displayas']: '';
		$tidy_taxonomy = isset($instance['tidy_taxonomy']) ? $instance['tidy_taxonomy'] : 'xili_tidy_tags';

		$thecondition =  isset($instance['thecondition']) ? stripslashes($instance['thecondition']) : '' ;
 		$theparams =  isset($instance['theparams']) ? stripslashes($instance['theparams']) : '' ;

 		if ( is_multisite() ) {
			$targetsite = isset($instance['targetsite']) ? $instance['targetsite']: '';
		}

		$listtagsgroup = get_terms( $tidy_taxonomy, array('hide_empty' => false));
		$listtagsgroupssorted = walk_TagGroupList_sorted( $listtagsgroup, 3, null, null );

		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<label for="<?php echo $this->get_field_id('tagsgroup'); ?>" ><?php _e('Group','xili_tidy_tags') ?> : </label><br />
		<select name="<?php echo $this->get_field_name('tagsgroup'); ?>" id="<?php echo $this->get_field_id('tagsgroup'); ?>" style="width:90%;">
		<option value="" ><?php _e('Choose a group…','xili_tidy_tags'); ?></option>
		<?php /* group named as current language */
		if (class_exists('xili_language') ) { ?>
			<option value="the_curlang" <?php selected( $tagsgroup, 'the_curlang' ); ?> ><?php _e('Current language','xili_tidy_tags');  ?></option>
		<?php }
		/* group named as current category */ ?>

		<option value="the_category" <?php selected( $tagsgroup, 'the_category' ); ?> ><?php _e('Current category','xili_tidy_tags');  ?></option>
		<?php
		if ( $listtagsgroupssorted ) {
			foreach ($listtagsgroupssorted as $curterm) {
				$ttab = ($curterm->parent == 0) ? '' : '– ' ;
				$selected = selected( $tagsgroup, $curterm->slug, false ) ;
				echo '<option value="'.$curterm->slug.'" '. $selected . ' >'.$ttab.$curterm->name.'</option>';

			}
		}
		?>
		</select>

		<br />
		<label for="<?php echo $this->get_field_id('tagsallgroup'); ?>" ><?php _e('Group #2','xili_tidy_tags') ?> : </label><br />

		<select name="<?php echo $this->get_field_name('tagsallgroup'); ?>" id="<?php echo $this->get_field_id('tagsallgroup'); ?>" style="width:90%;">
		<option value="" ><?php _e('(Option) Choose a 2nd group…','xili_tidy_tags'); ?></option>

		<?php
		if ( $listtagsgroupssorted ) {
			foreach ($listtagsgroupssorted as $curterm) {
				$ttab = ($curterm->parent == 0) ? '' : '– ' ;
				$selected = selected( $tagsallgroup, $curterm->slug, false ) ;
				echo '<option value="'.$curterm->slug.'" '. $selected .' >'.$ttab.$curterm->name.'</option>';

			}
		}?>
		</select>

		<br />
		<label for="<?php echo $this->get_field_id('smallest'); ?>" ><?php _e('Smallest size','xili_tidy_tags') ?> : <input id="<?php echo $this->get_field_id('smallest'); ?>" name="<?php echo $this->get_field_name('smallest'); ?>" type="text" value="<?php echo $smallest ?>" /></label><br />
		<label for="<?php echo $this->get_field_id('largest'); ?>" ><?php _e('Largest size','xili_tidy_tags') ?> : <input id="<?php echo $this->get_field_id('largest'); ?>" name="<?php echo $this->get_field_name('largest'); ?>" type="text" value="<?php echo $largest ?>" /></label><br />
		<label for="<?php echo $this->get_field_id('quantity'); ?>" ><?php _e('Number','xili_tidy_tags') ?> : <input id="<?php echo $this->get_field_id('quantity'); ?>" name="<?php echo $this->get_field_name('quantity'); ?>" type="text" value="<?php echo $quantity ?>" /></label>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('Order and sorting infos','xili_tidy_tags') ?></legend>
		<select name="<?php echo $this->get_field_name('orderby'); ?>" id="<?php echo $this->get_field_id('orderby'); ?>" style="width:100%;"> 		<?php
		echo '<option value="no" >'.__('no orderby','xili_tidy_tags').'</option>';
		echo '<option value="count" '. selected( $orderby, 'count', false ) .' >'.__('count','xili_tidy_tags').'</option>';
		echo '<option value="name" '. selected( $orderby, 'name', false ) .' >'.__('name','xili_tidy_tags').'</option>'; ?>
		</select>
		<select name="<?php echo $this->get_field_name('order'); ?>" id="<?php echo $this->get_field_id('order'); ?>" style="width:100%;">
		<?php
		echo '<option value="no" >'.__('no order','xili_tidy_tags').'</option>';
		echo '<option value="ASC" '. selected( $order, 'ASC', false ) .' >'.__('ASC','xili_tidy_tags').'</option>';
		echo '<option value="DESC" '. selected( $order, 'DESC', false ) .' >'.__('DESC','xili_tidy_tags').'</option>';
		?>
		</select>
		</fieldset>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('Display as','xili_tidy_tags') ?></legend>
		<select name="<?php echo $this->get_field_name('displayas'); ?>" id="<?php echo $this->get_field_id('displayas'); ?>" style="width:100%;"> <?php
		echo '<option value="flat" '. selected( $displayas, 'flat', false ) .' >'.__('Cloud','xili_tidy_tags').'</option>';
		echo '<option value="list" '. selected( $displayas, 'list', false ) .' >'.__('List','xili_tidy_tags').'</option></select>';
		?>
		<br /></fieldset>

		<?php if ( is_multisite() ) { // 1.6.5
			$all_blogs = get_blogs_of_user( get_current_user_id() );

			if ( count( $all_blogs ) > 1 ) { ?>
				<label for="<?php echo $this->get_field_id('targetsite'); ?>" ><?php _e('Target site ID','xili_tidy_tags') ?> :
				<?php
				$echodis = "" ; //( $disabled == true ) ? 'disabled="disabled"' : '' ;
				echo '<select id="'.$this->get_field_id('targetsite').'" name="'.$this->get_field_name('targetsite').'" '.$echodis.' class="widefat" ><option value=0 '. selected( $targetsite,  0, false ).' >'.__('Choose site...', 'xili_tidy_tags').'</option>';
				foreach( (array) $all_blogs as $blog ) {
						$wplang = ( '' != get_blog_option ($blog->userblog_id, 'WPLANG') ) ? get_blog_option ($blog->userblog_id, 'WPLANG') : __('undefined', 'xili_tidy_tags') ;	 // to adapt if xlms ready
						?>
						<option value="<?php echo $blog->userblog_id ?>" <?php selected( $targetsite,  $blog->userblog_id ); ?> ><?php echo esc_url( get_home_url( $blog->userblog_id ) ).' ('.$blog->userblog_id.') - WPLANG = '.$wplang ; ?></option>
						<?php
					} ?>
				</select>
				</label>

			<?php } else { ?>
				<input id="<?php echo $this->get_field_id('targetsite'); ?>" name="<?php echo $this->get_field_name('targetsite'); ?>" type="hidden" value="<?php echo $targetsite ?>" />
				<?php
				echo '<span style="color:red">'.__('No site assigned to current admin user ! Please verify user\'s list for targeted sites.','xili_tidy_tags').'</span>';
			}

			?>
		<br />
		<?php } ?>

		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('Taxonomies','xili_tidy_tags') ?></legend>
		<?php
		$taxos_list = get_object_taxonomies ('term');
		?>
		<label for="<?php echo $this->get_field_id('tidy_taxonomy'); ?>" ><?php _e('tidy taxonomy','xili_tidy_tags') ?> : </label><br />
		<select name="<?php echo $this->get_field_name('tidy_taxonomy'); ?>" id="<?php echo $this->get_field_id('tidy_taxonomy'); ?>" style="width:90%;">
		<?php
		foreach ( $taxos_list as $curterm ) {
			if ( !in_array( $curterm, array ( 'languages_group', 'xl-dictionary-langs' ) ) ) {
				$selected = selected ($tidy_taxonomy, $curterm, false );
				echo '<option value="'.$curterm.'" '. $selected . ' >'. $curterm .'</option>';
			}
		} ?>
		</select>

		</fieldset>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;" >
			<label for="<?php echo $this->get_field_id('thecondition'); ?>"><?php _e('Condition','xili_tidy_tags'); ?></label>:
			<input class="widefat" id="<?php echo $this->get_field_id('thecondition'); ?>" name="<?php echo $this->get_field_name('thecondition'); ?>" type="text" value="<?php echo $thecondition; ?>" />
			( <input id="<?php echo $this->get_field_id('theparams'); ?>" name="<?php echo $this->get_field_name('theparams'); ?>" type="text" value="<?php echo $theparams; ?>" /> )
		</fieldset>
		<p><small><?php echo '© xili-tidy-tags v.'.XILITIDYTAGS_VER ; ?></small></p>
	<?php
	}
}

/**
 * Shortcode to insert a cloud of a group of tags inside a post.
 *
 * Example of shortcode : [xili-tidy-tags params="tagsgroup=trademark&largest=10&smallest=10" glue=" | "]
 *
 * @since 1.0
 *
 * @updated 1.5.5
 *
 *	[xili-tidy-tags params="tagsgroup=french-actors&tidy_taxonomy=xili_tidy_tags_actors&tidy_post_tag=actors&largest=10&smallest=10" glue=" | " emptyresult="vide"]
 *
 */
function xili_tidy_tags_shortcode ($atts) {
	$arr_result = shortcode_atts(array('params'=>'', 'glue'=> ' ', 'emptyresult'=> ' '), $atts);
	extract($arr_result);
	$tags = xili_tidy_tag_cloud(html_entity_decode($params)."&format=array");
	if ($tags)
		return implode($glue, $tags );
	else
		return $emptyresult;
}
add_shortcode('xili-tidy-tags', 'xili_tidy_tags_shortcode');


/**
 * template tag to insert a dropdown (instead cloud) of a group of tags inside a theme.
 *
 * Example without jquery
 * Use echo and add your JS script if running and if jQuery is enable (use wp_head filter if not done by another plugin) to redirect to link of selected tag without a form
 *
 * @since 1.8.6
 *
 * @updated 1.10.1
 *
 * xili_tidy_tags_dropdown();
 *
 * use (color is slug of a group) : echo xili_tidy_tags_dropdown( array ( 'tagsgroup' => 'color' ) ); or
 * use : xili_tidy_tags_dropdown( array ( 'tagsgroup' => 'color', echo => true ) );
 *
 * param as in xili_tidy_tag_cloud() template tag
 * plus
 * id_dropdown to set ID of <select> - by default 'xtt_cloud_drop' (will be used by js and jQuery) - define a unique if more than one dropdown
 *
 */
function xili_tidy_tags_dropdown ( $args = array() ) {

	if ( is_array($args) )
		$r = &$args;
	else
		parse_str( $args, $r );

	$defaults = array(
		'format' => 'array', 'orderby' => 'name', 'order' => 'ASC',
		'exclude' => '', 'include' => '', 'link' => 'view', 'tagsgroup' => '', 'tagsallgroup' => '',
		'tidy_post_tag' => 'post_tag', 'echo' => true, 'id_dropdown' => 'xtt_cloud_drop',
	);
	$r = array_merge( $defaults, $r );
	$echo = $r['echo'];
	$r['echo'] = false;
	$list = xili_tidy_tag_cloud($r);

	if ( is_array($list) && $list != array() ) {
		$dropdown = '<select id='.$r['id_dropdown'].'>';
		$dropdown .= '<option value="#">'. sprintf(__('Select %s') , __('Tag')) .'</option>'; //uses admin translation
		foreach ( $list as $one_line )  {

			$preg = preg_match('(^<(.*)href=\'(.*)\' class=(.*)>(.*)</a>$)', $one_line, $matches) ;
			// extract title
			$one_line_title =  $preg ? $matches[4] : '??';
			// extract link
			$one_line_link = $preg ? $matches[2] : '#';

			$dropdown .= '<option value="'.$one_line_link. '" ' . selected( is_tag( $one_line_title ),  true, false ) . '>'.$one_line_title.'</option>';

		}
		$dropdown .= '</select>';

		if ( $echo ) {
			echo $dropdown;
			?>
			<script type='text/javascript'>
	/* <![CDATA[ */
		var dropdown = document.getElementById("<?php echo $r['id_dropdown']; ?>");
		function onTagChange() {
			if ( dropdown.options[dropdown.selectedIndex].value != '' ) {
				location.href = dropdown.options[dropdown.selectedIndex].value;
			}
		}
		dropdown.onchange = onTagChange;
	/* ]]> */
	</script>
			<?php


		} else {
			return $dropdown;
		}
	}
}

/**
 * template tag to insert a link to other tags of group in another language inside a theme.
 *
 *
 * @since 1.8.7
 *
 * @updated
 *
 * xili_tidy_tags_group_links();
 *
 * params:
 *
 * args: see defaults array below
 *
 */
function xili_tidy_tags_group_links ( $args = array() ) {
	global $wpdb;
	if ( is_array($args) )
		$r = &$args;
	else
		parse_str( $args, $r );

	$defaults = array(
		'term_id' => null, // null = get current tag of current_query
		'format' => 'list',  // array
		'separator' => ' ',
		'orderby' => 'name',
		'order' => 'ASC',
		'link' => 'view',
		'lang' => '', // '%1$s [%2$s]', // if '' no display
		'lang_separator' => '',
		'tidy_post_tag' => 'post_tag',
		'echo' => false
	);
	$r = array_merge( $defaults, $r );


	if ( empty ( $r['term_id'] ) ) {

		$term = get_queried_object();
		if ( !$term ) {
			$term_id = 0;
		} else {
			$term_id = $term->term_id;
		}

	} else {
		$term_id = $r['term_id'];
	}

	if ( $r['format'] == 'array' )
			$r['echo'] = false ;

	$tag = get_term( (int) $term_id, $r['tidy_post_tag'], OBJECT, 'edit' );
	$the_linklist = '';
	if ( isset ($tag->term_group) && $tag->term_group > 0 ) {

		$alias_group = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->terms WHERE term_group = %s", $tag->term_group) );

		if ( $alias_group ) {

			$linklist = array();

			foreach ( $alias_group as $one_alias ) {
				if ( $one_alias->slug != $tag->slug ) {

					if ( $r['format'] = 'list' ) {

						if ( $r['link'] == 'view' ) {
							if ( $r['lang'] == '' ) {
								$linklist[] = '<a href="'. get_term_link( $one_alias->slug, $r['tidy_post_tag'] ) .'">' . $one_alias->name . '</a>' ;
							} else {

								$param2 = ( $r['tidy_post_tag'] == 'post_tag' ) ? 'xili_tidy_tags' : 'xili_tidy_tags_' . $r['tidy_post_tag'] ; // to be used with another type of tag

								$langs = apply_filters ( 'xtt_return_lang_of_tag_'.$r['tidy_post_tag'], $one_alias->term_id, $param2 ) ;

								$alias_lang = "";

								if ( $langs ) {

									$langs_name = array();
							 		foreach ( $langs as $one_lang ) {
							 			$langs_name[] = $one_lang->name;
							 		}
							 		$alias_lang = implode ( $r['lang_separator'], $langs_name );
								}

								$item = sprintf (  $r['lang']  , $one_alias->name, $alias_lang );

								$linklist[] = '<a href="'. get_term_link( $one_alias->slug, $r['tidy_post_tag'] ) .'">' . $item  . '</a>' ;
							}
						} else {

							$linklist[] = $one_alias->name ;
						}

					} else if ( $r['format'] == 'array') {

						$linklist[] = $one_alias->name ;

					} else {
						$linklist[] = $one_alias->name ;
					}

				}
			}
		}

		if ( $r['format'] == 'list' ) {
			$the_linklist = implode ( $r['separator'], $linklist );
		}

	}

	if ( $r['echo'] ) {
		echo $the_linklist;
	} else {
		return $the_linklist;
	}
}

/**
 * If tags are grouped (via alias_of), return the tag in another language (various format)
 * @since 1.8.7
 *
 * return null if no group or no lang
 *
 */
function xili_tidy_tag_in_other_lang ( $args = array() ) {
	global $wpdb;
	if ( is_array($args) )
		$r = &$args;
	else
		parse_str( $args, $r );

	$defaults = array(
		'term_id' => null, // null = get current tag of current_query
		'format' => OBJECT,
		'lang' => 'en_US',
		'tidy_post_tag' => 'post_tag'

	);
	$r = array_merge( $defaults, $r );


	if ( empty ( $r['term_id'] ) ) {

		$term = get_queried_object();
		if ( !$term ) {
			$term_id = 0;
		} else {
			$term_id = $term->term_id;
		}

	} else {
		$term_id = $r['term_id'];
	}

	if ( $r['format'] == 'array' )
			$r['echo'] = false ;

	$tag = get_term( (int) $term_id, $r['tidy_post_tag'], OBJECT, 'edit' );

	if ( isset ($tag->term_group)  && $tag->term_group > 0 ) {

		$alias_group = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->terms WHERE term_group = %s", $tag->term_group) );

		if ( $alias_group ) {

			foreach ( $alias_group as $one_alias ) {
				if ( $one_alias->slug != $tag->slug ) {

					// search lang

					$param2 = ( $r['tidy_post_tag'] == 'post_tag' ) ? 'xili_tidy_tags' : 'xili_tidy_tags_' . $r['tidy_post_tag'] ; // to be used with another type of tag
					$langs = apply_filters ( 'xtt_return_lang_of_tag_'.$r['tidy_post_tag'], $one_alias->term_id, $param2 ) ;

					if ( $langs ) {
						foreach ( $langs as $one_lang ) {

							if ( $one_lang->name == $r['lang']) {
								if ( $r['format'] == OBJECT ) {
									return $one_alias;
								} elseif ( $r['format'] == ARRAY_A ) {
									$__term = get_object_vars($one_alias);
									return $__term;
								} elseif ( $r['format'] == ARRAY_N ) {
									$__term = array_values(get_object_vars($one_alias));
									return $__term;
								} elseif ( $r['format'] == 'term_link' ) {
									return get_term_link( $one_alias->slug, $r['tidy_post_tag'] ) ;
								} elseif ( $r['format'] == 'term_slug' ) {
									return $one_alias->slug ;
								} elseif ( $r['format'] == 'term_name' ) {
									return $one_alias->slug ;
								} else {
									return $one_alias;
								}
							}
						}
					}

				}
			}
		}
	}

	return null;
}

/**
 * instantiation of xili_tidy_tags class
 *
 * @since 1.6 = ready for custom taxonomy with param !
 * @updated 1.8.0
 */
function xili_tidy_tags_start () {
	global $xili_tidy_tags;

	$xili_tidy_tags = new xili_tidy_tags (); // no params by default for post_tag

	add_filter( 'xtt_return_lang_of_tag_post_tag', array(&$xili_tidy_tags, 'return_lang_of_tag' ) , 10, 2 ); // to be adapted if another instancing

	/**
	 *
	 * class admin in separated file
	 *
	 */
	if ( is_admin() ) {
		$plugin_path = dirname(__FILE__) ;
		require( $plugin_path . '/xili-includes/xtt-class-admin.php' );
		$xili_tidy_tags_admin = new xili_tidy_tags_admin( $xili_tidy_tags );
	}
}
add_action( 'plugins_loaded', 'xili_tidy_tags_start', 15 ); // after xili_language (13) - before xili_dictionary (20)


/**
 *
 * widgets
 *
 */
function add_xtt_widgets() {
 	register_widget('xili_tidy_tags_cloud_multiple_widgets'); // since 1.5.0
}
	// comment below lines if you don't use widget(s)
add_action( 'widgets_init', 'add_xtt_widgets' );


/********* EXAMPLES *************/


/**
 * example of selection of tags of a group as used in xili-tidy-tags dashboard
 * only for tests
 * @since 1.4.2
 *
 * @updated 1.5.5
 *
 * @params
 */
 function xili_tags_from_group( $group_name, $mode = 'slug', $taxonomy = 'xili_tidy_tags', $taxonomy_child = 'post_tag' ) {
	// from $group_name to ID

	$groupterm = term_exists($group_name, $taxonomy);
	$group_id  = $groupterm['term_id'];
	// return array of tags as object
	$args = array( 'orderby' => 'name', 'order' => 'ASC', 'hide_empty' => false ); // even if no post attached to a tag - see doc inside source of xili-tidy-tags
	$thetags = xtt_get_terms_of_groups_new ( $group_id, $taxonomy, $taxonomy_child, $args );
	// example of array as expected by S.Y. but here with key -
	$result_array = array();
	if ( $thetags ) {
		foreach ( $thetags as $onetag ) {
			if ( $mode == 'array' ) {
				$result_array[] = array('tag_name' => $onetag->name, 'tag_id' => $onetag->term_id);
			} else { // slug for link or $query
				$result_array[] = $onetag->slug ;
			}

		}

	return $result_array ;

	}

}

/**
 *  return the link to show posts of a xili_tags_group
 *	can be used in template - used in tags group cloud
 *  example : echo '<a href="'.link_for_posts_of_xili_tags_group ('trademark').'" >Trademark</a>'
 *
 * @param: slug of target tags-group
 * @since 1.5.4
 */
 function link_for_posts_of_xili_tags_group ( $tags_group ) {
 	if ( $tags_group != "" ) {
		$thetags = xili_tags_from_group( $tags_group ) ;
		if ( $thetags ) {
			$list = implode ( ',', $thetags );
			return get_bloginfo( 'siteurl' ).'?tag='.$list;
		}
 	}
 }

/**
 * get tags-group as list with link to show Posts with tags belonging to each tags-group
 *
 * examples :
 * echo xili_tags_group_list (); // by default show only non languages group
 * echo xili_tags_group_list ( ', ', array ('tidy-languages-group','software') ); // show all tags group excluding langs and 'software'
 * echo xili_tags_group_list ( ', ', array ('tidy-languages-group') , 'Posts with tags belonging to %s tags-group') ;
 *
 * @param: $separator in list
 * @param: array of excluded slugs - 'tidy-languages-group' is for languages groups
 * @param: title format as in sprintf - %s = tagsgroup name
 * @param: tidy_taxonomy
 *
 * @since 1.5.4
 *
 * @updated 1.5.5
 *
 *
 */
 function xili_tags_group_list ( $separator = ', ', $exclude = array ( 'tidy-languages-group' ), $title ='', $tidy_taxonomy = 'xili_tidy_tags' ) {
	global $xili_tidy_tags;

	$result = array();
	$listgroups = get_terms( $tidy_taxonomy, array('hide_empty' => false,'get'=>'all') );

	if ( $listgroups ) {
		foreach ( $listgroups as $tagsgroup ) {
			if ( !in_array( $tagsgroup->slug , $exclude ) &&  !( in_array( 'tidy-languages-group' , $exclude ) && $tagsgroup->parent == $xili_tidy_tags->langgroupid ) ) {
				$thetitle = ( $title == '' ) ? '' :  'title="'.sprintf( $title, $tagsgroup->name ).'"' ;

				$result[] = '<a href="'.link_for_posts_of_xili_tags_group ($tagsgroup->slug).'" '.$thetitle.' >'.$tagsgroup->name.'</a>';
			}
		}
		return implode ( $separator, $result );
	}
 }

/**
 * example to display ID of posts in a group tags
 *
 * @since 1.5.4
 *
 */
 function example_get_posts_of_xili_tags_group ( $tags_group ) {

	if ( $tags_group != "" ) {
		$thetags = xili_tags_from_group( $tags_group ) ;
		if ( $thetags ) {
			$list = implode ( ',', $thetags );
			$query = new WP_Query( 'tag='.$list );
			if ( $query->have_posts() )  {
				while ( $query->have_posts() ) : $query->the_post();
					// modify here
					echo '- '.get_the_ID().' -';
				endwhile;
			}
		}
	}

 }

?>