<?php
/**
 * Plugin Name: Basic Contact Form
 * Plugin URI: http://www.functionsphp.com/basic-contact-form
 * Description: A very basic contact form shortcode. No-frills! If you want to change the messages or spam question, you can do so in the admin settings.
 * Version: 0.0.1
 * Author: Mokum Music
 * Author URI: http://www.mokummusic.com
 * License: GPL2
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function enqueue_bcf_style() {
    wp_register_style('bcfStyleSheet',  plugins_url('/basic-contact-form.min.css', __FILE__ ),'','0.0.2');
}
add_action( 'wp_enqueue_scripts', 'enqueue_bcf_style');
add_action( 'admin_menu', 'bcf_add_admin_menu' );
add_action( 'admin_init', 'bcf_settings_init' );


function bcf_add_admin_menu(  ) { 
    add_options_page( 'Basic Contact Form', 'Basic Contact Form', 'manage_options', 'basic_contact_form', 'bcf_options_page' );
}

function bcf_settings_init(  ) { 
    register_setting( 'pluginPage', 'bcf_settings' );
    add_settings_section(
        'bcf_pluginPage_section', 
        __( 'No bloat contact form. So slim, Dr Atkins would be proud!', 'wordpress' ), 
        'bcf_settings_section_callback', 
        'pluginPage'
    );
    add_settings_field( 
        'bcf_to_address', 
        __( 'Send emails to', 'wordpress' ), 
        'bcf_to_address_render', 
        'pluginPage', 
        'bcf_pluginPage_section' 
    );
    add_settings_field( 
        'bcf_spamtrap', 
        __( 'Add Spamtrap Q&A', 'wordpress' ), 
        'bcf_spamtrap_render', 
        'pluginPage', 
        'bcf_pluginPage_section' 
    );
    add_settings_field( 
        'bcf_question', 
        __( 'Spamtrap Question', 'wordpress' ), 
        'bcf_question_render', 
        'pluginPage', 
        'bcf_pluginPage_section' 
    );
    add_settings_field( 
        'bcf_answer', 
        __( 'Spamtrap Answer', 'wordpress' ), 
        'bcf_answer_render', 
        'pluginPage', 
        'bcf_pluginPage_section' 
    );
}

function bcf_to_address_render(  ) { 
    $options = get_option( 'bcf_settings' );
    ?>
    <input type='email' name='bcf_settings[bcf_to_address]' value='<?php echo $options['bcf_to_address']?$options['bcf_to_address']:get_option('admin_email'); ?>'>
    <?php
}

function bcf_spamtrap_render(  ) { 
    $options = get_option( 'bcf_settings' );
    ?>
    <input type='checkbox' name='bcf_settings[bcf_spamtrap]' <?php checked( $options['bcf_spamtrap'], 1 ); ?> value='1'>
    <?php
}

function bcf_question_render(  ) { 
    $options = get_option( 'bcf_settings' );
    ?>
    <input type='text' name='bcf_settings[bcf_question]' value='<?php echo $options['bcf_question']?$options['bcf_question']:'2 + 3 = '; ?>'>
    <?php
}

function bcf_answer_render(  ) { 
    $options = get_option( 'bcf_settings' );
    ?>
    <input type='text' name='bcf_settings[bcf_answer]' value='<?php echo $options['bcf_answer']?$options['bcf_answer']:'5'; ?>'>
    <?php
}

function bcf_settings_section_callback(  ) { 
    echo __( 'To use, just add the shortcode [contactus] to your contact page. See the plugin\'s homepage for more info <a href="http://functionsphp.com">Basic Contact Form</a>.', 'wordpress' );
}

function bcf_options_page(  ) { 
    ?>
    <div class="wrap">
    <form action='options.php' method='post'>      
        <h2>Basic Contact Form</h2>   
        <?php
        settings_fields( 'pluginPage' );
        do_settings_sections( 'pluginPage' );
        submit_button();
        ?>   
    </form>
    </div>
    <?php
}
// FRONT END FUNCTIONS
add_shortcode('contactus', 'bcf_render_contact_form');
function bcf_render_contact_form() {
    wp_enqueue_style('bcfStyleSheet');
    $options = get_option( 'bcf_settings' );
    $to = $options['bcf_to_address']?$options['bcf_to_address']:get_option('admin_email');
    $subject = "New Contact Form Message on ".get_bloginfo('name');
    $headers = 'From: '. $_POST['message_email'] . "\r\n" .'Reply-To: ' . $_POST['message_email'] . "\r\n";
    $html = '<div id="contact-response">';
    if (isset($_POST['submitted'])) {
        if($options['bcf_spamtrap'] == 1 && $_POST['message_human'] != $options['bcf_answer']) {
        $html .= '<div class="bcf-error">Human verification Failed.</div>';
        } elseif (!filter_var($_POST['message_email'], FILTER_VALIDATE_EMAIL)) {
            $html .= '<div class="bcf-error">Email Address Invalid.</div>';
        } elseif (empty($_POST['message_name']) || empty($_POST['message_text'])) {
            $html .= '<div class="bcf-error">Please supply all information.</div>';
        } elseif (!isset($_POST['basic_contact_form']) || !wp_verify_nonce( $_POST['basic_contact_form'],'basic_contact_form' )) {
            $html .= '<div class="bcf-error">Permissions Error.</div>';
        } else {
            $email_content = strip_tags("Name: ".$_POST['message_name']."\r\nEmail: ".$_POST['message_email']);
            $email_content .= "\r\nIP Address: ".$_SERVER['REMOTE_ADDR']."\r\n\r\nMessage:\r\n".$_POST['message_text'];
            $sent = wp_mail($to, $subject, $email_content, $headers);

            if($sent) {
                $html .= '<div class="bcf-success">Thanks! Your message has been sent.</div>'; //message sent!
            } else {
                $html .= '<div class="bcf-error">Message was not sent. Try Again.</div>'; //message wasn't sent
            }
        }
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $html .= '<div class="bcf-error">Please supply all information.</div>';
    }
    $html .= '</div><form action="'. get_permalink(). '" method="post">';
    $html .= wp_nonce_field( 'basic_contact_form','basic_contact_form',true,false );
    $html .= '<p><label for="name">Name: <span>*</span> <br><input type="text" name="message_name" value="'. esc_attr($_POST['message_name']).'"></label></p>';
    $html .= '<p><label for="message_email">Email: <span>*</span> <br><input type="email" name="message_email" value="'. esc_attr($_POST['message_email']).'"></label></p>';
    $html .= '<p><label for="message_text">Message: <span>*</span> <br><textarea type="text" rows=10 name="message_text">'.esc_textarea($_POST['message_text']).'</textarea></label></p><p>';
    if ($options['bcf_spamtrap'] == 1) $html .= '<label for="message_human">Human Verification: <span>*</span> <br><div class="bcf-question">'.$options['bcf_question'].'</div> <input type="text" class="human-verify-input" name="message_human"></label>';
    $html .= '<input type="hidden" name="submitted" value="1"><input class="bcf-submit" type="submit"></p></form>';
    return $html;
}