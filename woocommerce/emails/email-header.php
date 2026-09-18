<?php
/**
 * Email Header
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-header.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates/Emails
 * @version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!DOCTYPE html>
<html dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
        <style type="text/CSS">
body,
#body_style,
td,
h1,
h2 {
    font-weight: 200 !important;
}

#outlook A {
    padding:0;
}

A {
    color: #67A6F8;
    text-decoration: none;
    font-weight: normal;
}

img {
    border:none;
    font-weight:bold;
    height:auto;
    outline:none;
    text-decoration:none;
    text-transform:capitalize;
    line-height: 1;
}

#content img {
    margin: 0 0 2em 0;
}

#content_inner tfoot tr:first-child th.td {
	border-style: solid ! important;
	border-width: 1px 0 0 0 ! important;
	border-color: #000000 ! important;
}

#content_inner tfoot th.td {
	text-align: right ! important;
}
        </style>
    </head>
    <body width="100%" style="width:100% !important;margin:0;padding:0;-webkit-text-size-adjust:none;font-family: Helvetica,Arial;font-size:16px;">
        <center dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
            <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="margin: 0;padding: 0;height: 100% !important; width: 100% !important;font-size: 16px;">
                <tr><!-- header / spacer -->
                    <td valign="top">
                        <!-- headerimage would be here :) -->
                        <table border="0" cellpadding="0" cellspacing="0" width="800" height="96" id="header">
                            <tr><td>
                             </td></tr>
                        </table>
                    </td>
                </tr>
                <tr><!-- content -->
                    <td align="center" valign="top">
                        <table border="0" cellpadding="0" cellspacing="0" width="600" id="content">
                            <tr><td style="padding: 0 0 5em 0; line-height: 1.5; font-weight: 200 !important;" id="content_inner"><!-- content -->
                                <h1 style="margin: 0 0 1em 0; text-align: center; font-size: 3em; line-height: 1; font-weight: 200;"><?php echo $email_heading; ?></h1>
