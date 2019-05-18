<?php

/**
 * XTT widget class
 *
 * @package Xili-Tidy-Tags
 * @subpackage add-ons
 * @since 1.12
 */


class Xili_Tidy_Tags_Cloud_Multiple_Widgets extends WP_Widget {

	public function __construct() {
		load_plugin_textdomain( 'xili-tidy-tags', false, 'xili-tidy-tags/languages' );
		$widget_ops = array(
			'classname' => 'xili_tdtc_widget',
			'description' => esc_html__( 'Cloud of grouped tags by xili-tidy-tags plugin', 'xili-tidy-tags' ) . ' - v.' . XILITIDYTAGS_VER,
		);
		parent::__construct( 'xili_tidy_tags_cloud_widget', '[©xili] ' . esc_html__( 'Tidy tags cloud', 'xili-tidy-tags' ), $widget_ops );
		$this->alt_option_name = 'xili_tidy_tags_cloud_widgets_options';
	}

	public function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );

		$thecondition = trim( $instance['thecondition'], '!' );

		if ( '' != $instance['thecondition'] && function_exists( $thecondition ) ) {
			$not = ( $thecondition == $instance['thecondition'] ) ? false : true;
			$arr_params = ( '' != $instance['theparams'] ) ? array( explode( ', ', $instance['theparams'] ) ) : array();
			$condition_ok = ( $not ) ? ! call_user_func_array( $thecondition, $arr_params ) : call_user_func_array( $thecondition, $arr_params );
		} else {
			$condition_ok = true;
		}
		if ( $condition_ok ) {
			$title = apply_filters( 'widget_title', $instance['title'] );
			echo $before_widget . $before_title . $title . $after_title;

			$cloudsargs = array();

			if ( 'the_curlang' == $instance['tagsgroup'] && class_exists( 'xili_language' ) ) { // if xl temporary desactivate
				$cloudsargs[] = 'tagsgroup=' . the_curlang();
			} elseif ( 'the_category' == $instance['tagsgroup'] ) {
				$cloudsargs[] = 'tagsgroup=' . single_cat_title( '', false );
			} else {
				$cloudsargs[] = 'tagsgroup=' . $instance['tagsgroup'];
			}
			$cloudsargs[] = 'tagsallgroup=' . $instance['tagsallgroup'];

			if ( abs( (int) $instance['smallest'] ) > 0 ) {
				$cloudsargs[] = 'smallest=' . abs( (int) $instance['smallest'] );
			}
			if ( abs( (int) $instance['largest'] ) > 0 ) {
				$cloudsargs[] = 'largest=' . abs( (int) $instance['largest'] );
			}
			if ( abs( (int) $instance['quantity'] ) > 0 ) {
				$cloudsargs[] = 'number=' . abs( (int) $instance['quantity'] ); // fixe number
			}

			if ( 'no' != $instance['orderby'] ) {
				$cloudsargs[] = 'orderby=' . $instance['orderby'];
			}
			if ( 'no' != $instance['order'] ) {
				$cloudsargs[] = 'order=' . $instance['order'];
			}

			$cloudsargs[] = 'format=' . $instance['displayas'];

			// 'tidy_taxonomy' => 'xili_tidy_tags', 'tidy_post_tag' => 'post_tag' - by default -
			// $cloudsargs[] = 'tidy_taxonomy=' . $instance['tidy_taxonomy']; // set in cloud 1.6.2
			$cloudsargs[] = ( 'xili_tidy_tags' == $instance['tidy_taxonomy'] ) ? 'tidy_post_tag=post_tag' : 'tidy_post_tag=' . str_replace( TAXOTIDYTAGS . '_', '', $instance['tidy_taxonomy'] );

			echo '<div class="xilitidytagscloud">';

			if ( is_multisite() ) { // 1.7 - only for current clouds
				global $blog_id;
				$targetsite = ( isset( $instance['targetsite'] ) && 0 != $instance['targetsite'] ) ? $instance['targetsite'] : $blog_id;
				$targetsite = (int) $targetsite;
				$switch_to = ( $blog_id != $targetsite ) ? true : false; // if other
			} else {
				$switch_to = false;
			}

			if ( $switch_to ) {
				switch_to_blog( $targetsite );
			}

			xili_tidy_tag_cloud( implode( '&', $cloudsargs ) );

			if ( $switch_to ) {
				restore_current_blog();
			}
			echo '</div>';
			echo $after_widget;
		}
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );

		$instance['tagsgroup'] = strip_tags( stripslashes( $new_instance['tagsgroup'] ) );
		$instance['tagsallgroup'] = strip_tags( stripslashes( $new_instance['tagsallgroup'] ) );
		$instance['smallest'] = strip_tags( stripslashes( $new_instance['smallest'] ) );
		$instance['largest'] = strip_tags( stripslashes( $new_instance['largest'] ) );
		$instance['quantity'] = strip_tags( stripslashes( $new_instance['quantity'] ) );
		$instance['orderby'] = strip_tags( stripslashes( $new_instance['orderby'] ) );
		$instance['order'] = strip_tags( stripslashes( $new_instance['order'] ) );
		$instance['displayas'] = strip_tags( stripslashes( $new_instance['displayas'] ) );
		$instance['tidy_taxonomy'] = strip_tags( $new_instance['tidy_taxonomy'] );

		$instance['thecondition'] = strip_tags( stripslashes( $new_instance['thecondition'] ) ); // 1.6.0
		$instance['theparams'] = strip_tags( stripslashes( $new_instance['theparams'] ) );

		if ( is_multisite() ) {
			$instance['targetsite'] = strip_tags( stripslashes( $new_instance['targetsite'] ) );
		}
		return $instance;
	}

	public function form( $instance ) {

		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$tagsgroup = isset( $instance['tagsgroup'] ) ? esc_attr( $instance['tagsgroup'] ) : '';
		$tagsallgroup = isset( $instance['tagsallgroup'] ) ? esc_attr( $instance['tagsallgroup'] ) : '';
		$smallest = isset( $instance['smallest'] ) ? esc_attr( $instance['smallest'] ) : '';
		$largest = isset( $instance['largest'] ) ? esc_attr( $instance['largest'] ) : '';
		$quantity = isset( $instance['quantity'] ) ? esc_attr( $instance['quantity'] ) : '';
		$orderby = isset( $instance['orderby'] ) ? $instance['orderby'] : '';
		$order = isset( $instance['order'] ) ? $instance['order'] : '';
		$displayas = isset( $instance['displayas'] ) ? $instance['displayas'] : '';
		$tidy_taxonomy = isset( $instance['tidy_taxonomy'] ) ? $instance['tidy_taxonomy'] : 'xili_tidy_tags';

		$thecondition = isset( $instance['thecondition'] ) ? stripslashes( $instance['thecondition'] ) : '';
		$theparams = isset( $instance['theparams'] ) ? stripslashes( $instance['theparams'] ) : '';

		if ( is_multisite() ) {
			$targetsite = isset( $instance['targetsite'] ) ? $instance['targetsite'] : '';
		}

		$listtagsgroup = get_terms( $tidy_taxonomy, array( 'hide_empty' => false ) );
		global $xili_tidy_tags; // 1.11.5
		$listtagsgroupssorted = $xili_tidy_tags->create_taggrouplist_sorted( walk_TagGroupList_sorted( $listtagsgroup, 3, null, null ) );
		//$listtagsgroupssorted = $listtagsgroups;

		?>
		<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<label for="<?php echo esc_attr( $this->get_field_id( 'tagsgroup' ) ); ?>" ><?php esc_html_e( 'Group', 'xili-tidy-tags' ); ?> : </label><br />
		<select name="<?php echo esc_attr( $this->get_field_name( 'tagsgroup' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'tagsgroup' ) ); ?>" style="width:90%;">
		<option value="" ><?php esc_html_e( 'Choose a group…', 'xili-tidy-tags' ); ?></option>
		<?php
		/* group named as current language */
		if ( class_exists( 'xili_language' ) ) {
			?>
			<option value="the_curlang" <?php selected( $tagsgroup, 'the_curlang' ); ?> ><?php esc_html_e( 'Current language', 'xili-tidy-tags' ); ?></option>
		<?php
		}
		/* group named as current category */
		?>

		<option value="the_category" <?php selected( $tagsgroup, 'the_category' ); ?> ><?php esc_html_e( 'Current category', 'xili-tidy-tags' ); ?></option>
		<?php
		if ( $listtagsgroupssorted ) {
			foreach ( $listtagsgroupssorted as $curterm ) {
				$ttab = ( 0 == $curterm->parent ) ? '' : '– ';
				$selected = selected( $tagsgroup, $curterm->slug, false );
				echo '<option value="' . $curterm->slug . '" ' . $selected . ' >' . $ttab . $curterm->name . '</option>';

			}
		}
		?>
		</select>

		<br />
		<label for="<?php echo esc_attr( $this->get_field_id( 'tagsallgroup' ) ); ?>" ><?php esc_html_e( 'Group #2', 'xili-tidy-tags' ); ?> : </label><br />

		<select name="<?php echo esc_attr( $this->get_field_name( 'tagsallgroup' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'tagsallgroup' ) ); ?>" style="width:90%;">
		<option value="" ><?php esc_html_e( '(Option) Choose a 2nd group…', 'xili-tidy-tags' ); ?></option>

		<?php
		if ( $listtagsgroupssorted ) {
			foreach ( $listtagsgroupssorted as $curterm ) {
				$ttab = ( 0 == $curterm->parent ) ? '' : '– ';
				$selected = selected( $tagsallgroup, $curterm->slug, false );
				echo '<option value="' . $curterm->slug . '" ' . $selected . ' >' . $ttab . $curterm->name . '</option>';

			}
		}
		?>
		</select>

		<br />
		<label for="<?php echo esc_attr( $this->get_field_id( 'smallest' ) ); ?>" ><?php esc_html_e( 'Smallest size', 'xili-tidy-tags' ); ?> : <input id="<?php echo esc_attr( $this->get_field_id( 'smallest' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'smallest' ) ); ?>" type="text" value="<?php echo $smallest; ?>" /></label><br />
		<label for="<?php echo esc_attr( $this->get_field_id( 'largest' ) ); ?>" ><?php esc_html_e( 'Largest size', 'xili-tidy-tags' ); ?> : <input id="<?php echo esc_attr( $this->get_field_id( 'largest' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'largest' ) ); ?>" type="text" value="<?php echo $largest; ?>" /></label><br />
		<label for="<?php echo esc_attr( $this->get_field_id( 'quantity' ) ); ?>" ><?php esc_html_e( 'Number', 'xili-tidy-tags' ); ?> : <input id="<?php echo esc_attr( $this->get_field_id( 'quantity' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'quantity' ) ); ?>" type="text" value="<?php echo $quantity; ?>" /></label>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php esc_html_e( 'Order and sorting infos', 'xili-tidy-tags' ); ?></legend>
		<select name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" style="width:100%;">
		<?php
		echo '<option value="no" >' . esc_html__( 'no orderby', 'xili-tidy-tags' ) . '</option>';
		echo '<option value="count" ' . selected( $orderby, 'count', false ) . ' >' . esc_html__( 'count', 'xili-tidy-tags' ) . '</option>';
		echo '<option value="name" ' . selected( $orderby, 'name', false ) . ' >' . esc_html__( 'name', 'xili-tidy-tags' ) . '</option>';
		?>
		</select>
		<select name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" style="width:100%;">
		<?php
		echo '<option value="no" >' . esc_html__( 'no order', 'xili-tidy-tags' ) . '</option>';
		echo '<option value="ASC" ' . selected( $order, 'ASC', false ) . ' >' . esc_html__( 'ASC', 'xili-tidy-tags' ) . '</option>';
		echo '<option value="DESC" ' . selected( $order, 'DESC', false ) . ' >' . esc_html__( 'DESC', 'xili-tidy-tags' ) . '</option>';
		?>
		</select>
		</fieldset>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php esc_html_e( 'Display as', 'xili-tidy-tags' ); ?></legend>
		<select name="<?php echo esc_attr( $this->get_field_name( 'displayas' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'displayas' ) ); ?>" style="width:100%;"> <?php
		echo '<option value="flat" ' . selected( $displayas, 'flat', false ) . ' >' . esc_html__( 'Cloud', 'xili-tidy-tags' ) . '</option>';
		echo '<option value="list" ' . selected( $displayas, 'list', false ) . ' >' . esc_html__( 'List', 'xili-tidy-tags' ) . '</option></select>';
		?>
		<br /></fieldset>

		<?php
		if ( is_multisite() ) {
			// 1.6.5
			$all_blogs = get_blogs_of_user( get_current_user_id() );

			if ( count( $all_blogs ) > 1 ) {
				?>
				<label for="<?php echo esc_attr( $this->get_field_id( 'targetsite' ) ); ?>" ><?php esc_html_e( 'Target site ID', 'xili-tidy-tags' ); ?> :
				<?php
				$echodis = ''; //( $disabled == true ) ? 'disabled="disabled"' : '';
				echo '<select id="' . $this->get_field_id( 'targetsite' ) . '" name="' . $this->get_field_name( 'targetsite' ) . '" ' . $echodis . ' class="widefat" ><option value=0 ' . selected( $targetsite,  0, false ) . ' >' . esc_html__( 'Choose site...', 'xili-tidy-tags' ) . '</option>';
				foreach ( (array) $all_blogs as $blog ) {
						$wplang = ( '' != get_blog_option( $blog->userblog_id, 'WPLANG' ) ) ? get_blog_option ( $blog->userblog_id, 'WPLANG' ) : esc_html__( 'undefined', 'xili-tidy-tags' ); // to adapt if xlms ready
						?>
						<option value="<?php echo $blog->userblog_id; ?>" <?php selected( $targetsite, $blog->userblog_id ); ?> ><?php echo esc_url( get_home_url( $blog->userblog_id ) ) . ' ( ' . $blog->userblog_id . ' ) - WPLANG = ' . $wplang; ?></option>
						<?php
				}
				?>
				</select>
				</label>

			<?php
			} else {
				?>
				<input id="<?php echo esc_attr( $this->get_field_id( 'targetsite' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'targetsite' ) ); ?>" type="hidden" value="<?php echo $targetsite; ?>" />
				<?php
				echo '<span style="color:red">' . esc_html__( 'No site assigned to current admin user ! Please verify user\'s list for targeted sites.', 'xili-tidy-tags' ) . '</span>';
			}
			?>
		<br />
	<?php } ?>

		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php esc_html_e( 'Taxonomies', 'xili-tidy-tags' ); ?></legend>
		<?php
		$taxos_list = get_object_taxonomies( 'term' );
		?>
		<label for="<?php echo esc_attr( $this->get_field_id( 'tidy_taxonomy' ) ); ?>" ><?php esc_html_e( 'tidy taxonomy', 'xili-tidy-tags' ); ?> : </label><br />
		<select name="<?php echo esc_attr( $this->get_field_name( 'tidy_taxonomy' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'tidy_taxonomy' ) ); ?>" style="width:90%;">
		<?php
		foreach ( $taxos_list as $curterm ) {
			if ( ! in_array( $curterm, array( 'languages_group', 'xl-dictionary-langs' ) ) ) {
				$selected = selected( $tidy_taxonomy, $curterm, false );
				echo '<option value="' . $curterm . '" ' . $selected . ' >' . $curterm . '</option>';
			}
		}
		?>
		</select>

		</fieldset>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;" >
			<label for="<?php echo esc_attr( $this->get_field_id( 'thecondition' ) ); ?>"><?php esc_html_e( 'Condition', 'xili-tidy-tags' ); ?></label>:
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'thecondition' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thecondition' ) ); ?>" type="text" value="<?php echo $thecondition; ?>" />
			( <input id="<?php echo esc_attr( $this->get_field_id( 'theparams' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'theparams' ) ); ?>" type="text" value="<?php echo $theparams; ?>" /> )
		</fieldset>
		<p><small><?php echo '© xili-tidy-tags v.' . XILITIDYTAGS_VER; ?></small></p>
	<?php
	}
}
