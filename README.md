#Buddypress-group-taxonomy

This plugin creates a custom taxonomy from the Buddypress groups so you can assign posts to them.

## Installation and 

Just place the .php file in `yourdomain.com/wp-content/plugins/` and enable it on the WordPress backend. 

## Backend usage

It adds dropdown selector to assign a buddypress group on the backend post edit page automatically. You can assign one group at the time. 

Only groups the current user is assigned to are displayed. Groups deleted from buddypress itself are automatcally removed including their assignments.

## Theme usage

### Display group assignements

Add the following within the post loop to display a link to display the assigment linked to the taxonomy overview page:

`<?php printBuddypressGroupAssignment('This post is assigned to the group %s.'); ?>`

Text can be adjusted where `%s` is the placeholder for the link. Optionally there is a 2nd parameter to pass a specific post object directly.

There is an optional 3rd parameter to either link to the real Buddypress group page (true, default) or to the group taxonomy (false).

### Link list of all terms of buddypress_group taxonomy

To display a link list of all buddypress groups of the taxonomy:

`<?php printBuddyPressGroups('All groups'); ?>`

The text is optional and placed before the list. Groups that have no post assigned are not displayed.

### Display all posts assigned to a group

Place on the theme's `page.php` (normally the page for the real Buddypress groups):

`<?php printPostsByBuddypressGroupTax(); ?>`

It automatically takes the current Buddypress group and gets the posts assigned to the buddypress_group taxonomy term of the same name.

Alternatively the slug of the Buddypress group can be passed as a parameter.

`<?php printPostsByBuddypressGroupTax('<slug ofthe group to get>'); ?>`




