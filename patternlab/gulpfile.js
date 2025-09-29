const gulp = require('gulp');
const browserSync = require('browser-sync').create();
const sass = require('gulp-sass')(require('sass'));
const concat = require('gulp-concat');
const order = require('gulp-order');
const environments = require('gulp-environments');
const replace = require('gulp-replace');
const gulpIf = require('gulp-if');
const print = require('gulp-print').default;
const webpack = require('webpack-stream');

process.env.NODE_ENV = process.env.NODE_ENV || 'development';

const sync_port = parseInt(process.env.SYNC_PORT) || 4001;
const ui_port = parseInt(process.env.UI_PORT) || 4002;

const development = environments.development;
const production = environments.production;

const filesStyles = [
	'./source/css/style.scss',
	'./source/css/scss/**/*.scss',
	'./source/_patterns/**/*.scss',
];

const filesScripts = [
	//'./source/js/*.js',
	'./source/js/main.js',
	'./source/_patterns/**/*.js',
];

const libScripts = [
	// './node_modules/jquery/dist/jquery.min.js',
	// './node_modules/bootstrap/dist/js/bootstrap.min.js',
	// './node_modules/popper.js/dist/umd/popper.min.js',
	'./source/js/*.js',
];

gulp.task('js', function () {
	return gulp
		.src('./source/js/main.js')
		.pipe(webpack(require('./webpack.config')))
		.pipe(gulp.dest('./public/js'))
		.pipe(browserSync.stream());
});

gulp.task('sass', function () {
	return (
		gulp
			.src(filesStyles, { allowEmpty: true })
			//.pipe(print(filepath => `SCSS File: ${filepath}`))
			.pipe(sass().on('error', sass.logError))
			.pipe(concat('style.css'))
			.pipe(gulpIf(production(), replace('../../', '../')))
			.pipe(gulp.dest('./public/css'))
			.pipe(browserSync.stream())
	);
});

gulp.task('serve', function () {
	browserSync.init({
		server: './public/',
		port: sync_port,
		notify: true,
		open: false,
		logConnections: true,
		ui: {
			port: ui_port,
		},
	});

	gulp.watch(filesStyles, gulp.series('sass'));
	gulp.watch(filesScripts, gulp.series('js'));
	gulp.watch(
		['./source/_patterns/**/*.twig', './source/_patterns/**/*.json', './source/_data/*.json'],
		{
			interval: 1000,
			usePolling: true,
		}
	).on('change', function (path) {
		console.log(`File ${path} was changed`);
		setTimeout(function () {
			browserSync.reload();
		}, 500);
	});
});

gulp.task('default', gulp.series('sass', 'js', 'serve'));
gulp.task('build', gulp.series('sass', 'js'));
