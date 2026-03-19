=== 3DWeb Print Studio ===
Contributors: jtermaat
Tags: 3d, print, customization, visualization, ecommerce
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A white-label WooCommerce integration for real-time 3D print design sessions with design references in orders.

== Description ==

**Design your print in real-time 3D.**

3DWeb Print Studio is a white-label 3D print design platform that enables businesses to let customers design and visualize print artwork in real-time 3D. The focus is on print and artwork customization, not full product configuration.

Let customers visualize, customize and approve print designs instantly — fully branded and easy to integrate.

### Key Features

*   **White-Label & Fully Brandable**: Use your own colors, logo, icons, and templates to create a seamless branded print design experience.
*   **Easy Integration**: Connect the 3D Print Studio quickly using our API or ready-made plugins for WooCommerce and other major platforms.
*   **Real-Time 3D Previews**: Customers instantly see their artwork applied to realistic 3D models, increasing confidence and conversions.
*   **Pay Per Session**: No high monthly fees. Only pay for completed design sessions — simple, transparent, and cost-effective.

### Why 3DWeb Print Studio?

*   **Professional**: Built for B2B environments.
*   **Innovative**: Powered by real-time 3D technology.
*   **Clear**: No unnecessary complexity.
*   **Creative**: A design-led experience.
*   **Scalable**: API-first and modular architecture.

== External services ==

This plugin connects to external 3DWeb services to run and complete the print-design flow.

Service: 3DWeb API and 3DWeb-hosted design assets (including `3dweb.io` domains).

Purpose:
- Create/start a design session for the selected WooCommerce product.
- Redirect the customer to the hosted 3DWeb design environment and back to your site.
- Retrieve generated session assets (preview image URLs and design file URLs) for product/cart/order display.
- Download generated design files from allowed 3DWeb hosts when a user clicks a download link.

Data sent and when:
- On session start (customer action): product SKU/identifier and callback URL.
- On return/session display: session reference is used to request related session assets.
- On design download (user action): WordPress requests the design file URL from 3DWeb-hosted domains.

This service is provided by 3DWeb:
- Terms of Use: https://3dweb.io/terms
- Privacy Policy: https://3dweb.io/privacy

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/3dweb-print-studio` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure settings in the 3DWeb Print Studio menu.

== Frequently Asked Questions ==

= How do I integrate the 3D viewer? =

When you add a product to WooCommerce and assign an SKU that matches a product in the 3DWeb Print Studio, the customer is automatically redirected to the 3DWeb Print Studio viewer.
There, the customer can customize their print design. When they return to your website and place an order, the order will include a reference to the customized print design.

== Screenshots ==

1. Real-time 3D visualization of a print design.

== Changelog ==

= 1.0.0 =
* Initial release with core 3D visualization and customization features.
