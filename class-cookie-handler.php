<?php

namespace LDTSQ;

if (!class_exists('cookieHandler')) {



    class cookieHandler {

        private static $instance;
        protected $cookie_meta_key = "_wdllsqr_cookies";

        private function __construct() {


            add_action('wp_ajax_wdllsqr_save_cookie', [ $this, 'save_cookie']);
           
            add_action('wp_ajax_wdllsqr_reset_saved_quiz', [ $this, 'reset_saved_quiz']);

            add_action('wp_enqueue_scripts', [ $this, 'load_scripts']);

            add_action('learndash_quiz_completed', [ $this, 'after_quiz_submission'], 10, 2);

            add_action('learndash_delete_user_data', [ $this, 'reset_cookie_value']);

            add_filter('wdllsqr_get_cookie_db', [ $this, 'load_wdllsqr_get_cookie_db'], 10, 3);

            //add_action( 'learndash_quiz_completed', [ $this, 'after_quiz_submission_all' ], 5, 2 );
        }

        /**

         * @var singleton

         */
        public static function get_instance() {



            if (!self::$instance) {

                self::$instance = new self();
            }



            return self::$instance;
        }

        /**

         * Used for Save & Next AJAX request.

         *

         * @return void

         */
        public function save_cookie() {


            
            if (wp_verify_nonce($_POST['auth'], get_current_user_id() . 'wdllsqr-cookie-nonce')) {





                $last_q_id = $_POST['last_question_id'];

                $last_time = $_POST['last_time'];

                $cookie_val = stripslashes(str_replace("\\n", " ", $_POST['cookie_val']));

                //$cookie_val = apply_filters( 'wdllsqr_save_cookie_val', $cookie_val );



                $this->set_cookies_in_db($_POST['pro_quiz_id'], $cookie_val, get_current_user_id(), $last_q_id, $last_time);


                 wp_send_json_success(["msg" => 'success']);
                die();
            }
        }

        /**

         * Used for Save & Next AJAX request.

         *

         * @return void

         */
        public function reset_saved_quiz() {



            if (wp_verify_nonce($_POST['auth'], get_current_user_id() . 'wdllsqr-cookie-nonce')) {



                $this->reset_cookies_in_db($_POST['pro_quiz_id'], get_current_user_id());



                die();
            }
        }

        /**

         * Actually saves cookie in DB

         */
        protected function set_cookies_in_db($pro_quiz_id = 0, $cookie_val = '', $user_id = 0, $last_q_id = '', $last_time = '') {



            if (empty($pro_quiz_id)) {

                return FALSE;
            }



            if (!$this->validate_user_id($user_id)) {

                return FALSE;
            }



            $cookie_data = get_user_meta($user_id, '_wdllsqr_cookies_all', TRUE);

            if (empty($cookie_data) || !is_array($cookie_data)) {

                $cookie_data = [];
            }



            if (!isset($cookie_data[$pro_quiz_id])) {

                $cookie_data[$pro_quiz_id] = [ 'cookie'];

                $cookie_data[$pro_quiz_id]['cookie'] = '';
            }



            if (isset($cookie_data[$pro_quiz_id])) {

                $saved_answers = trim($cookie_data[$pro_quiz_id]['cookie']);



                if (!empty($saved_answers)) {

                    $saved_answers = Json::decode($saved_answers, true);

                    //var_dump($saved_answers);
                } else {

                    $saved_answers = [];
                }



                $cookie_val = Json::decode($cookie_val);



                if ($cookie_val->type === 'essay') {

                    //$cookie_val->value = $_POST['raw_response'];
                }

                $saved_answers[$last_q_id] = $cookie_val;

                $cookie_data[$pro_quiz_id]['last_q_id'] = $last_q_id;

                $cookie_data[$pro_quiz_id]['cookie'] = json_encode(str_replace("\n", "<-n->", $saved_answers), JSON_UNESCAPED_UNICODE);
            }



            update_user_meta($user_id, '_wdllsqr_cookies_all', $cookie_data);
        }

        /**

         * Actually saves cookie in DB

         */
        protected function reset_cookies_in_db($pro_quiz_id = 0, $user_id = 0) {



            if (empty($pro_quiz_id)) {

                return FALSE;
            }



            if (!$this->validate_user_id($user_id)) {

                return FALSE;
            }



            $cookie_data = get_user_meta($user_id, '_wdllsqr_cookies_all', TRUE);

            if (empty($cookie_data) || !is_array($cookie_data)) {

                return false;
            }



            if (!isset($cookie_data[$pro_quiz_id])) {

                return false;
            }



            if (isset($cookie_data[$pro_quiz_id])) {



                unset($cookie_data[$pro_quiz_id]);
            }



            update_user_meta($user_id, '_wdllsqr_cookies_all', $cookie_data);



            return true;
        }

        /**

         * Does actions after quiz submission. Preferably to clear saved cookie from DB.

         */
        public function after_quiz_submission($quizdata, $current_user) {



            $pro_quiz_id = $quizdata['pro_quizid'];

            $user_id = $current_user->ID;

            $db_cookies = get_user_meta($user_id, $this->cookie_meta_key, TRUE);



            if (empty($db_cookies) || !isset($db_cookies[$pro_quiz_id])) {

                return;
            }



            // Let us clear DB cookie value after quiz submission.

            unset($db_cookies[$pro_quiz_id]);



            $db_cookies = array_filter($db_cookies);



            update_user_meta($user_id, $this->cookie_meta_key, $db_cookies);
        }

        /**

         * @var integer

         *

         * Validates user id. If user id is empty, try to get current user id.

         *



         */
        protected function validate_user_id($user_id) {



            if (empty($user_id)) {

                $user_id = get_current_user_id();

                if (empty($user_id)) {

                    return FALSE;
                }
            }



            return $user_id;
        }

        /**

         * Returns cookie values from the database.

         *

         * @param  mixed   $pro_quiz_id Integer for specific quiz's cookie, otherwise 'all' for all the values

         * @param  integer $user_id

         */
        public function get_cookie_from_db($pro_quiz_id = 'all', $user_id = 0) {



            $cookie = '{}';



            if (empty($pro_quiz_id)) {

                return $cookie;
            }



            $user_id = $this->validate_user_id($user_id);



            if (!$user_id) {



                return $cookie;
            }



            $db_cookies = get_user_meta($user_id, $this->cookie_meta_key, TRUE);



            if (isset($db_cookies[$pro_quiz_id])) {



                $cookie = [ $pro_quiz_id => $db_cookies[$pro_quiz_id]];
            } elseif ('all' == $pro_quiz_id) {



                $cookie = $db_cookies;
            }



            $cookie = apply_filters('wdllsqr_get_cookie_db', $cookie, $pro_quiz_id, $user_id);



            return $cookie;
        }

        public function load_scripts() {
         
            global $post_type;

            global $post;



            if (!is_archive() && in_array($post_type, [ 'sfwd-quiz']) && is_user_logged_in()) {

                $post_options_timeout = learndash_get_setting($post);



                // if (isset($post_options_timeout['wdllsqr_save_resume']) && 'yes' === $post_options_timeout['wdllsqr_save_resume']) {



                    $pro_quizzes = $this->get_enabled_quizzes_on_page();



                    if (is_user_logged_in() && !empty($pro_quizzes)) {
                        // Load required global variables.



                        global $wpdevlms_quiz_resume_config;



                        //$quizSettings = quizSettings::get_instance();



                        $min = ( SCRIPT_DEBUG ) ? '' : '';



                        wp_register_script('wpProQuiz_front_custom', plugin_dir_url(__FILE__) . 'src/assets/js/dummy' . $min . '.js', [ 'jquery'], 1.5, TRUE);



                        $data = [

                            'ajax_url' => admin_url('admin-ajax.php'),
                            'button_names' => [

                                'next' => "Save & Next",
                                'startQuiz' => " Resume Quiz ",
                            ],
                            'auth' => wp_create_nonce(get_current_user_id() . 'wdllsqr-cookie-nonce'),
                            'cookies' => $this->get_cookie_from_db('all'),
                            'expiry' => apply_filters('wdllsqr_default_cookie_expiry', 1400),
                            'enabled_quizzes' => $pro_quizzes,
                            'uid' => get_current_user_id(),
                        ];



                        wp_localize_script('wpProQuiz_front_custom', 'wdllsqr_front', $data);

                        wp_enqueue_script('wpProQuiz_front_custom');
                    }
                // }
            }
        }

        /**

         * Returns if setting is enabled for that quiz.

         *

         * @return boolean              TRUE if enabled

         */
        public function is_save_resume_enabled($pro_quiz_id = 0, $quiz_id = 0) {





            if (!$quiz_id) {



                if (empty($pro_quiz_id)) {



                    return FALSE;
                }



                $quiz_id = learndash_get_quiz_id_by_pro_quiz_id($pro_quiz_id);
            }





            if ($quiz_id) {



                $settings = get_post_meta($quiz_id, '_sfwd-quiz', TRUE);





                if (isset($settings['sfwd-quiz_wdllsqr_save_resume']) && $settings['sfwd-quiz_wdllsqr_save_resume']) {



                    return TRUE;
                }
            }



            return FALSE;
        }

        protected function get_enabled_quizzes_on_page() {



            global $post;



            $pro_quizzes = [];



            // If quiz single page



            if (is_singular('sfwd-quiz')) {



                $quiz_pro_id = get_post_meta($post->ID, 'quiz_pro_id', TRUE);



                if ($quiz_pro_id) {



//					if ( $this->is_save_resume_enabled( $quiz_pro_id ) ) {



                    $pro_quizzes[$quiz_pro_id] = 1;

//					}
                }
            } else {



                // To extract all the quizzes inserted in the page using 'ld_quiz' shortcode. This gives post IDs and not pro_qui_id.



                $ld_quizzes = $this->get_shortcode_attributes('ld_quiz', $post->post_content);



                if (!empty($ld_quizzes)) {



                    foreach ($ld_quizzes as $val) {



//						if ( $this->is_save_resume_enabled( 0, $val['quiz_id'] ) ) {



                        $pro_quizzes[$this->get_pro_quiz_id_by_post_id($val['quiz_id'])] = 1;

//						}
                    }
                }





                $LDAdvQuiz = $this->get_shortcode_attributes('LDAdvQuiz', $post->post_content);





                if (!empty($LDAdvQuiz)) {



                    foreach ($LDAdvQuiz as $val) {



//						if ( $this->is_save_resume_enabled( $val[0] ) ) {



                        $pro_quizzes[$val[0]] = 1;

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
        function get_shortcode_attributes($tag, $text) {



            preg_match_all('/' . get_shortcode_regex() . '/s', $text, $matches);



            $out = [];



            if (isset($matches[2])) {



                foreach ((array) $matches[2] as $key => $value) {



                    if ($tag === $value) {



                        $out[] = shortcode_parse_atts($matches[3][$key]);
                    }
                }
            }



            return $out;
        }

        function get_pro_quiz_id_by_post_id($post_id = 0) {





            if ($post_id) {



                return get_post_meta($post_id, 'quiz_pro_id', TRUE);
            }



            return FALSE;
        }

        /**

         * Remove cookie if user's data permanently deleted.

         *

         * @param  integer $user_id

         */
        public function reset_cookie_value($user_id) {





            if (!current_user_can('edit_users') || empty($user_id)) {



                return;
            }



            delete_user_meta($user_id, $this->cookie_meta_key);
        }

        public function load_wdllsqr_get_cookie_db($cookie, $pro_quiz_id, $user_id) {

            $saved_data = get_user_meta($user_id, '_wdllsqr_cookies_all', TRUE);

            //		var_dump($saved_data);
            //		var_dump($cookie);

            if (empty($cookie) && !empty($saved_data)) {

                if (isset($saved_data[$pro_quiz_id])) {

                    $cookie = [ $pro_quiz_id => $saved_data[$pro_quiz_id]];
                } elseif ('all' == $pro_quiz_id) {

                    $cookie = $saved_data;
                }



                return $cookie;
            } else if (!empty($cookie) && !empty($saved_data)) {

                foreach ($saved_data as $key => $value) {

                    if (!isset($cookie[$key]) || empty($cookie[$key])) {

                        $cookie[$key] = $value;
                    }
                }
            }



            return $cookie;
        }

        public function after_quiz_submission_all($quizdata, $current_user) {

            //   mail('ahsancheema26@gmail.com', "hihi", print_r($quizdata, true));

            $pro_quiz_id = $quizdata['pro_quizid'];

            $user_id = $current_user->ID;



            $db_cookies = get_user_meta($user_id, '_wdllsqr_cookies', TRUE);

            $save_db_cookies = get_user_meta($user_id, '_wdllsqr_cookies_all', TRUE);



            if (empty($save_db_cookies)) {

                $save_db_cookies = [];
            }



            if (empty($db_cookies) || !isset($db_cookies[$pro_quiz_id])) {

                return;
            }



            $save_db_cookies[$pro_quiz_id] = $db_cookies[$pro_quiz_id];



            update_user_meta($user_id, '_wdllsqr_cookies_all', $save_db_cookies);
        }

    }

}

abstract class Json {

    public static function getLastError($asString = FALSE) {

        $lastError = \json_last_error();



        if (!$asString)
            return $lastError;



        // Define the errors.

        $constants = \get_defined_constants(TRUE);

        $errorStrings = array();



        foreach ($constants["json"] as $name => $value)
            if (!strncmp($name, "JSON_ERROR_", 11))
                $errorStrings[$value] = $name;



        return isset($errorStrings[$lastError]) ? $errorStrings[$lastError] : FALSE;
    }

    public static function getLastErrorMessage() {

        return \json_last_error_msg();
    }

    public static function clean($jsonString) {

        if (!is_string($jsonString) || !$jsonString)
            return '';



        // Remove unsupported characters
        // Check http://www.php.net/chr for details

        for ($i = 0; $i <= 31; ++$i)
            $jsonString = str_replace(chr($i), "", $jsonString);



        $jsonString = str_replace(chr(127), "", $jsonString);



        // Remove the BOM (Byte Order Mark)
        // It's the most common that some file begins with 'efbbbf' to mark the beginning of the file. (binary level)
        // Here we detect it and we remove it, basically it's the first 3 characters.

        if (0 === strpos(bin2hex($jsonString), 'efbbbf'))
            $jsonString = substr($jsonString, 3);



        return $jsonString;
    }

    public static function encode($value, $options = 0, $depth = 512) {

        return \json_encode($value, $options, $depth);
    }

    public static function decode($jsonString, $asArray = TRUE, $depth = 512, $options = JSON_BIGINT_AS_STRING) {

        if (!is_string($jsonString) || !$jsonString)
            return NULL;



        $result = \json_decode($jsonString, $asArray, $depth, $options);



        if ($result === NULL)
            switch (self::getLastError()) {

                case JSON_ERROR_SYNTAX :

                    // Try to clean json string if syntax error occured

                    $jsonString = self::clean($jsonString);

                    $result = \json_decode($jsonString, $asArray, $depth, $options);

                    break;



                default:

                // Unsupported error
            }



        return $result;
    }

}
