<?php
/**
 * Hero Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */
$block_id = 'block-' . $block['id'];
if ( ! empty( $block['anchor'] ) ) {
	$block_id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values. and "align" values.
$class_name = 'dgwltd-block dgwltd-block--hero';
if ( ! empty( $block['className'] ) ) {
	$class_name .= ' ' . $block['className'];
}

if ( ! empty( $block['align'] ) ) {
	$class_name .= 'align' . $block['align'];
}

if ( isset($block['full_height']) && true === $block['full_height'] ) {
    $class_name .= ' full-height';
}
// Random ID
$rand = substr( md5( microtime() ), wp_rand( 0, 26 ), 5 );

// Block Fields
$block_type      = get_field( 'block_type' ) ? : '';
$image           = get_field( 'background_image' ) ? : '';
$image_mobile    = get_field( 'background_image_mobile' ) ? : '';
$video           = get_field( 'video', false, false );

$background_color = get_field( 'background_color' ) ? : '';
$background_color_array = array(
	'#270d88' => 'primary', 
	'#ed4911' => 'secondary', 
	'#000000' => 'black', 
	'#ffffff' => 'white', 
	'#081248' => 'azul', 
	'#10827b' => 'teal', 
	'#00FFD9' => 'neon', 
	'#761456' => 'plum', 
	'#e465ab' => 'fuchsia', 
	'#ffa07a' => 'peach'
);
//Loop though the array and create a class for each color
foreach ($background_color_array as $key => $value) {
	if ($background_color == $key) {
		$background_color_name .= 'bg--' . $value;
	}
}
//If not in array, add the default
if (!$background_color_name) {
	$custom_hex = $background_color;
}

$overlay  = get_field( 'overlay' ) ? : '';
if($overlay) {
	$overlay_opacity  = get_field( 'overlay_opacity' ) ? : '';
	$overlay_opacity = $overlay_opacity / 100;
}

//Heights
$has_height = get_field( 'has_height' ) ? : '';
$vertical_height = get_field( 'vertical_height' ) ? : '';

// Classes
$block_image   = $image ? 'has-image ' : '';
$block_type_class	= $block_type ? 'block-type--' . $block_type : '';
$block_video   = $video ? 'has-video ' : '';
$block_height = $has_height ? 'has-height ' : '';
$block_align = $block['align'] ? 'align' . $block['align'] : '';

if($block_height) {
	$block_height  = $vertical_height ? 'height--' . $vertical_height : '';
}
$block_overlay  = $overlay ? 'has-overlay ' : '';
$block_color   = $background_color ? 'has-bg ' . $background_color_name : '';

$block_classes = array( $class_name, $block_type_class, $block_align, $block_image, $block_video, $block_height, $block_overlay, $block_color);

// JSX Innerblocks - https://www.billerickson.net/innerblocks-with-acf-blocks/
$allowed_blocks = array( 'core/heading', 'core/paragraph', 'core/list', 'core/button' );
$block_template = array(
	array(
		'core/heading',
		array(
			'level'       => 1,
			'placeholder' => 'Add title...',
		),
	),
	array(
		'core/paragraph',
		array(
			'placeholder' => 'Add content...',
		),
	),
);

?>
<div id="<?php echo $block_id; ?>" class="<?php echo esc_attr( implode( ' ', $block_classes ) ); ?>" <?php echo ($custom_hex ? 'style="background-color: ' . $custom_hex . '"' : ''); ?>>

			<?php if ( ! empty( $image ) && $block_type == 'image' ) : ?>
				<?php // print_r($image) ?>
				<?php
				$image_tiny        = $image['sizes']['dgwltd-tiny'];
				if($image_mobile) {
					$image_small = $image_mobile['sizes']['dgwltd-medium'];	
					$image_small_width  = esc_attr( $image_mobile['sizes']['dgwltd-medium-width'] );
					$image_small_height = esc_attr( $image_mobile['sizes']['dgwltd-medium-height'] );
				} else {
					$image_small = $image['sizes']['dgwltd-medium'];
					$image_small_width  = esc_attr( $image['sizes']['dgwltd-medium-width'] );
					$image_small_height = esc_attr( $image['sizes']['dgwltd-medium-height'] );
				}
				$image_large       = $image['sizes']['dgwltd-large'];
				$image_alt         = esc_attr( $image['alt'] );
				$image_width       = esc_attr( $image['width'] );
				$image_height      = esc_attr( $image['height'] );
				// For Low quality image placeholders (LQIP)
				$type   = pathinfo( $image_tiny, PATHINFO_EXTENSION );
				$data   = file_get_contents( $image_tiny );
				$base64 = 'data:image/' . $type . ';base64,' . base64_encode( $data );
				?>
				<link rel="preload" href="<?php echo $image_small_crop; ?>" as="image" media="(max-width: 39.6875em)">
				<link rel="preload" href="<?php echo $image_large; ?>" as="image" media="(min-width: 40.0625em)">
				<?php if ( $overlay ) : ?>
					<style>
					#<?php echo $block_id; ?>.dgwltd-block--hero:before {
						display: block;
						z-index: 2;
						content: '';
						position: absolute;
						top: 0;
						right: 0;
						bottom: 0;
						left: 0;
						background-color: <?php echo $overlay; ?>;
						opacity:<?php echo ($overlay_opacity ? $overlay_opacity : '0.7'); ?>;
					}
					#<?php echo $block_id; ?>.dgwltd-block--hero .block__background img {
						filter: grayscale(100%) contrast(200%);
					}
					</style>
				<?php endif; ?>
				<div class="block__background">
					<figure>
						<picture>
							<source media="(min-width: 64em)" srcset="<?php echo $image_large; ?>">
							<img src="<?php echo $image_small; ?>" alt="<?php echo $image_alt ?>" width="<?php echo $image_small_width; ?>" height="<?php echo $image_small_height; ?>" style="background-image: url(<?php echo $base64; ?>)" />
						</picture>
					</figure>
				</div>
			<?php endif; ?>    

			<div class="dgwltd-hero__wrapper">

				<div class="dgwltd-hero__inner">   

					<div class="dgwltd-hero__content stack">
						<InnerBlocks allowedBlocks="<?php echo esc_attr( wp_json_encode( $allowed_blocks ) ); ?>" template="<?php echo esc_attr( wp_json_encode( $block_template ) ); ?>" />

						<?php if ( ! empty( $video ) && $block_type === 'video' ) : ?>
							<div class="dgwltd-hero__play">
								<a class="dgwltd-button popup-trigger"  data-popup-trigger="videoModal<?php echo $rand; ?>">
									<?php esc_html_e( 'Watch', 'dgwltd' ); ?>            
								</a>
							</div>
						<?php endif; ?>

					</div>
				</div>

			</div>

			

			<?php if ( ! empty( $video ) && $block_type === 'video' ) : ?>
				<?php
					// echo $video;
					$parse = parse_url( $video );
					// echo $parse['host'];
				if ( $parse['host'] == 'youtu.be' ) {
					$video_type = 'youtube';
					$video_id   = ltrim( $parse['path'], '/' );
				}
				if ( ( $parse['host'] == 'youtube.com' ) || ( $parse['host'] == 'www.youtube.com' ) ) {

					$video_type = 'youtube';
					parse_str( $parse['query'] );
					$video_id = $v;

					if ( strpos( $parse['path'], 'embed' ) == 1 ) {
						$video_id = end( explode( '/', $parse['path'] ) );
					}
				}
				if ( ( $parse['host'] == 'vimeo.com' ) || ( $parse['host'] == 'www.vimeo.com' ) ) {
					$video_type = 'vimeo';
					$video_id   = ltrim( $parse['path'], '/' );
				}
				?>
				<?php if ( ( $parse['host'] == 'youtube.com' ) || ( $parse['host'] == 'www.youtube.com' ) ) { ?>
				<div class="video-wrapper dgwltd-hero__video">
				<iframe 
				id="youtube_player" 
				src="http://www.youtube.com/embed/<?php echo $video_id; ?>?enablejsapi=1&rel=0&autoplay=1&loop=1&controls=0" 
				frameborder="0" 
				volume="0" 
				allow="autoplay; fullscreen" 
				allowfullscreen 
				></iframe>
				<script src="//www.youtube.com/player_api"></script>    
				<script type="text/javascript">
						//youtube api
						var player;
						function onYouTubeIframeAPIReady() {
						player = new YT.Player('youtube_player', {
							events: {
							'onStateChange': onPlayerStateChange
							}
						});
						}
						function onPlayerStateChange() {
						//...
						}
					</script>
				</div>
				<?php } ?>    

				<?php if ( ( $parse['host'] == 'vimeo.com' ) || ( $parse['host'] == 'www.vimeo.com' ) ) { ?>

				<div class="vimeo-wrapper dgwltd-hero__video">
				<iframe 
				id="vimeo_player" 
				src="https://player.vimeo.com/video/<?php echo $video_id; ?>?background=1&muted=1&autoplay=1&loop=1&byline=0&title=0&controls=0" 
				frameborder="0" 
				allow="autoplay; fullscreen" 
				allowfullscreen 
				></iframe>
				<script src="https://player.vimeo.com/api/player.js"></script>
				</div>
				<?php } ?>

		<?php endif; ?>

 <?php if ( ! empty( $video ) && $block_type === 'video' ) : ?>

 <div 
  class="popup-modal shadow" 
  data-popup-modal="videoModal<?php echo $rand; ?>"
  role="dialog"
  aria-labelledby="dialog-title"
  aria-modal="true"
  >
  <i class="popup-modal__close" aria-label="Close">
  <svg class="icon" viewBox="0 0 320 512" width="0.75em" height="0.75em" stroke="currentColor" stroke-width="2" role="presentation" focusable="false">
  <path d="M312.1 375c9.369 9.369 9.369 24.57 0 33.94s-24.57 9.369-33.94 0L160 289.9l-119 119c-9.369 9.369-24.57 9.369-33.94 0s-9.369-24.57 0-33.94L126.1 256L7.027 136.1c-9.369-9.369-9.369-24.57 0-33.94s24.57-9.369 33.94 0L160 222.1l119-119c9.369-9.369 24.57-9.369 33.94 0s9.369 24.57 0 33.94L193.9 256L312.1 375z"/>
  </svg>
  </i>
  <h2 class="popup-modal__heading" id="dialog-title" tabindex="-1"><?php esc_html_e( 'Play the video', 'dgwltd' ); ?></h2>
		<?php if ( ( $parse['host'] == 'youtube.com' ) || ( $parse['host'] == 'www.youtube.com' ) ) { ?>
		<iframe 
		id="youtube_player" 
		src="http://www.youtube.com/embed/<?php echo $video_id; ?>" 
		frameborder="0" 
		allowfullscreen 
		></iframe>
  <?php } ?>
		<?php if ( ( $parse['host'] == 'vimeo.com' ) || ( $parse['host'] == 'www.vimeo.com' ) ) { ?>
		<iframe 
		id="vimeo_player" 
		src="https://player.vimeo.com/video/<?php echo $video_id; ?>" 
		frameborder="0" 
		allowfullscreen 
		></iframe>
  <?php } ?>
</div>


<script>
 /*
 //https://medium.com/@GistCoding/simple-popup-modal-with-vanilla-javascript-a14515ec630b
*/

 document.addEventListener("DOMContentLoaded", function() {
	const modalTriggers = document.querySelectorAll('.popup-trigger');
	const modalCloseTrigger = document.querySelector('.popup-modal__close');

	modalTriggers.forEach(trigger => {
	trigger.addEventListener('click', () => {
		const { popupTrigger } = trigger.dataset
		const popupModal = document.querySelector(`[data-popup-modal="${popupTrigger}"]`);
		const popupModalTitle = document.querySelector('#dialog-title');

		popupModal.classList.add('is--visible');
		document.body.classList.add('is-blacked-out');

		//allocate focus to the title 
		popupModalTitle.focus();

		popupModal.querySelector('.popup-modal__close').addEventListener('click', () => {
			popupModal.classList.remove('is--visible');
			document.body.classList.remove('is-blacked-out');
		});

	})

	})

});
</script>

<?php endif; ?>

</div>
