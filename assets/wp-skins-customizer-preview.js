(function( $, document ) {
    "use strict";

    console.log( "WP customizeready" );

    /**
     * Skins Customizer :: universal function
     */

    $.each( WP_Skins_Settings, function( key, css ) {
        wp.customize( key, function( setting ) {
            setting.bind( function( new_value ) {
                console.log( key );
                console.log( new_value );
                for (var css_selector in css) {
                    // add units, like "px" or "em"
                    var new_value_with_units = css[css_selector].units ? new_value + css[css_selector].units : new_value;

                    if ( css[css_selector].type == "style" ) {
                        // Apply styles direct to the element
                        $( css_selector ).css( css[css_selector].attribute, new_value_with_units );
                    } else {
                        // Apply styles via Dfining CSS style
                        define_css(key, css_selector, css[css_selector].attribute, new_value_with_units);
                    }
                }

            } );
        });

    });

    function define_css ( key, selector, attribute, value ) {
        var style_id = key+"-"+ murmurhash3_32_gc(selector, 5646);
        var style_css = selector + "{" + attribute + ":" + value + ";}";
        var $style_el = $( "style#" + style_id );
        if ( $style_el.length ) {
            $style_el.text( style_css );
        } else {
            $( "<style id='" + style_id + "'>" + style_css + "</style>" ).appendTo( $("body") );
        }
    }

	/**
	 * JS Implementation of MurmurHash3 (r136) (as of May 20, 2011)
	 *
	 * @author <a href="mailto:gary.court@gmail.com">Gary Court</a>
	 * @see http://github.com/garycourt/murmurhash-js
	 * @author <a href="mailto:aappleby@gmail.com">Austin Appleby</a>
	 * @see http://sites.google.com/site/murmurhash/
	 *
	 * @param {string} key ASCII only
	 * @param {number} seed Positive integer only
	 * @return {number} 32-bit positive integer hash
	 */
	function murmurhash3_32_gc (key, seed) {
		var remainder, bytes, h1, h1b, c1, c1b, c2, c2b, k1, i;

		remainder = key.length & 3; // key.length % 4
		bytes = key.length - remainder;
		h1 = seed;
		c1 = 0xcc9e2d51;
		c2 = 0x1b873593;
		i = 0;

		while (i < bytes) {
			k1 =
				  ((key.charCodeAt(i) & 0xff)) |
				  ((key.charCodeAt(++i) & 0xff) << 8) |
				  ((key.charCodeAt(++i) & 0xff) << 16) |
				  ((key.charCodeAt(++i) & 0xff) << 24);
			++i;

			k1 = ((((k1 & 0xffff) * c1) + ((((k1 >>> 16) * c1) & 0xffff) << 16))) & 0xffffffff;
			k1 = (k1 << 15) | (k1 >>> 17);
			k1 = ((((k1 & 0xffff) * c2) + ((((k1 >>> 16) * c2) & 0xffff) << 16))) & 0xffffffff;

			h1 ^= k1;
			h1 = (h1 << 13) | (h1 >>> 19);
			h1b = ((((h1 & 0xffff) * 5) + ((((h1 >>> 16) * 5) & 0xffff) << 16))) & 0xffffffff;
			h1 = (((h1b & 0xffff) + 0x6b64) + ((((h1b >>> 16) + 0xe654) & 0xffff) << 16));
		}

		k1 = 0;

		switch (remainder) {
			case 3: k1 ^= (key.charCodeAt(i + 2) & 0xff) << 16;
			case 2: k1 ^= (key.charCodeAt(i + 1) & 0xff) << 8;
			case 1: k1 ^= (key.charCodeAt(i) & 0xff);

				k1 = (((k1 & 0xffff) * c1) + ((((k1 >>> 16) * c1) & 0xffff) << 16)) & 0xffffffff;
				k1 = (k1 << 15) | (k1 >>> 17);
				k1 = (((k1 & 0xffff) * c2) + ((((k1 >>> 16) * c2) & 0xffff) << 16)) & 0xffffffff;
				h1 ^= k1;
		}

		h1 ^= key.length;

		h1 ^= h1 >>> 16;
		h1 = (((h1 & 0xffff) * 0x85ebca6b) + ((((h1 >>> 16) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
		h1 ^= h1 >>> 13;
		h1 = ((((h1 & 0xffff) * 0xc2b2ae35) + ((((h1 >>> 16) * 0xc2b2ae35) & 0xffff) << 16))) & 0xffffffff;
		h1 ^= h1 >>> 16;

		return h1 >>> 0;
	}
    
})( jQuery, document );