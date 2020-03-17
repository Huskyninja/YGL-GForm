<?php

GFForms::include_addon_framework();
 
class YGL_Gform extends GFAddOn {
 
    protected $_version = YGL_GFORM_VERSION;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'ygl_gform';
    protected $_path = 'ygl_gform/ygl_gform.php';
    protected $_full_path = __FILE__;
    protected $_title = 'You\'ve Got Leads Add-On for Gravity Forms';
    protected $_short_title = 'YGL GForm';
 
    private static $_instance = null;
	
	/**
	 * Get an instance of this class.
	 *
	 * @return YGL_Gform
	 */
    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new YGL_Gform();
        }
 
        return self::$_instance;
    }
	
	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 */
    public function init() {
        parent::init();
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
		add_action( 'gform_after_submission', array( $this, 'ygl_after_submission' ), 10, 2 );
    }
	
	/**
	 * This function maps the fields and then sends the data to the endpoint.
	 *
	 * @param array $entry The entry currently being processed.
	 * @param array $form The form currently being processed.
	 */		
	public function ygl_after_submission( $entry, $form ) {

		if (!function_exists('write_log')) {

			function write_log($log) {
				if (true === WP_DEBUG) {
					if (is_array($log) || is_object($log)) {
						error_log(print_r($log, true));
					} else {
						error_log($log);
					}
				}
			}

		}
		
		$active_form = $form['id'];
		
		$settings = $this->get_form_settings($form);
		$plugin_settings = $this->get_plugin_settings();
		
		$send_form = '';
		if (isset($settings['send_form'])) {
			$send_form = $settings['send_form'];
		}
		
		if ($send_form == '1') {
			
			$send_mail = false;
			if (isset($plugin_settings['send_debug_email']) && ($plugin_settings['send_debug_email'])) {
				if (isset($plugin_settings['debug_email']) && !empty($plugin_settings['debug_email']) && is_email($plugin_settings['debug_email'])) {
					$send_mail = true;
					$target_email = $plugin_settings['debug_email'];
					$site_name = get_bloginfo('name');
					$email_subject = 'YGL GForm Debug mail for form id ' . $active_form . ' from ' . $site_name;
				} else {
					write_log('There is an issue with the debug email attached to the YGL Gform configuration. Please check the configuration.');
				}
			}
		
			if (empty($plugin_settings['username']) || empty($plugin_settings['password'])) {
				$message = 'YGL GForm has a form set to send, but no Username or Password has been set. Form ID: ' . $active_form . ' Please set the Username and Password in the YGL GForm settings menu.';
				write_log($message);
				if ($send_mail) {
					wp_mail($target_email, $email_subject, $message);
				}
				return;
			} else {
				$username = $plugin_settings['username'];
				$password = $plugin_settings['password'];
			}
			
			// write_log('YGL sending form ' . $active_form);
			
			$json_settings = json_encode($settings);
			
			$lead_source_name = $plugin_settings['lead_source_name'];
			$lead_source_id = $plugin_settings['lead_source_id'];
			$lead_source_rank = $plugin_settings['lead_source_rank'];
			
			$referral_sources = array(
				'LeadSourceName' => $lead_source_name,
				'LeadSourceId' => $lead_source_id,
				'LeadSourceRank' => $lead_source_rank,
			);
			
			$quick_query['ReferralSources'] = array($referral_sources);
			
			if ( isset($settings['ygl_fields_first_name']) && !empty($settings['ygl_fields_first_name']) ) {
				$map_first_name = $settings['ygl_fields_first_name'];
				$quick_query['PrimaryContact']['FirstName'] = $entry[$map_first_name];
			}
			
			if ( isset($settings['ygl_fields_last_name']) && !empty($settings['ygl_fields_last_name']) ) {
				$map_last_name = $settings['ygl_fields_last_name'];
				$quick_query['PrimaryContact']['LastName'] = $entry[$map_last_name];
			}
			
			if ( isset($settings['ygl_fields_email_address']) && !empty($settings['ygl_fields_email_address']) ) {
				$map_email = $settings['ygl_fields_email_address'];
				$quick_query['PrimaryContact']['Address']['Email'] = $entry[$map_email];
			}
			
			if ( isset($settings['ygl_fields_phone']) && !empty($settings['ygl_fields_phone']) ) {
				$map_phone = $settings['ygl_fields_phone'];
				$quick_query['PrimaryContact']['Address']['PhoneHome'] = $entry[$map_phone];
			}

			$json_query = json_encode($quick_query);
			
			$auth_key = $username . ':' . $password;
			$encode_key = base64_encode($auth_key);
			
			if(isset($settings['community_id']) && !empty($settings['community_id'])) {
				$community = $settings['community_id'];
			} else {
				$message = 'YGL Gform has a form set to send, but no Community ID is atached to the form. Form ID: ' . $active_form . ' Please set the Community ID in the form\'s setting page.';
				write_log($message);
				if ($send_mail) {
					wp_mail($target_email, $email_subject, $message);
				}
				return;
			}
			
			$base_url = $plugin_settings['target_url'];
			$post_url = rtrim($base_url, '/') . '/' . $community . '/leads';
			
			$send_w_curl = false;
			
			if ($settings['use_curl']) {
				$send_w_curl = true;
			}
			
			if ( $send_w_curl ) {
			
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $post_url);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_query);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
				// curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);
				
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
					'Authorization: BASIC ' . $encode_key,
					'Accept: application/json',
					'Content-Type: application/json'                        
					)
				);
				
				$result = curl_exec($ch);
				$error = curl_error($ch);
				
				$info = curl_getinfo($ch);
				$response = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
				
				// write_log('cURL used to post to You\'ve Got Leads. Query Sent: ' . $json_query . ' Response: ' . $response . ' Result: ' . $result);
				
				if ($result === false) {
					$error = curl_error($ch);
					$error_number = curl_errno($ch);
					$message = 'cURL error posting Gravity Form ID #' . $active_form . ' to You\'ve Got Leads. Query Sent: ' . $json_query . ' Error information: ' . $error_number . ' ' . $error;
					
				} else {
					$message = 'cURL used to post Gravity Form ID #' . $active_form . ' to You\'ve Got Leads. Query Sent: ' . $json_query . ' Response: ' . $response . ' Result: ' . $result;
				}
				
				write_log($message);
				
				if ($send_mail) {
					wp_mail($target_email, $email_subject, $message);
				}
			
			} else {
			
				$headers = array (
					'Authorization' => 'BASIC ' . $encode_key,
					'Accept' => 'application/json',
					'Content-type' => 'application/json'
				);

				$em_connect = array (
					'method' => 'POST',
					'timeout' => 15,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => $headers,
					'body' => $json_query,
					'cookies' => array()
				);

				$response = wp_remote_post( $post_url, $em_connect );
				
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					$message = 'Wordpress Remote Post error posting Gravity Form ID #' . $active_form . ' to You\'ve Got Leads. Query Sent: ' . $json_query . ' Error information: ' . $error_message;
				} else {
					$message = 'Wordpress Remote Post used to post Gravity Form ID #' . $active_form . ' to You\'ve Got Leads. Query Sent: ' . $json_query . ' Response: ' . wp_remote_retrieve_response_code($response) . ' - ' . wp_remote_retrieve_response_message($response). ' Result: ' . wp_remote_retrieve_body($response);
				}
				
				write_log($message);
				
				if ($send_mail) {
					wp_mail($target_email, $email_subject, $message);
				}
			
			}
		
		} else {
			// write_log('YGL not sending form ' . $active_form);
		}

	}
 
	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
    public function scripts() {
        $scripts = array(
            array(
                'handle'  => 'my_script_js',
                'src'     => $this->get_base_url() . '/js/my_script.js',
                'version' => $this->_version,
                'deps'    => array( 'jquery' ),
                'strings' => array(
                    'first'  => esc_html__( 'First Choice', 'ygl_gform' ),
                    'second' => esc_html__( 'Second Choice', 'ygl_gform' ),
                    'third'  => esc_html__( 'Third Choice', 'ygl_gform' )
                ),
                'enqueue' => array(
                    array(
                        'admin_page' => array( 'form_settings' ),
                        'tab'        => 'ygl_gform'
                    )
                )
            ),
 
        );
 
        return array_merge( parent::scripts(), $scripts );
    }

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
    public function styles() {
        $styles = array(
            array(
                'handle'  => 'my_styles_css',
                'src'     => $this->get_base_url() . '/css/my_styles.css',
                'version' => $this->_version,
                'enqueue' => array(
                    array( 'field_types' => array( 'poll' ) )
                )
            )
        );
 
        return array_merge( parent::styles(), $styles );
    }
 
	/**
	 * Add the text in the plugin settings to the bottom of the form if enabled for this form.
	 *
	 * @param string $button The string containing the input tag to be filtered.
	 * @param array $form The form currently being displayed.
	 *
	 * @return string
	 */
    function form_submit_button( $button, $form ) {
        $settings = $this->get_form_settings( $form );
        if ( isset( $settings['enabled'] ) && true == $settings['enabled'] ) {
            $text   = $this->get_plugin_setting( 'mytextbox' );
            $button = "<div>{$text}</div>" . $button;
        }
 
        return $button;
    }

	/**
	 * Creates a custom page for this add-on.
	 */		
    public function plugin_page() {
        $instructions = '';
		$instructions .= '<p>For use only with Gravity Forms v1.9 or greater.</p>';
		$instructions .= '<h2>Main Settings</h2>';
		$instructions .= '<p>Main for settings can be found under admin -> Forms -> Settings -> YGL GForm. You will need a YGL username and password. The base endpoint url may be edited if necessary.</p>';
		$instructions .= '<p>You may need to enter a LeadSourceName, LeadSourceID, and LeadSourceRank. Leave the default values if you have not received any information from YGL.</p>';
		$instructions .= '<h3>Sending a Debug Email</h3>';
		$instructions .= '<p>You can send a debug email for all submissions that contain logging information which can be useful if you do not have logging enabled. Select "Send a debug email" to enable this feature, and enter a valid email under "Debug email address". This will send an email containing logging information for all forms submitted to You\'ve Got Leads.</p>';
		$instructions .= '<h2>Form Settings</h2>';
		$instructions .= '<p>Individual form settings can be found under admin -> Forms -> Forms -> {form name} -> Settings -> YGL GForm.</p>';
		$instructions .= '<p>Select the "Send this form to You\'ve Got Leads" checkbox to attach the form. You will need to set the Community ID, as the default value is only a placeholder and will not work.</p>';
		$instructions .= '<p>By default this plugin uses Remote Post (wp_remote_post) to send form data. This can be changed to to use cURL. If you have cURL installed and wish to use this method, select this checkbox.</p>';
		$instructions .= '<h3>Field Mapping</h3>';
		$instructions .= '<p>To map the form fields, select the relevant Field (to be mapped for YGL) to the Form Field (from the Gravity Form).</p>';
		$instructions .= '<p>The form field must be of the correct type. The mapping is as follows:</p>';
		$instructions .= '<ul>';
		$instructions .= '<li>First Name -> textfield</li>';
		$instructions .= '<li>Last Name -> textfield</li>';
		$instructions .= '<li>Email Address -> email</li>';
		$instructions .= '<li>Phone -> phone</li>';
		$instructions .= '</ul>';
		$instructions .= '<p>So make sure when creating your form that you use the correct form field types for the YGL field mapping.</p>';
		
		echo $instructions;	
    }

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
    public function plugin_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'YGL GForm Settings', 'ygl_gform' ),
                'fields' => array(
                    array(
                        'name'              => 'username',
                        'tooltip'           => esc_html__( 'You\'ve Got Leads user name', 'ygl_gform' ),
                        'label'             => esc_html__( 'User Name', 'ygl_gform' ),
                        'type'              => 'text',
                        'class'             => 'small',
						'required' => true,
						'default_value' => 'username',
                    ),
					array(
						'name' => 'password',
						'tooltip' => esc_html__('You\'ve Got Leads password', 'ygl_gform'),
						'label' => esc_html__('Password', 'ygl_gform'),
						'type' => 'text',
						'class' => 'small',
						'required' => true,
						'default_value' => 'password',
					),
					array(
						'label' => esc_html__('The base You\'ve Got Leads Endpoint URL', 'ygl_gform'),
						'type' => 'text',
						'name' => 'target_url',
						'tooltip' => esc_html__('The base endpoint url.'),
						'required' => true,
						'default_value' => 'https://www.youvegotleads.com/api/properties/',
						'style' => 'width: 400px;',
						'validation_callback' => array($this, 'validate_config_settings'),
						'error_message' => esc_html__('Invalid URL. Make sure you are using the full URL including https://.', 'ygl_gform'),
					),
					array(
						'label' => 'LeadSourceName',
						'type' => 'text',
						'name' => 'lead_source_name',
						'required' => true,
						'default_value' => 'Web-Form Lead',
					),
					array(
						'label' => 'LeadSourceId',
						'type' => 'text',
						'name' => 'lead_source_id',
						'required' => true,
						'default_value' => '2286386',
					),
					array(
						'label' => 'LeadSourceRank',
						'type' => 'text',
						'name' => 'lead_source_rank',
						'required' => true,
						'default_value' => '1',
					),
					array(
						'label' => esc_html__('Send a debug email', 'ygl_gform'),
						'type' => 'checkbox',
						'name' => 'send_debug_email',
						'choices' => array(
							array(
								'label' => esc_html__('Yes', 'ygl_gform'),
								'name' => 'send_debug_email'
							),
						),
					),
					array(
						'label' => esc_html__('Debug email address', 'ygl_gform'),
						'type' => 'text',
						'name' => 'debug_email',
						'default_value' => 'someone@example.com',
						'tooltip' => esc_html__('Enter a valid email address.', 'ygl_gform'),
						'style' => 'width: 300px;',
					),
                )
            )
        );
    }
	
	/**
	 * Validate the end point URL for YGL with https
	 *
	 * @param array $field_settings 
	 * 		The settings of the validated field
	 * @param string $field_value
	 *		The value of the field being validated
	 * 
	 * @return bool
	 */
	 
	 // https://www.youvegotleads.com/api/properties/
	
	public function validate_config_settings($field_settings = array(), $field_value = '') {
		
		$valid = false;
		
		if (filter_var($field_value, FILTER_VALIDATE_URL)) {
			$valid = true;
		}
		
		if (!preg_match('/https\:\/\//i', $field_value)) {
			$valid = false;
		}
	
		if (!$valid) {
			$this->set_field_error($field_settings, rgar($field_settings, 'error_message'));
			return false;
		}
		
		return true;
		
	}
	
 	/**
	 * Configures the settings which should be rendered on the feed edit page in the Form Settings > You've Got Leads GForm area.
	 *
	 * @return array
	 */
    public function form_settings_fields($form) {
		
        return array(
			array(
				'title' => esc_html__('You\'ve Got Leads GForm Settings', 'ygl_gform'),
				'fields' => array(
					array(
						'label' => esc_html__('Send this form to You\'ve Got Leads'),
						'type' => 'checkbox',
						'name' => 'send_form',
						'tooltip' => esc_html__('Select to send form submissions to You\'ve Got Leads.', 'ygl_gform'),
						'choices' => array(
							array(
								'label' => esc_html__('Yes', 'ygl_gform'),
								'name' => 'send_form'
							),
						),
					),
					array(
						'label' => esc_html__('Use cURL', 'ygl_gform'),
						'type' => 'checkbox',
						'name' => 'use_curl',
						'tooltip' => esc_html__('Send form data using cURL. If unselected, Wordpress Remote Post will be used.', 'ygl_gform'),
						'choices' => array(
							array(
								'label' => esc_html__('Yes', 'ygl_gform'),
								'name' => 'use_curl',
							),
						),
					),
					array(
						'label' => esc_html__('Community ID', 'ygl_gform'),
						'type' => 'text',
						'name' => 'community_id',
						'tooltip' => esc_html__('The You\'ve Got Leads Community ID.', 'ygl_gform'),
						'required' => true,
						'default_value' => 'xxxxxxx',
					),					
				),			
				
			),
			array(
				'title'  => esc_html__( 'Map You\'ve Got Leads Fields', 'ygl_gform' ),
				'fields' => array(
					array(
						'name'      => 'ygl_fields',
						'label'     => esc_html__( 'Map Fields', 'ygl_gform' ),
						'type'      => 'field_map',
						'field_map' => $this->ygl_fields_for_feed_mapping(),
						'tooltip'   => '<h6>' . esc_html__('Map Fields', 'ygl_gform' ) . '</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective third-party service fields.', 'ygl_gform'),
					),
				),
			),
        );
    }
	
 	/**
	 * Configures the mapping fiels on the GForm config page.
	 *
	 * @return array
	 */
	public function ygl_fields_for_feed_mapping() {
		return array(
			array(
				'name'          => 'first_name',
				'label'         => esc_html__( 'First Name', 'ygl_gform' ),
				'required'      => false,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'tooltip' => esc_html__('Must be a text field type', 'ygl_gform'),
				'default_value' => $this->get_first_field_by_type( 'name', 3 ),
			),
			array(
				'name'          => 'last_name',
				'label'         => esc_html__( 'Last Name', 'ygl_gform' ),
				'required'      => false,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'tooltip' => esc_html__('Must be a text field type', 'ygl_gform'),
				'default_value' => $this->get_first_field_by_type( 'name', 6 ),
			),
			array(
				'name'          => 'email_address',
				'label'         => esc_html__( 'Email Address', 'ygl_gform' ),
				'required'      => true,
				'field_type'    => array( 'email', 'hidden' ),
				'tooltip' => esc_html__('Must be an email field type', 'ygl_gform'),
				'default_value' => $this->get_first_field_by_type( 'email' ),
			),
			array(
				'name' => 'phone',
				'label' => esc_html__('Phone', 'ygl_gform'),
				'required' => false,
				'field_type' => array('name', 'phone', 'hidden'),
				'tooltip' => esc_html__('Must be a text phone type', 'ygl_gform'),
				'default_value' => $this->get_first_field_by_type( 'phone' ),
			),
		);
	}
 
}
