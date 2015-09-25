<?php
/**
 * xili-tidy-tags class admin (now separated file since 1.8.0) - 1.8.1 fixes - 1.8.2 (s)-
 *
 * 1.8.3 - Add errors management to detect bad instancing with other tag (custom taxonomy) ...
 * 1.8.7 - Tags grouping via alias - fixes errors when no declared taxonomy and more...
 * 1.9.1 - WP 3.8 updated message
 * 1.9.2 - WP 3.9-b1 updated notice message prepare
 * 1.9.3 - 141128 - Fixes $news_id pointer in admin
 * 1.10.0 - 141219 - Fixes editing alias tags
 * 1.10.1 - 150228 - pre-tested WP 4.2-alpha - rewrite selected(), checked()
 * 1.10.2 - 150322 - new datatables JS+CSS (1.10.5)
 * 1.10.3 - 150408 - fixes notice with constante line 61
 * 1.11.0 - 150703 - datatables js 1.10.7 updated WP 43 + debug
 */

class xili_tidy_tags_admin extends xili_tidy_tags {

	var $subselect = 0; /* selected parent group */
	var $fromgroupselect = 0; /* group in which belong tags */
	var $groupexclude = false; /* exclude group from the query */
	var $uncheckedtags = false; /* exclude uncheckedtags from the query if false */
	var $onlyuncheckedtags = false; /* for general query to see only unchecked */
	var $onlyparent = false; /* when a group is parent, show all tags of childs of the group */
	var $news_id = 0; //for multi pointers

	/**
	 * PHP 5 Constructor
	 */
	function __construct( $xtt_parent, $post_tag = 'post_tag', $post_tag_post_type = 'post' ){

		$this->parent = $xtt_parent; // to keep values built in parent filters...

		parent::__construct( $post_tag, $post_tag_post_type, true );  // need parent constructed values (third param - tell coming admin-class //2.6
		add_action( 'admin_init', array( &$this,'admin_init') ); // 1.5.0
		add_action( 'admin_menu', array( &$this,'xili_add_pages') );

		add_action( $this->post_tag.'_add_form', array( &$this,'xili_add_tag_form') ); /* to choose a group for a new tag */
		add_action( $this->post_tag.'_add_form_fields', array( &$this,'xili_add_tag_alias') ); /* to assign a tag as alias - 1.8.7 */
		add_action( $this->post_tag.'_edit_form', array( &$this,'xili_edit_tag_form') );
		add_action( $this->post_tag.'_edit_form', array( &$this,'xili_add_tag_alias') ); /* to assign a tag as alias - 1.8.7 */

		/*  edit-tags table */
		add_filter( 'manage_edit-'.$this->post_tag.'_columns', array(&$this,'xili_manage_tax_column_name'));
		add_filter( 'manage_'.$this->post_tag.'_custom_column', array(&$this,'xili_manage_tax_column'), 10, 3); // 2.6

		/* actions for post and page admin UI */
		add_action( 'save_post', array( &$this,'xili_tags_grouping'), 50 ); /* to affect tags to lang of saved post */

		add_action( 'created_term', array( &$this,'xili_created_term'), 9, 2); /* a new term was created */
		add_action( 'edited_term', array( &$this,'xili_edited_term'), 9, 2); /* a term was edited */

		/* plugin list */
		add_filter( 'plugin_action_links',  array( &$this,'xili_filter_plugin_actions'), 100, 2 );
		/* help */
		add_action( 'contextual_help', array( &$this,'add_help_text'), 10, 3 ); /* 1.5.0 */
	}

	function admin_init() {
		$suffix = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '' : '.min';
        /* Register our script. */
        wp_register_script( 'datatables-v10', plugins_url('js/jquery.dataTables'.$suffix.'.js', $this->file_file ) , array( 'jquery' ), '1.10.7', true );

        wp_register_style( 'table_style-v10', plugins_url('/css/jquery.dataTables'.$suffix.'.css', $this->file_file ), array(), '1.10.7', 'screen' );
    }

    /**
	 * add admin menu and associated pages of admin UI tools page xili_tidy_editor_set
	 *
	 * @since 0.8.0
	 * @updated 0.9.5 - menu without repeat of main title, levels with new caps set by plugin array(&$this,'top_tidy_menu_title')
	 * @updated 1.0.1 - favicon.ico for menu title - 1.3.4 Special window for Super User
	 */
	function xili_add_pages() {

			$taxo_tag = get_taxonomy ( $this->post_tag ) ;
		 	$this->tags_name = ( isset ( $taxo_tag->labels->name ) ) ? $taxo_tag->labels->name : $this->post_tag ;
		 	$this->singular_name = ( isset ( $taxo_tag->labels->singular_name ) ) ? $taxo_tag->labels->singular_name : $this->post_tag ; // 1.8.6

		 	$pre = ( $this->post_tag == 'post_tag') ? ''  : '_'.$this->post_tag;

			$this->thehook0 = add_object_page( sprintf(__('%s groups','xili_tidy_tags'), $this->tags_name), sprintf(__('Tidy %s','xili_tidy_tags'), $this->tags_name ) , '', 'xili-tidy-tags'.$pre, array(&$this,'top_tidy_menu_title'), plugins_url( 'images/xilitidy-logo-16.png', $this->file_file ) ); //1.5.5

		  	$this->thehooka = add_submenu_page('xili-tidy-tags'.$pre, sprintf( __('%s groups','xili_tidy_tags'), $this->tags_name),__('Info for SuperAdmin','xili_tidy_tags'), '', 'xili-tidy-tags'.$pre, array(&$this,'top_tidy_menu_title'));

		  	$this->thehook = add_submenu_page('xili-tidy-tags'.$pre, sprintf( __('%s groups','xili_tidy_tags'), $this->tags_name), sprintf(__('Tidy %s settings','xili_tidy_tags'), $this->tags_name), 'xili_tidy_editor_set', 'xili_tidy_tags_settings'.$pre, array(&$this,'xili_tidy_tags_settings'));
		  	add_action( 'load-'.$this->thehook, array(&$this,'on_load_page' ) );

		 	/* sub-page */
		 	$this->thehook2 = add_submenu_page('xili-tidy-tags'.$pre, sprintf(__('Tidy %s','xili_tidy_tags'), $this->tags_name ), sprintf(__('Tidy %s assign','xili_tidy_tags'), $this->tags_name), 'xili_tidy_editor_group', 'xili_tidy_tags_assign'.$pre, array(&$this,'xili_tidy_tags_assign'));


		 	add_action( 'load-'.$this->thehook2, array(&$this,'on_load_page2') );

		 	add_action( 'admin_print_scripts-' . $this->thehook2, array( &$this, 'admin_enqueue_scripts' ) );
		 	add_action( 'admin_print_styles-' . $this->thehook2, array( &$this, 'admin_enqueue_styles' ) );

		 	$this->insert_news_pointer ( 'xtt_new_version' ); // pointer in menu for updated version
		 	add_action( 'admin_print_footer_scripts', array(&$this, 'print_the_pointers_js') );
		 	// 1.5
 	}

	function top_tidy_menu_title () { // again with wp3.0 instead '' call v1.3.4
 		$pre = ( $this->post_tag == 'post_tag') ? ''  : '_'.$this->post_tag;
 		?>
 		<div class='wrap'>
		<h2><?php printf(__("Tidy %s settings","xili_tidy_tags"), $this->tags_name); ?></h2>
		<h4><?php _e("This window is reserved for future settings in multisite mode (wpmu) for administrator like SuperAdmin...","xili_tidy_tags"); ?></h4>
		<p><?php printf(__("Link to set tidy %s in current site","xili_tidy_tags"), $this->tags_name); ?>: <a href="<?php echo "admin.php?page=xili_tidy_tags_settings".$pre; ?>" title="xili-tidy-tags settings" ><?php printf(__("To create groups of %s","xili_tidy_tags"), $this->tags_name); ?></a></p>
		<p><?php printf(__("Link to assign tidy %s in current site","xili_tidy_tags"), $this->tags_name); ?>: <a href="<?php echo "admin.php?page=xili_tidy_tags_assign".$pre; ?>" title="xili-tidy-tags assign"><?php printf(__("To assign a group to %s","xili_tidy_tags"), $this->tags_name); ?></a></p>

		<h4><a href="http://dev.xiligroup.com/xili-tidy-tags" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo plugins_url( 'images/xilitidy-logo-32.png', $this->file_file ) ; ?>" alt="xili-tidy-tags logo"/></a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2009-15 - v. <?php echo XILITIDYTAGS_VER; ?></h4>
		</div>
		<?php
 	}

 	/* Dashboard - Manage - Tidy tags */
	function xili_tidy_tags_settings() {
		global $wp_version ;

		$xili_tidy_tags_page = 'settings';
		$formtitle = __('Add a group', 'xili_tidy_tags'); /* translated in form */
		$submit_text = __('Add &raquo;', 'xili_tidy_tags');
		$cancel_text = __('Cancel');
		$action = '';
		$optionmessage = '';
		$emessage = "";
		$tagsgroup = null;
		$term_id = 0;
		if (isset($_POST['reset'])) {
			$action=$_POST['reset'];
		} elseif (isset($_POST['updateoptions'])) {
			$action='updateoptions';
		} elseif (isset($_POST['importxililanguages'])) {
			$action='importxililanguages';
		} elseif (isset($_POST['importacat'])) {
			$action='importacat';
		} elseif (isset($_POST['editor_caps_submit'])) { /* 0.9.5 capabilities */
			$action='editor_caps_submit';
		} elseif ( isset($_POST['sendmail']) ) { //1.5.4
			$action = 'sendmail' ;
		} elseif (isset($_POST['action'])) {
			$action=$_POST['action'];
		}

		if (isset($_GET['action'])) :
			$action=$_GET['action'];
			$term_id = $_GET['term_id'];
		endif;
		$message = $action ;
		$msg = 0;
		switch($action) {
			case 'editor_caps_submit';
				check_admin_referer( 'xilitagsettings' );
				$new_cap = $_POST['editor_caps'];

				$this->xili_settings['editor_caps'] = $new_cap ;
				$this->xili_settings['datatable_js'] = ( isset ( $_POST['datatable_js'] )) ? $_POST['datatable_js'] : "" ; // 1.5.3.1

				update_option('xili_tidy_tags_settings', $this->xili_settings);

				$actiontype = "add";
			    $message .= " - ".__('Editor Capabilities changed to: ','xili_tidy_tags')." (".$new_cap.") ";
			    $optionmessage = $message;
			    $msg = 6;
				break;
			case 'importacat';
				check_admin_referer( 'xilitagsettings' );
				$chosencatid = $_POST['catsforgroup'];
				$chosenparent = $_POST['tags_parent'];
				$chosencat = get_category($chosencatid);
				$desc = __('Group for: ','xili_tidy_tags').$chosencat->name .' '. __('category','xili_tidy_tags');
				$args = array( 'alias_of' => '', 'description' => $desc, 'parent' => (int) $_POST['tags_parent']);
			    $theids = wp_insert_term( $chosencat->name, $this->tidy_taxonomy, $args);
			    if ( !is_wp_error($theids) )
			    	wp_set_object_terms($theids['term_id'], (int)$_POST['tags_parent'], $this->tidy_taxonomy);

				$actiontype = "add";
			    $message .= " - ".__('This group was added: ','xili_tidy_tags')." (".$chosencatid.") parent = ".$chosenparent;
				break;

			case 'importxililanguages';
				check_admin_referer( 'xilitagsettings' );
				$this->xili_langs_import_terms ();
				$actiontype = "add";
			    $message .= " - ".__('The languages groups was added.','xili_tidy_tags');
			    $msg = 5;
				break;

			case 'add';
				check_admin_referer( 'xilitagsettings' );
				$term = $_POST['tagsgroup_name'];
				if ('' != $term) {
					$args = array( 'alias_of' => '', 'description' => $_POST['tagsgroup_description'], 'parent' => (int) $_POST['tagsgroup_parent'], 'slug' => $_POST['tagsgroup_nicename']);
				    $theids = wp_insert_term( $term, $this->tidy_taxonomy, $args);
				    if (!is_wp_error($theids)) {
				    	wp_set_object_terms($theids['term_id'], (int)$_POST['tagsgroup_parent'], $this->tidy_taxonomy);
				    	$message .= " - ".__('A new group was added.','xili_tidy_tags');
				    	$msg = 1;
				    } else {
				    	$message .= " - Error DB: ". serialize ( $theids ) ;
				    	$msg = 2;
				    }
					$actiontype = "add";

				} else {
					$actiontype = "add";
				    $message .= " - ".__('NO new group was added.','xili_tidy_tags');
				    $msg = 2;
				}
			    break;

			case 'edit';
				$actiontype = "edited";
			    $tagsgroup = get_term( (int) $term_id, $this->tidy_taxonomy,OBJECT,'edit');
			    $submit_text = __('Update &raquo;','xili_tidy_tags');
			    $formtitle =  __('Edit Group', 'xili_tidy_tags');
			    $message .= " - ".__('Group to update.','xili_tidy_tags');
				break;

			case 'edited';
				check_admin_referer( 'xilitagsettings' );
			    $actiontype = "add";
			    $term_id = $_POST['tagsgroup_term_id'];
			    $term_name = $_POST['tagsgroup_name']; // fixed 1.6.0
				$args = array( 'name' => $term_name,'alias_of' => '', 'description' => $_POST['tagsgroup_description'], 'parent' => (int)$_POST['tagsgroup_parent'], 'slug' =>$_POST['tagsgroup_nicename']);
				$theids = wp_update_term( $term_id, $this->tidy_taxonomy, $args);
				$message .= " - ".__('A group was updated.','xili_tidy_tags');
				$msg = 3;
			    break;

			case 'delete';
			    $actiontype = "deleting";
			    $submit_text = __('Delete &raquo;','xili_tidy_tags');
			    $formtitle = __('Delete group','xili_tidy_tags');
			    $tagsgroup = get_term( (int) $term_id, $this->tidy_taxonomy,OBJECT, 'edit');
			    $message .= " - ".__('A group to delete.','xili_tidy_tags');
			    break;

			case 'deleting';
				check_admin_referer( 'xilitagsettings' );
			    $actiontype = "add";
			    $term = $_POST['tagsgroup_term_id'];
			    wp_delete_term( $term, $this->tidy_taxonomy ); // 1.8.1
			    $message .= " - ".__('A group was deleted.','xili_tidy_tags');
			    $msg = 4;
			    break;

			case 'reset';
			    $actiontype = "add";
			    break;

			case 'sendmail'; // 1.5.4
				check_admin_referer( 'xilitagsettings' );
				$this->xili_settings['url'] = ( isset( $_POST['urlenable'] ) ) ? $_POST['urlenable'] : '' ;
				$this->xili_settings['theme'] = ( isset( $_POST['themeenable'] ) ) ? $_POST['themeenable'] : '' ;
				$this->xili_settings['wplang'] = ( isset( $_POST['wplangenable'] ) ) ? $_POST['wplangenable'] : '' ;
				$this->xili_settings['version-wp'] = ( isset( $_POST['versionenable'] ) ) ? $_POST['versionenable'] : '' ;
				$this->xili_settings['xiliplug'] = ( isset( $_POST['xiliplugenable'] ) ) ? $_POST['xiliplugenable'] : '' ;
				$this->xili_settings['webmestre-level'] = $_POST['webmestre']; // 1.8.2
				update_option('xili_tidy_tags_settings', $this->xili_settings);
				$contextual_arr = array();
				if ( $this->xili_settings['url'] == 'enable' ) $contextual_arr[] = "url=[ ".get_bloginfo ('url')." ]" ;
				if ( isset($_POST['onlocalhost']) ) $contextual_arr[] = "url=local" ;
				if ( $this->xili_settings['theme'] == 'enable' ) $contextual_arr[] = "theme=[ ".get_option ('stylesheet')." ]" ;
				if ( $this->xili_settings['wplang'] == 'enable' ) $contextual_arr[] = "WPLANG=[ ". $this->get_WPLANG()." ]" ;
				if ( $this->xili_settings['version-wp'] == 'enable' ) $contextual_arr[] = "WP version=[ ".$wp_version." ]" ;
				if ( $this->xili_settings['xiliplug'] == 'enable' ) $contextual_arr[] = "xiliplugins=[ ". $this->check_other_xili_plugins() ." ]" ;

				$contextual_arr[] = $this->xili_settings['webmestre-level'];  // 1.9.1

				$headers = 'From: xili-tidy-tags plugin page <' . get_bloginfo ('admin_email').'>' . "\r\n" ;
	   			if ( '' != $_POST['ccmail'] ) {
					$headers .= 'Cc: <'.$_POST['ccmail'].'>' . "\r\n";
					$headers .= 'Reply-To: <'.$_POST['ccmail'].'>' . "\r\n";
				}
	   			$headers .= "\\";
	   			$message = "Message sent by: ".get_bloginfo ('admin_email')."\n\n" ;
	   			$message .= "Subject: ".$_POST['subject']."\n\n" ;
	   			$message .= "Topic: ".$_POST['thema']."\n\n" ;
	   			$message .= "Content: ".$_POST['mailcontent']."\n\n" ;
	   			$message .= "Checked contextual infos: ". implode ( ', ', $contextual_arr ) ."\n\n" ;
	   			$message .= "This message was sent by webmaster in xili-tidy-tags plugin settings page.\n\n";
	   			$message .= "\n\n";
	   			$result = wp_mail('contact@xiligroup.com', $_POST['thema'].' from xili-tidy-tags v.'.XILITIDYTAGS_VER.' plugin settings page.' , $message, $headers );
				$message = __('Email sent.','xili_tidy_tags');
				$msg = 7;
				$emessage = sprintf( __( 'Thanks for your email. A copy was sent to %s (%s)','xili_tidy_tags' ), $_POST['ccmail'], $result ) ;
				$actiontype = "add";
				break;

			default :
			    $actiontype = "add";
			    $message .= sprintf( __('Find the list of groups for %s.','xili_tidy_tags'), $this->tags_name);
		}

		/* register the main boxes always available */

		add_meta_box('xili_tidy_tags-normal-group-tags-list', sprintf( __('Groups of %s','xili_tidy_tags'), $this->tags_name), array(&$this,'on_normal_group_tags_list_content'), $this->thehook , 'normal', 'core'); /* list of groups*/
		add_meta_box('xili_tidy_tags-normal-form', __('The group','xili_tidy_tags'), array(&$this,'on_normal_group_form_content'), $this->thehook , 'normal', 'core'); /* the group*/
		add_meta_box('xili_tidy_tags-sidebox-import', __('Actions','xili_tidy_tags'), array(&$this,'on_sidebox_import_content'), $this->thehook , 'side', 'core'); /* Actions */

		$themessages[1] = __('A new group was added.','xili_tidy_tags');
		$themessages[2] = __('NO new group was added.','xili_tidy_tags');
		$themessages[3] = __('A group was updated.','xili_tidy_tags');
		$themessages[4] = __('A group was deleted.','xili_tidy_tags');
		$themessages[5] = __('The languages groups was added.','xili_tidy_tags');
		$themessages[6] = $optionmessage ;
		$themessages[7] = __('Email sent.','xili_tidy_tags');

		/* form datas in array for do_meta_boxes() */
		$data = array('xili_tidy_tags_page' => $xili_tidy_tags_page,'message' => $message, 'action'=>$action, 'formtitle'=>$formtitle, 'tagsgroup'=>$tagsgroup, 'submit_text'=>$submit_text,'cancel_text'=>$cancel_text, 'term_id'=>$term_id, 'emessage'=>$emessage);
		?>
		<div id="xili-tidy-tags-settings" class="wrap columns-2" style="min-width:880px">
			<?php screen_icon('tools'); ?>
			<h2><?php printf(__('Tidy %s groups','xili_tidy_tags'), $this->tags_name) ?></h2>
			<?php if (0!= $msg ) { ?>
			<div id="message" class="updated fade"><p><?php echo $themessages[$msg]; ?></p></div>
			<?php }
			$pre = ( $this->post_tag == 'post_tag') ? 'xili_tidy_tags_settings'  : 'xili_tidy_tags_settings_'.$this->post_tag ;
			?>
			<form name="add" id="add" method="post" action="admin.php?page=<?php echo $pre; ?>">
				<input type="hidden" name="action" value="<?php echo $actiontype ?>" />
				<?php wp_nonce_field('xili-tidy-tags-settings'); ?>
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false );
				/* 0.9.3 add has-right-sidebar for next wp 2.8*/

				global $wp_version;
				if ( version_compare($wp_version, '3.3.9', '<') ) {
					$poststuff_class = 'class="metabox-holder has-right-sidebar"';
					$postbody_class = "";
					$postleft_id = "";
					$postright_id = "side-info-column";
					$postleft_class = "";
					$postright_class = "inner-sidebar";
				} else { // 3.4
					$poststuff_class = "";
					$postbody_class = 'class="metabox-holder columns-2"';
					$postleft_id = 'id="postbox-container-2"';
					$postright_id = "postbox-container-1";
					$postleft_class = 'class="postbox-container"';
					$postright_class = "postbox-container";
				}
				?>
				<div id="poststuff" <?php echo $poststuff_class; ?>>
					<div id="post-body" <?php echo $postbody_class; ?> >
						<div id="<?php echo $postright_id; ?>" class="<?php echo $postright_class; ?>">
							<?php do_meta_boxes($this->thehook, 'side', $data); ?>
						</div>
						<div id="post-body-content">
							<div <?php echo $postleft_id; ?> <?php echo $postleft_class; ?> style="min-width:580px">
								<?php do_meta_boxes($this->thehook, 'normal', $data); ?>
							</div>

							<h4><a href="http://dev.xiligroup.com/xili-tidy-tags" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo plugins_url( 'images/xilitidy-logo-32.png', $this->file_file ); ?>" alt="xili-tidy-tags logo"/></a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2009-15 - v. <?php echo XILITIDYTAGS_VER; ?></h4>

						</div>
					</div>
					<br class="clear" />
				</div>
				<?php wp_nonce_field('xilitagsettings'); ?>
		</form>
		</div>
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {

				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->thehook; ?>');
			});
			//]]>
		</script>

	<?php
	}

 	function on_load_page() {
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');

			$pre = ( $this->post_tag == 'post_tag') ? ''  : $this->post_tag.'_' ;
			add_meta_box('xili_tidy_tags-sidebox-1', __('Message','xili_tidy_tags'), array(&$this,'on_sidebox_msg_content'), $this->thehook , 'side', 'core');
			add_meta_box($pre.'xili_tidy_tags-sidebox-3', __('Info','xili_tidy_tags'), array(&$this,'on_sidebox_info_content'), $this->thehook , 'side', 'core');
			add_meta_box($pre.'xili_tidy_tags-sidebox-mail', __('Mail & Support','xili_tidy_tags'), array(&$this,'on_sidebox_mail_content'), $this->thehook , 'normal', 'low');
			if (current_user_can( 'xili_tidy_admin_set'))
				add_meta_box($pre.'xili_tidy_tags-sidebox-4', __('Capabilities','xili_tidy_tags'), array(&$this,'on_sidebox_admin_content'), $this->thehook , 'side', 'core');

	}

	/* page for tags assign to (a) group(s) */
	function xili_tidy_tags_assign () {

		$current_taxonomy = get_taxonomy( $this->post_tag ) ;
		if ( $current_taxonomy == false || !in_array ( $this->post_tag_post_type, $current_taxonomy->object_type ) ) $msg = 3 ;

		$action = "";
		$msg = "";
		$term_id = '';
		$tagsnamelike = '';
		$tagsnamesearch = '';
		$xili_tidy_tags_page = 'assign';
		$submit_text = __('Update','xili_tidy_tags');
		$cancel_text = __('Cancel');
		$tagsnamelike = ( isset( $_POST['tagsnamelike'] ) ) ? $_POST['tagsnamelike'] : "" ;
		$tagsnamesearch = ( isset( $_POST['tagsnamesearch'] ) ) ? $_POST['tagsnamesearch']: "";
		if (isset($_POST['update'])) {
			$action='update';
		}
		/* since 1.3.0 */
		if (isset($_POST['tagsgroup_from_select']) && $_POST['tagsgroup_from_select'] != 'no_select') {
			$this->fromgroupselect = (int) $_POST['tagsgroup_from_select'];
		} elseif (isset($_GET['tps']) && $_GET['tps'] != 'no_select') {
			$this->fromgroupselect = (int) $_GET['tps'];
		} else {
			$this->fromgroupselect = 0;
		}


		$this->onlyuncheckedtags = (isset($_POST['tagsgroup_from_select']) && $_POST['tagsgroup_from_select'] == 'onlyuncheckedtags') ? true : false ;
		$this->onlyparent = (isset($_POST['xili_group_only_parent']) && $_POST['xili_group_only_parent'] == 'onlyparent') ? true : false ;
		$this->groupexclude = (isset($_POST['xili_group_not_select']) && $_POST['xili_group_not_select'] == 'not') ? true : false ;
		$this->uncheckedtags = (isset($_POST['xili_uncheckedtags']) && $_POST['xili_uncheckedtags'] == 'include') ? true : false ;
		$subselectgroups = array();
		if (isset($_POST['tagsgroup_parent_select']) && $_POST['tagsgroup_parent_select'] != 'no_select') {
			$this->subselect = (int) $_POST['tagsgroup_parent_select'];
		} else {
			$this->subselect = 0;
		}


		if ( isset($_GET['tps']) ) { // 1.7
			$action='subselectiong';
		}
		if (isset($_POST['subselection']) ) {
			$action='subselection';
		}
		if (isset($_POST['notagssublist'])) {
			$action='notagssublist';
		}
		if (isset($_POST['tagssublist'])) {
			$action='tagssublist';
		}
		if (isset($_GET['action'])) :
			$action = $_GET['action'];
			$term_id = $_GET['term_id'];
		endif;
		$message = $action ;
		switch($action) {

			case 'notagssublist';
				$tagsnamelike = '';
				$tagsnamesearch = '';
				$this->fromgroupselect = 0; /* since 1.3.0 */
				$this->groupexclude = false;
				$message .= ' no sub list of tags';
				$actiontype = "add";
				break;

			case 'tagssublist';
				check_admin_referer( 'xilitagassign' );
				$message .= ' sub list of tags starting with '.$_POST['tagsnamelike'];
				$message .= '. From group '.$_POST['tagsgroup_from_select'];
				$actiontype = "add";
				break;

			case 'subselection';
				check_admin_referer( 'xilitagassign' );
				$tagsnamelike = $_POST['tagsnamelike'];
				$tagsnamesearch = $_POST['tagsnamesearch'];
				$message .= ' selection of '.$_POST['tagsgroup_parent_select'];
				$msg = 2 ;
				$actiontype = "add";
				break;

			case 'subselectiong'; // 1.7
				check_admin_referer( 'xtt-tps' );
				$message .= ' selection of '.$_GET['tps'];
				$msg = 2 ;
				$actiontype = "add";
				break;

			case 'update';
				check_admin_referer( 'xilitagassign' );
				$message .= ' ok: datas are saved... ';
				$message .= $this->checkboxes_update_them( $tagsnamelike, $tagsnamesearch ); $msg=1;
				$actiontype = "add";
				break;

			case 'reset';
			    $actiontype = "add";
			    break;

			default :
			    $actiontype = "add";
			    $message .= sprintf( __('Find the list of %s.','xili_tidy_tags'), $this->tags_name);
		}
		/* form datas in array for do_meta_boxes() */
		$data = array( 'xili_tidy_tags_page' => $xili_tidy_tags_page, 'message'=>$message, 'action'=>$action, 'submit_text'=>$submit_text, 'cancel_text'=>$cancel_text, 'term_id'=>$term_id, 'tagsnamesearch'=>$tagsnamesearch, 'tagsnamelike'=>$tagsnamelike);

		/* register the main boxes always available */
		add_meta_box( 'xili_tidy_tags-sidebox-action', __('Actions','xili_tidy_tags'), array( &$this,'on_sub_sidebox_action_content' ), $this->thehook2 , 'side', 'core'); /* Actions */
		add_meta_box( 'xili_tidy_tags-normal-tags', sprintf(__('Tidy %s','xili_tidy_tags'), $this->tags_name ), array( &$this,'on_sub_normal_tags_list_content' ), $this->thehook2 , 'normal', 'core'); /* list of tags*/

		$themessages[1] = __('List updated in database !','xili_tidy_tags');

		$themessages[2] = $message ;
		$themessages[3] = __('Post Type <strong>not declared</strong> in Custom Taxonomy !','xili_tidy_tags');
			?>
		<div id="xili-tidy-tags-assign" class="wrap columns-2" style="min-width:880px">
			<?php screen_icon('post'); ?>
			<h2><?php printf(__('%s in group with post type named: %s','xili_tidy_tags'), $this->tags_name, $this->post_tag_post_type); ?></h2>
			<?php if ( 0 != $msg && "" != $msg ) { ?>
			<div id="message" class="updated fade"><p><?php echo $themessages[$msg]; ?></p></div>
			<?php }
			$preassign = ( $this->post_tag == 'post_tag') ? 'xili_tidy_tags_assign'  : 'xili_tidy_tags_assign_'.$this->post_tag;
			?>
			<form name="add" id="add" method="post" action="admin.php?page=<?php echo $preassign ?>">
				<input type="hidden" name="action" value="<?php echo $actiontype ?>" />
				<?php wp_nonce_field('xili-tidy-tags-settings'); ?>
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false );

				global $wp_version;
				if ( version_compare($wp_version, '3.3.9', '<') ) {
					$poststuff_class = 'class="metabox-holder has-right-sidebar"';
					$postbody_class = "";
					$postleft_id = "";
					$postright_id = "side-info-column";
					$postleft_class = "";
					$postright_class = "inner-sidebar";
				} else { // 3.4
					$poststuff_class = "";
					$postbody_class = 'class="metabox-holder columns-2"';
					$postleft_id = 'id="postbox-container-2"';
					$postright_id = "postbox-container-1";
					$postleft_class = 'class="postbox-container"';
					$postright_class = "postbox-container";
				}

				?>
				<div id="poststuff" <?php echo $poststuff_class; ?>>
					<div id="post-body" <?php echo $postbody_class; ?> >
						<div id="<?php echo $postright_id; ?>" class="<?php echo $postright_class; ?>">
							<?php do_meta_boxes($this->thehook2, 'side', $data); ?>
						</div>

						<div id="post-body-content" >
							<div <?php echo $postleft_id; ?> <?php echo $postleft_class; ?> style="min-width:580px">
	   							<?php do_meta_boxes($this->thehook2, 'normal', $data); ?>
							</div>

							<h4><a href="http://dev.xiligroup.com/xili-tidy-tags" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo plugins_url( 'images/xilitidy-logo-32.png', $this->file_file ) ; ?>" alt="xili-tidy-tags logo"/></a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2009-15 - v. <?php echo XILITIDYTAGS_VER; ?></h4>

						</div>
					</div>
					<br class="clear" />
				</div>
				<?php wp_nonce_field('xilitagassign'); ?>
		</form>
		</div>
		<script type="text/javascript">
			//<![CDATA[


			var assignTable;
			jQuery(document).ready( function($) {
				<?php if ( $this->xili_settings['datatable_js'] == '' ) {  ?>
				$('#tableupdating').hide();

				var assignTable = $('#assigntable').dataTable( {
					"iDisplayLength": 30,
					"bStateSave": true,
					"sDom": '<"topbanner"ipf>rt<"bottombanner"lp><"clear">',
					"sPaginationType": "full_numbers",
					"aLengthMenu": [[15, 30, 60, -1], [15, 30, 60, "<?php _e('All lines','xili_tidy_tags') ?>"]],
					"oLanguage": {
						"oPaginate": {
							"sFirst": "<?php _e('First','xili_tidy_tags') ?>",
							"sLast": "<?php _e('Last page','xili_tidy_tags') ?>",
							"sNext": "<?php _e('Next','xili_tidy_tags') ?>",
							"sPrevious": "<?php _e('Previous','xili_tidy_tags') ?>"
						},
						"sInfo": "<?php printf(__('Showing (_START_ to _END_) of _TOTAL_ %s','xili_tidy_tags'), $this->tags_name ); ?>",
						"sInfoFiltered": "<?php _e('(filtered from _MAX_ total entries)','xili_tidy_tags') ?>",
						"sLengthMenu": "<?php _e('Show _MENU_ tags','xili_tidy_tags') ?>",
						"sSearch": "<?php _e('Filter tags:','xili_tidy_tags') ?>"
					},
					"aaSorting": [[1,'asc']],
					"aoColumns": [
						{ "bSearchable": false },
						null,
						{ "bSortable": false, "bSearchable": false },
						{ "bSortable": false, "bSearchable": false }]
				} );

				$('#assigntable').css({ visibility:'visible' });

				$('#update').click( function () {

					$('#assigntable').hide();

					$('#tableupdating').html("<br /><br /><h1><?php _e('Updating table of tags !','xili_tidy_tags') ?></h1>");
					$('#tableupdating').show();
					assignTable.fnDestroy();
				} );
				<?php } ?>
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->thehook2; ?>');
			});

						//]]>
		</script>
		<?php
	}

	/*
	 * Update the relationships according CheckBoxes
	 *
	 */
	function checkboxes_update_them($tagsnamelike='',$tagsnamesearch='') {

		$listgroups = get_terms($this->tidy_taxonomy, array('hide_empty' => false,'get'=>'all'));

		if ( $this->fromgroupselect == 0 && $this->onlyuncheckedtags === false ) {
			$listtags = get_terms($this->post_tag , array('hide_empty' => false, 'get'=>'all', 'name__like'=>$tagsnamelike, 'search'=>$tagsnamesearch) );
		} else {/* since 1.3.0 */
			if ( $this->onlyuncheckedtags === false ) { // one group
				$group_id[] = $this->fromgroupselect;
				if ( $this->onlyparent === false ) {
					$childterms = get_terms($this->tidy_taxonomy, array('hide_empty' => false,'parent' => $this->fromgroupselect));
			 		if ( !empty($childterms) ) {
			 			foreach ( $childterms as $childterm ) { /* if group is a parent, add all childs */
			 			 	$group_id[] = $childterm->term_id;
			 			}
			 		}
				}
			} else { // only all unchecked
				$listgroups = get_terms( $this->tidy_taxonomy, array('hide_empty' => false, 'get'=>'all') );
				foreach ( $listgroups as $group ) {
					$group_id[] = $group->term_id;
				}
				$this->groupexclude = true;
				$this->uncheckedtags = true;
			}
			$listtags = xtt_get_terms_of_groups_new ($group_id, $this->tidy_taxonomy, $this->post_tag, array('hide_empty' => false, 'get'=>'all', 'name__like'=>$tagsnamelike,'search'=>$tagsnamesearch, 'orderby'=>'name'), $this->groupexclude, $this->uncheckedtags);
		}

		foreach ($listtags as $tag) {
			$groupids = array();
			foreach ($listgroups as $group){
				$idcheck = 'line-'.$tag->term_id.'-'.$group->term_id;
				if ( isset($_POST[$idcheck]) ) {
					$groupids[]= (int) $group->term_id;
				}
			}
			$datavisible = 'hidden_termid-'.$tag->term_id;
			// if ( isset($_POST[$datavisible]) )
				wp_set_object_terms((int) $tag->term_id, $groupids, $this->tidy_taxonomy,false);
		}

		return ;
	}

	function on_load_page2() {
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');

			$pre = ( $this->post_tag == 'post_tag') ? ''  : $this->post_tag.'_' ;
			add_meta_box($pre.'xili_tidy_tags-sidebox-1', __('Message', 'xili_tidy_tags'), array(&$this,'on_sidebox_msg_content'), $this->thehook2 , 'side', 'core');
			add_meta_box($pre.'xili_tidy_tags-sidebox-3', __('Info', 'xili_tidy_tags'), array(&$this,'on_sidebox_info_content'), $this->thehook2 , 'side', 'core');
	}

	/**
	 * @since 1.5.0
	 * @updated 2.10.3
	 */
	function admin_enqueue_scripts() {
		wp_enqueue_script( 'datatables-v10' );
	}

	function admin_enqueue_styles() {
		wp_enqueue_style('table_style-v10');
	}


	/******************************* TAXONOMIES LIST ****************************/

	/**
	 * add in edit list language column
	 *
	 * @since 1.7
	 *
	 *
	 */
	function xili_manage_tax_column_name ( $cols ) {

		//if ( class_exists ('xili_language' ) ) {
			$ends = array('posts');
			$end = array();
			foreach( $cols AS $k=>$v ) {
				if(in_array($k, $ends)) {
					$end[$k] = $v;
					unset($cols[$k]);
				}
			}
			if ( defined ( 'TAXONAME' ) )
				$cols[TAXONAME] = __('Language','xili-language');
			// for grouping
			$cols['alias_of'] = __('Alias of','xili_tidy_tags');
			$cols = array_merge($cols, $end);


			///$this->local_theme_mos = $this->get_localmos_from_theme() ;
		//}
		return $cols;
	}

	function xili_manage_tax_column ( $dummy, $name, $id ) {

		global $wpdb, $taxonomy ;
		$tax = get_term((int)$id , $taxonomy ) ;
		$a = "";

		if ( defined ( 'TAXONAME' ) && $name == TAXONAME ) {

			//$a .= __( 'translated in:', 'xili_tidy_tags' )." ";

			// loop of languages
			$listlanguages = get_terms( TAXONAME, array('hide_empty' => false ) );

			foreach ( $listlanguages as $lang) {

			// test in group xx_yy-tidy-languages-group
				if ( is_object_in_term( $id, $this->tidy_taxonomy, $lang->slug. '-'. $this->xili_settings['tidylangsgroup'] ) ) {
			// link + class
					$group = term_exists ( $lang->slug. '-'. $this->xili_settings['tidylangsgroup'] , $this->tidy_taxonomy ) ;
			        $nonce_url = wp_nonce_url ('admin.php?page=xili_tidy_tags_assign&tps='.$group['term_id'] , 'xtt-tps'  ) ; // wp-admin/admin.php?page=xili_tidy_tags_assign&tps=8

					$a .=  '<span title="'. sprintf(__('Tags in %s.','xili_tidy_tags'), $lang->description ) .'" class="curlang lang-'. $lang->slug .'"><a href="'. $nonce_url .'" >' . $lang->name . '</a></span>' ;
				}
			}
			if ( $a != "" ) {
				$a = '<div class="edittag" >'. __( 'assigned in:', 'xili_tidy_tags' )." " . $a ;
				$a .= '</div>';
		  		return $a;
			}

		} else if ( $name == 'alias_of' ) {

			if ( $tax->term_group > 0 ) {
				// search terms of same group
				$alias_group = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->terms WHERE term_group = %s", $tax->term_group) );

				if ( $alias_group ) {
					foreach ( $alias_group as $one_alias ) {
						if ( $one_alias->slug != $tax->slug ) {
							$link = get_edit_tag_link( $one_alias->term_id, $taxonomy );
							$a .= '<a href="'.$link.'">' . $one_alias->name . '</a>';
							//if ( class_exists('xili_language') ) {
							 	if ( $langs = $this->return_lang_of_tag ( $one_alias->term_id ) ) {
							 		foreach ( $langs as $lang ) {
										$a .=  ' <span title="'. sprintf(__('Tags in %s.','xili_tidy_tags'), $lang->description ) .'" class="curlang lang-'. $lang->slug .'"><a href="'.$link.'">' . $lang->name . '</a></span>' ;
							 		}
							 	}
							//}
							$a .= '<br />';
						}
					}
				}
				if ( $a != "" ) {
					$a = '<div class="edittag" ><em>'. __( 'Alias:', 'xili_tidy_tags' )."</em><br />" . $a ;
					$a .= '</div>';
			  		return $a;
				}
			}
			return $a;
		}
	}


	/**
	 * in post edit UI if new term was created - give it a group
	 *
	 * @since 0.9.4
	 *
	 *
	 */
	function xili_tags_grouping ($post_ID) {

		if (!class_exists('xili_language')) return ; /* only used if present */

			$list_tags = wp_get_object_terms($post_ID, $this->post_tag );
			if ( !$list_tags )
				return ; /* no tag*/
			$post_curlang = get_cur_language($post_ID);

			$listlanggroups = get_terms($this->tidy_taxonomy, array('hide_empty' => false,'parent' => $this->langgroupid));
			if ($listlanggroups) {
				foreach ($listlanggroups as $curgroup) {
					$langsgroup[] = $curgroup->term_id;
				}
				$langsgroup[] = $this->langgroupid; /* add group parent */
				foreach ( $list_tags as $tag ) { /* test if the tag is owned by a group */
					$nbchecked = false;
					foreach ($langsgroup as $onelanggroup) {
						if (is_object_in_term($tag->term_id,$this->tidy_taxonomy,$onelanggroup)) {
							$nbchecked = true ;
						}
					}
					if ($nbchecked == false) {
						if ($post_curlang == false) { /* add to group parent */
						    wp_set_object_terms((int) $tag->term_id, (int) $this->langgroupid, $this->tidy_taxonomy,false);
						} else {
							$res = term_exists ($post_curlang,$this->tidy_taxonomy);
							wp_set_object_terms((int) $tag->term_id, (int) $res ['term_id'], $this->tidy_taxonomy,false);
						}
					}
				}
			}
	}

	/**
	 * a new term was created - grouping is assigned
	 *
	 * @since 0.8.0
	 * @updated 1.2.1, 1.10 - see xili_edited_term
	 *
	 */
	function xili_created_term ( $term_id, $tt_id ) {
		/* check if it is a term from $this->post_tag  */
		if ( !isset($_POST['_inline_edit']) ) { /* to avoid delete relationship when in quick_edit (edit-tags.php) */

			$term = get_term( (int) $term_id, $this->post_tag  );
			if ( $term ) {
				$listgroups = get_terms( $this->tidy_taxonomy, array('hide_empty' => false,'get'=>'all') );
				$groupids = array();
				foreach ($listgroups as $group) {
					$idcheck = 'group-'.$group->term_id;
					if (isset($_POST[$idcheck])) {

							$groupids[]= (int) $group->term_id;
					}
				}
				wp_set_object_terms( $term_id, $groupids, $this->tidy_taxonomy, false );
			}
		}
	}


	/**
	 * a new term was edited - grouping is assigned
	 *
	 * @since 1.10
	 *
	 */
	function xili_edited_term ( $term_id, $tt_id ) {
		// to avoid calls when aliases are updated
		if ( isset($_POST['action'] ) && $_POST['action'] == "add-tag" ) return; // when called in add tag screen

		if ( isset($_POST['tag_ID'] ) && $_POST['tag_ID'] != $term_id ) return; // when called in edit tag screen (modify alias)

		/* check if it is a term from $this->post_tag  */
		if (!isset($_POST['_inline_edit']) ) { /* to avoid delete relationship when in quick_edit (edit-tags.php) */

			$term = get_term( (int) $term_id, $this->post_tag  );
			if ( $term ) {
				$listgroups = get_terms( $this->tidy_taxonomy, array('hide_empty' => false,'get'=>'all') );
				$groupids = array();
				foreach ($listgroups as $group) {
					$idcheck = 'group-'.$group->term_id;
					if (isset($_POST[$idcheck])) {

							$groupids[]= (int) $group->term_id;
					}
				}
				wp_set_object_terms( $term_id, $groupids, $this->tidy_taxonomy, false );
			}
		}
	}


	/**
	 * add in new tag form to choose a group for a new tag
	 *
	 * @since 0.8.0
	 * @update 1.6.2 - sorted display
	 *
	 */
	function xili_add_tag_form() {
		$listtagsgroups = get_terms( $this->tidy_taxonomy, array('hide_empty' => false,'get'=>'all') );
		if ( !is_wp_error( $listtagsgroups ) && $listtagsgroups ) {
			$listtagsgroupssorted = walk_TagGroupList_sorted( $listtagsgroups, 3 , null, null );
			$checkline ='';
			$i = 0;
			foreach ( $listtagsgroupssorted as $group ) {
				if ( $group->parent != 0 ) {
					$disp_group = $group->name ;
					$checkline .= '&nbsp;-&nbsp;';
				} else {
					if ( $group->slug == $this->xili_settings['tidylangsgroup'] ) {
						$lang_class = true ;
					} else {
						$lang_class = false ;
					}
					$disp_group = "<strong>". $group->name . "</strong>" ;
					if ( $i != 0 ) $checkline .= '</br>';
				}
				if ( $lang_class ) {
					 $the_class = 'class="curlang lang-'.str_replace ( '-'.$this->xili_settings['tidylangsgroup'], '', $group->slug).'"';
					 $disp_group = '<span '.$the_class.' title="'.$group->name.'" >'.$disp_group .'</span>';
				}
				$checkline .= '<input type="checkbox" id="group-'.$group->term_id.'" name="group-'.$group->term_id.'" value="'.$group->term_id.'" />' . $disp_group ;
				$i++;
			}
			$checkline .='<br /><br /><small>'. sprintf(__('© by xili-tidy-tags v. %s','xili_tidy_tags'), XILITIDYTAGS_VER ) .'</small>';
			// display
			echo '<div id="xtt-edit-tag" style="max-width:525px; margin:2px 22px 2px 2px; padding:3px; border:1px solid #ccc;"><label>'.sprintf( __('%s groups','xili_tidy_tags'), $this->tags_name ) . ':</label><br />'.$checkline.'</div>';
		} else {
			echo '<p style="color:red"> ERROR IN xili-tidy-tags TAXONOMY: '.$this->tidy_taxonomy.'</p>'; // 1.8.3
		}

	}


	/**
	 * add in edit tag form to choose a group for a edited tag
	 *
	 * @since 0.8.0
	 *
	 *
	 */
	function xili_add_tag_alias ( $tag ) {
		global $wpdb;
		$checkline = '';


		$val = ( is_string( $tag ) ) ?  __('new', 'xili_tidy_tags').' ' : '' ;

		$context = ( class_exists('xili_language') ) ? __('in another language','xili_tidy_tags') : __( 'as synonym', 'xili_tidy_tags' );

		$checkline = '<p>'. sprintf(__('This sub-form contains a field to define alias of this %1$s%2$s. Choose a %2$s already present %3$s.','xili_tidy_tags'), $val, $this->singular_name, $context ) .'</p>';

		if ( !is_string( $tag ) &&  $tag->term_group > 0 ) {

			$alias_group = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->terms WHERE term_group = %s", $tag->term_group) );

				if ( $alias_group ) {

					$checkline .= '<p><em>' . sprintf( __('This %1$s (%2$s) is already present in the group %3$s:','xili_tidy_tags'), $this->singular_name, $tag->name , _n( sprintf(_x('of the following %s','singular','xili_tidy_tags'), $this->singular_name), sprintf(_x('of the following %s','plural','xili_tidy_tags'), $this->tags_name), count($alias_group)-1 )  )  . '</em></p>';
					$list = array();
					foreach ( $alias_group as $one_alias ) {
						if ( $one_alias->slug != $tag->slug ) {

							$list[] = $one_alias->name ;
						}
					}
					$checkline .= implode ( ' - ', $list );
					$checkline .='<br /><br />';

					$checkline .= '<p><em>' . sprintf( __('Select below if you want to change the group of this %1$s (%2$s). Be careful!','xili_tidy_tags'), $this->singular_name, $tag->name )  . '</em></p>';
				}

		}

		$checkline .= '<select name="alias_of" id="alias_of">'; // to be used when saved id= new field

		// check another tags
		$tags = get_terms( $this->post_tag, array( 'hide_empty' => false,'get'=>'all' ) );

		if ( ! is_wp_error( $tags ) ) {
			$checkline .= '<option value="">'.__('Choice alias...','xili_tidy_tags').'</option>';
			foreach ($tags as $one_tag) {

				if ( class_exists('xili_language') ) {

					$langs = $this->return_lang_of_tag ( $one_tag->term_id );

					if ( $langs ) {
						$listnames  = array ();
						foreach ( $langs  as $onelang ) {

							$listnames[] = $onelang -> name ;
						}

						$lang_line = ' ['. implode ( ' | ', $listnames ) . ']';

					} else {
						$lang_line = '';
					}
				} else {
					$lang_line = '';
				}

				$checkline .= '<option value="'.$one_tag->slug.'">' . $one_tag->name . $lang_line . '</option>';
			}

			$checkline .= '</select>';
		}

		$checkline .='<br /><br /><small>'. sprintf(__('© by xili-tidy-tags v. %s','xili_tidy_tags'), XILITIDYTAGS_VER ) .'</small>';

		echo '<div id="xtt-edit-tag" style="max-width:525px; margin:2px 22px 2px 2px; padding:3px; border:1px solid #ccc;"><label>'.sprintf( __('The %s aliases group','xili_tidy_tags'), $this->tags_name ) .':<br /></label><br />'.$checkline.'</div>';

	}


	/**
	 * add in edit tag form to choose a group for a edited tag
	 *
	 * @since 0.8.0
	 *
	 *
	 */
	function xili_edit_tag_form( $tag ) {
		$listtagsgroups = get_terms( $this->tidy_taxonomy, array( 'hide_empty' => false,'get'=>'all' ) );

		if ( !is_wp_error( $listtagsgroups ) && $listtagsgroups ) {
			$listtagsgroupssorted = walk_TagGroupList_sorted( $listtagsgroups, 3 , null, null );
			$checkline ='';
			$i = 0;
			foreach ($listtagsgroupssorted as $group) {
				/* add checked="checked" */
				if ( is_object_in_term( $tag->term_id, $this->tidy_taxonomy, (int) $group->term_id) ) {
						$checked = 'checked="checked"';
					} else {
						$checked = '';
					}
				if ( $group->parent != 0 ) {
					$disp_group = $group->name ;
					$checkline .= '&nbsp;-&nbsp;';
				} else {

					if ( $group->slug == $this->xili_settings['tidylangsgroup'] ) {
						$lang_class = true ;
					} else {
						$lang_class = false ;
					}
					$disp_group = "<strong>". $group->name . "</strong>" ;
					if ( $i != 0 ) $checkline .= '</br>';
				}
				if ( $lang_class ) {
					 $the_class = 'class="curlang lang-'.str_replace ( '-'.$this->xili_settings['tidylangsgroup'], '', $group->slug).'"';
					 $disp_group = '<span '.$the_class.' title="'.$group->name.'" >'.$disp_group .'</span>';
				}
				$checkline .= '<input type="checkbox" name="group-'.$group->term_id.'" id="group-'.$group->term_id.'" value="'.$group->term_id.'" '.$checked.' />'. $disp_group ;
				$i++;
			}
			$checkline .='<br /><br /><small>'. sprintf(__('© by xili-tidy-tags v. %s','xili_tidy_tags'), XILITIDYTAGS_VER ) .'</small>';
			// display
			echo '<div id="xtt-edit-tag" style="width:525px; margin:2px; padding:3px; border:1px solid #ccc;"><label>'.sprintf( __('%s groups','xili_tidy_tags'), $this->tags_name ) .':<br /></label><br />'.$checkline.'</div>';

		} else {
			echo '<p style="color:red"> ERROR IN xili-tidy-tags TAXONOMY: '.$this->tidy_taxonomy.'</p>'; // 1.8.3
			echo '<br /><br /><small>'. sprintf(__('© by xili-tidy-tags v. %s','xili_tidy_tags'), XILITIDYTAGS_VER ) .'</small>';
		}
	}




	/**
	 * Add action link(s) to plugins page
	 *
	 * @since 0.8.0
	 * @author MS
	 * @copyright Dion Hulse, http://dd32.id.au/wordpress-plugins/?configure-link and scripts@schloebe.de
	 *
	 * @updated 1.9 - fixes link when instancing...
	 */
	function xili_filter_plugin_actions($links, $file){
		static $this_plugin;

		if( !$this_plugin ) $this_plugin = plugin_basename( $this->file_file );

		if( $file == $this_plugin ){
			$suffix = ( 'post_tag' == $this->post_tag ) ? '' : '_'. $this->post_tag;
			$title = ( 'post_tag' == $this->post_tag ) ? __('Settings') : __('Settings'). ': '. $this->post_tag;
			$settings_link = '<a title="'. esc_attr( $title ) .'" href="admin.php?page=xili_tidy_tags_settings'.$suffix.'">' . __('Settings') . '</a>';
			$links = array_merge( array($settings_link), $links); // before other links
		}
		return $links;
	}

	/**
	 * Contextual help
	 *
	 * @since 1.5.0
	 */
	 function add_help_text($contextual_help, $screen_id, $screen) {

	  if ( false !== strpos( $screen->id,'xili_tidy_tags_settings' ) ) {
	    $to_remember =
	      '<p>' . __('Things to remember to set xili-tidy-tags:','xili_tidy_tags') . '</p>' .
	      '<ul>' .
	      '<li>' . __('If you use it for multilingual website: verify that the xili-language trilogy is active.','xili_tidy_tags') . '</li>' .
	      '<li>' . __('Update the list of targeted languages. See Actions left box.','xili_tidy_tags') . '</li>' .
	      '<li>' . __('For current site with lot of tags, create group or sub-group and go in assign page.','xili_tidy_tags') . '</li>' .
	      '<li>' . __('Don’t forget to activate a tags cloud widget.','xili_tidy_tags') . '</li>' .
	      '<li>' . __('In widget: the group for displaying tags in current lang is "the_curlang".','xili_tidy_tags') . '</li>' .
	      '<li>' . __('In widget: the group to set is the slug - trademark for TradeMark group - if you create one.','xili_tidy_tags') . '</li>' .
	      '</ul>' ;

	      $more_infos =
	      '<p><strong>' . __('For more information:') . '</strong></p>' .
	      '<p>' . __('<a href="http://dev.xiligroup.com/xili-tidy-tags" target="_blank">Xili-tidy-tags Plugin Documentation</a>','xili_tidy_tags') . '</p>' .
	      '<p>' . __('<a href="http://codex.wordpress.org/" target="_blank">WordPress Documentation</a>','xili_tidy_tags') . '</p>' .
	      '<p>' . __('<a href="https://wordpress.org/support/plugin/xili-tidy-tags" target="_blank">Support Forums</a>','xili_tidy_tags') . '</p>' ;

	      $screen->add_help_tab( array(
 				'id'      => 'to-remember',
				'title'   => __('Things to remember','xili-language'),
				'content' => $to_remember,
		  ));

	      $screen->add_help_tab( array(
 				'id'      => 'more-infos',
				'title'   => __('For more information', 'xili-language'),
				'content' => $more_infos,
		  ));


	  } else if ( false !== strpos( $screen->id, 'xili_tidy_tags_assign' ) ) {
	  	$to_remember =
	  	'<p>' . __('Things to remember to assign tags to groups:','xili_tidy_tags') . '</p>'.
	  	'<ul>' .
	      '<li>' . __('Use features of paginated table.','xili_tidy_tags') . '</li>' .
	      '<li>' . __('Don’t forget to update by clicking button in left - Actions - titled meta box.','xili_tidy_tags') . '</li>' .
	  	'</ul>';

	  	$more_infos =
	  	'<p><strong>' . __('For more information:') . '</strong></p>' .
	    '<p>' . __('<a href="http://dev.xiligroup.com/xili-tidy-tags" target="_blank">Xili-tidy-tags Plugin Documentation</a>','xili_tidy_tags') . '</p>' .
	    '<p>' . __('<a href="http://wiki.xiligroup.org/" target="_blank">Xili Wiki Documentation</a>','xili_tidy_tags') . '</p>' .
	    '<p>' . __('<a href="https://wordpress.org/support/plugin/xili-tidy-tags" target="_blank">Support Forums</a>','xili_tidy_tags') . '</p>' .
	    '<p>' . __('<a href="http://codex.wordpress.org/" target="_blank">WordPress Documentation</a>','xili_tidy_tags') . '</p>' ;


	    $screen->add_help_tab( array(
 				'id'      => 'to-remember',
				'title'   => __('Things to remember','xili_tidy_tags'),
				'content' => $to_remember,
		  ));

	    $screen->add_help_tab( array(
 				'id'      => 'more-infos',
				'title'   => __('For more information', 'xili_tidy_tags'),
				'content' => $more_infos,
		  ));


	  }
	  return $contextual_help;
	}

	// called by each pointer
	function insert_news_pointer ( $case_news ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer', false, array('jquery') );
			++$this->news_id;
			$this->news_case[$this->news_id] = $case_news;
	}
	// insert the pointers registered before
	function print_the_pointers_js (  ) {
		if ( $this->news_id != 0 ) {
			for ($i = 1; $i <= $this->news_id; $i++) {
				$this->print_pointer_js ( $i );
			}
		}
	}

	function print_pointer_js ( $indice  ) {  ;

		$args = $this->localize_admin_js( $this->news_case[$indice], $indice );
		if ( $args['pointerText'] != '' ) { // only if user don't read it before
		?>
		<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function() {

 	var strings<?php echo $indice; ?> = <?php echo json_encode( $args ); ?>;

	<?php /** Check that pointer support exists AND that text is not empty - inspired www.generalthreat.com */ ?>

	if(typeof(jQuery().pointer) != 'undefined' && strings<?php echo $indice; ?>.pointerText != '') {
		jQuery( strings<?php echo $indice; ?>.pointerDiv ).pointer({
			content    : strings<?php echo $indice; ?>.pointerText,
			position: { edge: strings<?php echo $indice; ?>.pointerEdge,
				at: strings<?php echo $indice; ?>.pointerAt,
				my: strings<?php echo $indice; ?>.pointerMy,
				offset: strings<?php echo $indice; ?>.pointerOffset
			},
			close  : function() {
				jQuery.post( ajaxurl, {
					pointer: strings<?php echo $indice; ?>.pointerDismiss,
					action: 'dismiss-wp-pointer'
				});
			}
		}).pointer('open');
	}
});
		//]]>
		</script>
		<?php
		}
	}


	/**
	 * News pointer for tabs
	 *
	 * @since 2.6.2
	 *
	 */
	function localize_admin_js( $case_news, $news_id ) {
 			$about = __('Docs about xili-tidy-tags', 'xili_tidy_tags');
 			$pointer_edge = '';
 			$pointer_at = '';
 			$pointer_my = '';
 			$pre = ( $this->post_tag == 'post_tag') ? ''  : '_'.$this->post_tag;

 		switch ( $case_news ) {

 			case 'xtt_new_version' :
 				$pointer_text = '<h3>' . esc_js( __( 'xili-tidy-tags updated', 'xili_tidy_tags') ) . '</h3>';
				$pointer_text .= '<p>' . esc_js( sprintf( __( 'xili-tidy-tags was updated to version %s', 'xili_tidy_tags' ) , XILITIDYTAGS_VER) ). '.</p>';
				$pointer_text .= '<p>' . esc_js( sprintf( __( 'This version %s is tested with WP 4.3', 'xili_tidy_tags' ) , XILITIDYTAGS_VER) ). '.</p>';
				$pointer_text .= '<p>' . esc_js( __( 'Now tags can be grouped using alias feature existing in WP taxonomy and two new template tags for theme are available for files tag.php and taxonomy.php.', 'xili_tidy_tags')). ',</p>';

				$pointer_text .= '<p>' . esc_js( __( 'See submenu', 'xili_tidy_tags' ).' “<a href="admin.php?page=xili_tidy_tags_settings'.$pre.'">'. __('to define groups of tags','xili_tidy_tags')."</a>”" ). '.</p>';

				$pointer_text .= '<p>' . esc_js( __( 'See submenu', 'xili_tidy_tags' ).' “<a href="admin.php?page=xili_tidy_tags_assign'.$pre.'">'. __('to assign tags to group','xili_tidy_tags')."</a>”" ). '.</p>';

				$pointer_text .= '<p>' . esc_js( sprintf(__( 'Before to question dev.xiligroup support, do not forget to visit %s documentation', 'xili_tidy_tags' ), '<a href="http://wiki.xiligroup.org" title="'.$about.'" >wiki</a>' ) ). '.</p>';

 				$pointer_dismiss = 'xd-new-version-'.str_replace('.', '-', XILITIDYTAGS_VER);
 				$pointer_div = '#toplevel_page_xili-tidy-tags';
 				$pointer_Offset = '0 0';
 				$pointer_edge = 'left';
 				$pointer_my = 'left';
 				$pointer_at = 'right';
				break;

			default: // nothing
				$pointer_text = '';
		}

 		// inspired from www.generalthreat.com
		// Get the list of dismissed pointers for the user
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		if ( in_array( $pointer_dismiss, $dismissed ) && $pointer_dismiss == 'xtt-new-version-'.str_replace('.', '-', XILITIDYTAGS_VER) ) {
			$pointer_text = '';

		} elseif ( in_array( $pointer_dismiss, $dismissed ) ) {
			$pointer_text = '';
		}

		return array(
			'pointerText' => html_entity_decode( (string) $pointer_text, ENT_QUOTES, 'UTF-8'),
			'pointerDismiss' => $pointer_dismiss,
			'pointerDiv' => $pointer_div,
			'pointerEdge' => ( '' == $pointer_edge ) ? 'top' : $pointer_edge ,
			'pointerAt' => ( '' == $pointer_at ) ? 'left top' : $pointer_at ,
			'pointerMy' => ( '' == $pointer_my ) ? 'left top' : $pointer_my ,
			'pointerOffset' => $pointer_Offset,
			'newsID' => $news_id
		);
    }






	/**
	 * private functions for xili-tidy-tags settings
	 *
	 * @since 0.8.0
	 *
	 * fill the content of the boxes (right side and normal)
	 *
	 */
	function  on_sidebox_msg_content($data=array()) {
		extract($data);
		?>
	 	<h4><?php _e('Note:','xili_tidy_tags') ?></h4>
		<p><?php echo $message;?></p>
		<?php
	}

	function  on_sidebox_info_content($data=array()) {
	 	extract($data);
	 	if ($xili_tidy_tags_page == 'settings') {
	 		echo '<p style="margin:2px; padding:3px; border:1px solid #ccc;">'.__('On this page, the tags groups are defined. The special groups for xili-language plugin are importable.<br /> For debug, some technical infos are displayed in the tables or boxes.<br />','xili_tidy_tags').'</p>';
	 	} elseif ($xili_tidy_tags_page == 'assign') {
	 		echo '<p style="margin:2px; padding:3px; border:1px solid #ccc;">'.__('On this page, in a oneshot way, it is possible to assign the tags to one or more groups defined on the other page of <i>xili-tidy-tags</i> plugin.','xili_tidy_tags').'</p>';
	 	}	 ?>
		<p><?php _e('<b>xili-tidy-tags</b> is a tool for grouping tags by language or semantic group. Initially developed to enrich multilingual website powered by xili-language plugin.','xili_tidy_tags') ?></p>
		<?php
	}

	/*
	 * Admin capabilities setting box
	 * @since 0.9.5
	 *
	 * @updated 1.4.0
	 * Only visible if admin (cap : update_plugins)
	 */
	function  on_sidebox_admin_content($data=array()) {
			$editor_set = $this->xili_settings['editor_caps'];
			$selected2 = "";
			$selected3 = "";
			if ( $editor_set == "caps_setting_grouping"  )
	  		{
	  			$selected3 = ' selected = "selected"';
	  		} elseif ( $editor_set == "caps_grouping" ) {
	  			$selected2 = ' selected = "selected"';
			}
	 	?>
	 	<div style="margin:2px; padding:3px; border:1px solid #ccc;">
		<p><?php _e('Here, as admin, set capabilities of the editor:','xili_tidy_tags') ?></p>
		<select name="editor_caps" id="editor_caps" style="width:80%;">
  				<option value="no_caps" ><?php _e('no capability','xili_tidy_tags'); ?></option>
  				<option value="caps_grouping" <?php echo $selected2;?>><?php _e('Grouping','xili_tidy_tags');  ?></option>
  				<option value="caps_setting_grouping" <?php echo $selected3;?>><?php _e('Setting and grouping','xili_tidy_tags');?></option>
  		</select><br /><br />
  		<label for="datatable_js"><?php _e('Disable Datatable javascript','xili_tidy_tags') ?> : <input id="datatable_js" name="datatable_js" type="checkbox" value="disable" <?php checked( $this->xili_settings['datatable_js'], 'disable' ); ?> /></label>

  		<?php
  		echo'<p class="submit"><input type="submit" name="editor_caps_submit" value="'.__('Set &raquo;','xili_tidy_tags').'" /></p></div>';
	}

	/**
	 * Get_terms without annoying cache
	 *
	 * @since 1.5.0
	 */
	function no_cache_get_terms ($taxonomy, $args) {
		global $wpdb;
		$defaults = array('orderby' => 'name', 'order' => 'ASC',
		'hide_empty' => true, 'exclude' => array(), 'exclude_tree' => array(), 'include' => array(),
		'number' => '', 'fields' => 'all', 'slug' => '', 'parent' => '',
		'hierarchical' => true, 'child_of' => 0, 'get' => '', 'name__like' => '',
		'pad_counts' => false, 'offset' => '', 'search' => '');
		$args = wp_parse_args( $args, $defaults );
		extract($args, EXTR_SKIP);
		$orderby = 't.name';
		if ( !empty($orderby) )
			$orderby = "ORDER BY $orderby";
		else
			$order = '';
		$where = "";
		if ( '' !== $parent ) {
			$parent = (int) $parent;
			$where .= " AND tt.parent = '$parent'";
		}

		$selects = array();
		switch ( $fields ) {
	 		case 'all':
	 			$selects = array('t.*', 'tt.*');
	 			break;
	 		case 'ids':
			case 'id=>parent':
	 			$selects = array('t.term_id', 'tt.parent', 'tt.count');
	 			break;
	 		case 'names':
	 			$selects = array('t.term_id', 'tt.parent', 'tt.count', 't.name');
	 			break;
	 		case 'count':
				$orderby = '';
				$order = '';
	 			$selects = array('COUNT(*)');
	 	}
	 	$select_this = implode(', ', apply_filters( 'get_terms_fields', $selects, $args ));
    	$query = "SELECT $select_this FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ( %s ) $where $orderby $order";

// $taxonomy set as param (wp 3.9 - placeholder notice )
		$terms = $wpdb->get_results($wpdb->prepare( $query, $taxonomy ) );

		return $terms;
	}

	/*
	 * Action's box
	 */
	function  on_sidebox_import_content($data=array()) {
	 	extract($data);
	 	if ( $this->post_tag == 'post_tag' ) { // 1.5.5
		 	echo '<div style="margin:2px; padding:3px; border:1px solid #ccc;">';
		 	echo '<p>'.__('Add a tag\'s group for a chosen category','xili_tidy_tags').'</p>';
		 	/* build the selector of available categories */
		 	$categories = get_categories(array('get'=>'all')); /* even if cat is empty */
		 	echo '<select name="catsforgroup" id="catsforgroup" style="width:100%;">';
			  				echo '<option value="no" >'.__('choose a category','xili_tidy_tags').'</option>';
		 	foreach ($categories as $cat) {
		 		$catinside = term_exists ($cat->slug,$this->tidy_taxonomy);
				if ($catinside == 0 && $cat->term_id != 1)
					echo '<option value="'.$cat->term_id.'" >'.$cat->name.'</option>';
			}
		 	echo '</select>';
		 	echo '<p>'.__('Choose a parent tag\'s group','xili_tidy_tags').'</p>';
		 	$listterms = get_terms($this->tidy_taxonomy, array('hide_empty' => false));
			?>
			<select name="tags_parent" id="tags_parent" style="width:100%;">
	  				<option value="no_parent" ><?php _e('no parent','xili_tidy_tags'); ?></option>
			<?php

			foreach ($listterms as $curterm) {
				if ($curterm->parent == 0 && $curterm->term_id != $this->langgroupid)
					echo '<option value="'.$curterm->term_id.'" >'.$curterm->name.'</option>';
			} ?>
			</select>
			<br />
		    <?php
		 	echo '<p class="submit"><input type="submit" name="importacat" value="'.__('Add &raquo;','xili_tidy_tags').'" /></p>';
		 	echo '<p>'.__('See docs to set xili_tidy_tag_cloud function or widget to implement in theme…','xili_tidy_tags').'</p>';
		 	echo '</div>';
	 	}
	 	echo '<div style="margin:2px; padding:3px; border:1px solid #ccc;">';
	 	if ( !class_exists('xili_language') ) { ?>
	 		<p class="submit"><?php _e('xili-language plugin is not activated.','xili_tidy_tags') ?> </p>
	 		<?php
	 	} else {

	 		$res = term_exists ( $this->xili_settings['tidylangsgroupname']  ,$this->tidy_taxonomy);
	 		if ( is_array($res) ) $childterms = $this->no_cache_get_terms($this->tidy_taxonomy, array('hide_empty' => false, 'fields'=> 'all', 'parent' => $res['term_id']));
	 		//echo 'nb'.count($childterms) ;
	 		if ($res && !empty($childterms)) {
		 		?>
				<p><?php _e('The group of languages is set for use with xili-language plugin.','xili_tidy_tags') ?> </p>
				<?php

				if ( current_user_can( 'xili_tidy_admin_set' ) ) { //current_user_can( 'xili_tidy_admin_set' )
					$langinxtt = array();
					foreach ( $childterms as $childterm ) {
			 			$langinxtt[] = $childterm -> name ;
					}

					$langinxl = array();
					$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
					foreach ( $listlanguages as $language ) {
			 			$langinxl[] = $language -> name ;
					}

			 		if ( array_diff( $langinxl, $langinxtt ) != array() ) { // since 1.5.0
			 		$s = implode( ', ', array_diff( $langinxl, $langinxtt ) );
			 		?>
						<p><?php printf(__('The group of languages in xili-language plugin has been changed.','xili_tidy_tags').' ( %s )', $s ); ?> </p>
					<?php
					echo '<p class="submit">'.__('It is possible to update the group of languages.','xili_tidy_tags').'</p>';
			 		echo '<p class="submit"><input type="submit" name="importxililanguages" value="'.__('Update &raquo;','xili_tidy_tags').'" /></p>';
			 		}
			 		if ( array_diff( $langinxtt, $langinxl ) != array() ) {// ( count($langinxtt) != count($langinxl) )
			 		$s = implode( ', ', array_diff( $langinxtt, $langinxl ) );
			 			echo '<p class="submit">'.sprintf(__('One or more language(s) here are not present in active xili-language list.','xili_tidy_tags').' ( %s )', $s ).'</p>';
			 		}
				}

		 	} else { /* since 0.9.5 */
		 		if ( current_user_can( 'xili_tidy_admin_set') ) {
			 		$count_xl = wp_count_terms(TAXONAME); /* count a minima one language */
			 		if ( $count_xl > 0 ) {
			 			echo '<p class="submit">'.__('It is possible to import the group of languages.','xili_tidy_tags').'</p>';
			 			echo '<p class="submit"><input type="submit" name="importxililanguages" value="'.__('Import…','xili_tidy_tags').'" /></p>';

			 		} else {
			 			echo '<p class="submit">'.__('Go to settings of xili-language plugin and add languages','xili_tidy_tags').'</p>';
			 		}
		 		} else {
		 			echo '<p class="submit">'.__('See administrator for language settings.','xili_tidy_tags').'</p>';
		 		}
		 	}
	 	}
	 	echo '</div>';

	}

	function  on_normal_group_tags_list_content($data=array()) {
	 	extract($data); ?>

					<table class="widefat" style="clear:none;">
						<thead>
						<tr>
						<th scope="col" style="text-align: center"><?php _e('ID','xili_tidy_tags') ?></th>
	        			<th scope="col"><?php _e('Name','xili_tidy_tags') ?></th>
	        			<th scope="col"><?php _e('Description','xili_tidy_tags') ?></th>
	        			<th scope="col"><?php _e('Group slug','xili_tidy_tags') ?></th>
	        			<th scope="col"><?php _e('Group taxo ID','xili_tidy_tags') ?></th>
	        			<th scope="col"><?php _e('Parent','xili_tidy_tags') ?></th>
	        			<th scope="col" width="90" style="text-align: center"><?php _e('Tags') ?></th>
	        			<th colspan="2" style="text-align: center"><?php _e('Action') ?></th>
						</tr>
						</thead>
						<tbody id="the-list">
							<?php $this->xili_tags_group_row(); /* the lines */ ?>
						</tbody>
					</table>

		<?php
	}

	function on_normal_group_form_content( $data=array() ) {
		extract( $data ); /* form to add or edit group */
		?>

		<h2 id="addgroup" <?php if ($action=='delete') echo 'style="color:#FF1111;"'; ?>><?php echo $formtitle; ?></h2>
		<?php if ($action=='edit' || $action=='delete') :?>
			<input type="hidden" name="tagsgroup_term_id" value="<?php echo $tagsgroup->term_id ?>" />
			<input type="hidden" name="tagsgroup_parent" value="<?php echo $tagsgroup->parent ?>" />
		<?php endif; ?>
		<table class="editform" width="100%" cellspacing="2" cellpadding="5">
			<tr>
				<th width="33%" scope="row" valign="top" align="right"><label for="tagsgroup_name"><?php _e('Name','xili_tidy_tags') ?></label>:&nbsp;</th>
				<td width="67%"><input name="tagsgroup_name" id="tagsgroup_name" type="text" value="<?php if (isset($tagsgroup)) echo esc_attr( $tagsgroup->name ); ?>" size="40" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="tagsgroup_nicename"><?php _e('tags group slug','xili_tidy_tags') ?></label>:&nbsp;</th>
				<td><input name="tagsgroup_nicename" id="tagsgroup_nicename" type="text" value="<?php if (isset($tagsgroup)) echo esc_attr($tagsgroup->slug); ?>" size="40" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="tagsgroup_description"><?php _e('Description','xili_tidy_tags') ?></label>:&nbsp;</th>
				<td><input name="tagsgroup_description" id="tagsgroup_description" size="40" value="<?php if (isset($tagsgroup)) echo esc_attr($tagsgroup->description); ?>" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>

			</tr>
			<tr class="form-field">
				<th scope="row" valign="top" align="right"><label for="tagsgroup_parent"><?php _e('kinship','xili_tidy_tags') ?></label> :&nbsp;</th>
				<td>
			  	<?php if (isset($tagsgroup)) {
			  			$this->xili_selectparent_row($tagsgroup->term_id, $tagsgroup, $action);
			  			/* choice of parent line*/
			  	} else {
			  		$this->xili_selectparent_row(0, $tagsgroup, $action);
			  	}
			  			?>
             	</td>
			</tr>
			<tr>
			<th><p class="submit"><input type="submit" name="reset" value="<?php echo $cancel_text ?>" /></p></th>
			<td>
			<p class="submit"><input type="submit" name="submit" value="<?php echo $submit_text ?>" /></p>
			</td>
			</tr>
		</table>
	<?php
	}

	function xili_selectparent_row($term_id=0,$tagsgroup,$action) {
		if ($term_id == 0) {
				$listterms = get_terms($this->tidy_taxonomy, array('hide_empty' => false));
				?>
				<select name="tagsgroup_parent" id="tagsgroup_parent" style="width:100%;">
		  				<option value="no_parent" ><?php _e('no parent','xili_tidy_tags'); ?></option>
				<?php
				foreach ($listterms as $curterm) {
					if ($curterm->parent == 0) {
						if ( current_user_can( 'xili_tidy_admin_set') ) {
							$possible = true;
						} elseif  ($curterm->term_id == $this->langgroupid) {
							$possible = false;
						} else {
							$possible = true;
						}
						if ($possible)
							echo '<option value="'.$curterm->term_id.'" >'.$curterm->name.'</option>';
					}
				} ?>
				</select>
				<br />
	    		<?php _e('Select the parent if necessary','xili_tidy_tags');
	     	} else {
	     		if ($tagsgroup->parent == 0) {
	     			$listterms = get_terms($this->tidy_taxonomy, array('hide_empty' => false,'parent' => $term_id));
					// display childs
					if (!empty($listterms)) {
						echo __('parent of: ','xili_tidy_tags');
						echo '<ul>';
						foreach ($listterms as $curterm) {
							echo '<li value="'.$curterm->term_id.'" >'.$curterm->name.'</li>';
						}
						echo '</ul>';
					} else {
						echo __('no child now','xili_tidy_tags')."<br /><br />";
					}
					/* if modify*/
					if($action=='edit') {
						$listterms = get_terms($this->tidy_taxonomy, array('hide_empty' => false));
				?>
				<select name="tagsgroup_parent" id="tagsgroup_parent" style="width:100%;">
		  				<option value="no_parent" ><?php _e('no parent','xili_tidy_tags'); ?></option>
				<?php
					foreach ($listterms as $curterm) {
						if ($curterm->parent == 0 && $curterm->term_id != $term_id)
							echo '<option value="'.$curterm->term_id.'" >'.$curterm->name.'</option>';
					} ?>
				</select>
				<br />
	    		<?php _e('Select the parent if necessary','xili_tidy_tags');

					}

	     		} else {
	     			/* if modify*/
	     			$parent_term = get_term( (int) $tagsgroup->parent,$this->tidy_taxonomy,OBJECT,'edit');
	     			if($action=='delete') {
	     				echo __('child of: ','xili_tidy_tags');
	     				echo $parent_term->name; ?>
	     					<input type="hidden" name="tagsgroup_parent" value="<?php echo $parent_term->term_id ?>" />
	     		<?php } else {
	     					$listterms = get_terms($this->tidy_taxonomy, array('hide_empty' => false));
	     			?>
							<select name="tagsgroup_parent" id="tagsgroup_parent" style="width:100%;">
		  					<option value="no_parent" ><?php _e('no parent','xili_tidy_tags'); ?></option>
				<?php
							foreach ($listterms as $curterm) {
								if ($curterm->parent == 0 && $curterm->term_id != $term_id) {
									//$checked = ($parent_term->term_id == $curterm->term_id) ? 'selected="selected"' :'' ;
									echo '<option value="'.$curterm->term_id.'" '. selected( $parent_term->term_id, $curterm->term_id, false ).' >'.$curterm->name.'</option>';
								}
							} ?>
							</select>
				<br />
	    		<?php _e('Modify the parent if necessary','xili_tidy_tags');
	     		}
	     	}
			}
	}



	/*
	 * Import or update the terms (languages) set by xili-language	 *
	 * @updated 1.5.0
	 */
	function xili_langs_import_terms () {

		$term = $this->xili_settings['tidylangsgroupname'];
		$error = false;
		$args = array( 'alias_of' => '', 'description' => 'default lang group', 'parent' => 0, 'slug' => $this->xili_settings['tidylangsgroup']  );
		$theids = term_exists ( $term, $this->tidy_taxonomy); // impossible to use get_term as formerly !!!
		if (!$theids) {
			$theids = wp_insert_term( $term, $this->tidy_taxonomy, $args);
			if (  !is_wp_error ( $theids ) )
				$this->langgroupid = $theids['term_id'];
			else
				$error = true;

		}
		if ($error ) {
			return false;
		} else {
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false ));
			foreach ($listlanguages as $language) {
				$args = array( 'alias_of' => '', 'description' => $language->description, 'parent' => $this->langgroupid, 'slug' =>$language->slug.'-'.$this->xili_settings['tidylangsgroup']  ); // slug to be compatible with former release of WP

				$ifhere = term_exists ( $language->name, $this->tidy_taxonomy );
				if (!$ifhere) {
					$res = wp_insert_term( $language->name, $this->tidy_taxonomy, $args );
				} else {
					$error = true;
				}
			}
			return !$error;
		}
	}

	/*
	 * Display the rows of group of tags
	 *
	 * @updated since 1.3.0 - use now walker class to sort Tag's groups
	 */
	function xili_tags_group_row() {
		$listtagsgroups = get_terms( $this->tidy_taxonomy, array('hide_empty' => false,'get'=>'all'));

		if ( is_wp_error( $listtagsgroups ) || $listtagsgroups == array() ) {
			/* import */
			if ( class_exists('xili_language') ) { /* xili-language is present */
				$res = $this->xili_langs_import_terms ();

			} else {
				/*create a default line with the default group*/
				$term = 'tidy group';
				$args = array( 'alias_of' => '', 'description' => 'default xili tidy tags group', 'parent' => 0);
				$resgroup = wp_insert_term( $term, $this->tidy_taxonomy, $args);
				$res = !is_wp_error ( $resgroup ) ;
			}
			if ( $res )
				$listtagsgroups = $this->no_cache_get_terms($this->tidy_taxonomy, array('hide_empty' => false,'get'=>'all'));

		} else {

		$listtagsgroupssorted = walk_TagGroupList_sorted( $listtagsgroups, 3 , null, null );
		$class = '';
		if ($listtagsgroupssorted) { // @since 1.4.2 - no warning if no groups at init
			foreach ($listtagsgroupssorted as $tagsgroup) {
				$class = ((defined('DOING_AJAX') && DOING_AJAX) || " class='alternate'" == $class ) ? '' : " class='alternate'";

				$tagsgroup->count = number_format_i18n( $tagsgroup->count );
				$posts_count = ( $tagsgroup->count > 0 ) ? "<a href='edit.php?lang=$tagsgroup->term_id'>$tagsgroup->count</a>" : $tagsgroup->count;
			    /* since 0.9.5 */
			    if ( current_user_can( 'xili_tidy_editor_set') ) { /* all admin only */
			    	$possible = true;
			    } elseif (current_user_can( 'xili_tidy_editor_set')) { /* editor if set */
			    	if ($tagsgroup->term_id == $this->langgroupid || $tagsgroup->parent == $this->langgroupid) {
			    		$possible = false;
			    	} else {
			    		$possible = true;
			    	}
			    } else {
			    	$possible = false;
			    }
			    $pre = ( $this->post_tag == 'post_tag') ? 'xili_tidy_tags_settings'  : 'xili_tidy_tags_settings_'.$this->post_tag ;
		    	if (true === $possible ) {
					$edit = "<a href='?page=".$pre."&amp;action=edit&amp;term_id=".$tagsgroup->term_id."' >".__( 'Edit' )."</a></td>";
					/* delete link &amp;action=edit&amp;term_id=".$tagsgroup->term_id."*/
					$edit .= "<td><a href='?page=".$pre."&amp;action=delete&amp;term_id=".$tagsgroup->term_id."' class='delete'>".__( 'Delete' )."</a>";
		    	} else {
		    		$edit = __('no capability','xili_tidy_tags').'</td><td>';
		    	}

				$line="<tr id='cat-$tagsgroup->term_id'$class>
				<th scope='row' style='text-align: center'>$tagsgroup->term_id</th>
				<td> ";
				$tabb = ($tagsgroup->parent != 0) ? " –" : "" ;
				$tagsgroupname = ($tagsgroup->parent == 0) ? "<strong>".$tagsgroup->name."</strong>": $tagsgroup->name;
				$line .= "$tabb $tagsgroupname</td>
				<td>$tagsgroup->description</td>
				<td>$tagsgroup->slug</td>
				<td>$tagsgroup->term_taxonomy_id</td>
				<td>$tagsgroup->parent</td>
				<td align='center'>$tagsgroup->count</td>
				<td>$edit</td>\n\t</tr>\n"; /*to complete*/
				echo $line;
			}
		}
		}

	}

	/**
	 * @updated 1.5.0 with datatables js (ex widefat)
	 *
	 */
	function on_sub_normal_tags_list_content ($data=array()) {
	 	extract($data); ?>
		<?php /**/ ?>
		<div id="topbanner">
		</div>
		<?php if ( $this->xili_settings['datatable_js'] == '' ) { ?>
		<div id="tableupdating" ><br /><br /><h2><?php _e('Creating table of tags !','xili_tidy_tags') ?></h2>
		</div>
		<table class="display" id="assigntable" style="visibility:hidden; clear:none; font-size:10px; line-height:1.8em;" >
		<?php } else { ?>
		<table class="display" id="assigntable" style="visibility:visible; clear:none;">
		<?php }?>

						<thead>
						<tr>
							<th scope="col" class="center colid" ><?php _e('ID','xili_tidy_tags') ?></th>
	        				<th scope="col" class="colname" ><?php _e('Name','xili_tidy_tags') ?></th>
	        				<th scope="col" class="center colposts"><?php _e('Posts') ?></th>
	        				<th colspan="2" class="colgrouping"><?php _e('Group(s) to choose','xili_tidy_tags') ?></th>
						</tr>
						</thead>
						<tbody id="the-list">
							<?php $this->xili_tags_row( $tagsnamelike, $tagsnamesearch ); /* the lines */?>
						</tbody>
						<tfoot>
							<tr>
								<th><?php _e('ID','xili_tidy_tags') ?></th>
								<th><?php _e('Name','xili_tidy_tags') ?></th>
								<th><?php _e('Posts') ?></th>
								<th><?php _e('Group(s) to choose','xili_tidy_tags') ?></th>
							</tr>
						</tfoot>
					</table>
		<div id="bottombanner">
		</div>


		<?php

	}
	/**
	 *
	 * @updated 1.5.3
	 */
	function  on_sub_sidebox_action_content( $data=array() ) {
	 	extract($data);?>
	 	<p><?php _e('After checking or unchecking do not forget to click update button !','xili_tidy_tags'); ?></p>
		<p class="submit"><input type="submit" class="button-primary" id="update" name="update" value="<?php echo $submit_text ?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="reset" value="<?php echo $cancel_text ?>" /></p>

		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('Sub list of tags','xili_tidy_tags'); ?></legend>

			<label for="tagsnamelike"><?php _e('Starting with:','xili_tidy_tags') ?></label>
			<input name="tagsnamelike" id="tagsnamelike" type="text" value="<?php echo esc_attr($tagsnamelike); ?>" /><br />

			<label for="tagsnamesearch"><?php _e('Containing:','xili_tidy_tags') ?></label>
			<input name="tagsnamesearch" id="tagsnamesearch" type="text" value="<?php echo esc_attr($tagsnamesearch); ?>" /><br /><br />
			<label for="tagsfromgroup"><?php _e('Choose:','xili_tidy_tags') ?></label>
			<?php $listtagsgroups = get_terms($this->tidy_taxonomy, array('hide_empty' => false));

			if ( !is_wp_error( $listtagsgroups ) && $listtagsgroups != array() ) {
				$listtagsgroupssorted = walk_TagGroupList_sorted( $listtagsgroups, 3, null, null );
				?>
					<select name="tagsgroup_from_select" id="tagsgroup_from_select" style="width:45%;">
			  				<option value="no_select" ><?php _e('every','xili_tidy_tags'); ?></option>
							<option value="onlyuncheckedtags" <?php selected( $this->onlyuncheckedtags ) ; ?>><?php _e('Unchecked only','xili_tidy_tags'); ?></option>
					<?php
					$show = false;

					foreach ($listtagsgroupssorted as $curterm) {
						$ttab = ($curterm->parent == 0) ? '' : '– ' ;
						if ($this->fromgroupselect == $curterm->term_id) {
							$selected =  'selected="selected"';
							if ( $curterm->parent == 0 ) {
								$listlanggroups = get_terms( $this->tidy_taxonomy, array('hide_empty' => true, 'parent' => $curterm->term_id ) );
								if ( !is_wp_error( $listlanggroups ) && $listlanggroups ) $show = true ;
							}
						} else {
							$selected = '' ;
						}
						echo '<option value="'.$curterm->term_id.'" '.$selected.' >'.$ttab.$curterm->name.'</option>';

					}

					?>
					</select>
					<?php
			}
				if( $this->onlyuncheckedtags == false &&  $this->fromgroupselect != 0 ) { ?>
				<br /><label for="xili_group_not_select"><?php _e('Exclude this group','xili_tidy_tags') ?> <input id="xili_group_not_select" name="xili_group_not_select" type="checkbox" value="not" <?php checked( $this->groupexclude, true ); ?> /></label>
				<?php if ($show) { ?>
				&nbsp;–&nbsp;<label for="xili_group_only_parent"><?php _e('No childs','xili_tidy_tags') ?> <input id="xili_group_only_parent" name="xili_group_only_parent" type="checkbox" value="onlyparent" <?php checked( $this->onlyparent, true); ?> /></label>
				<?php } ?>
				<br />
				<?php if($this->groupexclude == true) { ?>
					<label for="xili_uncheckedtags"><?php _e('Include unchecked','xili_tidy_tags') ?> <input id="xili_uncheckedtags" name="xili_uncheckedtags" type="checkbox" value="include" <?php checked( $this->uncheckedtags, true ); ?> /></label>
				<?php }
				}?>
			<p class="submit"><input type="submit" id="tagssublist" name="tagssublist" value="<?php _e('Sub select…','xili_tidy_tags'); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" id="notagssublist" name="notagssublist" value="<?php _e('No select…','xili_tidy_tags'); ?>" /></p>
		</fieldset>
		<?php /* only show one group to select */ ?>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('Columns: Group selection','xili_tidy_tags'); ?></legend>
			<?php if ( !is_wp_error( $listtagsgroups ) && $listtagsgroups != array() ) { ?>
				<select name="tagsgroup_parent_select" id="tagsgroup_parent_select" style="width:100%;">
		  				<option value="no_select" ><?php _e('No sub-selection','xili_tidy_tags'); ?></option>
				<?php
				foreach ($listtagsgroups as $curterm) {
					if ($curterm->parent == 0) {
						//$checked = ($this->subselect == $curterm->term_id) ? 'selected="selected"' :'' ;
						echo '<option value="' . $curterm->term_id . '" ' . selected( $this->subselect, $curterm->term_id, false ) . ' >' . $curterm->name . '</option>';
					}
				} ?>
				</select>
				<?php } ?>
				<br /> <p class="submit"><input type="submit" id="subselection" name="subselection" value="<?php _e('Sub select…','xili_tidy_tags'); ?>" /></p></fieldset><?php
	}
	/**
	 * The rows of the tags and checkboxes to assign group(s)
	 *
	 * @since 0.8.0
	 * @updated 1.3.0 - Call walker instantiation
	 * @uses
	 * @param
	 * @return the rows for admin ui
	 */
	function xili_tags_row( $tagsnamelike = '', $tagsnamesearch = '' ) {
		$listgroups = get_terms($this->tidy_taxonomy, array('hide_empty' => false,'get'=>'all'));

		if ( !is_wp_error( $listgroups ) && $listgroups != array() ) {
			$hiddenline = array ();
			$edit =''; $i=0;
			$listgroupids = array();
			$sub_listgroups = array();
			$subselectgroups = array();
			if ($this->subselect > 0) {
				$subselectgroups[] = $this->subselect; /* the parent group and */
				/*childs of */
				$listterms = get_terms($this->tidy_taxonomy, array('hide_empty' => false,'parent' => $this->subselect));
				if (!empty($listterms)) {
						foreach ($listterms as $curterm) {
								$subselectgroups[] = $curterm->term_id;
						}
				}
			}

			if (!empty($subselectgroups)) {	 /* columns sub-selection */
				foreach ($listgroups as $group) {
					$listgroupids[] = $group->term_id;
					if (in_array ($group->term_id,$subselectgroups)) {
						$sub_listgroups[] = $group;
					} else {
						 $hiddenline[] = $group->term_id ;	/* keep line values */
					}
				}
				$editformat = walk_TagGroupList_tree_row( $sub_listgroups, 3, null );
			} else {
				foreach ($listgroups as $group) {
					$listgroupids[] = $group->term_id;
				}
				$editformat = walk_TagGroupList_tree_row( $listgroups, 3, null );
			}

			if ( $this->fromgroupselect == 0 && $this->onlyuncheckedtags === false ) {
				$listtags = get_terms($this->post_tag, array('hide_empty' => false, 'get'=>'all','name__like'=>$tagsnamelike, 'search'=>$tagsnamesearch ));
			} else { /* since 1.3.0 */
				if ( $this->onlyuncheckedtags === false ) { // one group
					$group_id[] = $this->fromgroupselect;
					if ( $this->onlyparent === false ) {
						$childterms = get_terms($this->tidy_taxonomy, array('hide_empty' => false,'parent' => $this->fromgroupselect));
				 		if ( !empty($childterms) ) {
				 			foreach ( $childterms as $childterm ) { /* if group is a parent, add all childs */
				 			 	$group_id[] = $childterm->term_id;
				 			}
				 		}
					}
				} else { // only unchecked
					$listgroups = get_terms( $this->tidy_taxonomy, array('hide_empty' => false, 'get'=>'all') );
					foreach ( $listgroups as $group ) {
						$group_id[] = $group->term_id;
					}
					$this->groupexclude = true;
					$this->uncheckedtags = true;
				}
				$listtags = xtt_get_terms_of_groups_new ( $group_id, $this->tidy_taxonomy, $this->post_tag , array('hide_empty' => false, 'get'=>'all', 'name__like'=>$tagsnamelike, 'search'=>$tagsnamesearch, 'orderby'=>'name'), $this->groupexclude, $this->uncheckedtags );
			}

			$class = '';
			if ( ! is_wp_error (  $listtags ) ) {
				foreach ( $listtags as $tag ) {
					$class = ((defined('DOING_AJAX') && DOING_AJAX) || " class='alternate'" == $class ) ? '' : " class='alternate'";
					$tag->count = number_format_i18n( $tag->count );

					if ( $this->post_tag == 'post_tag' ) {
						$posts_count = ( $tag->count > 0 ) ? "<a href='edit.php?tag=$tag->name'>$tag->count</a>" : $tag->count;
					} else {
						$posts_count = ( $tag->count > 0 ) ? "<a href='edit.php?".$this->post_tag."=".$tag->name."&amp;post_type=".$this->post_tag_post_type."'>".$tag->count."</a>" : $tag->count;
					}
					$edit = sprintf( $editformat, $tag->term_id );
					$hiddenlines = "";
					foreach ($listgroupids as $groupid) {
						if ( is_object_in_term( $tag->term_id, $this->tidy_taxonomy, (int)$groupid ) ) {
							$edit = str_replace('"checked'.$groupid.'"', 'checked="checked"', $edit ); // 1.7
							if ( in_array( $groupid, $hiddenline ) )
								$hiddenlines .= '<input type="hidden" name="line-'.$tag->term_id.'-'.$groupid.'" value="'.$tag->term_id.'" />';
						} else {
							$edit = str_replace( '"checked'.$groupid.'"', '', $edit ); // 1.7
						}
					}
					//
					if ( $this->post_tag == 'post_tag' ) {
						$what = "&amp;tag_ID=".$tag->term_id ;
					} else {
						$what = "&amp;taxonomy=".$this->post_tag."&amp;tag_ID=".$tag->term_id."&amp;post_type=".$this->post_tag_post_type;
					}
					$line="<tr id='cat-$tag->term_id'$class>
					<td class='termid' id='termid-{$tag->term_id}' scope='row' style='text-align: center'>$tag->term_id</td>
					<td> <a href='edit-tags.php?action=edit".$what."'>".$tag->name."</a> </td>
					<td align='center'>$posts_count</td>
					<td>$edit\n$hiddenlines</td>\n\t</tr>\n"; /*to complete*/
					echo $line;
				}
			} else {
				echo '<tr><td>ERROR</td><td> in TAXONOMY: '. $this->tidy_taxonomy .'</td><td>ERROR</td><td>ERROR</td></tr>';
				if ( !taxonomy_exists ( $this->post_tag ) )
					echo '<tr><td>ERROR</td><td> TAXONOMY: '. $this->post_tag .' NOT SET</td><td>ERROR</td><td>ERROR</td></tr>'; // 1.9 fixes
			}
		} else {
			echo '<tr><td>ERROR</td><td> in TAXONOMY: '. $this->tidy_taxonomy .'</td><td>ERROR</td><td>ERROR</td></tr>';
		}
	}




	function check_other_xili_plugins () {
		$list = array();
		//if ( class_exists( 'xili_language' ) ) $list[] = 'xili-language' ;
		if ( class_exists( 'xili_language' ) ) $list[] = 'xili-language' ;
		if ( class_exists( 'xili_dictionary' ) ) $list[] = 'xili-dictionary' ;
		if ( class_exists( 'xilithemeselector' ) ) $list[] = 'xilitheme-select' ;
		if ( function_exists( 'insert_a_floom' ) ) $list[] = 'xili-floom-slideshow' ;
		if ( class_exists( 'xili_postinpost' ) ) $list[] = 'xili-postinpost' ;
		return implode (', ',$list) ;
	}

	function on_sidebox_mail_content ( $data ) {
		extract( $data );
		global $wp_version ;
		$theme = ( isset ($this->xili_settings['theme']) ) ? $this->xili_settings['theme'] : "";
		$wplang = ( isset ($this->xili_settings['wplang']) ) ? $this->xili_settings['wplang'] : "";
		$xiliplug = ( isset ($this->xili_settings['xiliplug']) ) ? $this->xili_settings['xiliplug'] : "";
		if ( '' != $emessage ) { ?>
	 		<h4><?php _e('Note:','xili_tidy_tags') ?></h4>
			<p><strong><?php echo $emessage;?></strong></p>
		<?php } ?>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo _e('Mail to dev.xiligroup', 'xili_tidy_tags'); ?></legend>
		<label for="ccmail"><?php _e('Cc: (Reply to:)','xili_tidy_tags'); ?>
		<input class="widefat" id="ccmail" name="ccmail" type="text" value="<?php bloginfo ('admin_email') ; ?>" /></label><br /><br />
		<?php if ( false === strpos( get_bloginfo ('url'), 'local' ) ){ ?>
			<label for="urlenable">
				<input type="checkbox" id="urlenable" name="urlenable" value="enable" <?php checked(( isset ($this->xili_settings['url']) && $this->xili_settings['url']=='enable'), true, true ); ?> />&nbsp;<?php bloginfo ('url') ; ?>
			</label><br />
		<?php } else { ?>
			<input type="hidden" name="onlocalhost" id="onlocalhost" value="localhost" />
		<?php } ?>
		<br /><em><?php _e('When checking and giving detailled infos, support will be better !', 'xili_tidy_tags'); ?></em><br />
		<label for="themeenable">
			<input type="checkbox" id="themeenable" name="themeenable" value="enable" <?php checked( $theme, 'enable', true ); ?> />&nbsp;<?php echo "Theme name= ".get_option ('stylesheet') ; ?>
		</label><br />
		<?php if (''!= $this->get_WPLANG() ) {?>
		<label for="wplangenable">
			<input type="checkbox" id="wplangenable" name="wplangenable" value="enable" <?php checked( $wplang,'enable', true ) ; ?> />&nbsp;<?php echo "WPLANG= ". $this->get_WPLANG() ; ?>
		</label><br />
		<?php } ?>
		<label for="versionenable">
			<input type="checkbox" id="versionenable" name="versionenable" value="enable" <?php if( isset ($this->xili_settings['version-wp']) ) checked( $this->xili_settings['version-wp'],'enable', true); ?> />&nbsp;<?php echo "WP version: ".$wp_version ; ?>
		</label><br /><br />
		<?php $list = $this->check_other_xili_plugins();
		if (''!= $list ) {?>
		<label for="xiliplugenable">
			<input type="checkbox" id="xiliplugenable" name="xiliplugenable" value="enable" <?php checked ( $xiliplug, 'enable', true ); ?> />&nbsp;<?php echo "Other xili plugins = ".$list ; ?>
		</label><br /><br />
		<?php } ?>
		<label for="webmestre"><?php _e('Type of webmaster:','xili_tidy_tags'); ?>
		<select name="webmestre" id="webmestre" style="width:100%;">
		<?php if ( !isset ( $this->xili_settings['webmestre-level'] ) ) $this->xili_settings['webmestre-level'] = '?' ; ?>
			<option value="?" <?php selected( $this->xili_settings['webmestre-level'], '?' ); ?>><?php _e('Define your experience as webmaster…','xili_tidy_tags'); ?></option>
			<option value="newbie" <?php selected( $this->xili_settings['webmestre-level'], "newbie" ); ?>><?php _e('Newbie in WP','xili_tidy_tags'); ?></option>
			<option value="wp-php" <?php selected( $this->xili_settings['webmestre-level'], "wp-php" ); ?>><?php _e('Good knowledge in WP and few in php','xili_tidy_tags'); ?></option>
			<option value="wp-php-dev" <?php selected( $this->xili_settings['webmestre-level'], "wp-php-dev" ); ?>><?php _e('Good knowledge in WP, CMS and good in php','xili_tidy_tags'); ?></option>
			<option value="wp-plugin-theme" <?php selected( $this->xili_settings['webmestre-level'], "wp-plugin-theme" ); ?>><?php _e('WP theme and /or plugin developper','xili_tidy_tags'); ?></option>
		</select></label>
		<br /><br />
		<label for="subject"><?php _e('Subject:','xili_tidy_tags'); ?>
		<input class="widefat" id="subject" name="subject" type="text" value="" /></label>
		<select name="thema" id="thema" style="width:100%;">
			<option value="" ><?php _e('Choose topic...','xili_tidy_tags'); ?></option>
			<option value="Message" ><?php _e('Message','xili_tidy_tags'); ?></option>
			<option value="Question" ><?php _e('Question','xili_tidy_tags'); ?></option>
			<option value="Encouragement" ><?php _e('Encouragement','xili_tidy_tags'); ?></option>
			<option value="Support need" ><?php _e('Support need','xili_tidy_tags'); ?></option>
		</select>
		<textarea class="widefat" rows="5" cols="20" id="mailcontent" name="mailcontent"><?php _e('Your message here…','xili_tidy_tags'); ?></textarea>
		</fieldset>
		<p>
		<?php _e('Before send the mail, check the infos to be sent and complete textarea. A copy (Cc:) is sent to webmaster email (modify it if needed).','xili_tidy_tags'); ?>
		</p>
		<div class='submit'>
		<input id='sendmail' name='sendmail' type='submit' tabindex='6' value="<?php _e('Send email','xili_tidy_tags') ?>" /></div>

		<div style="clear:both; height:1px"></div>
		<?php
	}

} // end of admin class

?>