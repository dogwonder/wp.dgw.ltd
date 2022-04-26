const {dest, src} = require('gulp');
const rollup = require('gulp-better-rollup');
const babel = require('rollup-plugin-babel');
const resolve = require('rollup-plugin-node-resolve');
const commonjs = require('rollup-plugin-commonjs');
const sourcemaps = require("gulp-sourcemaps");
const { terser } = require("rollup-plugin-terser");

// Flags wether we compress the output etc
const isProduction = process.env.NODE_ENV === 'production';

const options = {
  in: './src/scripts/app.js',
  out: './dist/scripts'
}

const scripts = () => {

  let stream = src(options.in)

  // initialise sourcemaps
  if (!isProduction) {
    stream = stream
      .pipe(sourcemaps.init())
  }
  
  //ES6 see https://nshki.com/es6-in-gulp-projects/
  stream = stream
  .pipe(rollup({ 
    plugins: [
      resolve(), 
      commonjs(),
      babel({
        runtimeHelpers: true
      }),
      terser()
    ] 
  },{
    format: "umd"
  }))

  //Sourcemaps write
  if (!isProduction) {
    stream = stream
      .pipe(sourcemaps.write('.'))
  }
  //Output
  return stream.pipe(dest(options.out))
};
  
module.exports = scripts;