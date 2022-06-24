<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package dgwltd
 */

?>

	</main><!-- #content -->
	<?php include(locate_template( 'template-parts/_layout/colophon.php')); ?><!-- #colophon -->
</div><!-- #page -->
<?php include(locate_template( 'template-parts/_molecules/sprite.php' )); ?>
<?php wp_footer(); ?>
<script src="<?php echo get_template_directory_uri(); ?>/dist/scripts/app.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/dist/scripts/govuk-frontend-4.0.1.min.js"></script>
<script>window.GOVUKFrontend.initAll()</script>
<script type="module">
	CSS.paintWorklet.addModule('<?php echo get_template_directory_uri() ?>/src/scripts/houdini.js');
    document.documentElement.style.setProperty('--pattern-seed', Math.random() * 10000);
</script>
<script>
  if ('serviceWorker' in navigator) {
	window.addEventListener('load', function() {
	  navigator.serviceWorker.register('/sw.js').then(function(registration) {
		// Successfully registered the Service Worker
		console.log('Service Worker registration successful with scope: ', registration.scope);
	  }).catch(function(err) {
		// Failed to register the Service Worker
		console.log('Service Worker registration failed: ', err);
	  });
	});
  }
</script>
</body>
</html>
