<?php

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
    // https://developer.wordpress.org/reference/functions/wp_kses_hair/
    // What an inane function name.
    // "Builds an attribute list from string containing attributes."
    $old_attributes = wp_kses_hair( $old_attributes_str, wp_allowed_protocols() );

    if ( empty( $old_attributes['src'] ) ) {
        return $matches[0];
    }

    $image_src = $old_attributes['src']['value'];

    // Remove src and lazy-src since we manually add them
    $new_attributes = $old_attributes;
    unset( $new_attributes['src'], $new_attributes['data-lazy-src'] );

    $new_attributes_str = ptws_ll_build_attributes_string( $new_attributes );

    return sprintf( '<img src="%1$s" data-lazy-src="%2$s" %3$s /><noscript>%4$s</noscript>',
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