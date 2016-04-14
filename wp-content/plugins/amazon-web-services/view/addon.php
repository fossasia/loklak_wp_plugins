<li class="addon <?php echo $slug; ?>">
	<article>
		<div class="info">
			<?php if ( isset( $addon['icon'] ) ) : ?>
				<img src="<?php echo $this->get_addon_icon_url( $slug ); ?>">
			<?php endif; ?>
			<h1><?php echo $addon['title']; // xss ok ?></h1>
			<?php if ( isset( $addon['sub'] ) ) : ?>
				<h2><?php echo esc_html( $addon['sub'] ); ?></h2>
			<?php endif; ?>
			<ul>
				<?php echo $this->get_addon_details_link( $slug, $addon ); ?>
				<?php echo $this->get_addon_install_link( $slug, $addon ); ?>
			</ul>
		</div>
		<?php if ( isset( $addon['label'] ) ) : ?>
			<span class="label"><?php echo esc_html( $addon['label'] ); ?></span>
		<?php endif; ?>
	</article>

	<?php if ( isset( $addon['addons'] ) ) : ?>
		<ul>
			<?php $this->render_addons( $addon['addons'] ); ?>
		</ul>
	<?php endif; ?>
</li>