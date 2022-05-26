<?php 
$social = get_field('social_links', 'options');
$github = $social['github_link'];
$twitter = $social['twitter_link'];
?>
<ul class="dgwltd-footer__social-links dgwltd-footer__inline-list">
    
    <li>
        <a href="<?php echo ($twitter ? $twitter : 'https://twitter.com/dogwonder'); ?>" rel="external noopener noreferrer" aria-label="Visit our Twitter page">
            <svg aria-hidden="true" focusable="false" class="icon icon-social icon-social__twitter">
            <use xlink:href="#social-twitter" />
            </svg>
        </a>
    </li>

    <li>
        <a href="<?php echo ($github ? $github : 'https://github.com/dogwonder'); ?>" rel="external noopener noreferrer" aria-label="Visit our Twitter page">
            <svg aria-hidden="true" focusable="false" class="icon icon-social icon-social__github">
            <use xlink:href="#social-github" />
            </svg>
        </a>
    </li>

</ul>