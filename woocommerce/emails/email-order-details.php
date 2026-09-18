<?php
/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-details.php.
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
 * @version     2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php if ( ! $sent_to_admin ) : ?>
	<h2><?php printf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ); ?></h2>
<?php else : ?>
	<h2><a class="link" href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->id . '&action=edit' ) ); ?>"><?php printf( __( 'Order #%s', 'woocommerce'), $order->get_order_number() ); ?></a> (<?php printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order->order_date ) ), date_i18n( wc_date_format(), strtotime( $order->order_date ) ) ); ?>)</h2>
<?php endif; ?>

<br><br>
<table width="100%" cellpadding="0" cellspacing="0" style="line-height: 1.5;">
		<?php echo $order->email_order_items_table( array(
			'show_sku'      => $sent_to_admin,
			'show_image'    => true,
			'image_size'    => array( 32, 32 ),
			'plain_text'    => $plain_text,
			'sent_to_admin' => $sent_to_admin
		) ); ?>
</table>
<table border="0" cellpadding="0" cellspacing="0" style="width: 100%;font-family: Helvetica,Arial;font-size:16px;" id="inner_totals">
	<tfoot>
		<?php
			if ( $totals = $order->get_order_item_totals() ) {
				$totals = (array)$totals;
				foreach ($totals as $total) {
					if (strpos($total['label'], 'esamt:') > 0) {
						preg_match_all('/(\d*\.\d{2})/u', $total['value'], $chf, PREG_SET_ORDER);
						echo '
					<tr>
						<td class="td" style="width:33%;"> </td>
						<th class="td" scope="row" style="font-weight:200 ! important;text-align:left;">MWST inkl.</th>
						<td class="td" style="text-align:right;">'.$chf[1][0].' CHF</td>
					</tr>
					<tr>
						<td class="td" style="width:50%;"></td>
						<th class="tdl" scope="row" style="font-weight:200 ! important;text-align:left;border-bottom: 3px double #AAAAAA;"><b>Rechnungsbetrag</b></th>
						<td class="tdl" style="text-align:right;border-bottom: 3px double #AAAAAA;"><b>'.$chf[0][0].' CHF</b></td>
					</tr>';
					} else if (!strpos($total['label'], 'ahlungsart')) {
						preg_match('/\d.*?\d\d/', $total['value'], $chf);
						echo'
					<tr>
						<td class="td" style="width:33%;"></td>
						<th class="td" scope="row" style="font-weight:200 ! important;text-align:left;">'.str_replace(':', '', $total['label']).'</th>
						<td class="td" style="text-align:right;">'.$chf[0].' CHF</td>
					</tr>';
					}
				}
			}
		?>
	</tfoot>
</table>
<br><br>
	
<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>
