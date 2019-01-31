<?php
/**
 * BuddyBoss Activity Feeds Loader.
 *
 * An activity feed component, for users, groups, and site tracking.
 *
 * @package BuddyBoss
 * @subpackage ActivityCore
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Activity Class.
 *
 * @since BuddyPress 1.5.0
 */
class BP_Activity_Component extends BP_Component {

	/**
	 * Start the activity component setup process.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		parent::start(
			'activity',
			__( 'Activity Feed', 'buddyboss' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 25,
				'search_query_arg' => 'activity_search',
				'features' => array( 'embeds' )
			)
		);
	}

	/**
	 * Include component files.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {

		// Files to include.
		$includes = array(
			'cssjs',
			'filters',
			'adminbar',
			'template',
			'functions',
			'cache'
		);

		// Notifications support.
		if ( bp_is_active( 'notifications' ) ) {
			$includes[] = 'notifications';
		}

		// Load Akismet support if Akismet is configured.
		$akismet_key = bp_get_option( 'wordpress_api_key' );

		/** This filter is documented in bp-activity/bp-activity-akismet.php */
		if ( defined( 'AKISMET_VERSION' ) && class_exists( 'Akismet' ) && ( ! empty( $akismet_key ) || defined( 'WPCOM_API_KEY' ) ) && apply_filters( 'bp_activity_use_akismet', bp_is_akismet_active() ) ) {
			$includes[] = 'akismet';
		}

		// Embeds
		if ( bp_is_active( $this->id, 'embeds' ) ) {
			$includes[] = 'embeds';
		}

		if ( is_admin() ) {
			$includes[] = 'admin';
		}

		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		/*
		 * Load activity action and screen code if PHPUnit isn't running.
		 *
		 * For PHPUnit, we load these files in tests/phpunit/includes/install.php.
		 */
		if ( bp_is_current_component( 'activity' ) ) {
			// Authenticated actions - Only fires when JS is disabled.
			if ( is_user_logged_in() &&
				in_array( bp_current_action(), array( 'delete', 'spam', 'post', 'reply', 'favorite', 'unfavorite' ), true )
			) {
				require $this->path . 'bp-activity/actions/' . bp_current_action() . '.php';
			}

			// RSS feeds.
			if ( bp_is_current_action( 'feed' ) || bp_is_action_variable( 'feed', 0 ) ) {
				require $this->path . 'bp-activity/actions/feeds.php';
			}

			// Screens - Directory.
			if ( bp_is_activity_directory() ) {
				require $this->path . 'bp-activity/screens/directory.php';
			}

			// Screens - User main nav.
			if ( bp_is_user() ) {
				require $this->path . 'bp-activity/screens/just-me.php';
			}

			// Screens - User secondary nav.
			if ( bp_is_user() && in_array( bp_current_action(), array( 'friends', 'groups', 'favorites', 'mentions' ), true ) ) {
				require $this->path . 'bp-activity/screens/' . bp_current_action() . '.php';
			}

			// Screens - Single permalink.
			if ( bp_is_current_action( 'p' ) || is_numeric( bp_current_action() ) ) {
				require $this->path . 'bp-activity/screens/permalink.php';
			}

			// Theme compatibility.
			new BP_Activity_Theme_Compat();
		}
	}

	/**
	 * Set up component global variables.
	 *
	 * The BP_ACTIVITY_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_ACTIVITY_SLUG' ) ) {
			define( 'BP_ACTIVITY_SLUG', $this->id );
		}

		// Global tables for activity component.
		$global_tables = array(
			'table_name'        => $bp->table_prefix . 'bp_activity',
			'table_name_meta'   => $bp->table_prefix . 'bp_activity_meta',
			'table_name_follow' => $bp->table_prefix . 'bp_follow',
		);

		// Metadata tables for groups component.
		$meta_tables = array(
			'activity' => $bp->table_prefix . 'bp_activity_meta',
		);

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[$this->id];

		// All globals for activity component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'                  => BP_ACTIVITY_SLUG,
			'root_slug'             => isset( $bp->pages->activity->slug ) ? $bp->pages->activity->slug : BP_ACTIVITY_SLUG,
			'has_directory'         => true,
			'directory_title'       => isset( $bp->pages->activity->title ) ? $bp->pages->activity->title : $default_directory_title,
			'notification_callback' => 'bp_activity_format_notifications',
			'search_string'         => __( 'Search Feed...', 'buddyboss' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		parent::setup_globals( $args );

		// locally cache total count values for logged-in user
		if ( is_user_logged_in() ) {
			$bp->loggedin_user->total_follow_counts = bp_total_follow_counts( array(
				'user_id' => bp_loggedin_user_id()
			) );
		}

		// locally cache total count values for displayed user
		if ( bp_is_user() && ( bp_loggedin_user_id() != bp_displayed_user_id() ) ) {
			$bp->displayed_user->total_follow_counts = bp_total_follow_counts( array(
				'user_id' => bp_displayed_user_id()
			) );
		}
	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Stop if there is no user displayed or logged in.
		if ( ! is_user_logged_in() && ! bp_displayed_user_id() ) {
			return;
		}

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$slug          = bp_get_activity_slug();
		$activity_link = trailingslashit( $user_domain . $slug );

		$main_slug = ['just-me'];
		if ( bp_activity_do_mentions() )   $main_slug[] = 'mentions';
		if ( bp_is_active( 'friends' ) && bp_is_my_profile() )   $main_slug[] = 'friends';
		if ( bp_is_active( 'groups' ) && bp_is_my_profile() )    $main_slug[] = 'groups';
		if ( bp_is_active( 'follow' ) && bp_is_my_profile() )    $main_slug[] = 'following';


		// Add 'Activity' to the main navigation.
		$main_nav = array(
			'name'                => _x( 'Timeline', 'Profile activity screen nav', 'buddyboss' ),
			'slug'                => $slug,
			'position'            => 25,
			'screen_function'     => 'bp_activity_screen_my_activity',
			'default_subnav_slug' => implode( ',', $main_slug ),
			'item_css_id'         => $this->id
		);

		// Add the subnav items to the activity nav item if we are using a theme that supports this.
		$sub_nav[] = array(
			'name'            => _x( 'Posts', 'Profile activity screen sub nav', 'buddyboss' ),
			'slug'            => 'just-me',
			'parent_url'      => $activity_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bp_activity_screen_my_activity',
			'position'        => 10
		);

		// Favorite activity items.
		if ( bp_activity_can_favorite() && bp_is_my_profile() && bp_is_activity_like_active() ) {
			$sub_nav[] = array(
				'name'            => _x( 'My Likes', 'Profile activity screen sub nav', 'buddyboss' ),
				'slug'            => 'favorites',
				'parent_url'      => $activity_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_activity_screen_favorites',
				'position'        => 20,
				'item_css_id'     => 'activity-favs'
			);
		}

		// Check @mentions.
		// if ( bp_activity_do_mentions() ) {
		// 	$sub_nav[] = array(
		// 		'name'            => _x( 'Mentions', 'Profile activity screen sub nav', 'buddyboss' ),
		// 		'slug'            => 'mentions',
		// 		'parent_url'      => $activity_link,
		// 		'parent_slug'     => $slug,
		// 		'screen_function' => 'bp_activity_screen_mentions',
		// 		'position'        => 30,
		// 		'item_css_id'     => 'activity-mentions'
		// 	);
		// }

		// Additional menu if friends is active.
		// if ( bp_is_active( 'friends' ) ) {
		// 	$sub_nav[] = array(
		// 		'name'            => _x( 'Connections', 'Profile activity screen sub nav', 'buddyboss' ),
		// 		'slug'            => bp_get_friends_slug(),
		// 		'parent_url'      => $activity_link,
		// 		'parent_slug'     => $slug,
		// 		'screen_function' => 'bp_activity_screen_friends',
		// 		'position'        => 40,
		// 		'item_css_id'     => 'activity-friends'
		// 	) ;
		// }

		// Additional menu if groups is active.
		// if ( bp_is_active( 'groups' ) ) {
		// 	$sub_nav[] = array(
		// 		'name'            => _x( 'Groups', 'Profile activity screen sub nav', 'buddyboss' ),
		// 		'slug'            => bp_get_groups_slug(),
		// 		'parent_url'      => $activity_link,
		// 		'parent_slug'     => $slug,
		// 		'screen_function' => 'bp_activity_screen_groups',
		// 		'position'        => 50,
		// 		'item_css_id'     => 'activity-groups'
		// 	);
		// }

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a
	 *                            description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$activity_link = trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() );

			// Unread message count.
			if ( bp_activity_do_mentions() ) {
				$count = bp_get_total_mention_count_for_user( bp_loggedin_user_id() );
				if ( !empty( $count ) ) {
					$title = sprintf(
						/* translators: %s: Unread mention count for the current user */
						_x( 'Mentions %s', 'Toolbar Mention logged in user', 'buddyboss' ),
						'<span class="count">' . bp_core_number_format( $count ) . '</span>'
					);
				} else {
					$title = _x( 'Mentions', 'Toolbar Mention logged in user', 'buddyboss' );
				}
			}

			// Add the "Activity" sub menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => _x( 'Timeline', 'My Account Activity sub nav', 'buddyboss' ),
				'href'   => $activity_link
			);

			// Personal.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-personal',
				'title'    => _x( 'Posts', 'My Account Activity sub nav', 'buddyboss' ),
				'href'     => $activity_link,
				'position' => 10
			);

			// Favorite activity items.
			if ( bp_activity_can_favorite() && bp_is_activity_like_active() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-favorites',
					'title'    => _x( 'My Likes', 'My Account Activity sub nav', 'buddyboss' ),
					'href'     => trailingslashit( $activity_link . 'favorites' ),
					'position' => 30
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 */
	public function setup_title() {

		// Adjust title based on view.
		if ( bp_is_activity_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = _x( 'My Activity', 'Page and <title>', 'buddyboss' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'	  => sprintf( __( 'Profile picture of %s', 'buddyboss' ), bp_get_displayed_user_fullname() )
				) );
				$bp->bp_options_title  = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups.
	 *
	 * @since BuddyPress 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups( array(
			'bp_activity',
			'bp_activity_comments',
			'activity_meta'
		) );

		parent::setup_cache_groups();
	}
}
