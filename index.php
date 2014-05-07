<?php
/*
Plugin Name: WooCommerce Compare List
Plugin URI: http://wordpress.org/plugins/woocommerce-compare-list/
Description: The plugin adds ability to compare some products of your WooCommerce driven shop.
Version: 1.0.0
Author: Madpixels
Author URI: http://madpixels.net
License: GPL v2.0 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

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

// load appropriate files
require_once 'includes/common.php';

if ( is_admin() ) {
	require_once 'includes/settings.php';
} elseif ( intval( get_option( 'wccm_compare_page' ) ) > 0 ) {
	require_once 'includes/buttons.php';
	require_once 'includes/compare-page.php';
	require_once 'includes/catalog.php';
}

// activation and deactivation stuff
register_activation_hook( __FILE__, 'flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
