<?php
/**
 * Plugin Name: Basic Contact Form
 * Plugin URI: http://www.functionsphp.com/basic-contact-form
 * Description: A very basic contact form shortcode. No-frills! If you want to change the messages or spam question you must edit the code! 
 * Version: 0.0.1
 * Author: Mokum Music
 * Author URI: http://www.mokummusic.com
 * Network: false
 * License: GPL2
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function enqueue_bcf_style() {
    wp_register_style('bcfStyleSheet',  plugins_url('/basic-contact-form.min.css', __FILE__ ),'','0.0.1');
}
add_action( 'wp_enqueue_scripts', 'enqueue_bcf_style');
add_shortcode('contactus', 'bcf_render_contact_form');

function bcf_render_contact_form() {
    wp_enqueue_style('bcfStyleSheet');
    $to = get_option('admin_email');
    $subject = "Someone sent a message from ".get_bloginfo('name');
    $headers = 'From: '. $_POST['message_email'] . "\r\n" .'Reply-To: ' . $_POST['message_email'] . "\r\n";
    $html = '<div id="contact-response">';
    if (isset($_POST['submitted'])) {
        if($_POST['message_human'] != "4") { // the answer to human question
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
            add_to_debug(array($to, $subject, $email_content, $headers, $sent));
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
    $html .= '<p><label for="message_email">Email: <span>*</span> <br><input type="text" name="message_email" value="'. esc_attr($_POST['message_email']).'"></label></p>';
    $html .= '<p><label for="message_text">Message: <span>*</span> <br><textarea type="text" name="message_text">'.esc_textarea($_POST['message_text']).'</textarea></label></p>';
    $html .= '<p><label for="message_human">Human Verification: <span>*</span> <br><input type="text" class="human-verify-input" name="message_human"> + 3 = 7</label>';
    $html .= '<input type="hidden" name="submitted" value="1"><input class="bcf-submit" type="submit"></p></form>';
    return $html;
}