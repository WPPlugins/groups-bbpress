<?php
/**
 * class-groups-bbpress.php
 *
 * Copyright (c) www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package groups-bbpress
 * @since 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements user access on topics and replies based on forum access restriction.
 */
class Groups_bbPress {

	/**
	 * Adds our filter on groups_post_access_user_can_read_post.
	 */
	public static function init() {
		// $result = apply_filters( 'groups_post_access_user_can_read_post', $result, $post_id, $user_id );
		add_filter( 'groups_post_access_user_can_read_post', array( __CLASS__, 'groups_post_access_user_can_read_post' ), 10, 3 );
		// instead of filtering 'posts_where' which would be complicated (conditional query based on three post types)
		// we use the last stage where we simply remove the posts - it's not optimal but simpler
		// $this->posts = apply_filters_ref_array( 'the_posts', array( $this->posts, &$this ) );
		add_filter( 'the_posts', array( __CLASS__, 'the_posts' ), 10, 2 );
	}

	/**
	 * Returns corresponding forum's result for topics and replies.
	 * 
	 * @param boolean $result
	 * @param int $post_id
	 * @param int $user_id
	 * @return boolean
	 */
	public static function groups_post_access_user_can_read_post( $result, $post_id, $user_id ) {

		if ( $result ) {
			$post_type = get_post_type( $post_id );
			switch ( $post_type ) {
				// check if the user can read the forum
				case 'topic' :
					if ( function_exists( 'bbp_get_topic' ) ) {
						if ( $topic = bbp_get_topic( $post_id ) ) {
							$forum_id = $topic->post_parent;
							if ( !empty( $forum_id ) ) {
								if ( class_exists( 'Groups_Post_Access' ) && method_exists( 'Groups_Post_Access', 'user_can_read_post' ) ) {
									$result = Groups_Post_Access::user_can_read_post( $forum_id, $user_id );
								}
							}
						}
					}
					break;
				case 'reply' :
					// check if 1) the user can read the topic and 2) the forum
					if ( function_exists( 'bbp_get_reply' ) ) {
						if ( $reply = bbp_get_reply( $post_id ) ) {
							$topic_id = $reply->post_parent;
							if ( !empty( $topic_id ) ) {
								if ( class_exists( 'Groups_Post_Access' ) && method_exists( 'Groups_Post_Access', 'user_can_read_post' ) ) {
									$result = Groups_Post_Access::user_can_read_post( $topic_id, $user_id );
									if ( $result ) {
										if ( function_exists( 'bbp_get_topic' ) ) {
											if ( $topic = bbp_get_topic( $topic_id ) ) {
												$forum_id = $topic->post_parent;
												if ( !empty( $forum_id ) ) {
													$result = Groups_Post_Access::user_can_read_post( $forum_id, $user_id );
												}
											}
										}
									}
								}
							}
						}
					}
					break;
			}
		}
		return $result;
	}

	/**
	 * Removes the posts that a user cannot access.
	 * 
	 * This needs to be done explicitly here,
	 * as the posts_where filter in groups does not have any effect on topics and replies
	 * that are related to protected forums. Using a posts_where filter would require to
	 * use complex queries that have to take the post type and the post parent into account
	 * with three related post types (forums <- topics <- replies) and instead we simply
	 * post-process the result from WP_Query->get_posts() using the 'the_posts' filter
	 * implemented here.
	 * 
	 * In this filter, it is sufficient to check if a user can read a post,
	 * as this will trigger our groups_post_access_user_can_read_post filter that will
	 * check the relation between topics, replies and forums.
	 * The filter only executes the check on 'topic' and 'reply' post types and will let any
	 * others pass.
	 * 
	 * @param array $posts
	 * @param WP_Query $query
	 * @return array
	 */
	public static function the_posts( $posts, &$query ) {
		$filtered_posts = array();
		$user_id = get_current_user_id();
		foreach( $posts as $post ) {
			switch( $post->post_type ) {
				case 'topic' :
				case 'reply' :
					if ( class_exists( 'Groups_Post_Access' ) && method_exists( 'Groups_Post_Access', 'user_can_read_post' ) ) {
						if ( Groups_Post_Access::user_can_read_post( $post->ID, $user_id ) ) {
							$filtered_posts[] = $post;
						}
					} else {
						$filtered_posts[] = $post;
					}
					break;
				default :
					$filtered_posts[] = $post;
			}
		}
		return $filtered_posts;
	}
}
Groups_bbPress::init();
