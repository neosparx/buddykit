<?php
namespace OptionKit\FieldTypes;

class MultiSelect {
	
	protected $atts = array();

	public function __construct( $args ) {
		$this->atts = $args;
	}

	public function display() {
		do_action('optionkit-before-field');
		if ( empty( $this->atts['options'] ) ) {
			esc_html_e('options argument for field type "multiselect" is not defined', 'optionkit');
			return;
		}
		$value = (array) get_option( $this->atts['id'], $this->atts['default'] );
		?>
		<?php 
		$options = apply_filters( 'optionkit-field-value-'.$this->atts['id'], $this->atts['options'] ); 
		?>
		<select multiple <?php echo $this->atts['attributes']; ?> id="<?php echo esc_attr( $this->atts['name'] ); ?>" name="<?php echo esc_attr( $this->atts['name'] ); ?>[]" <?php echo $this->atts['attributes'] ; ?>>
			<?php foreach( $options as $key => $val ): ?>
				<?php $selected = ''; ?>
				<?php if ( in_array( $key, $value ) ) {?>
					<?php $selected = 'selected'; ?>
				<?php } ?>
				<option <?php echo esc_attr($selected); ?> value="<?php echo esc_attr($key); ?>">
					<?php echo esc_html( $val ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php echo wp_kses_post( $this->atts['description'] ); ?>
		</p>
		<?php
		do_action('optionkit-after-field');
		return;
	}
}