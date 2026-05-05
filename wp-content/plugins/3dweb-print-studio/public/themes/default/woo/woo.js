(function ($) {
	'use strict';

	$( document ).ready(
		function () {
			if (typeof dwebPsConfig === 'undefined') {
				return;
			}

			const core = window.cnf3DWebCore;
			if ( ! core) {
				return;
			}

			const themeHooks = {

				buttonSelector: '.single_add_to_cart_button',
				gallerySelector: '.woocommerce-product-gallery',

				initStartButton: function () {
					const selector = this.buttonSelector;
					core.logDebug( 'Button selector found:', selector );
					core.changeTextOnButton( selector, core.config.translations.startConfiguration );

					$( document ).on(
						'click',
						selector,
						(e) => {
                        e.preventDefault();
                        core.logDebug( 'Button clicked' );
                        core.changeTextOnButton( selector, core.config.translations.loading );
                        core.startNewSession( selector );
						}
					);
				},

				onSessionLoading: function (isLoading, config) {
					const $button = $( this.buttonSelector );
					if (isLoading) {
						$button.html( config.translations.loading );
						const sessionText = config.translations.sessionLoading || 'Opening configurator...';
						this.showGalleryLoader( sessionText );
					} else {
						this.hideGalleryLoader();
						if (config.teamReference) {
							$button.html( `Start ${config.teamReference}` );
						} else {
							$button.html( config.translations.startConfiguration );
						}
					}
				},

				onBackFromSession: function (config) {
					const sleep = (ms) => new Promise( (resolve) => setTimeout( resolve, ms ) );

					const replaceMainImageWithRetry = async( imageUrl, maxRetries = 20, interval = 500 ) => {
						let lastError               = null;
						for (let attempt = 1; attempt <= maxRetries; attempt++) {
							try {
								await this.replaceMainImage( imageUrl );
								return;
							} catch (error) {
								lastError = error;
								core.logDebug( `replaceMainImage attempt ${attempt} / ${maxRetries} failed`, error );
								await sleep( interval );
							}
						}

						throw lastError || new Error( 'Could not replace main image' );
					};

					const applyMainImage              = (assets) => {
						const mainImageUrl            = core.addHeightToUrl( assets.main_0.url, 600 );
						const cacheBustedMainImageUrl = mainImageUrl + (mainImageUrl.includes( '?' ) ? '&' : '?') + '_cb=' + Date.now();
						core.logDebug( 'Replacing main image with:', mainImageUrl );

						return core.checkIfReady( cacheBustedMainImageUrl )
						.then(
							() => {
								core.logDebug( 'Main image ready' );
								return replaceMainImageWithRetry( cacheBustedMainImageUrl );
							}
						);
					};

					this.startGalleryLoader();
					this.renderSessionDesignReference( config, config.assets || null );

					if (config.assets && config.assets.main_0 && config.assets.main_0.url) {
						applyMainImage( config.assets )
						.catch(
							(error) => {
								core.handleError( error );
							}
						)
							.finally(
								() => {
									this.stopGalleryLoader();
								}
							);
					} else {
						core.logDebug( 'No imageUrl found in config. Polling session assets.' );
						core.waitForSessionAssets()
						.then(
							(assets) => {
								core.config.assets = assets;
								this.renderSessionDesignReference( config, assets );
								return applyMainImage( assets );
							}
						)
						.catch(
							(error) => {
								core.handleError( error );
							}
						)
						.finally(
							() => {
								this.stopGalleryLoader();
							}
						);
					}
				},

				getDesignUrlFromAssets: function (assets) {
					if ( ! assets || ! assets.design) {
						return null;
					}

					if (Array.isArray( assets.design ) && assets.design.length > 0) {
						const firstDesign = assets.design[0];
						if (typeof firstDesign === 'string') {
							return firstDesign;
						}
						if (firstDesign && typeof firstDesign.url === 'string') {
							return firstDesign.url;
						}
					}

					if (typeof assets.design === 'string') {
						return assets.design;
					}
					if (assets.design && typeof assets.design.url === 'string') {
						return assets.design.url;
					}

					return null;
				},

				renderSessionDesignReference: function (config, assets) {
					const $button = $( this.buttonSelector );
					if ( ! $button.length) {
						return;
					}

					const $container = $button.parent();
					$container.find( '.cnf3dweb-session-reference' ).remove();

					if ( ! config.showDesignLink) {
						return;
					}

					const labelTemplate = config.translations.sessionClosed || 'Design: {reference}';
					const label         = labelTemplate.replace( '{reference}', config.team_reference || '' );
					const designUrl     = this.getDesignUrlFromAssets( assets );

					const $wrapper = $( '<div class="cnf3dweb-session-reference"></div>' );

					if (designUrl) {
						const $link = $( '<a class="cnf3dweb-session-reference__link"></a>' );
						$link.attr( 'href', designUrl );
						$link.attr( 'target', '_blank' );
						$link.attr( 'rel', 'noopener noreferrer' );
						$link.text( label );
						$wrapper.append( $link );
					} else {
						const $text = $( '<span class="cnf3dweb-session-reference__text"></span>' );
						$text.text( label );
						$wrapper.append( $text );
					}

					$container.append( $wrapper );
				},

				startGalleryLoader: function () {
					this.showGalleryLoader();
				},

				stopGalleryLoader: function () {
					this.hideGalleryLoader();
				},

				showGalleryLoader: function (text) {
					const gallery = document.querySelector( this.gallerySelector );
					if (gallery) {
						const wrapper = gallery.querySelector( '.woocommerce-product-gallery__wrapper' ) || gallery;
						if ( ! wrapper.dataset.cnf3dwebLoaderOpacity) {
							wrapper.dataset.cnf3dwebLoaderOpacity = wrapper.style.opacity || '';
						}
						wrapper.style.opacity = '0.45';
					}

					if ( ! document.querySelector( '.cnf3dweb-gallery-loader' )) {
						const loadingText = text || (core.config && core.config.translations && core.config.translations.galleryLoading
							? core.config.translations.galleryLoading
							: 'Preparing your preview...');

						const loader     = document.createElement( 'div' );
						loader.className = 'cnf3dweb-gallery-loader';
						loader.setAttribute( 'aria-hidden', 'true' );
						const content     = document.createElement( 'div' );
						content.className = 'cnf3dweb-gallery-loader__content';
						const spinner     = document.createElement( 'span' );
						spinner.className = 'cnf3dweb-gallery-loader__spinner';
						spinner.setAttribute( 'aria-hidden', 'true' );

						const textEl       = document.createElement( 'span' );
						textEl.className   = 'cnf3dweb-gallery-loader__text';
						textEl.textContent = loadingText;

						content.appendChild( spinner );
						content.appendChild( textEl );
						loader.appendChild( content );
						document.body.appendChild( loader );
					}
				},

				hideGalleryLoader: function () {
					const gallery = document.querySelector( this.gallerySelector );
					if (gallery) {
						const wrapper         = gallery.querySelector( '.woocommerce-product-gallery__wrapper' ) || gallery;
						wrapper.style.opacity = wrapper.dataset.cnf3dwebLoaderOpacity || '';
						delete wrapper.dataset.cnf3dwebLoaderOpacity;
					}

					const loaders = document.querySelectorAll( '.cnf3dweb-gallery-loader' );
					loaders.forEach( (loader) => loader.remove() );
				},

				replaceMainImage: function (imageUrl) {
					if ( ! window.cnf3DWebFlexslider || ! window.cnf3DWebFlexslider.updateWooGalleryImage) {
						return Promise.reject( new Error( 'Flexslider helper not loaded' ) );
					}

					return window.cnf3DWebFlexslider.updateWooGalleryImage(
						1,
						imageUrl,
						null,
						{
							logger: (message) => core.logDebug( message ),
						}
					);
				},
			};

			window.cnf3DWebCore.init( dwebPsConfig, themeHooks );
		}
	);

})( jQuery );
