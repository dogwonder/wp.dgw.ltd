const {series, parallel, watch} = require('gulp');

// Pull in each task

//File tasks
const sass = require('./gulp-tasks/sass.js');
const scripts = require('./gulp-tasks/scripts.js');
const images = require('./gulp-tasks/images.js');

//Utlitiy tasks
const favicon = require('./gulp-tasks/icons.js');
const move = require('./gulp-tasks/move.js');
const clean = require('./gulp-tasks/clean.js');
const meta = require('./gulp-tasks/meta.js');
const rename = require('./gulp-tasks/rename.js');

//Build tasks
const bump = require('./gulp-tasks/bump.js');
const sw = require('./gulp-tasks/sw.js');

//Warnings
// const beep = require('beepbeep');

// Set each directory and contents that we want to watch and
// assign the relevant task. `ignoreInitial` set to true will
// prevent the task being run when we run `gulp watch`, but it
// will run when a file changes.
const watcher = () => {
  watch('./src/images/**/*', {ignoreInitial: true}, images);
  watch('./src/scripts/**/*', {ignoreInitial: true}, scripts);
  watch('./src/scss/**/*.scss', {ignoreInitial: true}, parallel(sass, rename));
};

//Public tasks are exported from this gulpfile, which allows them to be run by the gulp command, e.g. gulp watch, gulp build

// The default (if someone just runs `gulp`) is to run each task in parrallel
exports.default = series(clean, parallel(sass, scripts, images, move), rename);

// This is our watcher task that instructs gulp to watch directories and
// act accordingly
exports.watch = watcher;

//Production tasks
exports.prod = series(bump, sw, favicon, meta);