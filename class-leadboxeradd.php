<?php

GFForms::include_addon_framework();

class GFLeadboxerAddOn extends GFAddOn {

    protected $_version = GF_LEADBOXER_ADDON_VERSION;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'leadboxer';
    protected $_path = 'leadboxer/init.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms LeadBoxer Add-On';
    protected $_short_title = 'LeadBoxer';

    private static $_instance = null;

    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new GFLeadboxerAddOn();
        }

        return self::$_instance;
    }

    public function init() {
        parent::init();
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
        add_action( 'gform_after_submission', array( $this, 'leadboxer_form_content_data'), 10, 2 );

    }

  public  function leadboxer_form_content_data( $entry, $form ) {

           $data_set_id = get_option('gravityformsaddon_leadboxer_settings')['leadbox_dataset_id'];
           if(!empty($_COOKIE['_otui']) || !empty($_COOKIE['_ots']) ){
             $leaderbox_cookie = wp_validate_logged_in_cookie($_COOKIE['_otui']);
             $leaderbox_session = wp_validate_logged_in_cookie($_COOKIE['_ots']);
             $leadbox_expload = explode(".",$leaderbox_cookie);
             $lead_sess_expload = explode(".",$leaderbox_session);
             $leadbox_user_id = $leadbox_expload[1].'.'.$leadbox_expload[0];
             $leadbox_session_id = $lead_sess_expload[1].'.'.$leadbox_expload[0];
           }

           $leadboxer_map_data = $form['leadboxer'];

           $map_data = array();
           if($leadboxer_map_data['checkbox_enabled'] == 1){
            $entry_data = array();
            $custom_field_data = array();
              if(!empty($entry)){
                foreach ($entry as $key => $value) {
                    $entry_data[$key] = $value;
                    $custom_field_data[$key] = $value;
                }
              }
            if(!empty($leadboxer_map_data['leadboxertCustomFields'])){
                $custom_field_array = array();
                foreach ($leadboxer_map_data['leadboxertCustomFields'] as  $value) {
                    if(!empty($value['custom_key']) && !empty($value['value'])){
                        $custom_field_array[$value['custom_key']] = sanitize_text_field($custom_field_data[$value['value']]);
                    }
                }
            }

            if(!empty($custom_field_array)){
                 $this->leadboxer_mapping_data_api($entry_data,$leadboxer_map_data,$custom_field_array,$data_set_id,$leadbox_user_id,$leadbox_session_id,$entry);
             }else{
                $this->leadboxer_mapping_data_api($entry_data,$leadboxer_map_data,$custom_field_array='',$data_set_id,$leadbox_user_id,$leadbox_session_id,$entry);
             }


        }
          ?>
          <?php
    }

  public  function leadboxer_mapping_data_api($entry_data,$leadboxer_map_data,$custom_field_array,$data_set_id,$leaderbox_uer_id,$leadbox_session_id,$entry){
           $first_name = (!empty($entry_data[$leadboxer_map_data['leadboxertStandardFields_first_name']]))
            ? sanitize_text_field($entry_data[$leadboxer_map_data['leadboxertStandardFields_first_name']])
            : '';
            $last_name = (!empty($entry_data[$leadboxer_map_data['leadboxertStandardFields_last_name']]))
            ? sanitize_text_field($entry_data[$leadboxer_map_data['leadboxertStandardFields_last_name']])
            : '';
            $email = (!empty($entry_data[$leadboxer_map_data['leadboxertStandardFields_email']]))
            ? sanitize_email($entry_data[$leadboxer_map_data['leadboxertStandardFields_email']])
            : '';
            $phone = (!empty($entry_data[$leadboxer_map_data['leadboxertStandardFields_phone']]))
            ? sanitize_text_field($entry_data[$leadboxer_map_data['leadboxertStandardFields_phone']])
            : '';
            $company_name = (!empty($entry_data[$leadboxer_map_data['leadboxertStandardFields_company_name']]))
            ? sanitize_text_field($entry_data[$leadboxer_map_data['leadboxertStandardFields_company_name']])
            : '';
            $job_title = (!empty($entry_data[$leadboxer_map_data['leadboxertStandardFields_job_title']]))
            ? sanitize_text_field($entry_data[$leadboxer_map_data['leadboxertStandardFields_job_title']])
            : '';
            $message = (!empty($entry_data[$leadboxer_map_data['leadboxertStandardFields_message']]))
            ? sanitize_textarea_field($entry_data[$leadboxer_map_data['leadboxertStandardFields_message']])
            : '';

            $leaderbox_array = array(
                        'si'   => $data_set_id,
                        'ti'   => 'Form Submit',
                        'uid'  => $leaderbox_uer_id,
                        'sid'  => $leadbox_session_id,
                        'first_name'=>$first_name,
                        'last_name'=>$last_name,
                        'email'=>$email,
                        'companyName'=>$company_name,
                        'phoneNumber'=>$phone,
                        'role'=>$job_title,
                        'lc'=>$entry['source_url'],
                        'message'=>$message,
                        'form_id'=>$entry['form_id'],
                        'proxy'  =>true
                        );
                    if(!empty($custom_field_array)){
                        $leaderbox_merge = array_merge($leaderbox_array,$custom_field_array);
                    }else{

                        $leaderbox_merge = $leaderbox_array;
                    }

                    $fields_string = http_build_query($leaderbox_merge);
                    $api_url = 'https://log.leadboxer.com/?'.$fields_string;

                    $response = wp_remote_get($api_url);
                    if ( is_wp_error( $response ) ) {
                        wc_add_notice( __( 'There is something wrong! Please try again later.', 'leadboxer-gravityforms' ), 'error' );
                        $passed = false;
                    }else{
                        $body = wp_remote_retrieve_body( $response );
                    }

                    GFAPI::delete_entry( $entry['id'] );

    }


    function form_submit_button( $button, $form ) {
        $settings = $this->get_form_settings( $form );
        if ( isset( $settings['enabled'] ) && true == $settings['enabled'] ) {
            $text   = $this->get_plugin_setting( 'mytextbox' );
            $button = "</pre>
<div>{$text}</div>
<pre>" . $button;
        }

        return $button;
    }

    public function plugin_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'LeadBoxer Add-On Settings', 'leadboxer-gravityforms' ),
                'description' => 'Enter the Dataset ID from your LeadBoxer account. If you do not have a LeadBoxer account, you can create one at <a href="https://www.leadboxer.com?utm_source=wp-plugins&utm_campaign=plugin-settings&utm_medium=plugins">leadboxer.com</a>', 'leadboxer-gravityforms',
                'fields' => array(
                    array(
                        'name'              => 'leadbox_dataset_id',
                        'label'             => esc_html__( 'Dataset Id', 'leadboxer-gravityforms' ),
                        'type'              => 'text',
                        'class'             => 'leadbox_small',
                        'feedback_callback' => array( $this, 'is_valid_setting' ),
                    ),
                )
            )
        );
    }


    public function form_settings_fields( $form ) {

        return array(
            array(
                'title'  => esc_html__( 'LeadBoxer Add-On: Form Settings', 'leadboxer-gravityforms' ),
                'description' => 'In order to get the correct form fields into LeadBoxer, you need to map the form fields to the corresponding LeadBoxer properties. For detailed instructions please see the <a href="https://docs.leadboxer.com/article/147-gravity-form-tracking">documentation</a>', 'leadboxer-gravityforms',
                'fields' => array(
                    array(
                        'label'   => esc_html__( 'Enable or disable for this form', 'leadboxer-gravityforms' ),
                        'type'    => 'checkbox',
                        'name'    => 'checkbox_enabled',
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'Enabled', 'leadboxer-gravityforms' ),
                                'name'  => 'checkbox_enabled',
                            ),
                        ),
                    ),

                    array(
                        'name'      => 'leadboxertStandardFields',
                        'description' => 'Please map all the form fields you are using to the corresponding LeadBoxer fields on the left. Email is the only required field.', 'leadboxer-gravityforms',
                        'label'     => '<h3>' . esc_html__( 'Map Default Fields', 'leadboxer-gravityforms' ) . '</h3>',
                        'type'      => 'field_map',
                        'title'     => 'LeadBoxer field',
                        'field_map' => $this->standard_fields_for_feed_mapping()
                    ),
                    array(
                        'name'      => 'leadboxertCustomFields',
                        'description' => 'Map custom form fields here. Simply enter a name of your custom property, and map it to the corresponding form field.', 'leadboxer-gravityforms',
                        'type'      => 'generic_map',
                        'label'     => '<h3>' . esc_html__( 'Map Custom Fields', 'leadboxer-gravityforms' ) . '</h3>',
                        'key_field' => array(
                            'title' => 'Custom Property',
                            'type'  => 'text',
                         ),
                        'value_field' => array(
                           'title' => 'Form Field',
                           'text'  => 'text',
                         ),
                        'validation_callback' => array( $this, 'custom_validate_custom_meta' ),
                    ),

                ),
            ),
        );
    }

    public function custom_validate_custom_meta( $field ) {
    $settings = $this->get_posted_settings();
    $metaData = $settings['leadboxertCustomFields'];
    if ( empty( $metaData ) ) {
        return;
    }
    $metaCount = count( $metaData );
    if ( $metaCount > 50 ) {
        $this->set_field_error( array( esc_html__( 'You may only have 50 custom keys.' ), 'leadboxer-gravityforms' ) );
        return;
    }

    foreach ( $metaData as $meta ) {
        if ( empty( $meta['custom_key'] ) && ! empty( $meta['value'] ) ) {
            $this->set_field_error( array( 'name' => 'leadboxertCustomFields' ), esc_html__( "A field has been mapped to a custom key without a name. Please enter a name for the custom key, remove the metadata item, or use the corresponding drop down to 'Select a Field'.", 'leadboxer-gravityforms' ) );
            break;
        } elseif ( strlen( $meta['custom_key'] ) > 50 ) {
            $this->set_field_error( array( 'name' => 'leadboxertCustomFields' ), sprintf( esc_html__( 'The name of custom key %s is too long. Please shorten this to 50 characters or less.', 'leadboxer-gravityforms' ), $meta['custom_key'] ) );
            break;
        }
    }
}

    public function standard_fields_for_feed_mapping() {
    $mapping_field = array(
        array(
            'name'          => 'first_name',
            'label'         => esc_html__( 'First Name', 'leadboxer-gravityforms' ),
            'required'      => false,
            'field_type'    => array(  ),
            'default_value' => $this->get_first_field_by_type( 'name', 3 ),
        ),
        array(
            'name'          => 'last_name',
            'label'         => esc_html__( 'Last Name', 'leadboxer-gravityforms' ),
            'required'      => false,
            'field_type'    => array(  ),
            'default_value' => $this->get_first_field_by_type( 'name', 6 ),
        ),
        array(
            'name'          => 'email',
            'label'         => esc_html__( 'Email', 'leadboxer-gravityforms' ),
            'required'      => true,
            'field_type'    => array( 'email' ),
            'default_value' => $this->get_first_field_by_type( 'email', 6 ),
        ),
        array(
            'name'          => 'phone',
            'label'         => esc_html__( 'Phone Number', 'leadboxer-gravityforms' ),
            'required'      => false,
            'field_type'    => array( ),
            'default_value' => $this->get_first_field_by_type( 'phone' ),
        ),
        array(
            'name'          => 'company_name',
            'label'         => esc_html__( 'Company Name', 'leadboxer-gravityforms' ),
            'required'      => false,
            'field_type'    => array( ),
            'default_value' => $this->get_first_field_by_type( 'company name' ),
        ),
        array(
            'name'          => 'job_title',
            'label'         => esc_html__( 'Job Title', 'leadboxer-gravityforms' ),
            'required'      => false,
            'field_type'    => array( ),
            'default_value' => $this->get_first_field_by_type( 'Job Title' ),
        ),
        array(
            'name'          => 'message',
            'label'         => esc_html__( 'Message', 'leadboxer-gravityforms' ),
            'required'      => false,
            'field_type'    => array(  ),
            'default_value' => $this->get_first_field_by_type( 'message' ),
        ),
    );
    return $mapping_field;
}

}

?>
