# DGW.ltd Wordpress theme

## Requirements

| Prerequisite    | How to check | How to install                                  |
| --------------- | ------------ | ----------------------------------------------- |
| PHP >= 7.3.x    | `php -v`     | [php.net](http://php.net/manual/en/install.php) |
| Node.js >= 12.0 | `node -v`    | [nodejs.org](http://nodejs.org/)                |
| gulp >= 4.0.0   | `gulp -v`    | `npm install -g gulp`                           |
| acfpro >= 5.9.4 |              | [advancedcustomfields.com](https://www.advancedcustomfields.com/pro/)         |

## Build

- `npm run start` — Compile assets when file changes are made
- `npm run production` — Compile assets for production


## Overrides for Framework

This site uses the [GOV.UK design system](https://design-system.service.gov.uk) as the underlying framework. It's used pretty sparingly but userful for [components](https://design-system.service.gov.uk/components/) such as forms and other [layouts](https://design-system.service.gov.uk/styles/layout/)

This is installed via npm `npm install govuk-frontend --save` [see here for more instructions](https://frontend.design-system.service.gov.uk/installing-with-npm/#install-with-node-js-package-manager-npm). [Gov.uk github repo](https://github.com/alphagov/govuk-design-system)

In `vendor.scss` we need to overide the default font family. 

```
$govuk-include-default-font-face: false;
$govuk-font-family: -apple-system, BlinkMacSystemFont,"Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell","Fira Sans", "Droid Sans","Helvetica Neue",sans-serif ;
@import "../../node_modules/govuk-frontend/govuk/all.scss";
```

For the Javascript we need to [manually download](https://frontend.design-system.service.gov.uk/install-using-precompiled-files/#install-using-precompiled-files) and update the version as we use a precompiled version of the JS. Place it in the `src/vendor/` folder and update `footer.php`, `move.js` and `sw.njk.js` files to new version name

## Other notable 3rd party integrations

- [Photoswipe](Photoswipe) integration for galleries
- [Youtube](https://github.com/paulirish/lite-youtube-embed) and [Vimeo](https://github.com/slightlyoff/lite-vimeo) lite plugins (render the video as a screenshot until a user interacts with the video to save bandwidth)


## Performance

The site has been prepared for optimal performance with an overall lighthouse [score of 96](https://googlechrome.github.io/lighthouse/scorecalc/#FCP=2197.7309999999998&SI=2197.7309999999998&LCP=2449.9184999999998&TTI=2572.7309999999998&TBT=21&CLS=0&FCI=&FMP=&device=mobile&version=6.5.0) for the [style guide page](https://dgw.ltd/style-guide/)

See more perf scores here. 

- [Performance dashboard](https://perf.dgw.ltd/dgwltd/)


## Analytics

I am using [Plausible.io](https://plausible.io/simple-web-analytics) a simple privacy focused analyics service, as such I don't need to set a cookie banner. 

Obviously you would want to add your own analytics so replace `<script async defer data-domain="dgw.ltd" src="https://analytics.dgw.ltd/js/index.js"></script>` from `header.php`

Note: if you do use Plausible you can [exclude it](https://plausible.io/docs/excluding) recording your own visits by pasting this into the developer console `localStorage.plausible_ignore=true`


## Custom blocks (optional)

These are actived via a custom plugin *dgwltd: Blocks*

This requires [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/). $$ - but it really is the greatest plugin ever made. 

These are saved in `wp-plugins\dgwltd-blocks\src\acf-json`

- DGW.ltd Accordion - based on GOV.UK's [accordian pattern](https://design-system.service.gov.uk/components/accordion/) 
- DGW.ltd Cards - grid of cards linking to other pages, title, exerpt and featured image 
- DGW.ltd CTA - call to action split text and image
- DGW.ltd Details - based on GOV.UK's [details pattern](https://design-system.service.gov.uk/components/details/)
- DGW.ltd Embed - lite embed custom element for [Youtube](https://github.com/paulirish/lite-youtube-embed) and [Vimeo](https://github.com/slightlyoff/lite-vimeo)
- DGW.ltd Feature - text and background image similar to hero but less showy
- DGW.ltd Image - custom image with aspect ratio variables
- DGW.ltd Hero - hero section with big image / video as background
- DGW.ltd Summary list - based on GOV.UK's [summary list pattern](https://design-system.service.gov.uk/components/summary-list/) 
- DGW.ltd Related pages - list of related links

## Custom block patterns

Included in the plugin *dgwltd: Blocks* alongside the custom blocks this allows for pre-made collections of blocks, accessible under the 'DGW.ltd' in patterns dropdown

- Supporters
- FAQs
- Columns - dark
- Columns - light
- Cover - columns
- Meet the team

## Starter content (experimental)

Sets up a few pages (home, about, contact and blog) and menus (prinary, footer and legal) located in `starter-content.php` this can be activated by using the Wordpress customiser. 

## Templates

### Blocks template

`template-layout.php` 

For home and gateway pages, allows for full width blocks (e.g. DGW.ltd Hero / DGW.ltd Feature) these can be used in any post or page but would be restricted to a fixed width and look weird. This also removes the page title (can be re-added via a heading block)


### Guide template

`template-guide.php`

Similar to NHS [contents guide](https://www.nhs.uk/conditions/type-2-diabetes/) this allows for a parent / child relationshiop to be created with all child pages listed with the parent as the first item on a contents list. Allows the user to navigate forwards and backwards through the contents list. 

### Blog template

`template-blog.php`

Blog / posts list template

### Search results template

`template-search.php`

Search results template

### Cookies template

`template-cookies.php`

Cookie settings template. If the optional cookies functionality is turned on this will allow users to save their cookie settings. 

An initial cookie is set to save the users preferences where only stricly necessary cookies are set:

{ "essential": true, "functional": false, "performance": false, "advertising": false };

See `app.js` and the function `cookieScriptsEnable()` for more instructions on how to manually block third-party cookies

header.php - uncomment `<script src="<?php echo get_template_directory_uri(); ?>/dist/scripts/cookies.js"></script>`
header.php - uncomment `get_template_part('template-parts/_organisms/cookie-notice');` 
app.js - uncomment `cookieBanner(), cookieSettingsPage(), cookieSettingsUpdate(), cookieSettingsUpdate(), cookieScriptsEnable()` 

You can then use the PHP function `dgwltd_cookie_var()` to test for functional and analytics cookies. 

## Gallery

Add the Additional CSS class(es) `.dgwltd-gallery` to the block 'Gallery' make a Wordpress gallery block into a modal one (and make sure link to settings are Media file)

Based on PhotoSwipe [Javascript gallery](https://photoswipe.com) 