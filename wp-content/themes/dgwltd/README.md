# DGW.ltd Wordpress theme

## Requirements

| Prerequisite    | How to check | How to install                                  |
| --------------- | ------------ | ----------------------------------------------- |
| PHP >= 7.3.x    | `php -v`     | [php.net](http://php.net/manual/en/install.php) |
| Node.js >= 12.0 | `node -v`    | [nodejs.org](http://nodejs.org/)                |
| gulp >= 4.0.0   | `gulp -v`    | `npm install -g gulp`                           |
| acfpro >= 5.9.4 |              | [advancedcustomfields.com](https://www.advancedcustomfields.com/pro/)         |

## Build

- `npm run watch` — Compile assets when file changes are made
- `npm run build` — Compile assets for production

## Config

- dgwltd_env() - URL of current site
- dgwltd_get_font_face_styles() - Fonts
- Math div warning: `$ npm install -g sass-migrator` `$ sass-migrator division **/*.scss`
- dgwltd-blocks/src/actionkit/form.php $source // $akID // $actionkit_page_redirect
- Social links - in site settings option page: /wp-admin/admin.php?page=site-general-settings

## Overrides for Framework

This site uses the [GOV.UK design system](https://design-system.service.gov.uk) as the underlying framework. It's used pretty sparingly but userful for [components](https://design-system.service.gov.uk/components/) such as forms and other [layouts](https://design-system.service.gov.uk/styles/layout/)

This is installed via npm `npm install govuk-frontend --save` [see here for more instructions](https://frontend.design-system.service.gov.uk/installing-with-npm/#install-with-node-js-package-manager-npm). [Gov.uk github repo](https://github.com/alphagov/govuk-design-system)

In `vendor.scss` we need to overide the default font family. 

```
$govuk-include-default-font-face: false;
$govuk-focus-colour: #00FFD9;
$govuk-font-family: -apple-system, BlinkMacSystemFont,"Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell","Fira Sans", "Droid Sans","Helvetica Neue",sans-serif;
@import "../../node_modules/govuk-frontend/govuk/all.scss";
```

For the Javascript we need to [manually download](https://frontend.design-system.service.gov.uk/install-using-precompiled-files/#install-using-precompiled-files) and update the version as we use a precompiled version of the JS. Place it in the `src/vendor/` folder and update `footer.php`, `move.js` and `sw.njk.js` files to new version name

## Other notable 3rd party integrations

- [Bootstrap](https://getbootstrap.com/docs/5.0/layout/grid/) only using the grid system see `vendor.scss`
- [Youtube](https://github.com/paulirish/lite-youtube-embed) and [Vimeo](https://github.com/slightlyoff/lite-vimeo) lite plugins (render the video as a screenshot until a user interacts with the video to save bandwidth) -- note we changed the defulat thumbnail size to 1280px `https://i.ytimg.com/vi/${this.videoId}/maxresdefault.jpg`;


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