=== 3DWeb Print Studio — Simple Print Customizer for WooCommerce ===
Contributors: jtermaat
Tags: simple product customizer, print customizer, woocommerce product customizer, packaging customizer, print design
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Let customers easily customize simple print products like packaging or cups with instant live 3D preview.

== Description ==

Demo: [View the plugin demo on feestbekers.nl](https://feestbekers.nl) (start with the "Start Configuration" button on a product page)

**Design simple print products in real-time 3D.**

3DWeb Print Studio is a white-label 3D print design platform that enables businesses to let customers easily customize and visualize print artwork on simple products like packaging, cups, labels, and promotional items.

The focus is on fast and easy print customization — not complex product configuration.

Let customers design, preview, and approve print artwork instantly — fully branded and seamlessly integrated into your WooCommerce store.

### Key Features

* **White-Label & Fully Brandable**: Deliver a fully branded experience using your own colors, logo, icons, and templates. The entire design environment can match your webshop.
* **Simple Print Customization**: Perfect for basic products like boxes, labels, cups, and coasters — no complex configuration needed.
* **Real-Time 3D Preview**: Customers instantly see their design applied to realistic 3D models, increasing confidence and conversions.
* **Easy Integration**: Connect quickly using our API or ready-made WooCommerce plugin.
* **Pay Per Session**: No high monthly fees. Only pay for completed design sessions — simple and cost-effective.

### Why 3DWeb Print Studio?

* **Focused on Simplicity**: Built for fast customization of standard print products.
* **White-Label Ready**: Fully customizable interface for agencies, print shops, and B2B platforms.
* **Built for Packaging & Print**: Ideal for packaging suppliers and print businesses.
* **High Conversion**: Live 3D previews help customers make faster decisions.
* **Scalable**: API-first and modular architecture.

== External services ==

This plugin connects to external 3DWeb services to run and complete the print-design flow.

Service: 3DWeb API and 3DWeb-hosted design assets (including `3dweb.io` domains).

Purpose:

* Create/start a design session for the selected WooCommerce product.
* Redirect the customer to the hosted 3DWeb design environment and back to your site.
* Retrieve generated session assets (preview image URLs and design file URLs) for product/cart/order display.
* Download generated design files from allowed 3DWeb hosts when a user clicks a download link.

Data sent and when:

* On session start (customer action): product SKU/identifier and callback URL.
* On return/session display: session reference is used to request related session assets.
* On design download (user action): WordPress requests the design file URL from 3DWeb-hosted domains.

This service is provided by 3DWeb:

* Terms of Use: [https://3dweb.io/terms](https://3dweb.io/terms)
* Privacy Policy: [https://3dweb.io/privacy](https://3dweb.io/privacy)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/3dweb-print-studio` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure settings in the 3DWeb Print Studio menu.

== Frequently Asked Questions ==

= What kind of products is this plugin best for? =

This plugin is designed for simple print products such as packaging, labels, cups, mugs, and promotional items.

= Do I need complex 3D setup? =

No, the system is designed to keep things simple while still providing powerful 3D visualization.

= Can I fully brand the design tool? =

Yes, the platform is fully white-label and can be customized to match your brand.

= How does it work with WooCommerce? =

When you add a product to WooCommerce and assign an SKU that matches a product in the 3DWeb Print Studio, the customer is automatically redirected to the design environment. After completing the design, they return to your webshop and can place the order with their customized artwork.

== Screenshots ==

1. Real-time 3D visualization of a print design.
2. Example packaging customization.
3. Example cup or promotional product design.

== Changelog ==

= 1.0.0 =

* Initial release with core 3D visualization and print customization features.
