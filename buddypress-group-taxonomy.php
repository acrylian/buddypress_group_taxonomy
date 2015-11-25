<?php
/*
  Plugin Name: Buddypress group taxonomy
  Plugin URI: http://www.maltem.de
  Description: Adds dropdown selector to assign a buddypress group to a post
  Author: Malte Müller
  Author URI: http://www.maltem.de
  License: GPLv2 or later
  Version: 1.0
 */
 
/************************
 * Backend functions
 ***********************/
	
/*
 * Registers the taxonomy
 */

function bpgroups_taxonomy_init() {
	// create a new taxonomy
	register_taxonomy(
					'buddypress_groups', // don't attach to post so we can use our own meta box selector
					'', array(
			'label' => __('Gruppe'),
			'rewrite' => array('slug' => 'gruppe'),
			'capabilities' => array(
					'assign_terms' => 'edit_guides',
					'edit_terms' => 'publish_guides'
			)
		)
	);
}

add_action('init', 'bpgroups_taxonomy_init');

/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function bpgroups_add_meta_box() {
	$screen = get_current_screen();
	//print_r($screen);
	if ($screen->action != 'add') {
		$screens = array('post');
		foreach ($screens as $screen) {
			add_meta_box(
							'bpgroups_sectionid', ('Buddypress-Gruppen'), 'bpgroups_meta_box_callback', $screen
			);
		}
	}
}

add_action('add_meta_boxes', 'bpgroups_add_meta_box');

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function bpgroups_meta_box_callback($post) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field('bpgroups_meta_box', 'bpgroups_meta_box_nonce');

	// to store the groups this user is member of
	$buddypressgroups = array();

	// Get real Buddypress Groups
	if (function_exists('bp_groups')) {
		if (bp_has_groups()) :
			while (bp_groups()) : bp_the_group();
				$groupname = bp_get_group_name();

				// Set group as taxonomy term if not already done
				$termexists = term_exists($groupname, 'buddypress_groups');
				if (empty($termexists)) {
					wp_insert_term($groupname, 'buddypress_groups', array('slug' => strtolower($groupname)));
				}

				// Get only groups the current user is member of
				if (groups_is_user_member(get_current_user_id(), bp_get_group_id())) {
					$buddypressgroups[] = array(
							'name' => $groupname,
							'slug' => strtolower($groupname)
					);
					$checkgroups[] = strtolower($groupname);
				}
			endwhile;
		endif;
	}
	// Remove tax terms that have no buddypress group counterpart anymore in case of changes
	$allgroupterms = get_terms('buddypress_groups', array('hide_empty' => false));
	foreach ($allgroupterms as $groupterm) {
		if (!in_array($groupterm->slug, $checkgroups) && $groupterm->slug != 'keine') { // just keep non assignment term 
			$term = get_term_by('slug', $groupterm->name, 'buddypress_groups');
			if ($term) {
				wp_delete_term($term->term_id, 'buddypress_groups');
			}
		}
	}
	//get the group assigned to this post if any
	$assignedgroups = wp_get_object_terms(array($post->ID), array('buddypress_groups'));
	$assignedgroup = 'keine'; // posts without assignments
	if ($assignedgroups) {
		$assignedgroup = $assignedgroups[0]->name; // technically more than one but we use only one
	}
	?>
	<p>
		<select name="bpgroups_groupselector" id="bpgroups_groupselector" size="1">
			<option value="keine" <?php selected($assignedgroup, 'keine', true); ?>>Keine</option>
			<?php
			foreach ($buddypressgroups as $bpgroup) {
				$name = $bpgroup['name'];
				$slug = $bpgroup['slug'];
				?>
				<option value="<?php echo esc_html($slug); ?>" <?php selected($assignedgroup, esc_html($slug), true); ?>><?php echo esc_html($name); ?></option>
	<?php } ?>
		</select>
	</p>
	<?php
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function bpgroups_save_meta_box_data($post_id) {
	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if (!isset($_POST['bpgroups_meta_box_nonce'])) {
		return;
	}

	// Verify that the nonce is valid.
	if (!wp_verify_nonce($_POST['bpgroups_meta_box_nonce'], 'bpgroups_meta_box')) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	// Check the user's permissions.
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (isset($_POST['bpgroups_groupselector'])) {
		$selectedgroup = sanitize_text_field($_POST['bpgroups_groupselector']);
		wp_delete_object_term_relationships($post_id, 'buddypress_groups'); // remove all groups first as we only want one per post
		wp_set_object_terms($post_id, $selectedgroup, 'buddypress_groups'); // assign new group
	}
}

add_action('save_post', 'bpgroups_save_meta_box_data');

/************************
 * Theme functions
 ***********************/

/**
 * Prints the group assignment of a post on the theme on the single.php template file
 * 
 * @param text $text Text for the link. Use %s as placeholder for the link, e.g 'Dieser Beitrag ist mit der Gruppe %s verknüpft.' (default) 
 * @param obj $obj Optionally a specicific post object, if not set the current post in the loop
 */
function printBuddypressGroupAssignment($text = 'Dieser Beitrag ist mit der Gruppe %s verknüpft.', $obj = NULL) {
	global $post;
	if(!is_null($obj)) {
		$obj = $post;
	}
	$groups = wp_get_object_terms(array($post->ID), array('buddypress_groups'));
	if ($groups) {
		$group = $groups[0]->name;
		if ($group != 'keine') {
			$grouplink = get_term_link($groups[0]);
			$link = '<a href="'. esc_url($grouplink).'" title="'.esc_attr($group).'">'.esc_html($group).'</a>';
			if(is_null($text)) {
				$text = 'Gruppe: %s';
			}
			?>
			<p><?php printf($text, $link); ?></p>
			<?php
		}
	}
}

/**
 * Prints a linked list of all buddypress group taxonomy terms
 * @param string $text Optional text before the list
 */
function printBuddyPressGroups($text = null) {
	$allgroupterms = get_terms('buddypress_groups'); // only get those assigned to posts
	if (!empty($allgroupterms)) {
		if (!is_null($text)) {
			echo esc_html($text);
		}
		?>
		<ul class="buddypress_groups">
			<?php
			foreach ($allgroupterms as $groupterm) {
				if ($groupterm->name != 'keine') {
					?>
					<li><a href="<?php echo esc_url(get_term_link($groupterm)); ?>"><?php echo esc_html($groupterm->name); ?></a></li>
					<?php
				}
			}
			?>
		</ul>
		<?php
	}
}