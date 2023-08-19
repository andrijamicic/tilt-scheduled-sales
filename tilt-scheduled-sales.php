<?php
/*
Plugin Name: Tilt Scheduled Sales
Description: Tilt modification for scheduled sales in WooCommerce.
Version: 1.0
Author: Andrija Micic
*/

// Provera da li je konstanta ABSPATH definisana
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Ako nije, prekini izvršavanje i prikaži praznu stranicu
}

// Uklanjanje postojeće akcije WooCommerce scheduled sales
function remove_wc_scheduled_sales_action() {
    remove_action( 'woocommerce_scheduled_sales', 'wc_scheduled_sales' );
}
add_action( 'wp_loaded', 'remove_wc_scheduled_sales_action' );

// Modifikovana verzija funkcije wc_scheduled_sales
function custom_wc_scheduled_sales() {
    $data_store = WC_Data_Store::load( 'product' );

    // Sales which are due to start.
    $product_ids = $data_store->get_starting_sales();
    if ( $product_ids ) {
        do_action( 'wc_before_products_starting_sales', $product_ids );
        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );

            if ( $product ) {
                $sale_price = $product->get_sale_price();

                if ( $sale_price ) {
                    $product->set_price( $sale_price );
                    // Izbacivanje linije koja postavlja datum na "on sale" - $product->set_date_on_sale_from( '' );
                } else {
					$product->set_date_on_sale_to( '' );
					$product->set_date_on_sale_from( '' );
                }

                $product->save();
            }
        }
        do_action( 'wc_after_products_starting_sales', $product_ids );

        WC_Cache_Helper::get_transient_version( 'product', true );
        delete_transient( 'wc_products_onsale' );
    }

    // Sales which are due to end.
    $product_ids = $data_store->get_ending_sales();
    if ( $product_ids ) {
        do_action( 'wc_before_products_ending_sales', $product_ids );
        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );

            if ( $product ) {
                $regular_price = $product->get_regular_price();
                $product->set_price( $regular_price );
                $product->set_sale_price( '' );
                $product->set_date_on_sale_to( '' );
                $product->set_date_on_sale_from( '' );
                $product->save();
            }
        }
        do_action( 'wc_after_products_ending_sales', $product_ids );

        WC_Cache_Helper::get_transient_version( 'product', true );
        delete_transient( 'wc_products_onsale' );
    }
}


// Dodavanje izmenjene funkcije nazad kao akciju
add_action( 'woocommerce_scheduled_sales', 'custom_wc_scheduled_sales' );
