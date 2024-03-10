// gulpfile.js

const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const webpack = require('webpack-stream');

sass.compiler = require('node-sass');

function styles() {
	return gulp.src('src/assets/scss/*.scss').pipe(sass()).pipe(gulp.dest('dist/css/'));
}

function scripts() {
	return gulp
		.src('.')
		.pipe(webpack(require('./webpack.config.js')))
		.pipe(gulp.dest('dist/js/'));
}

exports.build = gulp.parallel(styles, scripts);
