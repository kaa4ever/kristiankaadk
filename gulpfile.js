var gulp = require('gulp');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var sourcemaps = require('gulp-sourcemaps');
var gutil = require("gulp-util");
var webpack = require("webpack");
var stream = require('webpack-stream');
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');
var minifyCSS = require('gulp-minify-css');
var rename = require('gulp-rename');

settings = {
  stylesheets: {
    watch: 'site/profiles/kk/themes/kaatheme/styles/**/*.scss',
    entry: 'site/profiles/kk/themes/kaatheme/styles/style.scss',
    build: 'site/profiles/kk/themes/kaatheme/styles'
  }
};

gulp.task('sass', function () {
  gulp.src(settings.stylesheets.entry)
    .pipe(sourcemaps.init())
    .pipe(sass()).on('error', gutil.log.bind(gutil, 'Error'))
    .pipe(autoprefixer({
      browsers: ['last 2 versions'],
      cascade: false
    })).on('error', gutil.log.bind(gutil, 'Error'))
    .pipe(minifyCSS()).on('error', gutil.log.bind(gutil, 'Error'))
    .pipe(rename('styles.min.css')).on('error', gutil.log.bind(gutil, 'Error'))
    .pipe(gulp.dest(settings.stylesheets.build));
});

gulp.task('watch', function () {
  gulp.watch([settings.stylesheets.watch], ['sass']);
});

/**
 * Default task. Watches JS, HTML and CSS.
 */
gulp.task('default', ['watch']);