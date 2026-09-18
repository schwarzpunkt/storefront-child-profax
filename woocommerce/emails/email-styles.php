<?php
/**
 * Email Styles
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-styles.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates/Emails
 * @version 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
H2 {
	font-size:16px;
	color: #000000;
}

#inner_totals .td {
	border: 0;
	border-width: 0;
	padding: 4px 0;
	font-family: Helvetica,Arial ! important;
	font-size: 16px;
	vertical-align: top;
}

#inner_totals .tdl {
	border-width: 0 0 3px 0;
	padding: 4px 0;
}