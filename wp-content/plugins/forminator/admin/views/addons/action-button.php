<?php
/**
 * Template admin/views/addons/action-button.php
 *
 * @package Forminator
 */

$uniqid = ( isset( $id ) && '' !== trim( $id ) ) ? $id : '';

$class  = ( isset( $class ) && '' !== trim( $class ) ) ? $class . ' ' : '';
$class .= 'sui-button';
$class .= isset( $color ) ? ' sui-button-' . $color : '';
$class .= ( isset( $ghost ) && true === is_bool( $ghost ) ) ? ' sui-button-ghost' : '';
?>

<?php
if ( isset( $compound ) && true === is_bool( $compound ) ) {
	$class .= ' sui-button-compound';
	?>

	<button
		<?php
		if ( '' !== $uniqid ) {
			echo 'id="' . esc_attr( $uniqid ) . '"';
		}
		?>
		class="<?php echo esc_attr( $class ); ?>"
		<?php
		if ( isset( $attrs ) ) {
			foreach ( $attrs as $data => $val ) {
				echo esc_attr( $data ) . '="' . esc_attr( $val ) . '"';
			}
		}
		echo isset( $disabled ) && $disabled ? 'disabled' : '';
		?>
	>

		<span class="sui-loading-text" aria-hidden="true">

			<span class="sui-compound-desktop">
				<i class="sui-icon-<?php echo esc_attr( $icon ); ?>"></i>
				<?php echo esc_html( $label ); ?>
			</span>

			<span class="sui-compound-mobile">
			<span class="sui-icon-<?php echo esc_attr( $icon ); ?>"></span>
			</span>

		</span>

		<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>

		<span class="sui-screen-reader-text"><?php echo esc_html( $label ); ?></span>

	</button>

<?php } else { ?>

	<button
		class="<?php echo esc_attr( $class ); ?>"
		<?php
		if ( isset( $attrs ) ) {
			foreach ( $attrs as $data => $val ) {
				echo esc_attr( $data ) . '="' . esc_attr( $val ) . '"';
			}
		}
		?>
	>

		<span class="sui-loading-text">
			<?php
			if ( isset( $icon ) && '' !== trim( $icon ) ) {
				echo '<i class="sui-icon-' . esc_attr( $icon ) . '" aria-hidden="true"></i> ' . esc_html( $label );
			} else {
				echo esc_html( $label );
			}
			?>
		</span>

		<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>

	</button>

<?php } ?>
