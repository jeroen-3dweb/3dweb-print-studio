jQuery(
	function ($) {
		var $search     = $( '#dweb_ps_product_search' );
		var $dropdown   = $( '#dweb_ps_product_dropdown' );
		var $hidden     = $( '#dweb_ps_product_id' );
		var $wrapper    = $( '#dweb_ps_select_wrapper' );
		var $display    = $( '#dweb_ps_select_display' );
		var i18n        = window.dwebPsWooMetabox || {};
		var isOpen      = false;
		var searchTimer = null;

		if ( ! $wrapper.length || typeof window.DWEB_PS_ADMIN === 'undefined' || typeof window.DWEB_PS_ADMIN.sync !== 'function') {
			return;
		}

		function getProductLabel(product) {
			var label = product.name || product.title || product.id || '';
			var sku   = product.sku || '';

			if ( ! label) {
				return sku;
			}

			return sku ? label + ' (' + sku + ')' : label;
		}

		function setDisplayLabel(text, isSelected) {
			if ( ! text) {
				return;
			}

			var safeText = $( '<span>' ).text( text ).html();
			if (isSelected) {
				$display.html( '<strong>' + safeText + '</strong>' );
				return;
			}

			$display.html( safeText );
		}

		function closeDropdown() {
			isOpen = false;
			$search.hide().val( '' );
			$dropdown.hide().empty();
		}

		function renderProducts(products) {
			$dropdown.empty();
			if ( ! products || ! Array.isArray( products ) || products.length === 0) {
				var items = products && products.data ? products.data : products;
				if ( ! items || ! Array.isArray( items ) || items.length === 0) {
					$dropdown.html( '<div style="padding:8px; color:#999;">' + (i18n.noProductsFound || 'No products found') + '</div>' ).show();
					return;
				}
				products = items;
			}

			$.each(
				products,
				function (i, product) {
					var sku  = product.sku || '';
					var text = getProductLabel( product );
					$dropdown.append(
						$( '<div>' )
						.text( text )
						.css( { padding: '8px', cursor: 'pointer' } )
						.hover(
							function () {
								$( this ).css( 'background', '#f0f0f0' ); },
							function () {
								$( this ).css( 'background', '#fff' ); }
						)
						.on(
							'click',
							function () {
								$hidden.val( sku );
								setDisplayLabel( text, true );
								closeDropdown();
							}
						)
					);
				}
			);

			$dropdown.show();
		}

		function loadProducts(query) {
			var params = query ? { search : query } : {};
			window.DWEB_PS_ADMIN.sync( 'dweb_ps_search_products', params, 'get' )
			.then(
				function (response) {
					var products = response.data;
					if ( ! products || ! Array.isArray( products )) {
						products = products && products.data ? products.data : [];
					}
					renderProducts( query ? products : products.slice( 0, 5 ) );
				}
			)
				.catch(
					function (err) {
						$dropdown.html( '<div style="padding:8px; color:red;">' + (i18n.searchError || 'Error searching products') + '</div>' ).show();
						console.warn( err );
					}
				);
		}

		function openDropdown() {
			if (isOpen) {
				return;
			}

			isOpen = true;
			$search.show().val( '' ).focus();
			loadProducts( '' );
		}

		function syncInitialSelection() {
			var currentSku = $hidden.val();
			if ( ! currentSku) {
				return;
			}

			window.DWEB_PS_ADMIN.sync( 'dweb_ps_search_products', { search: currentSku }, 'get' )
			.then(
				function (response) {
					var products = response.data;
					if ( ! products || ! Array.isArray( products )) {
						products = products && products.data ? products.data : [];
					}

					var matchingProduct = products.find(
						function (product) {
							return product && product.sku === currentSku;
						}
					);

					if (matchingProduct) {
						setDisplayLabel( getProductLabel( matchingProduct ), true );
					}
				}
			)
				.catch(
					function (err) {
						console.warn( err );
					}
				);
		}

		$display.on(
			'click',
			function () {
				if (isOpen) {
					closeDropdown();
					return;
				}

				openDropdown();
			}
		);

		$search.on(
			'input',
			function () {
				clearTimeout( searchTimer );
				var query   = $( this ).val();
				searchTimer = setTimeout(
					function () {
						loadProducts( query );
					},
					300
				);
			}
		);

		$( document ).on(
			'click',
			function (e) {
				if ( ! $( e.target ).closest( '#dweb_ps_select_wrapper' ).length) {
					closeDropdown();
				}
			}
		);

		syncInitialSelection();
	}
);
