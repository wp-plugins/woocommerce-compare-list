<?php

// +----------------------------------------------------------------------+
// | Copyright 2014  Madpixels  (email : contact@madpixels.net)           |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+
// | Author: Eugene Manuilov <eugene.manuilov@gmail.com>                  |
// +----------------------------------------------------------------------+

// prevent direct access
if ( !defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 404 Not Found', true, 404 );
	exit;
}

// register action hooks
add_action( 'wp_enqueue_scripts', 'wccm_enqueue_compare_scripts' );

// register filter hooks
add_filter( 'the_content', 'wccm_render_compare_page' );

/**
 * Enqueues scripts and styles.
 *
 * @since 1.0.0
 * @action wp_enqueue_scripts
 *
 * @global boolean $is_IE Determines whether or not the current user agent is Internet Explorer.
 * @global boolean $is_opera Determines whether or not the current user agent is Opera.
 * @global boolean $is_gecko Determines whether or not the current user agent is Gecko based.
 */
function wccm_enqueue_compare_scripts() {
	global $is_IE, $is_opera, $is_gecko;

	if ( is_page() && get_option( 'wccm_compare_page' ) == get_queried_object_id() ) {
		$base_path = plugins_url( '/', dirname( __FILE__ ) );
		wp_enqueue_style( 'wccm-compare', $base_path . 'css/compare.css', array( 'dashicons' ), WCCM_VERISON );

		wp_enqueue_script( 'wccm-compare', $base_path . 'js/compare.js', array( 'jquery' ), WCCM_VERISON );
		wp_localize_script( 'wccm-compare', 'wccm', array(
			'ie'      => $is_IE,
			'gecko'   => $is_gecko,
			'opera'   => $is_opera,
			'cursors' => $base_path . 'cursors/',
		) );
	}
}

/**
 * Renders compare page.
 *
 * @since 1.0.0
 * @filter the_content
 *
 * @param string $content The initial page content.
 * @return string The updated page content.
 */
function wccm_render_compare_page( $content ) {
	if ( !is_page() || get_option( 'wccm_compare_page' ) != get_the_ID() ) {
		return $content;
	}

	$list = get_query_var( wccm_get_endpoint(), false );
	if ( $list ) {
		$list = array_filter( array_map( 'intval', explode( '-', $list ) ) );
	}

	if ( empty( $list ) ) {
		$list = wccm_get_compare_list();
		if ( empty( $list ) ) {
			return $content . '<p class="wccm-empty-compare">' . esc_html__( 'No products found to compare.', 'wccm' ) . '</p>';
		}
	}

	$products = array();
	foreach ( $list as $product_id ) {
		$product = get_product( $product_id );
		if ( $product ) {
			$products[$product_id] = $product;
		}
	}

	ob_start();

	echo '<div class="wccm-compare-table">';
		wccm_render_compare_header( $products );
		wccm_render_compare_attributes( $products );
	echo '</div>';

	$content .= ob_get_contents();
	ob_end_clean();

	return $content;
}

/**
 * Renders compare table header.
 *
 * @since 1.0.0
 *
 * @param array $products The compare items list.
 */
function wccm_render_compare_header( $products ) {
	echo '<div class="wccm-thead">';
		echo '<div class="wccm-tr">';
			echo '<div class="wccm-th">';
			echo '</div>';
			echo '<div class="wccm-table-wrapper">';
				echo '<table class="wccm-table" cellspacing="0" cellpadding="0" border="0">';
					echo '<tr>';
						foreach ( $products as $product_id => $product ) {
							echo '<td class="wccm-td">';
								echo '<div class="wccm-thumb">';
									echo '<a class="dashicons dashicons-no" href="', wccm_get_compare_link( $product_id, 'remove-from-list' ), '"></a>';
									echo get_the_post_thumbnail( $product_id, 'thumbnail' );
								echo '</div>';
								echo '<div>';
									echo '<a href="', get_permalink( $product_id ), '">', $product->get_title(), '</a>';
								echo '</div>';
								echo '<div class="price">';
									echo $product->get_price_html();
								echo '</div>';
							echo '</td>';
						}
					echo '<tr>';
				echo '</table>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
}

/**
 * Renders compare table attributes.
 *
 * @since 1.0.0
 *
 * @param array $products The compare items list.
 */
function wccm_render_compare_attributes( $products ) {
	$attributes = array();
	foreach ( $products as $product ) {
		foreach ( $product->get_attributes() as $attribute_id => $attribute ) {
			if ( !isset( $attributes[$attribute_id] ) ) {
				$attributes[$attribute_id] = array(
					'name'     => $attribute['name'],
					'products' => array(),
				);
			}

			$attributes[$attribute_id]['products'][$product->id] = $attribute['is_taxonomy']
				? wc_get_product_terms( $product->id, $attribute['name'], array( 'fields' => 'names' ) )
				: array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );
		}
	}

	echo '<div class="wccm-tbody">';
		foreach ( $attributes as $attribute ) {
			echo '<div class="wccm-tr">';
				echo '<div class="wccm-th">';
					echo wc_attribute_label( $attribute['name'] );
				echo '</div>';
				echo '<div class="wccm-table-wrapper">';
					echo '<table class="wccm-table" cellspacing="0" cellpadding="0" border="0">';
						echo '<tr>';
							foreach ( $products as $product ) {
								echo '<td class="wccm-td">';
									$values = !empty( $attribute['products'][$product->id] ) ? $attribute['products'][$product->id] : array( '&#8212;' );
									echo apply_filters( 'woocommerce_attribute', wpautop( wptexturize( implode( ', ', $values ) ) ), $attribute, $values );
								echo '</td>';
							}
						echo '</tr>';
					echo '</table>';
				echo '</div>';
			echo '</div>';
		}
	echo '</div>';
}