const {dest, src} = require('gulp');
const cleanCSS = require('gulp-clean-css');
const sassProcessor = require('gulp-sass');
const sourcemaps = require("gulp-sourcemaps");

// We want to be using canonical Sass, rather than node-sass
sassProcessor.compiler = require('sass');

// Flags wether we compress the output etc
const isProduction = process.env.NODE_ENV === 'production';

const options = {
  in: './src/scss/*.scss',
  out: './dist/css',
  criticalOut: './dist/css',
  criticalStyles: [
    'critical.scss'
  ]
}

/**
 * calculateOutput
 *
 * determine where output file goes based on input
 *
 * @param {object}
 */
 const calculateOutput = ({history}) => {
  // get filename of source
  const sourceFileName = /[^/]*$/.exec(history[0])[0]

  // if critical, set output directory to criticalOut
  if (options.criticalStyles.includes(sourceFileName)) {
    return options.criticalOut
  }

  return options.out
}

// The main Sass method grabs all root Sass files,
// processes them, then sends them to the output calculator
const sass = () => {
  
  let stream = src(options.in)
   
  // initialise sourcemaps
   if (!isProduction) {
    stream = stream
      .pipe(sourcemaps.init())
  }

  //Log errors
  stream = stream
  .pipe(sassProcessor().on('error', sassProcessor.logError))

  if (isProduction) {
    stream = stream
    .pipe(cleanCSS({compatibility: 'ie8'}))
  }
  
  return stream.pipe(dest(calculateOutput));

};

module.exports = sass;
