(function (window) {
	'use strict';

	function getGalleryBaselineHeight(galleryEl) {
		const existing = galleryEl.dataset.cfgBaselineH;
		if (existing) {
			return parseFloat( existing );
		}

		const firstImg   = galleryEl.querySelector( '.woocommerce-product-gallery__image img' );
		const slide      = galleryEl.querySelector( '.woocommerce-product-gallery__image' );
		const slideWidth = slide ? slide.getBoundingClientRect().width : 0;

		const widthAttr  = parseFloat( firstImg ? firstImg.getAttribute( 'width' ) : 0 ) || 0;
		const heightAttr = parseFloat( firstImg ? firstImg.getAttribute( 'height' ) : 0 ) || 0;

		let baselineHeight = 0;
		if (slideWidth && widthAttr && heightAttr) {
			baselineHeight = slideWidth * (heightAttr / widthAttr);
		} else {
			const viewport = galleryEl.querySelector( '.flex-viewport' );
			baselineHeight = Math.min( (viewport ? viewport.getBoundingClientRect().height : 600), 900 );
		}

		galleryEl.dataset.cfgBaselineH = String( Math.round( baselineHeight ) );
		return baselineHeight;
	}

	function clearTemporarySizing(img) {
		if (img) {
			img.style.width     = '';
			img.style.height    = '';
			img.style.objectFit = '';
			img.style.display   = '';
		}
	}

	function syncViewportHeight(viewport, slide) {
		if ( ! viewport || ! slide) {
			return;
		}

		const slideHeight = Math.round( slide.getBoundingClientRect().height || 0 );
		if (slideHeight > 0) {
			viewport.style.height   = slideHeight + 'px';
			viewport.style.overflow = 'hidden';
			return;
		}

		viewport.style.height   = '';
		viewport.style.overflow = '';
	}

	async function updateWooGalleryImage(index, newUrl, thumbUrl, options) {
		const settings        = options || {};
		const gallerySelector = settings.gallerySelector || '.woocommerce-product-gallery';
		const logger          = settings.logger || null;

		const i       = index - 1;
		const gallery = document.querySelector( gallerySelector );
		if ( ! gallery) {
			throw new Error( 'Gallery not found' );
		}

		const wrapper = gallery.querySelector( '.woocommerce-product-gallery__wrapper' );
		const slides  = wrapper ? wrapper.querySelectorAll( '.woocommerce-product-gallery__image' ) : null;
		const slide   = slides ? slides[i] : null;
		if ( ! slide) {
			throw new Error( 'Slide ' + index + ' not found' );
		}

		const img      = slide.querySelector( 'img' );
		const anchor   = slide.querySelector( 'a' );
		const viewport = gallery.querySelector( '.flex-viewport' );
		if ( ! img) {
			throw new Error( 'Image element not found for slide ' + index );
		}

		const tUrl           = thumbUrl || newUrl;
		const baselineHeight = parseFloat( gallery.dataset.cfgBaselineH || '0' ) || getGalleryBaselineHeight( gallery );

		// Hold the gallery steady while the replacement image is loading.
		// These dimensions are temporary and are removed again once the real
		// image has loaded so Flexslider/WooCommerce can size the viewport naturally.
		if (baselineHeight && viewport) {
			viewport.style.height   = Math.round( baselineHeight ) + 'px';
			viewport.style.overflow = 'hidden';
		}

		img.src = newUrl;
		img.setAttribute( 'data-src', newUrl );
		img.setAttribute( 'data-large_image', newUrl );
		img.removeAttribute( 'srcset' );
		img.removeAttribute( 'sizes' );

		if (anchor) {
			anchor.href = newUrl;
		}

		if (baselineHeight) {
			img.style.width     = '100%';
			img.style.height    = Math.round( baselineHeight ) + 'px';
			img.style.objectFit = 'contain';
			img.style.display   = 'block';
		}

		slide.setAttribute( 'data-thumb', tUrl );
		slide.setAttribute( 'data-thumb-srcset', '' );
		slide.setAttribute( 'data-thumb-sizes', '(max-width: 100px) 100vw, 100px' );

		const thumbs   = gallery.querySelectorAll( '.flex-control-thumbs img' );
		const thumbImg = thumbs ? thumbs[i] : null;
		if (thumbImg) {
			const thumbItem = thumbImg.closest( 'li' );
			thumbImg.src    = tUrl;
			thumbImg.removeAttribute( 'srcset' );
			thumbImg.removeAttribute( 'sizes' );
			thumbImg.removeAttribute( 'onload' );
			if (thumbItem) {
				thumbItem.style.aspectRatio = '1 / 1';
				thumbItem.style.overflow    = 'hidden';
			}
			thumbImg.style.width          = '100%';
			thumbImg.style.height         = '100%';
			thumbImg.style.objectFit      = 'contain';
			thumbImg.style.objectPosition = 'center center';
			thumbImg.removeAttribute( 'width' );
			thumbImg.removeAttribute( 'height' );
		}

		await new Promise(
			function (resolve, reject) {
				if (img.complete) {
					if ((parseInt( img.naturalWidth, 10 ) || 0) > 0) {
						resolve();
					} else {
						reject( new Error( 'Image failed to load: ' + newUrl ) );
					}
					return;
				}
				img.addEventListener( 'load', resolve, { once: true } );
				img.addEventListener(
					'error',
					function () {
						reject( new Error( 'Image failed to load: ' + newUrl ) );
					},
					{ once: true }
				);
			}
		);

		const naturalWidth  = parseInt( img.naturalWidth, 10 ) || 0;
		const naturalHeight = parseInt( img.naturalHeight, 10 ) || 0;
		if (naturalWidth > 0 && naturalHeight > 0) {
			// Refresh the cached baseline from the actual replacement image.
			const slideWidth = slide.getBoundingClientRect().width || img.getBoundingClientRect().width || 0;
			if (slideWidth) {
				gallery.dataset.cfgBaselineH = String( Math.round( slideWidth * (naturalHeight / naturalWidth) ) );
			}

			// Keep WooCommerce lightbox dimensions in sync with the replaced image.
			img.setAttribute( 'data-large_image_width', String( naturalWidth ) );
			img.setAttribute( 'data-large_image_height', String( naturalHeight ) );
			img.setAttribute( 'width', String( naturalWidth ) );
			img.setAttribute( 'height', String( naturalHeight ) );

			if (anchor) {
				// Support both WooCommerce/PhotoSwipe attribute styles.
				anchor.setAttribute( 'data-size', naturalWidth + 'x' + naturalHeight );
				anchor.setAttribute( 'data-pswp-width', String( naturalWidth ) );
				anchor.setAttribute( 'data-pswp-height', String( naturalHeight ) );
			}
		}

		// Remove temporary image sizing, then lock the viewport to the real slide height
		// so the gallery keeps occupying space in the document flow.
		clearTemporarySizing( img );
		syncViewportHeight( viewport, slide );

		if (window.jQuery) {
			const $gallery = window.jQuery( gallery );
			const $img     = window.jQuery( img );

			// Remove stale zoom overlays so hover zoom cannot show an old source.
			window.jQuery( '.zoomImg', gallery ).remove();
			if (typeof $img.trigger === 'function') {
				$img.trigger( 'zoom.destroy' );
			}

			// Ask WooCommerce gallery to re-init zoom/lightbox bindings with new image attrs.
			if (typeof $gallery.trigger === 'function') {
				$gallery.trigger( 'woocommerce_gallery_init_zoom' );
				$gallery.trigger( 'woocommerce_gallery_reset_slide_position' );
			}

			window.jQuery( window ).trigger( 'resize' );
		}

		window.requestAnimationFrame(
			function () {
				syncViewportHeight( viewport, slide );
			}
		);

		if (typeof logger === 'function') {
			logger( 'WooCommerce image ' + index + ' updated' );
		}
	}

	window.cnf3DWebFlexslider = {
		getGalleryBaselineHeight: getGalleryBaselineHeight,
		updateWooGalleryImage: updateWooGalleryImage
	};
})( window );
