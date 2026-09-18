<?php
/**
 * Email Order Items
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-items.php.
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
 * @version     2.1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$i = 0;
foreach ( $items as $item_id => $item ) :
	$i++;
	$_product     = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
	$item_meta    = new WC_Order_Item_Meta( $item, $_product );
	if ($i == count($items)) {
		$border = "border-bottom:1px solid #AAAAAA;padding-bottom:8px;";
	} else {
		$border = "";
	}
	if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		?>
		
		<tr>
			<td valign="middle" width="32" style="<?php echo $border; ?>"><?php echo apply_filters( 'woocommerce_order_item_thumbnail', '<img src="'. ( $_product->get_image_id() ? current( wp_get_attachment_image_src( $_product->get_image_id(), 'thumbnail') ) : wc_placeholder_img_src() ) .'" height="32" width="32" style="margin: 1px 0 1px 0;">', $item ); ?></td>
			<td valign="middle" width="40" align="right" style="<?php echo $border; ?>"><b><?php echo apply_filters( 'woocommerce_email_order_item_quantity', $item['qty'], $item ); ?></b></td>
			<td valign="middle" colspan="2" style="<?php echo $border; ?>font-weight: 200;padding-left:4px;"> <b>x <?php echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false ); ?></b><?php if ($show_sku && is_object($_product) && $_product->get_sku() ) {echo ' '.$_product->get_sku();}; ?></td>
			<td valign="middle" align="right" style="<?php echo $border; ?>font-weight: 200;"><?php echo $order->get_formatted_line_subtotal( $item ); ?></td>
		</tr>
		<?php
	}

	if ( $show_purchase_note && is_object( $_product ) && ( $purchase_note = get_post_meta( $_product->id, '_purchase_note', true ) ) ) : ?>
		<tr>
			<td colspan="4" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: Helvetica,Arial;"><?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) ); ?></td>
		</tr>
	<?php endif; ?>

<?php endforeach; ?>
