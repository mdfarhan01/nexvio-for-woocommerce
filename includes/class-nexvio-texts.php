<?php
/**
 * Cart texts in the visitor's language.
 *
 * 1. Standard shop words use WooCommerce's own translations (installed with
 *    WooCommerce for every site language, and switched per language by
 *    WPML / Polylang / TranslatePress).
 * 2. Phrases WooCommerce does not have use the built-in dictionary below.
 * 3. A translation of this plugin (e.g. made with Loco Translate) wins over both.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.WP.I18n.TextDomainMismatch -- the 'woocommerce' strings below are WooCommerce's own texts, used on purpose so they arrive already translated.

class Nexvio_Texts {

	/** Phrases that WooCommerce does not have: language => text. */
	private static function dictionary() {
		return array(
			'title'       => array(
				'fr' => 'Votre panier', 'de' => 'Ihr Warenkorb', 'es' => 'Tu cesta', 'it' => 'Il tuo carrello', 'pt' => 'Seu carrinho', 'nl' => 'Je winkelmand',
				'bn' => 'আপনার শপিং ব্যাগ', 'ar' => 'حقيبة التسوق', 'hi' => 'आपका शॉपिंग बैग', 'tr' => 'Alışveriş çantanız', 'pl' => 'Twój koszyk', 'sv' => 'Din varukorg',
				'ru' => 'Ваша корзина', 'ja' => 'ショッピングバッグ', 'zh' => '您的购物袋',
			),
			'subtitle'    => array(
				'fr' => 'Vérifiez les articles sélectionnés', 'de' => 'Überprüfen Sie Ihre ausgewählten Artikel', 'es' => 'Revisa los artículos seleccionados', 'it' => 'Controlla gli articoli selezionati',
				'pt' => 'Revise os itens selecionados', 'nl' => 'Bekijk je geselecteerde artikelen', 'bn' => 'আপনার বাছাই করা পণ্যগুলো দেখে নিন', 'ar' => 'راجع المنتجات التي اخترتها',
				'hi' => 'अपने चुने हुए उत्पाद देखें', 'tr' => 'Seçtiğiniz ürünleri gözden geçirin', 'pl' => 'Sprawdź wybrane produkty', 'sv' => 'Granska dina valda artiklar',
				'ru' => 'Проверьте выбранные товары', 'ja' => '選択した商品をご確認ください', 'zh' => '查看您选择的商品',
			),
			'empty_title' => array(
				'fr' => 'Votre panier est vide', 'de' => 'Ihr Warenkorb ist leer', 'es' => 'Tu cesta está vacía', 'it' => 'Il tuo carrello è vuoto', 'pt' => 'Seu carrinho está vazio',
				'nl' => 'Je winkelmand is leeg', 'bn' => 'আপনার শপিং ব্যাগ খালি', 'ar' => 'حقيبة التسوق فارغة', 'hi' => 'आपका शॉपिंग बैग खाली है', 'tr' => 'Alışveriş çantanız boş',
				'pl' => 'Twój koszyk jest pusty', 'sv' => 'Din varukorg är tom', 'ru' => 'Ваша корзина пуста', 'ja' => 'ショッピングバッグは空です', 'zh' => '您的购物袋是空的',
			),
			'empty_text'  => array(
				'fr' => 'Découvrez nos nouveautés et trouvez la pièce qui vous plaît.', 'de' => 'Entdecken Sie unsere Neuheiten und finden Sie Ihr Lieblingsstück.',
				'es' => 'Descubre nuestras novedades y encuentra algo que te encante.', 'it' => 'Scopri le nostre novità e trova qualcosa che ami.',
				'pt' => 'Descubra nossas novidades e encontre algo que você ame.', 'nl' => 'Ontdek onze nieuwste items en vind iets wat je mooi vindt.',
				'bn' => 'আমাদের নতুন পণ্যগুলো দেখুন, পছন্দের কিছু খুঁজে নিন।', 'ar' => 'اكتشف أحدث منتجاتنا واعثر على ما تحبه.', 'hi' => 'हमारे नए उत्पाद देखें और अपनी पसंद की चीज़ ढूंढें।',
				'tr' => 'En yeni ürünlerimizi keşfedin ve sevdiğiniz bir şey bulun.', 'pl' => 'Odkryj nasze nowości i znajdź coś dla siebie.', 'sv' => 'Upptäck våra nyheter och hitta något du gillar.',
				'ru' => 'Откройте наши новинки и найдите то, что вам понравится.', 'ja' => '新着アイテムをチェックして、お気に入りを見つけてください。', 'zh' => '探索我们的新品，找到您喜爱的商品。',
			),
			'calc'        => array(
				'fr' => 'Calculé lors de la commande', 'de' => 'Wird an der Kasse berechnet', 'es' => 'Se calcula al finalizar la compra', 'it' => 'Calcolato al checkout',
				'pt' => 'Calculado na finalização da compra', 'nl' => 'Berekend bij het afrekenen', 'bn' => 'চেকআউটে হিসাব হবে', 'ar' => 'يُحسب عند الدفع', 'hi' => 'चेकआउट पर गणना होगी',
				'tr' => 'Ödeme sırasında hesaplanır', 'pl' => 'Obliczane przy kasie', 'sv' => 'Beräknas i kassan', 'ru' => 'Рассчитывается при оформлении', 'ja' => '購入手続き時に計算されます', 'zh' => '结账时计算',
			),
			'close'       => array(
				'fr' => 'Fermer le panier', 'de' => 'Warenkorb schließen', 'es' => 'Cerrar la cesta', 'it' => 'Chiudi il carrello', 'pt' => 'Fechar o carrinho', 'nl' => 'Winkelmand sluiten',
				'bn' => 'ব্যাগ বন্ধ করুন', 'ar' => 'إغلاق الحقيبة', 'hi' => 'बैग बंद करें', 'tr' => 'Sepeti kapat', 'pl' => 'Zamknij koszyk', 'sv' => 'Stäng varukorgen', 'ru' => 'Закрыть корзину',
				'ja' => '閉じる', 'zh' => '关闭',
			),
			'error'       => array(
				'fr' => 'Une erreur est survenue. Veuillez réessayer.', 'de' => 'Etwas ist schiefgelaufen. Bitte versuchen Sie es erneut.', 'es' => 'Algo salió mal. Inténtalo de nuevo.',
				'it' => 'Si è verificato un errore. Riprova.', 'pt' => 'Algo deu errado. Tente novamente.', 'nl' => 'Er ging iets mis. Probeer het opnieuw.',
				'bn' => 'কিছু একটা সমস্যা হয়েছে। আবার চেষ্টা করুন।', 'ar' => 'حدث خطأ ما. يرجى المحاولة مرة أخرى.', 'hi' => 'कुछ गलत हो गया। कृपया फिर से कोशिश करें।',
				'tr' => 'Bir sorun oluştu. Lütfen tekrar deneyin.', 'pl' => 'Coś poszło nie tak. Spróbuj ponownie.', 'sv' => 'Något gick fel. Försök igen.',
				'ru' => 'Что-то пошло не так. Попробуйте ещё раз.', 'ja' => 'エラーが発生しました。もう一度お試しください。', 'zh' => '出错了，请重试。',
			),
			'checkout' => array(
				'fr' => 'Valider la commande', 'de' => 'Zur Kasse', 'es' => 'Finalizar compra', 'it' => 'Procedi al checkout', 'pt' => 'Finalizar compra', 'nl' => 'Afrekenen', 'bn' => 'চেকআউট করুন', 'ar' => 'إتمام الطلب', 'hi' => 'चेकआउट करें', 'tr' => 'Ödemeye geç', 'pl' => 'Przejdź do kasy', 'sv' => 'Gå till kassan', 'ru' => 'Оформить заказ', 'ja' => 'レジに進む', 'zh' => '前往结账',
			),
			'view_cart' => array(
				'fr' => 'Voir le panier', 'de' => 'Warenkorb anzeigen', 'es' => 'Ver carrito', 'it' => 'Visualizza carrello', 'pt' => 'Ver carrinho', 'nl' => 'Bekijk winkelmand', 'bn' => 'ব্যাগ দেখুন', 'ar' => 'عرض السلة', 'hi' => 'बैग देखें', 'tr' => 'Sepeti görüntüle', 'pl' => 'Zobacz koszyk', 'sv' => 'Visa varukorg', 'ru' => 'Перейти в корзину', 'ja' => 'カートを見る', 'zh' => '查看购物车',
			),
			'continue' => array(
				'fr' => 'Continuer mes achats', 'de' => 'Weiter einkaufen', 'es' => 'Seguir comprando', 'it' => 'Continua lo shopping', 'pt' => 'Continuar comprando', 'nl' => 'Verder winkelen', 'bn' => 'কেনাকাটা চালিয়ে যান', 'ar' => 'متابعة التسوق', 'hi' => 'खरीदारी जारी रखें', 'tr' => 'Alışverişe devam et', 'pl' => 'Kontynuuj zakupy', 'sv' => 'Fortsätt handla', 'ru' => 'Продолжить покупки', 'ja' => '買い物を続ける', 'zh' => '继续购物',
			),
			'coupon_link' => array(
				'fr' => 'Vous avez un code promo ?', 'de' => 'Haben Sie einen Gutscheincode?', 'es' => '¿Tienes un cupón?', 'it' => 'Hai un codice sconto?', 'pt' => 'Tem um cupom?', 'nl' => 'Heb je een kortingscode?', 'bn' => 'কুপন কোড আছে?', 'ar' => 'هل لديك رمز قسيمة؟', 'hi' => 'क्या आपके पास कूपन कोड है?', 'tr' => 'Kupon kodunuz var mı?', 'pl' => 'Masz kod rabatowy?', 'sv' => 'Har du en rabattkod?', 'ru' => 'Есть промокод?', 'ja' => 'クーポンコードをお持ちですか？', 'zh' => '有优惠码吗？',
			),
			'coupon_placeholder' => array(
				'fr' => 'Code promo', 'de' => 'Gutscheincode', 'es' => 'Código de cupón', 'it' => 'Codice sconto', 'pt' => 'Código do cupom', 'nl' => 'Kortingscode', 'bn' => 'কুপন কোড', 'ar' => 'رمز القسيمة', 'hi' => 'कूपन कोड', 'tr' => 'Kupon kodu', 'pl' => 'Kod rabatowy', 'sv' => 'Rabattkod', 'ru' => 'Промокод', 'ja' => 'クーポンコード', 'zh' => '优惠码',
			),
			'apply' => array(
				'fr' => 'Appliquer', 'de' => 'Einlösen', 'es' => 'Aplicar', 'it' => 'Applica', 'pt' => 'Aplicar', 'nl' => 'Toepassen', 'bn' => 'প্রয়োগ করুন', 'ar' => 'تطبيق', 'hi' => 'लागू करें', 'tr' => 'Uygula', 'pl' => 'Zastosuj', 'sv' => 'Använd', 'ru' => 'Применить', 'ja' => '適用', 'zh' => '使用',
			),
			'subtotal' => array(
				'fr' => 'Sous-total', 'de' => 'Zwischensumme', 'es' => 'Subtotal', 'it' => 'Subtotale', 'pt' => 'Subtotal', 'nl' => 'Subtotaal', 'bn' => 'সাবটোটাল', 'ar' => 'المجموع الفرعي', 'hi' => 'उप-योग', 'tr' => 'Ara toplam', 'pl' => 'Suma częściowa', 'sv' => 'Delsumma', 'ru' => 'Подытог', 'ja' => '小計', 'zh' => '小计',
			),
			'shipping' => array(
				'fr' => 'Livraison', 'de' => 'Versand', 'es' => 'Envío', 'it' => 'Spedizione', 'pt' => 'Frete', 'nl' => 'Verzending', 'bn' => 'ডেলিভারি', 'ar' => 'الشحن', 'hi' => 'शिपिंग', 'tr' => 'Kargo', 'pl' => 'Wysyłka', 'sv' => 'Frakt', 'ru' => 'Доставка', 'ja' => '配送', 'zh' => '运费',
			),
			'total' => array(
				'fr' => 'Total', 'de' => 'Gesamtsumme', 'es' => 'Total', 'it' => 'Totale', 'pt' => 'Total', 'nl' => 'Totaal', 'bn' => 'মোট', 'ar' => 'الإجمالي', 'hi' => 'कुल', 'tr' => 'Toplam', 'pl' => 'Razem', 'sv' => 'Totalt', 'ru' => 'Итого', 'ja' => '合計', 'zh' => '总计',
			),
			'remove' => array(
				'fr' => 'Retirer', 'de' => 'Entfernen', 'es' => 'Eliminar', 'it' => 'Rimuovi', 'pt' => 'Remover', 'nl' => 'Verwijderen', 'bn' => 'সরান', 'ar' => 'إزالة', 'hi' => 'हटाएं', 'tr' => 'Kaldır', 'pl' => 'Usuń', 'sv' => 'Ta bort', 'ru' => 'Удалить', 'ja' => '削除', 'zh' => '移除',
			),
			'qty' => array(
				'fr' => 'Quantité', 'de' => 'Menge', 'es' => 'Cantidad', 'it' => 'Quantità', 'pt' => 'Quantidade', 'nl' => 'Aantal', 'bn' => 'পরিমাণ', 'ar' => 'الكمية', 'hi' => 'मात्रा', 'tr' => 'Adet', 'pl' => 'Ilość', 'sv' => 'Antal', 'ru' => 'Количество', 'ja' => '数量', 'zh' => '数量',
			),
			'open_cart' => array(
				'fr' => 'Ouvrir le panier', 'de' => 'Warenkorb öffnen', 'es' => 'Abrir la cesta', 'it' => 'Apri il carrello', 'pt' => 'Abrir o carrinho', 'nl' => 'Winkelmand openen', 'bn' => 'ব্যাগ খুলুন', 'ar' => 'فتح الحقيبة', 'hi' => 'बैग खोलें', 'tr' => 'Sepeti aç', 'pl' => 'Otwórz koszyk', 'sv' => 'Öppna varukorgen', 'ru' => 'Открыть корзину', 'ja' => 'カートを開く', 'zh' => '打开购物车',
			),
			'options' => array(
				'fr' => 'Veuillez d\'abord choisir les options du produit.', 'de' => 'Bitte wählen Sie zuerst die Produktoptionen.', 'es' => 'Elige primero las opciones del producto.', 'it' => 'Scegli prima le opzioni del prodotto.', 'pt' => 'Escolha primeiro as opções do produto.', 'nl' => 'Kies eerst de productopties.', 'bn' => 'আগে পণ্যের অপশন বেছে নিন।', 'ar' => 'يرجى اختيار خيارات المنتج أولاً.', 'hi' => 'कृपया पहले उत्पाद के विकल्प चुनें।', 'tr' => 'Lütfen önce ürün seçeneklerini seçin.', 'pl' => 'Najpierw wybierz opcje produktu.', 'sv' => 'Välj produktalternativ först.', 'ru' => 'Сначала выберите параметры товара.', 'ja' => '先に商品のオプションを選択してください。', 'zh' => '请先选择商品选项。',
			),
			'coupon_empty' => array(
				'fr' => 'Veuillez saisir un code promo.', 'de' => 'Bitte geben Sie einen Gutscheincode ein.', 'es' => 'Introduce un código de cupón.', 'it' => 'Inserisci un codice sconto.', 'pt' => 'Digite um código de cupom.', 'nl' => 'Vul een kortingscode in.', 'bn' => 'একটি কুপন কোড লিখুন।', 'ar' => 'يرجى إدخال رمز القسيمة.', 'hi' => 'कृपया कूपन कोड डालें।', 'tr' => 'Lütfen bir kupon kodu girin.', 'pl' => 'Wpisz kod rabatowy.', 'sv' => 'Ange en rabattkod.', 'ru' => 'Введите промокод.', 'ja' => 'クーポンコードを入力してください。', 'zh' => '请输入优惠码。',
			),
			'remove_coupon' => array(
				'fr' => 'Retirer', 'de' => 'Entfernen', 'es' => 'Eliminar', 'it' => 'Rimuovi', 'pt' => 'Remover', 'nl' => 'Verwijderen', 'bn' => 'সরান', 'ar' => 'إزالة', 'hi' => 'हटाएं', 'tr' => 'Kaldır', 'pl' => 'Usuń', 'sv' => 'Ta bort', 'ru' => 'Удалить', 'ja' => '削除', 'zh' => '移除',
			),
		);
	}

	/** English text of this plugin => [ text, WooCommerce text (or '') ]. */
	private static function english( $key ) {
		switch ( $key ) {
			case 'title':
				return array( __( 'Your Shopping Bag', 'nexvio' ), __( 'Cart', 'woocommerce' ), 'Cart' );
			case 'subtitle':
				return array( __( 'Review your selected items', 'nexvio' ), '', '' );
			case 'empty_title':
				return array( __( 'Your shopping bag is empty', 'nexvio' ), __( 'Your cart is currently empty.', 'woocommerce' ), 'Your cart is currently empty.' );
			case 'empty_text':
				return array( __( 'Discover our latest pieces and find something you love.', 'nexvio' ), '', '' );
			case 'continue':
				return array( __( 'Continue Shopping', 'nexvio' ), __( 'Continue shopping', 'woocommerce' ), 'Continue shopping' );
			case 'checkout':
				return array( __( 'Proceed to Checkout', 'nexvio' ), __( 'Proceed to checkout', 'woocommerce' ), 'Proceed to checkout' );
			case 'view_cart':
				return array( __( 'View Cart', 'nexvio' ), __( 'View cart', 'woocommerce' ), 'View cart' );
			case 'coupon_link':
				return array( __( 'Have a coupon code?', 'nexvio' ), __( 'Have a coupon?', 'woocommerce' ), 'Have a coupon?' );
			case 'coupon_placeholder':
				return array( __( 'Coupon code', 'nexvio' ), __( 'Coupon code', 'woocommerce' ), 'Coupon code' );
			case 'apply':
				return array( __( 'Apply', 'nexvio' ), __( 'Apply coupon', 'woocommerce' ), 'Apply coupon' );
			case 'subtotal':
				return array( __( 'Subtotal', 'nexvio' ), __( 'Subtotal', 'woocommerce' ), 'Subtotal' );
			case 'shipping':
				return array( __( 'Shipping', 'nexvio' ), __( 'Shipping', 'woocommerce' ), 'Shipping' );
			case 'total':
				return array( __( 'Total', 'nexvio' ), __( 'Total', 'woocommerce' ), 'Total' );
			case 'remove':
				return array( __( 'Remove', 'nexvio' ), __( 'Remove this item', 'woocommerce' ), 'Remove this item' );
			case 'remove_coupon':
				return array( __( 'Remove', 'nexvio' ), __( '[Remove]', 'woocommerce' ), '[Remove]' );
			case 'qty':
				return array( __( 'Quantity', 'nexvio' ), __( 'Quantity', 'woocommerce' ), 'Quantity' );
			case 'options':
				return array( __( 'Please choose the product options first.', 'nexvio' ), __( 'Please select some product options before adding this product to your cart.', 'woocommerce' ), 'Please select some product options before adding this product to your cart.' );
			case 'coupon_empty':
				return array( __( 'Please enter a coupon code.', 'nexvio' ), __( 'Please enter a coupon code.', 'woocommerce' ), 'Please enter a coupon code.' );
			case 'calc':
				return array( __( 'Calculated at checkout', 'nexvio' ), '', '' );
			case 'close':
				return array( __( 'Close the cart', 'nexvio' ), '', '' );
			case 'error':
				return array( __( 'Something went wrong. Please try again.', 'nexvio' ), '', '' );
			case 'open_cart':
				return array( __( 'Open cart', 'nexvio' ), __( 'View cart', 'woocommerce' ), 'View cart' );
		}
		return array( '', '', '' );
	}

	/** Raw English source of this plugin's own phrase (to know whether it was translated). */
	private static $source = array(
		'title' => 'Your Shopping Bag', 'subtitle' => 'Review your selected items', 'empty_title' => 'Your shopping bag is empty',
		'empty_text' => 'Discover our latest pieces and find something you love.', 'continue' => 'Continue Shopping', 'checkout' => 'Proceed to Checkout',
		'view_cart' => 'View Cart', 'coupon_link' => 'Have a coupon code?', 'coupon_placeholder' => 'Coupon code', 'apply' => 'Apply', 'subtotal' => 'Subtotal',
		'shipping' => 'Shipping', 'total' => 'Total', 'remove' => 'Remove', 'remove_coupon' => 'Remove', 'qty' => 'Quantity',
		'options' => 'Please choose the product options first.', 'coupon_empty' => 'Please enter a coupon code.', 'calc' => 'Calculated at checkout',
		'close' => 'Close the cart', 'error' => 'Something went wrong. Please try again.', 'open_cart' => 'Open cart',
	);

	public static function lang() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return strtolower( substr( (string) $locale, 0, 2 ) );
	}

	public static function get( $key ) {
		list( $own, $wc, $wc_source ) = self::english( $key );
		$lang = self::lang();

		/* a translation of this plugin always wins */
		if ( isset( self::$source[ $key ] ) && self::$source[ $key ] !== $own ) {
			return $own;
		}
		if ( 'en' === $lang ) {
			return $own;
		}
		/* the built-in luxury phrases, then WooCommerce's translation of standard shop words */
		$dict = self::dictionary();
		if ( isset( $dict[ $key ][ $lang ] ) ) {
			return $dict[ $key ][ $lang ];
		}
		if ( '' !== $wc && $wc !== $wc_source ) {
			return $wc;
		}
		return $own;
	}
}
