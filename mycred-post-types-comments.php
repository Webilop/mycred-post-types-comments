<?php
/**
 * Plugin Name: Post Type Comments for myCRED
 * Plugin URI:
 * Description: Add different amount of points for comments to different post types.
 * Tags: points, mycred, comments, custom post types, reward
 * Version: 0.1
 * Author: Webilop
 * Author URI: http://www.webilop.com
 * Requires at least: WP 3.8
 * Tested up to: WP 4.3
 * Text Domain: mycred-post-type-comments
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

$textdomain = 'mycred-post-type-comments';

/**
* Stop plugin activation if mycred is not active
*/
function mycredptc_activate() {
  if (!is_plugin_active('mycred/mycred.php')) {
    exit("Plugin myCRED is required by myCRED Post Type Comments");
  }
}
register_activation_hook( __FILE__, 'mycredptc_activate');

/**
* Function to create class for mycred_post_type_comments hooks.
* must be in mycred_init action to avoid php error
*/
function mptc_init_my_hook() {
  class mycred_post_type_comments extends myCRED_Hook{

    private $textdomain;
    private $available_post_types = array();

    /**
     * Constructor
     *
     * @param array $hook_prefs configured preferences
     * @param string point type
     */
    function __construct($hook_prefs, $type = 'mycred_default') {
      $registered_post_types = get_post_types();
      $defaults = array();
      $excludes = array('page', 'attachment');
      global $textdomain;
      $this->textdomain = $textdomain;

      foreach ($registered_post_types as $post_type) {
        if(post_type_supports($post_type, 'comments')) {
          $this->available_post_types[] = $post_type;
        }
      }

      $this->available_post_types = array_diff($this->available_post_types, $excludes);

      foreach ($this->available_post_types as $post_type) {
        $defaults[$post_type] = array(
          'creds'    => 1,
          'log'      => '%plural% for comment on ' . $post_type,
          'limit'  => '0/x'
        );
      }

      if ( isset( $hook_prefs['post_type_comments'] ) )
        $defaults = $hook_prefs['post_type_comments'];

      parent::__construct(array(
        'id'       => 'post_type_comments',
        'defaults' => $defaults
      ), $hook_prefs, $type);
    }

    /**
     * Hook into WordPress. Called when executing myCRED hooks
     */
    public function run() {
      add_action('wp_insert_comment', array($this, 'comment_inserted'), 99, 2);
    }

    /**
    * Callback when a new comment is inserted
    *
    * @param int $comment_id comment id
    * @param int $comment_object comment object
    */
    public function comment_inserted($comment_id, $comment_object) {
      $prefs = $this->prefs;
      $post_type = get_post_type($comment_object->comment_post_ID);
      if ($prefs[$post_type]['creds'] != 0) {
        $user_id = get_current_user_id();
        $this->core->add_creds(
          'post_type_comments',
          $user_id,
          $prefs[$post_type]['creds'],
          $prefs[$post_type]['log']
        );
      }
    }

    /**
    * Prints preferences fields to config hook options
    */
    public function preferences() {
      // Our settings are available under $this->prefs
      $prefs = $this->prefs;
      foreach ($this->available_post_types as $post_type):
        ?>
        <!-- First we set the amount -->
        <label class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for ' . str_replace('_', ' ', $post_type), $this->textdomain ) ); ?></label>
        <ol>
          <li>
            <div class="h2"><input type="text" name="<?php echo $this->field_name( array( $post_type => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( $post_type => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs[$post_type]['creds'] ); ?>" size="8" /></div>
          </li>
          <?php //TODO: Limits are not implemented yet ?>
          <!-- <li>
            <label for="<?php //echo $this->field_id( array( 'post' => 'limit' ) ); ?>"><?php //_e( 'Limit', 'mycred' ); ?></label>
            <?php //echo $this->hook_limit_setting( $this->field_name( array( $post_type => 'limit' ) ), $this->field_id( array( $post_type => 'limit' ) ), $prefs[$post_type]['limit'] ); ?>
          </li> -->
        </ol>
        <label class="subheader"><?php _e( 'Log template', $this->textdomain ); ?></label>
        <ol>
          <li>
            <div class="h2"><input type="text" name="<?php echo $this->field_name( array( $post_type => 'log' ) ); ?>" id="<?php echo $this->field_id( array( $post_type => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs[$post_type]['log'] ); ?>" class="long" /></div>
            <span class="description"><?php echo $this->available_template_tags( array( 'general', $post_type ) ); ?></span>
          </li>
        </ol>
        <?php
      endforeach;
    }
  }
}
add_action('mycred_init', 'mptc_init_my_hook');

/**
* Register custom hook
*
* @param array $installed array with installed hooks
*/
function mptc_register_comments_hook($installed) {
  global $textdomain;
  $installed['post_type_comments'] = array(
    'title'       => __('Post Type Comments', $textdomain),
    'description' => __('Add different points for comments to different post types (post type must support comments)', $textdomain),
    'callback'    => array('mycred_post_type_comments')
  );
  return $installed;
}
add_filter('mycred_setup_hooks', 'mptc_register_comments_hook' );

?>
