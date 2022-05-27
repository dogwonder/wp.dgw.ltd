<?php 
$social = get_field('social_links', 'options');
$facebook = $social['facebook_link'];
$twitter = $social['twitter_link'];
$instagram = $social['instagram_link'];
$tiktok = $social['tiktok_link'];
$youtube = $social['youtube_link'];
$linkedin = $social['linkedin_link'];
$github = $social['github_link'];
?>
<ul class="dgwltd-footer__social-links dgwltd-footer__inline-list">

    <?php if($facebook) : ?>
    <li>
        <a href="<?php echo ($facebook ? $facebook : 'https://www.facebook.com/'); ?>" rel="external noopener noreferrer" aria-label="Visit our Facebook page">
            <svg aria-hidden="true" focusable="false" class="icon icon-social icon-social__facebook">
            <use xlink:href="#social-facebook" />
            </svg>
        </a>
    </li>
    <?php endif; ?>

    <?php if($twitter) : ?>    
    <li>
        <a href="<?php echo ($twitter ? $twitter : 'https://twitter.com/'); ?>" rel="external noopener noreferrer" aria-label="Visit our Twitter page">
            <svg aria-hidden="true" focusable="false" class="icon icon-social icon-social__twitter">
            <use xlink:href="#social-twitter" />
            </svg>
        </a>
    </li>
    <?php endif; ?>

    <?php if($instagram) : ?>    
    <li>
        <a href="<?php echo ($instagram ? $instagram : 'https://instagram.com/'); ?>" rel="external noopener noreferrer" aria-label="Follow us on Instagram">
            <svg aria-hidden="true" focusable="false" class="icon icon-social icon-social__instagram">
            <use xlink:href="#social-instagram" />
            </svg>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if($tiktok) : ?>
    <li>
        <a href="<?php echo ($tiktok ? $tiktok : 'https://www.tiktok.com/'); ?>" rel="external noopener noreferrer" aria-label="Visit our TikTok profile">
            <svg aria-hidden="true" focusable="false" class="icon icon-social icon-social__tiktok">
            <use xlink:href="#social-tiktok" />
            </svg>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if($youtube) : ?>
    <li>
        <a href="<?php echo ($youtube ? $youtube : 'https://www.youtube.com/'); ?>" rel="external noopener noreferrer" aria-label="Visit our YouTube channel">
            <svg aria-hidden="true" focusable="false" class="icon icon-social icon-social__youtube">
            <use xlink:href="#social-youtube" />
            </svg>
        </a>
    </li>
    <?php endif; ?>

    <?php if($linkedin) : ?>
    <li>
        <a href="<?php echo ($linkedin ? $linkedin : 'https://www.linkedin.com/'); ?>" rel="external noopener noreferrer" aria-label="Visit our Linkedin profile">
            <svg aria-hidden="true" focusable="false" class="icon icon-social icon-social__linkedin">
            <use xlink:href="#social-linkedin" />
            </svg>
        </a>
    </li>
    <?php endif; ?>

    <?php if($github) : ?>
    <li>
        <a href="<?php echo ($github ? $github : 'http://github.com/dogwonder/'); ?>" rel="external noopener noreferrer" aria-label="Visit our Linkedin profile">
            <svg aria-hidden="true" focusable="false" class="icon icon-social icon-social__github">
            <use xlink:href="#social-github" />
            </svg>
        </a>
    </li>
    <?php endif; ?>

    
</ul>