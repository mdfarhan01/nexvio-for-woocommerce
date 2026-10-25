=== Nexvio for WooCommerce ===
Contributors: farhansadik01
Tags: woocommerce, elementor, product filter, wishlist, side cart
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce, elementor
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Elementor widgets for WooCommerce: product slider, grid, AJAX filter, side cart and a built-in wishlist.

== Description ==

Nexvio adds a set of WooCommerce widgets to Elementor — product slider, product grids, an AJAX product filter, header search, related products, add to cart, a side cart and a built-in wishlist — and gives you one dashboard in wp-admin to manage them all.

Nexvio is an independent plugin and is not affiliated with or endorsed by WooCommerce or Elementor.

Everything lives under **Nexvio** in your wp-admin menu, with pages for Dashboard, Widgets, Filters, Add to Cart, Wishlist, Settings and Documentation.

= Widgets =

You find them in the Elementor panel, in the **Nexvio** category.

**Products**

* **Product Slider** — carousel of products (for example featured or new), with continuous infinite scroll, arrows and drag.
* **Products Grid** — a grid of chosen products for any page, with its own pagination.

**Shop**

* **Products Archive** — shop and category grid with pagination, result count and sorting, Grid / List view and optional Grid / List buttons for shoppers. Works with the filter.
* **Product Filter** — AJAX filter with search, price range, categories, attributes, tags, availability, rating, on sale and featured, plus Sort by and Products per page.
* **Filter Popup (side panel)** — a Filters button that slides the Product Filter in from the left or right. Apply closes the panel and updates the products; the button shows the number of active filters.

**Single product**

* **Related Products** — products of the same category, most popular first.
* **Add to Cart** — quantity selector, AJAX add to cart (including variable products) and a wishlist heart.

**Header**

* **Header Search** — search icon that opens a search bar and filters products by title.
* **Wishlist Icon** — heart with a live counter that links to the wishlist page.

**Wishlist**

* **Wishlist Grid** — the "My favorites" page, with the same card designs.

**Cart**

* **Header Cart** — cart icon with a live counter that opens the side cart.
* **Floating Cart** — a fixed cart button with a live counter that opens the side cart.

= Product card designs =

* **12 card designs** — Classic Vault, Minimal Light, Image Overlay, Horizontal, Glass, Neon Outline, Elevated Shop, Compact Tiles, Editorial, Price Tag, Spotlight and Boutique — chosen with a visual picker, with design-specific style controls.
* **Colour swatches** on product cards, with the variation image of the chosen colour on variable products.
* **Description and rating** — optional short description and star rating on every card.

= Product Filter =

* **Build the fields visually** — add, reorder and remove fields on the Filters page, with a live preview and a Light / Dark look.
* **Categories** — list, checkboxes, dropdown or buttons, with counts; sub-categories are collapsed under a + button and the current branch opens automatically.
* **Attributes** — checkboxes, colour circles, image swatches or size buttons.
* **AJAX updates** — the products update without reloading the page.

= Side cart =

* **Slide-in cart** — quantities, remove, coupons, subtotal, discounts, shipping, total, and checkout / view cart buttons, using the native WooCommerce cart.
* **Look settings** — Luxury, Light or Dark with one main colour and a live preview.
* **Cart texts** follow the site language automatically (WooCommerce translations plus built-in phrases in 15 languages).

= Built-in wishlist =

* **No other plugin needed** — logged-in customers keep their wishlist in their account; visitors keep it in a cookie that is added to their account when they log in.
* **Settings** — wishlist page, visitor wishlist on/off and its duration.
* **Shortcode** — `[nexvio_wishlist_count]` shows the number of items.

= Admin =

* **Dashboard** — status, key numbers and a quick setup guide.
* **Widgets** — enable or disable each widget, or all of them; search, and Active / Inactive counters.
* **Settings** — wishlist, products per page and automatic related products.
* **Documentation** — how to set up and use every widget, shortcodes, hooks and FAQ, inside wp-admin.

= For developers =

* **URL parameters** — `nexvio_q`, `min_price`, `max_price`, `nexvio_tax_{taxonomy}`, `nexvio_tax_{taxonomy}_all`, `nexvio_stock`, `nexvio_sale`, `nexvio_featured`.
* **Filters** — `nexvio_product_query_args`, `nexvio_grid_pagination_args`, `nexvio_related_query_args`.
* **Actions** — `nexvio_wishlist_added` and `nexvio_wishlist_removed` (product ID).
* **JavaScript events** (jQuery, on `body`) — `nexvio_wishlist_changed` and `nexvio_filter_updated`.
* **Shortcode** — `[nexvio_related_products]` for related products outside the single product template.

== Source code ==

Nexvio ships no compiled, minified or bundled code. There is no build step (no npm, webpack or composer), and no third-party JavaScript or CSS library is included. Every JavaScript and CSS file in the plugin is hand-written and readable as shipped:

* `admin/assets/admin.js`, `admin/assets/filters.js`, `admin/assets/admin.css` - the wp-admin screens (Filters page builder and live preview).
* `assets/js/*.js` and `assets/css/*.css` - the front-end widgets (slider, filter, side cart, wishlist, search).

The files above are the source: edit them directly.

== Installation ==

1. Install and activate **WooCommerce** and **Elementor**.
2. Upload the `nexvio` folder to `/wp-content/plugins/`, or install the plugin through the Plugins screen in WordPress.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to **Nexvio** in the admin menu to see the dashboard and enable the widgets you need.
5. Edit a page or template with Elementor and add the widgets from the **Nexvio** category.

Suggested setup:

1. Header template: Header Search and Wishlist Icon.
2. Products archive template: Product Filter (sidebar) and Products Archive (source "Current query").
3. Single product template: Add to Cart and Related Products.
4. Wishlist page: Wishlist Grid (create the page from **Nexvio > Settings > Wishlist page**).

== Frequently Asked Questions ==

= What do I need to use the plugin? =

WooCommerce and Elementor must both be active. The widgets are Elementor widgets and the plugin shows a notice if one of them is missing.

= Does the wishlist need another plugin? =

No. The wishlist is built in. Set it up under **Nexvio > Settings > Wishlist page > Create the page**, edit the page with Elementor and add the **Wishlist Grid** widget.

= Are visitors' wishlists saved? =

Yes. Visitors' wishlists are saved in a cookie and added to their account when they log in. You can turn the visitor wishlist off and choose its duration in the settings.

= How do I show the Filter Popup? =

Add the **Filter Popup (side panel)** widget to your shop page or template, next to a Products Archive widget. Its fields come from **Nexvio > Filters**.

= Can I use the filter and the Filter Popup on the same page? =

Yes. For example, a Product Filter in a sidebar and a Filter Popup for small screens can be on the same page.

= What happens to my data when I delete the plugin? =

The plugin settings are removed. Customers' wishlists (stored as user meta) are kept on purpose.

== Changelog ==

= 1.0.0 =
* Initial release.
* Elementor widgets: Product Slider, Products Grid, Products Archive, Product Filter, Filter Popup (side panel), Related Products, Add to Cart, Header Search, Wishlist Icon, Wishlist Grid, Header Cart and Floating Cart.
* 12 product card designs with a visual picker, colour swatches, short description and star rating.
* AJAX Product Filter with fields built visually on the Filters page; collapsible sub-categories.
* Side cart with Luxury, Light and Dark looks.
* Built-in wishlist for customers and visitors.
* Admin dashboard with Widgets, Filters, Add to Cart, Wishlist, Settings and Documentation pages.
