<?php

/**

 * Plugin Name: ACL Custom Functionality

 * Description: Ahsan

 * Version: 1.0

 * Author: Ahsan

 * Author URI:

 * Plugin URI: /

 * Text Domain: LDTSQ

 * Domain Path: /languages

 */



namespace LDTSQ;



// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {

	die;

}

define( 'LDTSQ\DIR', plugin_dir_path( __FILE__ ) );

define( 'LDTSQ\DIR_FILE', DIR . basename( __FILE__ ) );

define( 'LDTSQ\OVERRIDE_DIR', trailingslashit( DIR . 'lib' ) );

define( 'ACL_VERSION', '1.0' );
define( 'ACL_SCRIPT_VERSION_TOKEN', ACL_VERSION . '-' . time() );

/**

 * Class InitializePlugin

 * @package LDTSQ

 */

class InitializePlugin {



	/**

	 * The plugin name

	 *

	 * @since    1.0.0

	 * @access   private

	 * @var      string

	 */

	private $plugin_name = 'ACL Customization';



	/**

	 * The plugin name acronym

	 *

	 * @since    1.0.0

	 * @access   private

	 * @var      string

	 */

	private $plugin_prefix = 'ldtsq';



	/**

	 * The plugin version number

	 *

	 * @since    1.0.0

	 * @access   private

	 * @var      string

	 */

	private $plugin_version = '2.0';



	/**

	 * The full path and filename

	 *

	 * @since    1.0.0

	 * @access   private

	 * @var      string

	 */

	private $path_to_plugin_file = __FILE__;



	/**

	 * Allows the debugging scripts to initialize and log them in a file

	 *

	 * @since    1.0.0

	 * @access   private

	 * @var      string

	 */

	private $log_debug_messages = false;



	/**

	 * The instance of the class

	 *

	 * @since    1.0.0

	 * @access   private

	 * @var      Object

	 */

	private static $instance = null;

	public static $settings_metabox_key = 'learndash-quiz-progress-settings';





	/**

	 * class constructor

	 */

	private function __construct() {



		// Load Utilities

		$this->initialize_utilities();



		// Load Configuration

		$this->initialize_config();



		// Load the plugin files

		add_action( 'plugins_loaded', [ $this, 'boot_plugin' ] );

		//$this->boot_plugin();

		$this->wp_plugin_dir   = DIR;

		 $this->wp_pro_quiz_dir = dirname( $this->wp_plugin_dir ) . DIRECTORY_SEPARATOR . "sfwd-lms" . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "wp-pro-quiz";

		add_action( 'plugins_loaded', [ $this, 'init' ] );
		add_action('init', [$this, 'load_my_scrips' ]);
		include 'class-cookie-handler.php';
		\LDTSQ\cookieHandler::get_instance();
                
			
			//add_filter('learndash_post_args', array($this, 'single_quiz_settings_lag'));
			
			// 3.0+  - Add auto complete setting to LearnDash Lessons (auto creates field and loads value)
			add_filter( 'learndash_settings_fields', [ __CLASS__, 'single_quiz_settings',], 10, 2 ); // 3.0+
			// 3.0+ - Save custom settings field
			add_filter( 'learndash_metabox_save_fields', [
				__CLASS__,
				'save_single_quiz_settings',
			], 90, 3 );
			
			add_filter( 'learndash_quiz_completed_result_settings', [ __CLASS__, 'show_questions_results' ], 10, 2 );
			wp_deregister_script('wpProQuiz_front_javascript');

         
			
              wp_deregister_script('jquery-cookie');
            wp_enqueue_script(
				'jquery-cookie',
                    Utilities::get_js('jquery.cookie') . '.js',
				array( 'jquery', 'jquery-ui-sortable' ),
				'1.4.0',
				true
			);

//                add_action('wp_enqueue_scripts', array($this, 'in_group_management_scripts'));

//                wp_enqueue_style('student_class_tbl', plugins_url('/src/assets/css/student-class.css', __FILE__), false, '1.0.0', 'all');

//                wp_enqueue_style('user_register_tbl', plugins_url('/src/assets/css/vone.css', __FILE__), false, '1.0.0', 'all');



	}



	public function load_my_scrips()
	{
		if( is_user_logged_in() )
			{
            wp_enqueue_script(
				'wpProQuiz_front_javascript',
				Utilities::get_js('wpProQuiz_front') . '.js',
				array('jquery', 'jquery-ui-sortable'),
				ACL_SCRIPT_VERSION_TOKEN,
				true
			);
		   }else{
			wp_enqueue_script(
				'wpProQuiz_front_javascript',
				Utilities::get_js('wpProQuiz_front_guest') . '.js',
				array('jquery', 'jquery-ui-sortable'),
				ACL_SCRIPT_VERSION_TOKEN,
				true
			);
		   }
	}



	/**

	 * Creates singleton instance of class

	 *

	 * @since 1.0.0

	 *

	 * @return InitializePlugin $instance The InitializePlugin Class

	 */

	public static function get_instance() {



		if ( null === self::$instance ) {

			self::$instance = new self();

		}



		return self::$instance;

	}



	/**

	 * Initialize Static singleton class that has shared function and variables that can be used anywhere in WP

	 *

	 * @since 1.0.0

	 */

	private function initialize_utilities() {



		include_once( dirname( __FILE__ ) . '/src/utilities.php' );

		Utilities::set_date_time_format();



	}



	/**

	 * Initialize Static singleton class that configures all constants, utilities variables and handles activation/deactivation

	 *

	 * @since 1.0.0

	 */

	private function initialize_config() {



		include_once( dirname( __FILE__ ) . '/src/config.php' );

		$config_instance = Config::get_instance();



		$plugin_name = apply_filters( $this->plugin_prefix.'_plugin_name', $this->plugin_name );



		$config_instance->configure_plugin_before_boot( $plugin_name, $this->plugin_prefix, $this->plugin_version, $this->path_to_plugin_file, $this->log_debug_messages );



	}



	/**

	 * Initialize Static singleton class auto loads all the files needed for the plugin to work

	 *

	 * @since 1.0.0

	 */

	public function boot_plugin() {

		if ( defined( 'LEARNDASH_VERSION' ) ) {

			include_once( dirname( __FILE__ ) . '/src/boot.php' );

			Boot::get_instance();

		}

	}

	

	/**

	 * Plugin load function

	 * Removes WP Pro Quiz autoload and instantiate our own autoload

	 *

	 * @return void

	 */

	public function init() {

		spl_autoload_unregister( 'wpProQuiz_autoload' );

		spl_autoload_register( [ $this, 'wp_pro_quiz_autoload_custom' ] );

	}

	/**

	 * Check if Class overrided

	 *

	 * @return boolean

	 */

	public function checkClassOverride( $load_class ) {

		$class_list = [];

		$dirs       = array_filter( glob( OVERRIDE_DIR . DIRECTORY_SEPARATOR . "*" ), 'is_dir' );

		foreach ( $dirs as $dir ) {

			$dh = opendir( $dir );

			if ( is_dir( $dir ) ) {

				while ( FALSE !== ( $filename = readdir( $dh ) ) ) {

					$pos = strpos( $filename, ".php" );

					if ( $pos !== FALSE ) {

						$class_list[] = str_replace( ".php", "", $filename );

					}

				}

			}
                      

			closedir( $dh );

		}
		

		if ( in_array( $load_class, $class_list ) ) {

			return TRUE;

		} else {

			return FALSE;

		}

	}
        private function wp_pro_quiz_autoload_custom( $class ) {
            
//                              if($_SERVER['REMOTE_ADDR'] == "119.160.96.37")
//                        {
//                          $class = "WpProQuiz_Controller_Front";
//                            
//                      var_dump($class);
//                            echo '-----------------------------';
//                        }
		$c = explode( '_', $class );
//		var_dump($c);

		if ( $c === FALSE || count( $c ) != 3 || $c[0] !== 'WpProQuiz' ) {
			return;
		}
		
		$dir = '';
		
		switch ( $c[1] ) {
			case 'View':
				$dir = 'view';
				break;
			case 'Model':
				$dir = 'model';
				break;
			case 'Helper':
				$dir = 'helper';
				break;
			case 'Controller':
				$dir = 'controller';
				break;
			case 'Plugin':
				$dir = 'plugin';
				break;
			default:
				return;
		}
		$overriden = $this->checkClassOverride( $class );
//		var_dump($overriden);
		if ( $overriden ) {
			$dyn_dir = $this->wp_plugin_dir; //Override class with current plugin
		} else {
			 $dyn_dir = $this->wp_pro_quiz_dir;
		}
		
		if ( file_exists( $dyn_dir . '/lib/' . $dir . '/' . $class . '.php' ) ) {
			include_once $dyn_dir . '/lib/' . $dir . '/' . $class . '.php';
		}
	}
	/**
		 * Adds save and resume setting at quiz level
		 *
		 * @param  array $post_args
		 *
		 * @return array    settings args
		 */
		public function single_quiz_settings_lag( $post_args ) {
			
			if ( isset( $post_args['sfwd-quiz'] ) ) {
				$post_args['sfwd-quiz']['fields'] = array_merge(
					$post_args['sfwd-quiz']['fields'],
					[
						'wdllsqr_save_resume' => [
							'name'      => __( 'Save & Resume', 'LDTSQ' ),
							'type'      => 'checkbox',
							'help_text' => __( sprintf( 'To save %s progress and resume later." setting.', \LearnDash_Custom_Label::label_to_lower( 'quiz' ) ), 'LDTSQ' ),
							'default'   => '',
							//'show_in_rest' => TRUE,
						],
					]
				);
			}
			
			//var_dump($post_args['sfwd-quiz']);die;
			return $post_args;
		}
		
		/**
		 * @param $setting_option_fields
		 * @param $settings_metabox_key
		 *
		 * @return mixed
		 */
		public static function single_quiz_settings( $setting_option_fields, $settings_metabox_key ) {
			global $post;
			
			if ( $settings_metabox_key === self::$settings_metabox_key ) {
				$learndash_post_settings = (array) learndash_get_setting( $post, NULL );
				$value                   = '';
				if ( isset( $learndash_post_settings['wdllsqr_save_resume'] ) ) {
					if ( ! empty( $learndash_post_settings['wdllsqr_save_resume'] ) ) {
						$value = $learndash_post_settings['wdllsqr_save_resume'];
					}
				}
				
				$setting_option_fields['wdllsqr_save_resume'] = [
					'name'      => 'wdllsqr_save_resume',
					'label'     => __( 'Save & Resume', 'LDTSQ' ),
					'type'      => 'checkbox',
					'help_text' => __( sprintf( 'To save %s progress and resume later.', \LearnDash_Custom_Label::label_to_lower( 'quiz' ) ), 'LDTSQ' ),
					'options'   => [
						'yes' => __( sprintf( 'Save %s progress and resume later.', \LearnDash_Custom_Label::label_to_lower( 'quiz' ) ), 'LDTSQ' ),
					],
					'value'     => $value,
				];
			} if( $settings_metabox_key === 'learndash-quiz-results-options' ){
				$learndash_post_settings = (array) learndash_get_setting( $post, NULL );
				$value                   = '';
				if ( isset( $learndash_post_settings['_btnViewQuestionHiddenPassed'] ) ) {
					if ( ! empty( $learndash_post_settings['_btnViewQuestionHiddenPassed'] ) ) {
						$value = $learndash_post_settings['_btnViewQuestionHiddenPassed'];
					}
				}
				
				$setting_option_fields['btnViewQuestionHiddenPassed'] = [
					'name'           => 'btnViewQuestionHiddenPassed',
					'type'           => 'checkbox-switch',
					'label'          => sprintf(
					// translators: placeholder: Questions.
						esc_html_x( 'Show %s/ Answers to passed students only', 'placeholder: Questions', 'learndash' ),
						learndash_get_custom_label( 'questions' )
					),
					'value'          => $value,
					'default'        => 'on',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_answer_feedback',
				];
			}
			
			return $setting_option_fields;
		}
		
		/**
		 * Save post metadata when a post is saved.
		 *
		 * @param int  $post_id The post ID.
		 * @param post $post    The post object.
		 * @param bool $update  Whether this is an existing post being updated or not.
		 */
		public static function save_single_quiz_settings( $settings_field_updates, $settings_metabox_key, $settings_screen_id ) {
			global $post;
			if ( $settings_metabox_key === self::$settings_metabox_key ) {
				if (
					isset( $_POST[ $settings_metabox_key ] )
					&& isset( $_POST[ $settings_metabox_key ]['wdllsqr_save_resume'] )
				) {
					$auto_complete_setting_value = sanitize_text_field( $_POST[ $settings_metabox_key ]['wdllsqr_save_resume'] );
					learndash_update_setting( $post, 'wdllsqr_save_resume', $auto_complete_setting_value );
					//var_dump(365*24*60*60);
					$timelimit = 365 * 24 * 60 * 60;
					learndash_update_setting( $post, 'timeLimitCookie', $timelimit );
					$_POST['learndash-quiz-admin-data-handling-settings']['timeLimitCookie']         = $timelimit;
					$_POST['learndash-quiz-admin-data-handling-settings']['timeLimitCookie_enabled'] = 'on';
					//	die;
				} else {
					$auto_complete_setting_value = sanitize_text_field( 'no' );
					learndash_update_setting( $post, 'wdllsqr_save_resume', $auto_complete_setting_value );
				}
			}
			
			if ( $settings_metabox_key === 'learndash-quiz-results-options' ) {
				
				if (
					isset( $_POST['learndash-quiz-results-options'] )
					&& isset( $_POST['learndash-quiz-results-options']['btnViewQuestionHiddenPassed'] )
				) {
					$auto_complete_setting_value = sanitize_text_field( $_POST['learndash-quiz-results-options']['btnViewQuestionHiddenPassed'] );
					learndash_update_setting( $post, '_btnViewQuestionHiddenPassed', $auto_complete_setting_value );
					
					//	die;
				} else {
					$auto_complete_setting_value = sanitize_text_field( '' );
					learndash_update_setting( $post, '_btnViewQuestionHiddenPassed', $auto_complete_setting_value );
				}
			}
			
			return $settings_field_updates;
		}
		protected function get_enabled_quizzes_on_page() {
			
			global $post;
			
			$pro_quizzes = [];
			
			// If quiz single page
			
			if ( is_singular( 'sfwd-quiz' ) ) {
				
				$quiz_pro_id = get_post_meta( $post->ID, 'quiz_pro_id', TRUE );
				
				if ( $quiz_pro_id ) {
					
					//					if ( $this->is_save_resume_enabled( $quiz_pro_id ) ) {
					
					$pro_quizzes[ $quiz_pro_id ] = 1;
					//					}
				}
			} else {
				
				// To extract all the quizzes inserted in the page using 'ld_quiz' shortcode. This gives post IDs and not pro_qui_id.
				
				$ld_quizzes = $this->get_shortcode_attributes( 'ld_quiz', $post->post_content );
				
				if ( ! empty( $ld_quizzes ) ) {
					
					foreach ( $ld_quizzes as $val ) {
						
						//						if ( $this->is_save_resume_enabled( 0, $val['quiz_id'] ) ) {
						
						$pro_quizzes[ $this->get_pro_quiz_id_by_post_id( $val['quiz_id'] ) ] = 1;
						//						}
					}
				}
				
				
				$LDAdvQuiz = $this->get_shortcode_attributes( 'LDAdvQuiz', $post->post_content );
				
				
				if ( ! empty( $LDAdvQuiz ) ) {
					
					foreach ( $LDAdvQuiz as $val ) {
						
						//						if ( $this->is_save_resume_enabled( $val[0] ) ) {
						
						$pro_quizzes[ $val[0] ] = 1;
						//						}
					}
				}
			}
			
			return $pro_quizzes;
		}
		
		/**
		 * Returns shortcode attrbutes in array format.
		 * Credits to https://wordpress.stackexchange.com/questions/172275/extract-attribute-values-from-every-shortcode-in-post#answer-172285
		 *
		 * @param  string $tag  Shortcode
		 * @param  string $text May be post content
		 *
		 * @return array            shortcode attributes
		 */
		function get_shortcode_attributes( $tag, $text ) {
			
			preg_match_all( '/' . get_shortcode_regex() . '/s', $text, $matches );
			
			$out = [];
			
			if ( isset( $matches[2] ) ) {
				
				foreach ( (array) $matches[2] as $key => $value ) {
					
					if ( $tag === $value ) {
						
						$out[] = shortcode_parse_atts( $matches[3][ $key ] );
					}
				}
			}
			
			return $out;
		}
		
		public static function show_questions_results( $results, $quizdata ){
			
			$learndash_post_settings = (array) learndash_get_setting( $quizdata['quiz'], NULL );
			$value                   = '';
			if ( isset( $learndash_post_settings['_btnViewQuestionHiddenPassed'] ) ) {
				if ( ! empty( $learndash_post_settings['_btnViewQuestionHiddenPassed'] ) ) {
					$value = $learndash_post_settings['_btnViewQuestionHiddenPassed'];
				}
			}
			if( 'on' === $value && $results['showViewQuestionButton'] == 1 ){
				if ( $quizdata['pass'] != 1 ){
					$results['showViewQuestionButton'] = 0;
				}
			
			}
			//var_dump($quizdata);
			//var_dump($results);
			return $results;
		}
	

}

// Let's run it

InitializePlugin::get_instance();