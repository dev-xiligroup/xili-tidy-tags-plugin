<?php
/**
 * XTT front class
 *
 * @package Xili-Tidy-Tags
 * @subpackage core
 * @since 1.12
 */

class Xili_Tidy_Tags {

	//var $is_metabox = false; /* for tests of special box in post */
	//var $is_post_ajax = false; /* for tests using ajax in UI */
	public $langgroupid = 0; /* group of langs*/


	public $post_tag = 'post_tag'; /* by default group post_tag 1.5.5 */
	public $post_tag_post_type = 'post';
	public $tidy_taxonomy = ''; // defined according tag


	// 1.8 - class admin in separate file
	public $file_file = ''; // see in construct below
	public $file_basename = '';
	public $plugin_basename = '';
	public $plugin_url = '';
	public $plugin_path = ''; // The path to this plugin - see construct

	public function __construct( $post_tag = 'post_tag', $post_tag_post_type = 'post', $class_admin = false ) {
		// default values - 1.5.5

		// 1.8 - class admin in separate file
		$this->file_file = __FILE__; // see in construct below
		$this->file_basename = basename( __FILE__ );
		$this->plugin_basename = plugin_basename( __FILE__ );
		$this->plugin_url = plugins_url( '', __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );

		if ( '' != $post_tag ) {
			$this->post_tag = $post_tag;
		}
		if ( '' != $post_tag_post_type ) {
			$this->post_tag_post_type = $post_tag_post_type;
		}

		/* activated when first activation of plug or automatic upgrade */
		register_activation_hook( __FILE__, array( &$this, 'xili_tidy_tags_activate' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'remove_capabilities' ) ); //2.3.7

		/* get current settings - name of taxonomy - name of query-tag - 0.9.8 new taxonomy taxolangsgroup */
		$this->xili_tidy_tags_activate();

		if ( $this->xili_settings['version'] < '0.5' ) { /* updating value by default 0.9.5 */
			$this->xili_settings['version'] = '0.5';
		}
		if ( $this->xili_settings['version'] == '0.5' ) {
			$this->xili_settings['editor_caps'] = 'no_caps';
			$this->xili_settings['version'] = '0.6';
		}
		if ( $this->xili_settings['version'] == '0.6' ) {
			$this->xili_settings['datatable_js'] = ''; // 1.5.3.1
			$this->xili_settings['version'] = '0.7';
		}
		update_option( 'xili_tidy_tags_settings', $this->xili_settings );

		if ( 'post_tag' == $this->post_tag ) {
			$this->tidy_taxonomy = $this->xili_settings['taxonomy']; // replace previous TAXOTIDYTAGS
		} else {
			$this->tidy_taxonomy = $this->xili_settings['taxonomy'] . '_' . $this->post_tag; // for new taxonomy
		}

		if ( ! $class_admin ) { // 1.8.1
			if ( ! defined( 'TAXOTIDYTAGS' ) ) {
				define( 'TAXOTIDYTAGS', $this->xili_settings['taxonomy'] ); // for use in widget or elsewhere 1.5.5
			}
			//if ( !defined( 'LANGSTAGSGROUPSLUG')) define( 'LANGSTAGSGROUPSLUG', $this->xili_settings['tidylangsgroup'] );
			//if ( !defined( 'LANGSTAGSGROUPNAME')) define( 'LANGSTAGSGROUPNAME', $this->xili_settings['tidylangsgroupname'] );
		}

		/* hooks */
		add_action( 'wp_head', array( &$this, 'head_insert_metas' ) );

		/* admin settings taxonomy and roles*/
		add_action( 'init', array( &$this, 'init_plugin' ), 10 ); /* text domain and caps of admin*/

	}

	public function xili_tidy_tags_activate() {

		$this->xili_settings = get_option( 'xili_tidy_tags_settings' );
		if ( empty( $this->xili_settings ) ) {
			$this->xili_settings = array(
				'taxonomy' => 'xili_tidy_tags',
				'tidylangsgroup' => 'tidy-languages-group',
				'tidylangsgroupname' => 'All lang.',
				'editor_caps' => 'no_caps',
				'datatable_js' => '',
				'version' => '0.7',
			);
			update_option( 'xili_tidy_tags_settings', $this->xili_settings );
		}
	}

	public function init_plugin() {
		/*multilingual for admin pages and menu*/
		load_plugin_textdomain( 'xili-tidy-tags', false, 'xili-tidy-tags/languages' ); // 1.5.5

		/* add new taxonomy in available taxonomies - move here for wpmu and wp 3.0*/
		register_taxonomy(
			$this->tidy_taxonomy,
			'term',
			array(
				'hierarchical' => true,
				'label' => false,
				'rewrite' => false,
				'update_count_callback' => '',
				'show_ui' => false,
				'show_in_nav_menus' => false, // WP 4.3
			)
		);

		$res = term_exists( $this->xili_settings['tidylangsgroupname'], $this->tidy_taxonomy );
		if ( $res ) {
			$this->langgroupid = $res['term_id'];
		}

		/* since 0.9.5 new default caps for admnistrator - updated 1.4.0 */
		if ( is_admin() ) {

			$role = get_role( 'administrator' );
			if ( current_user_can( 'activate_plugins' ) ) {

				$role->add_cap( 'xili_tidy_admin_set' );
				$role->add_cap( 'xili_tidy_editor_set' );
				$role->add_cap( 'xili_tidy_editor_group' );

			} elseif ( current_user_can( 'edit_others_pages' ) ) {
				$role = get_role( 'editor' );
				switch ( $this->xili_settings['editor_caps'] ) {
					case 'caps_grouping':
						$role->remove_cap( 'xili_tidy_editor_set' );
						$role->add_cap( 'xili_tidy_editor_group' );
						break;
					case 'caps_setting_grouping':
						$role->add_cap( 'xili_tidy_editor_set' );
						$role->add_cap( 'xili_tidy_editor_group' );
						break;
					case 'no_caps':
						$role->remove_cap( 'xili_tidy_editor_set' );
						$role->remove_cap( 'xili_tidy_editor_group' );
						break;
				}
			}
		}
	}

	// when desactivating - 1.8.4
	public function remove_capabilities() {

		global $wp_roles;

		$wp_roles->remove_cap( 'administrator', 'xili_tidy_admin_set' );
		$wp_roles->remove_cap( 'administrator', 'xili_tidy_editor_set' );
		$wp_roles->remove_cap( 'administrator', 'xili_tidy_editor_group' );

		$wp_roles->remove_cap( 'editor', 'xili_tidy_editor_set' ); // reset
		$wp_roles->remove_cap( 'editor', 'xili_tidy_editor_group' );

	}


	public function head_insert_metas() {
		echo '<!-- for tag ' . $this->post_tag . ', website powered with xili-tidy-tags v.' . XILITIDYTAGS_VER . ", a WP plugin by dev.xiligroup.com -->\n";
	}

	public function get_wplang() {
		global $wp_version;
		if ( version_compare( $wp_version, '4.0', '<' ) ) {
			if ( defined( 'WPLANG' ) ) {
				return WPLANG;
			} else {
				return '';
			}
		} else {
			return get_option( 'WPLANG', '' );
		}
	}

	/**
	 * for further dev.
	 *
	 */
	public function xili_manage_tax_action( $actions, $tag ) {
		return $actions;
	}

	/**
	* return array of languages assigned to a tag
	* called by filter in class instancing
	*
	*/
	public function return_lang_of_tag( $id, $tidy_taxonomy = '' ) {

		if ( '' == $tidy_taxonomy ) {
			$tidy_taxonomy = $this->tidy_taxonomy; // if called inside class
		}

		$langs = array();

		if ( ! class_exists( 'xili_language' ) ) {
			return false;
		}
		$listlanguages = get_terms( TAXONAME, array( 'hide_empty' => false ) );
		foreach ( $listlanguages as $lang ) {
			if ( is_object_in_term( $id, $tidy_taxonomy, $lang->slug . '-' . $this->xili_settings['tidylangsgroup'] ) ) {
				$langs[] = $lang;
			}
		}
		if ( array() == $langs ) {
			return false;
		} else {
			return $langs;
		}
	}

	/**
	 *  update walking sorting
	 *  @since  1.11.5
	 */
	public function create_taggrouplist_sorted( $walking_string ) {
		$term_id_array = explode( '/', $walking_string );
		if ( is_array( $term_id_array ) ) {
			$sorted_terms = array();
			foreach ( $term_id_array as $term_id ) {
				$sorted_terms[] = get_term( (int) $term_id, $this->tidy_taxonomy, OBJECT, 'edit' );
			}
		}
		return $sorted_terms;
	}


} /* end class */
