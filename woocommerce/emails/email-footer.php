<?php
/**
 * Email Footer
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-footer.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>



                            </td></tr>
                        </table>
                    </td>
                </tr>
                <tr><!-- footer -->
                    <td align="center" valign="top" bgcolor="#67A6F8" style="padding: 2em 0;">
                        <table border="0" cellpadding="0" cellspacing="0" width="600"><!-- footer -->
                            <tr>
                                <td colspan="3" align="center"><a href="https://www.profax.ch"><img alt="profax" src="<?php
								if ( $img = get_option( 'woocommerce_email_header_image' ) ) {
									echo esc_url( $img );
								}
							?>" width="151" height="63" border="0" style="max-width:151px;max-height:63px;margin:32px 0 64px 0;color:#FFFFFF;font-size:48px; font-weight: normal; font-weight: 200; text-transform: lowercase;" ></a></td>
                            </tr>
                            <tr>
                                <td valign="top" width="195" style="color:#FFFFFF; font-weight: 200;">
profax Verlag AG<br />
Postfach 53<br />
CH-8617 Mönchaltorf<br />
Schweiz
                                </td><td valign="top" width="165" style="color:#FFFFFF;">
<a style="color:#FFFFFF; font-weight: 200;" href="tel:+41449109206">+41 44 910 92 06</a><br />
<a style="color:#FFFFFF; font-weight: 200;" href="https://www.profax.ch">www.profax.ch</a><br />
<a style="color:#FFFFFF; font-weight: 200;" href="mailto:info@profax.ch">info@profax.ch</a>
                                </td><td valign="top" width="240" style="color:#FFFFFF; font-weight: 200;">
© <?php echo date('Y'); ?> profax Verlag AG.<br />
Sie erhalten diese E-Mail als<br />
profax Verlag AG Kunde.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>
