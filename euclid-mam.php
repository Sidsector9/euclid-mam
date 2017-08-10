<?php
/**
 * Plugin Name: Multi Author Metabox
 * Plugin URI:  https://github.com/Sidsector9/euclid-mam/
 * Description: A simple plugin to add contributors to a post.
 * Version:     2.0
 * Author:      T. Siddharth Unni
 * Author URI:  https://github.com/Sidsector9/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mam
 *
 * @package euclid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MultiAuthorMetabox' ) ) {

	/**
	 * Multi Author Metabox class.
	 *
	 * This class defines methods to select authors in the post editor
	 * screen and then display the contributors on the post page.
	 */
	class MultiAuthorMetabox {

		/**
		 * Constructor function.
		 *
		 * The constructor is used to call 2 action hooks and 1 filter
		 * hook that deals with the creation of metabox, saving the
		 * data and outputting the data on the post page.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'euclid_check_user_role' ) );
			add_action( 'save_post', array( $this, 'euclid_save_post' ) );

			if ( ! is_admin() ) {
				add_filter( 'the_content', array( $this, 'euclid_display_contributors' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'euclid_enqueue_css' ) );
			}
		}

		/**
		 * Enqueue the stylesheet
		 */
		function euclid_enqueue_css() {
			wp_enqueue_style( 'mam-style', plugins_url( '/css/mam-style.css', __FILE__ ) );
		}

		/**
		 * Checks user role and then create metabox
		 *
		 * This function will only allow the creation of the metabox
		 * only if the user role of the logged in user is one of the
		 * following:
		 *
		 * - administrator
		 * - editor
		 * - author
		 */
		public function euclid_check_user_role() {
			global $current_user;

			$user_roles = array(
				'administrator',
				'editor',
				'author',
			);

			if ( in_array( $current_user->roles[0], $user_roles, true ) ) {
		        add_meta_box(
		            'euclid-multi-author',
		            esc_html__( 'Contributors', 'mam' ),
		            array( $this, 'euclid_fill_metabox' ),
		            'post',
		            'normal'
		        );
		    }
		}

		/**
		 * Fill metabox with list of users with checkboxes.
		 *
		 * This function will add a list of all users with capability
		 * of 'edit_posts' to the metabox that was created using the
		 * euclid_check_user_role() function.
		 *
		 * @param Object $post This is the post object that contains
		 * data of the current post.
		 */
		public function euclid_fill_metabox( $post ) {
			wp_nonce_field( basename( __FILE__ ), 'mam_nonce' );
		    $postmeta = get_post_meta( $post->ID, 'contributors', true );
		    $contributors = get_users();
		    $post_author_id = get_post_field( 'post_author', $post->ID );

		    foreach ( $contributors as $user ) {
		    	$checked  = null;
		    	$disabled = null;

		    	if ( $user->has_cap( 'edit_posts' ) ) {
			        if ( is_array( $postmeta ) && in_array( $user->data->ID, $postmeta, true ) ) {
			            $checked = 'checked="checked"';
			        }

			        if ( $post_author_id === $user->data->ID ) {
			        	$checked  = 'checked="checked"';
			        	$disabled = 'disabled';
			        }
		        ?>

			        <p>
			            <input  
			                type="checkbox" 
			                name="contributors[]" 
			                value="<?php echo intval( $user->data->ID );?>" 
			                <?php echo esc_attr( $checked ); ?>
			                <?php echo esc_attr( $disabled ); ?>
			            >
			            <?php
						$user_data = get_userdata( $user->data->ID );
						$fn        = $user_data->first_name;
						$ln        = $user_data->last_name;

						if ( empty( $fn ) && empty( $ln ) ) {
							echo esc_html( $user->data->user_nicename );
						} else {
							echo esc_html( $fn . ' ' . $ln . '  ' );
							echo '<span style="color: #aaa;">( ' . esc_html( $user->data->user_nicename ) . ' )</span>';
						} ?>
			        </p>
			        <?php
			    }
			}
		}

		/**
		 * Save the selected contributors.
		 *
		 * This function saves the contributors selected for a
		 * particular post.
		 *
		 * @param int $post_id is the ID of the current post.
		 */
		public function euclid_save_post( $post_id ) {
			$is_autosave = wp_is_post_autosave( $post_id );
		    $is_revision = wp_is_post_revision( $post_id );
		    $is_valid_nonce = filter_input( INPUT_POST, 'mam_nonce', FILTER_SANITIZE_STRING );

		    if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
		        return;
		    }

		    $contributors = filter_input( INPUT_POST, 'contributors', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		    if ( ! empty( $contributors ) ) {
		        update_post_meta( $post_id, 'contributors', $contributors );
		    } else {
		        delete_post_meta( $post_id, 'contributors' );
		    }
		}

		/**
		 * Displays the contributors on post page.
		 *
		 * The contributors that were selected by the authorized users
		 * will be displayed at the end of the post with their
		 * gravatar, name and a link to their author page.
		 *
		 * @param string $content The content of the current post.
		 */
		public function euclid_display_contributors( $content ) {
			if ( is_singular( 'post' ) ) {

				$postmeta = get_post_meta( get_the_ID(), 'contributors', true );

				if ( ! empty( $postmeta ) ) {
					$content .= '<div class="euclid-multi-author-metabox">';
					$content .= '<h3>' . esc_html__( 'Contributors: ', 'mam' ) . '</h3>';
					$content .= '<div class="wrap">';

					foreach ( $postmeta as $author_id ) {
						$link     = get_author_posts_url( $author_id );
						$content .= '<a href="' . esc_url( $link ) . '">';
						$content .= '<div class="euclid-contributor">';
						$content .= '<div class="euclid-avatar">' . get_avatar( $author_id ) . '</div>';
						$content .= '<span class="euclid-author-name">' . get_the_author_meta( 'display_name', $author_id ) . '</span>';
						$content .= '</div></a>';
					}
					$content .= '</div></div>';
				}
			}
			return $content;
		}
	}
}

if ( class_exists( 'MultiAuthorMetabox' ) ) {
	$init = new MultiAuthorMetabox();
}
