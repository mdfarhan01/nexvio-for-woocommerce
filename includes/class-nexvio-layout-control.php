<?php
/**
 * Elementor control: pick a design layout from preview images.
 * Radio inputs with data-setting, handled by Elementor's standard data control view.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Layout_Control extends \Elementor\Base_Data_Control {

	const TYPE = 'nexvio-layout-select';

	public function get_type() {
		return self::TYPE;
	}

	protected function get_default_settings() {
		return array(
			'label_block' => true,
			'options'     => array(),
		);
	}

	public function enqueue() {
		wp_enqueue_style( 'nexvio-editor', NEXVIO_URL . 'assets/css/editor.css', array(), Nexvio_Plugin::ver( 'assets/css/editor.css' ) );
	}

	public function content_template() {
		?>
		<div class="elementor-control-field nexvio-lc">
			<# if ( data.label ) { #><label class="elementor-control-title">{{{ data.label }}}</label><# } #>
			<div class="elementor-control-input-wrapper">
				<div class="nexvio-lc__grid" role="radiogroup" aria-label="{{ data.label }}">
					<# _.each( data.options, function( opt, value ) { #>
						<label class="nexvio-lc__item" title="{{ opt.desc }}">
							<input type="radio" class="nexvio-lc__input" name="nexvio-lc-{{ data.name }}-{{ data._cid }}" value="{{ value }}" data-setting="{{ data.name }}">
							<span class="nexvio-lc__card">
								<span class="nexvio-lc__img"><img src="{{ opt.image }}" alt="" loading="lazy" width="220" height="140"></span>
								<span class="nexvio-lc__name">{{{ opt.title }}}</span>
								<span class="nexvio-lc__check" aria-hidden="true"><i class="eicon-check"></i></span>
							</span>
						</label>
					<# } ); #>
				</div>
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}
}
