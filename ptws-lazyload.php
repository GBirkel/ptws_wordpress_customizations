<?php
/*
Plugin Name: Poking Things With Sticks Extensions
Plugin URI:  http://www.mile42.net
Description: This plugin supports all the non-standard WP stuff I do on PTWS.  Among other things, it finds recent posted pictures on my Flickr feed and integrates them with recent WP posts in a fancypants way
Version:     2.00b1
Author:      Pokingthingswithsticks
Author URI:  http://www.pokingthingswithsticks.com
License:     GPL2
License URI: https://Icantbebothered.tolook.thisup.right.now

Written 2018 Mile42 (email : gbirkel@gmail.com)
This is free software: you can redistribute it and/or modify
it under the terms of the GPL2 License.

It is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

Uses LazyLoad Code by the WordPress.com VIP team, TechCrunch 2011 Redesign team, and Jake Goldman (10up LLC),
which uses jQuery.sonar by Dave Artz (AOL): http://www.artzstudio.com/files/jquery-boston-2010/jquery.sonar/
*/

namespace Poking_Things_With_Sticks_Lazyload;


function ptws_ll_build_attributes_string( $attributes ) {
    $strs = array();
    foreach ( $attributes as $name => $attribute ) {
        $value = $attribute['value'];
        if ( '' === $value ) {
            $strs[] = sprintf( '%s', $name );
        } else {
            $strs[] = sprintf( '%s="%s"', $name, esc_attr( $value ) );
        }
    }
    return implode( ' ', $strs );
}


function ptws_ll_process_image( $matches ) {
    // In case you want to change the placeholder image
    $placeholder_image = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    $old_attributes_str = $matches[2];
    $old_attributes = wp_kses_hair( $old_attributes_str, wp_allowed_protocols() );

    if ( empty( $old_attributes['src'] ) ) {
        return $matches[0];
    }

    $image_src = $old_attributes['src']['value'];

    // Remove src and lazy-src since we manually add them
    $new_attributes = $old_attributes;
    unset( $new_attributes['src'], $new_attributes['data-lazy-src'] );

    $new_attributes_str = ptws_ll_build_attributes_string( $new_attributes );

    return sprintf( '<img src="%1$s" data-lazy-src="%2$s" %3$s><noscript>%4$s</noscript>',
        $placeholder_image,
        esc_url( $image_src ),
        $new_attributes_str,
        $matches[0] );
}


function ptws_ll_add_image_placeholders( $content ) {

    // Don't lazyload for feeds, previews
    if( is_feed() || is_preview() )
        return $content;

    // Don't lazy-load if the content has already been run through previously
    if ( false !== strpos( $content, 'data-lazy-src' ) )
        return $content;

    // This is a pretty simple regex, but it works
    $content = preg_replace_callback( '#<(img)([^>]+?)(>(.*?)</\\1>|[\/]?>)#si', __NAMESPACE__ . '\ptws_ll_process_image', $content );

    return $content;
}


?>