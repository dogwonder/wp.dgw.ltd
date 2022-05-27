<footer class="dgwltd-footer">

	<div class="container">   

		<div class="dgwltd-footer__section">
			
				<div class="dgwltd-footer__links">
				<?php
				if ( has_nav_menu( 'footer-links' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer-links',
							'menu_id'        => 'footer-nav',
							'menu_class'     => 'dgwltd-footer__list',
							'container'      => false,
						)
					);	
				}
				?>
				</div>
			
				<div class="dgwltd-footer__social">
					<p class="visually-hidden"><?php esc_html_e( 'Connect', 'dgwltd' ); ?></p>
					<?php get_template_part( 'template-parts/_molecules/social-links' ); ?>
				</div>

		</div>

		<div class="dgwltd-footer__section dgwltd-footer__legal">
			<span class="dgwltd-footer__copyright">© Dogwonder Ltd, <?php echo date( 'Y' ); ?></span>
			<?php
			if ( has_nav_menu( 'legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'legal',
						'menu_id'        => 'legal-nav',
						'menu_class'     => 'dgwltd-footer__list dgwltd-footer__inline-list',
						'container'      => false,
					)
				);
			}
			?>
		</div>

	</div>

</footer>