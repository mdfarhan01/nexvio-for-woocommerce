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
* **Products Card** — a grid of simple product cards (image, title, description, price, button) with a short Style tab: Layout design, Image, Card Content, Button, Badges, Pagination and the No products message. Has the hover gallery image option.
* **Products Card Layouts** — a grid of chosen products (latest, featured, on sale, best sellers, categories, tags or IDs) for any page, with 12 selectable card designs and its own pagination.
* **Product Showcase (Tabs)** — a big product view (image, title, short description, price, stock countdown, payment options such as Tabby / Tamara, quantity and Add to cart) with a clickable product list that switches the view. Columns per device, list columns per device, every colour and size is a control.

**Shop**

* **Products Archive** — the shop and product archive widget with the Products Card design. It shows the products of the page being viewed, follows the filters and the archive pagination, and has result count and sorting.
* **Products Archive Layouts** — shop and category grid with 12 selectable card designs, pagination, result count and sorting, Grid / List view and optional Grid / List buttons for shoppers. Works with the filter.
* **Product Filter** — AJAX filter with search, price range, categories, attributes, tags, availability, rating, on sale and featured, plus Sort by and Products per page.
* **Filter Popup (side panel)** — a Filters button that slides the Product Filter in from the left or right. Apply closes the panel and updates the products; the button shows the number of active filters.

**Single product**

* **Related Products** — related products of the current product with the Products Card design (same category, most popular first), as a slider or a grid.
* **Related Products Layouts** — the same related products with 12 selectable card designs.
* **Add to Cart** — quantity selector, AJAX add to cart (including variable products) and a wishlist heart.

**Header**

* **Header Search** — search icon that opens a search bar and filters products by title.
* **Wishlist Icon** — heart with a live counter that links to the wishlist page.

**Wishlist**

* **Wishlist Card** — the "My favorites" page with the Products Card design; hearts remove cards live.
* **Wishlist Card Layouts** — the same wishlist page with 12 selectable card designs.

**Hover gallery image**

Products Card, Products Archive, Related Products, Wishlist Card and the three Layouts widgets can show a second picture from the product gallery when the pointer is over the card. Settings (Content tab, Hover gallery image): Enable Gallery Image on Hover, Enable Hover Animation, Animation Effect (Fade, Zoom In, Zoom Out, Slide, None), Animation Duration and Animation Easing (Ease, Ease In, Ease Out, Ease In Out). Products without a gallery image keep their main image.

**Cart**

* **Header Cart** — cart icon with a live counter that opens the side cart.
* **Cart Panel** — the full cart shown on a page, with full control over content and style.
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

== Screenshots ==

1. Nexvio dashboard with plugin status, key statistics and quick setup guide.
2. Products Card widget displaying customizable WooCommerce product card designs.
3. AJAX Product Filter with category, color, size, price and availability filters.
4. Side Cart panel with product quantities, coupons, subtotal and checkout options.
5. Built-in Wishlist displaying saved products with customizable card layouts.

== Installation ==

1. Install and activate **WooCommerce** and **Elementor**.
2. Upload the `nexvio` folder to `/wp-content/plugins/`, or install the plugin through the Plugins screen in WordPress.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to **Nexvio** in the admin menu to see the dashboard and enable the widgets you need.
5. Edit a page or template with Elementor and add the widgets from the **Nexvio** category.

Suggested setup:

1. Header template: Header Search and Wishlist Icon.
2. Products archive template: Product Filter (sidebar) and Products Archive (or Products Archive Layouts).
3. Single product template: Add to Cart and Related Products (or Related Products Layouts).
4. Wishlist page: Wishlist Card or Wishlist Card Layouts (create the page from **Nexvio > Settings > Wishlist page**).

== Source Code ==

All JavaScript and CSS files of this plugin are shipped unminified and human-readable, exactly as written. There is no build step, no compiled code and no external library bundle, so the files inside the plugin folder are the full source code:

* PHP: `nexvio.php`, `includes/`, `widgets/`, `admin/`
* JavaScript: `assets/js/` and `admin/assets/`
* CSS: `assets/css/` and `admin/assets/`

The development repository is on GitHub: https://github.com/mdfarhan01/nexvio-for-woocommerce

== Frequently Asked Questions ==

= What do I need to use the plugin? =

WooCommerce and Elementor must both be active. The widgets are Elementor widgets and the plugin shows a notice if one of them is missing.

= Does the wishlist need another plugin? =

No. The wishlist is built in. Set it up under **Nexvio > Settings > Wishlist page > Create the page**, edit the page with Elementor and add the **Wishlist Card** widget.

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
* New widget: Products Card (listed before Products Grid). Simple card design; Style tab with Layout design (card background, border top / right / bottom / left, text area padding, box shadow), Image (Default / Cover / Contain fit, image controls and image area), Card Content (title, description, price with colour, typography and spacing), Button (font, colour or gradient background, text and icon colour, padding, spacing, radius), Badges (font, padding, margin, radius), Pagination and No products message.
* Product Filter: colour, size, image and every other attribute now also filter a Products Archive that is on a normal page or uses a custom query (the filters of the URL are applied to the widget's own query), and price, stock, sale, featured, rating and search follow too. The colour ticked last is the one each card shows.
* Product Filter: Style > Attribute circles and buttons shows a size and radius block for each attribute added on Nexvio > Filters.
* Fix: Product Filter attributes (colour, size, image…) and categories did not filter the products (wrong URL parameter prefix on the server). They filter correctly now.
* Product Filter: the value picked in an attribute filter is shown on the product cards: the colour swatch is selected, the card shows that variation's image and links to it. With several values ticked, the one ticked last is shown (if the product has it). Works for colour, size and image attributes.
* Product Filter: Style > Attribute circles and buttons: size and radius for every attribute added to the filter.
* Products Archive, Related Products and Wishlist Grid: the Style tab now has the same sections as Products Grid (Layout design, Image, Card Content, Button, Badges, Wishlist heart).
* Rating: position (below / above the title / on the image with side, distance, background, padding, radius) and colours.
* Wishlist heart: full control (position, size, icon size, Normal / Hover / In wishlist colours, border, radius, shadow).
* Products Grid: Style tab reorganised into 7 sections: Layout design (card background, border top / right / bottom / left, corner radius, text area padding, box shadow), Image (image controls + image area + Cover / Contain / Default fit), Card Content (title, set line, description, price, stock, rating and swatches, each with typography, colour and top / bottom spacing), Button (font, solid or gradient background, text and icon colour, padding, spacing, border radius; Normal and Hover), Badges (Language, Category, New, Sale, Pre-order: colours, font, padding, margin, radius), Pagination, No products message.
* Product cards (Product Slider, Products Grid, Products Archive, Related Products, Wishlist Grid): Design Layout > Content alignment (left, center, right, justified, per device).
* Product cards: Style > Card > Box Shadow with full control (colour, offset, blur, spread, inset), Normal and Hover.
* Product cards: Style > Image with the Elementor Image controls (alignment, width, max width, height, opacity, CSS filters, border, radius, box shadow).
* Product cards: Design Layout > Add-to-cart click: add variable products to the cart from the card instead of opening the product page.
* Product cards: Design Layout > Hover gallery image (on / off) with seven smooth change effects, speed and motion settings.
* Fix: Product Showcase (Tabs) was invisible because its CSS class clashed with the side cart. It now has its own class prefix, theme-friendly default colours and stacks price and payments in narrow columns.
* Renamed widgets: Products Grid is now Products Card Layouts, Products Archive is now Products Archive Layouts, Related Products is now Related Products Layouts and Wishlist Grid is now Wishlist Card Layouts.
* New widgets: Products Archive, Related Products and Wishlist Card, with the Products Card design.
* Hover gallery image with animation effect, duration and easing in Products Card, the new widgets and the Layouts widgets.
* Opening the dashboard after activation now also works when background requests run first.
* Initial release.
* Elementor widgets: Product Slider, Products Card, Products Grid, Product Showcase (Tabs), Products Archive, Product Filter, Filter Popup (side panel), Related Products, Add to Cart, Header Search, Wishlist Icon, Wishlist Card, Header Cart, Floating Cart and Cart Panel.
* 12 product card designs with a visual picker, colour swatches, short description and star rating.
* AJAX Product Filter with fields built visually on the Filters page; collapsible sub-categories.
* Side cart with Luxury, Light and Dark looks.
* Built-in wishlist for customers and visitors.
* Admin dashboard with Widgets, Filters, Add to Cart, Wishlist, Settings and Documentation pages.
* Side cart: "Shop More" button instead of "View cart"; tidier empty-cart spacing.
* Floating cart: icon, button size and position (side, distance from the side and the bottom, phones separately) are set in Nexvio > Add to Cart.
* New widget: Cart Panel (the whole cart on a page, with full content and style controls).
* Side cart: bigger product images, tidy long titles (1-3 lines), clearer regular / sale prices.
* Floating cart: smoother pulse; the icon keeps its colour after the side cart closes.
* Product cards: Style > Spacing controls, Horizontal design image spacing and add-to-cart button colours.