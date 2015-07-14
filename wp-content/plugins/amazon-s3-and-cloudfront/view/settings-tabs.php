<h2 class="nav-tab-wrapper">
	<?php
	$tabs = $this->get_settings_tabs();
	foreach ( $tabs as $tab => $label ) : ?>
		<a href="#" class="nav-tab <?php echo 'media' == $tab ? 'nav-tab-active' : ''; ?> js-action-link <?php echo $tab; ?>" data-tab="<?php echo $tab; ?>">
			<?php echo esc_html( $label ); ?>
		</a>
	<?php endforeach; ?>
</h2>