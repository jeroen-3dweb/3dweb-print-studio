(function ($) {
	'use strict';

	// Helper function to poll an asset until it returns a 200 response
	async function waitForAsset(
		url,
		{ maxRetries = 60, interval = 1000, timeout = 8000 } = {}
	) {
		const sleep = (ms) => new Promise( (r) => setTimeout( r, ms ) );

		for (let attempt = 1; attempt <= maxRetries; attempt++) {
			const urlObj = new URL( url, location.href );
			urlObj.searchParams.set( "_cb", String( Date.now() ) );

			try {
				await new Promise(
					(resolve, reject) => {
						const img       = new Image();
						const t         = setTimeout(
						() => {
							img.src = ""; // stop loading
							reject( new Error( "Image load timeout" ) );
						},
						timeout
						);
					img.onload = () => {
                    clearTimeout( t );
                    // extra check: echt pixels?
						if (img.naturalWidth > 0) {
							resolve( true );
						} else {
							reject( new Error( "Image loaded but naturalWidth=0" ) );
						}
						};
						img.onerror = () => {
                    clearTimeout( t );
                    reject( new Error( "Image error" ) );
						};
						img.src     = urlObj.toString();
					}
				);

				return true;
			} catch (err) {
				console.warn( `[waitForImage] attempt ${attempt} / ${maxRetries} failed:`, err && err.message ? err.message : err );
				await sleep( interval );
			}
		}

		throw new Error( `Image did not load after ${maxRetries} attempts: ${url}` );
	}

	function normalizeAssets(assets) {
		if (assets && ! Array.isArray( assets )) {
			return assets;
		}

		if (Array.isArray( assets ) && assets.length >= 4) {
			return {
				main_0: assets[0],
				main_90: assets[1],
				main_180: assets[2],
				main_270: assets[3]
			};
		}

		return {};
	}

	async function pollSessionAssets(fetcher, { maxRetries = 30, interval = 2000 } = {}) {
		const sleep = (ms) => new Promise( (r) => setTimeout( r, ms ) );

		for (let attempt = 1; attempt <= maxRetries; attempt++) {
			try {
				const assets = normalizeAssets( await fetcher() );
				if (assets.main_0 && assets.main_0.url) {
					return assets;
				}
			} catch (err) {
				console.warn( `[pollSessionAssets] attempt ${attempt} / ${maxRetries} failed:`, err && err.message ? err.message : err );
			}

			await sleep( interval );
		}

		throw new Error( 'Session assets are not ready yet' );
	}

	window.cnf3DWebCore = {
		isListening: false,
		logPrefix: '[3DWeb]',

		init: function (config, themeHooks) {
			this.config     = config;
			this.themeHooks = themeHooks;
			this.logDebug( 'Core initialized', config );

			if (config.team_reference) {
				this.handleBackFromSession();
			} else {
				this.handleNewSession();
			}
		},

		checkIfReady: function (url, maxRetries = 60, interval = 1000) {
			return waitForAsset( url, { maxRetries, interval } );
		},

		getSessionAssets: function () {
			return new Promise(
				(resolve, reject) => {
					$.ajax(
					{
						url: this.config.ajax_url,
						type: 'POST',
						data: {
							method: 'get_assets',
							action: this.config.action,
							security: this.config.security
						},
						success: (response) => {
							if (response && response.success && response.data) {
								resolve( normalizeAssets( response.data ) );
								return;
							}

							reject(
								new Error(
									response && response.data && response.data.message
										? response.data.message
										: response && response.message
											? response.message
											: 'Could not fetch session assets'
								)
							);
						},
						error: (xhr, status, error) => {
							reject( new Error( error || status || 'Could not fetch session assets' ) );
						}
						}
					);
				}
			);
		},

		waitForSessionAssets: function (options = {}) {
			return pollSessionAssets( () => this.getSessionAssets(), options );
		},

		handleBackFromSession: async function () {
			this.logDebug( 'Existing session detected' );
			if (this.themeHooks.onBackFromSession) {
				this.themeHooks.onBackFromSession( this.config );
			} else {
				this.logDebug( 'No onBackFromSession hook provided' );
			}
		},

		changeTextOnButton: function (selector, text) {
			if (this.themeHooks.changeTextOnButton) {
				this.themeHooks.changeTextOnButton( this.config );
			} else {
				$( selector ).text( text );
			}
		},

		handleNewSession: function () {
			if (this.themeHooks.initStartButton) {
				this.themeHooks.initStartButton();
			}
		},

		startNewSession: function (selector) {
			if (this.isListening) {
				return;
			}
			this.isListening = true;
			this.logDebug( 'Starting new session' );

			if (this.themeHooks.onSessionLoading) {
				this.themeHooks.onSessionLoading( true, this.config );
			}

			$.ajax(
				{
					url: this.config.ajax_url,
					type: 'POST',
					data: {
						method: 'get_session',
						action: this.config.action,
						security: this.config.security,
						product_id: this.config.product_id,
						product_sku: this.config.product_sku,
						post_url: window.location.href,
					},
					success: (response) => {
						this.logDebug( 'Session response received', response );
						try {
							const {data, success} = response;
							if (success && data && data.url) {

								this.config.teamReference = data[this.config.team_reference_key];
								setTimeout(
									() => {
										location.assign( data.url );
									},
									0
								);
							} else {
								const errorMsg = response.message ? response.message : (data && data.message ? data.message : 'An error occurred while loading the API.');
								this.handleError( errorMsg );
							}
						} catch (e) {
							this.handleError( 'Error processing server response.' );
						} finally {
							this.isListening = false;
						}
					},
					error: (xhr, status, error) => {
						this.logDebug( 'AJAX Error:', status, error, xhr.responseText );
						let errorMessage     = 'A critical error occurred.';
						try {
							const response = JSON.parse( xhr.responseText );
							if (response && response.data && response.data.message) {
								errorMessage = response.data.message;
							}
						} catch (e) {
							if (error) {
								errorMessage = `Server error: ${error}`;
							}
						}
						this.handleError( errorMessage );
						this.isListening = false;
					}
				}
			);
		},

		logDebug: function (message, ...args) {
			if (this.config.debug) {
				message        = `${this.logPrefix}  ${message}`;
				const severity = (args.length > 0 && typeof args[args.length - 1] === 'string' && ['log','warn','error','info'].includes( args[args.length - 1] ))
					? args.pop()
					: 'log';
				console[severity]( message, ...args, this.config )
			}
		},

		handleError: function (message) {
			this.logDebug( 'Error:', message, 'error' );
			if (this.themeHooks.showError) {
				this.themeHooks.showError( message );
			}
			if (this.themeHooks.onSessionLoading) {
				this.themeHooks.onSessionLoading( false, this.config );
			}
		},

		addHeightToUrl: function (url, height) {
			const urlObj = new URL( url );
			urlObj.searchParams.set( 'h', height );
			return urlObj.toString();
		},

		loadThreeSixtyView: function (containerId, imageId) {
			if (typeof JavascriptViewer === 'undefined') {
				console.error( 'JavascriptViewer not loaded' );
				return;
			}

			if ( ! this.config.threeSixtyConfig) {
				this.handleError( '360 config not loaded' );
				return;
			}

			// test every url

			const viewer = new JavascriptViewer(
				{
					mainHolderId: containerId,
					mainImageId: imageId,
					imageUrls: this.config.assets['360'].map( asset => this.addHeightToUrl( asset.url, 600 ) ),
					speed: 70,
					zoom: true,
					defaultProgressBar: true,
					autoRotate: this.config.threeSixtyConfig.autoRotate,
					autoCDNResizer: true,
					autoCDNResizerConfig: {
						useHeight: true,
						extraParams: {
							t: Date.now()
						}
					},
					extraImageClass: 'cnf-jsv-image',
					license: this.config.threeSixtyConfig.license,
				}
			);

			viewer.start().catch(
				(error) => {
					this.handleError( error );
				}
			);
		}
	};

})( jQuery );
