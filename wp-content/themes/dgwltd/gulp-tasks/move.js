const {dest, src} = require('gulp');
const merge = require('merge-stream');

// Moving files
const move = () => {
    let scripts = src(['./src/vendor/govuk-frontend-4.0.1.min.js'])
        .pipe(dest('./dist/scripts'));
    let fav = src(['./src/images/fav/manifest.json'])
        .pipe(dest('./dist/images/fav'));
    let fonts = src(['./src/fonts/**/*'])
        .pipe(dest('./dist/fonts'));
    return merge(scripts, fav, fonts);
};

module.exports = move;