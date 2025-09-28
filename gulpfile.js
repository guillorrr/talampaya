const pkg = require('gulp');
const babel = require('gulp-babel');
const browserSync = require('browser-sync');
const concat = require('gulp-concat');
const path = require('path');
const del = require('del');
const log = require('fancy-log');
const fs = require('fs');
const gulpIf = require('gulp-if');
const through2 = require('through2');
const plumber = require('gulp-plumber');
const sourcemaps = require('gulp-sourcemaps');
const uglify = require('gulp-uglify');
const zip = require('gulp-vinyl-zip');
const replace = require('gulp-replace');
const rename = require('gulp-rename');
const webpack = require('webpack-stream');
const sass = require('gulp-sass')(require('sass'));
const { series, dest, src, watch } = pkg;

/* -------------------------------------------------------------------------------------------------
Theme Name
-------------------------------------------------------------------------------------------------- */
const themeName = process.env.APP_NAME.toLowerCase() || 'talampaya';

/* -------------------------------------------------------------------------------------------------
Styles Bundles
-------------------------------------------------------------------------------------------------- */
const frontendStyles = [
	'./src/theme/assets/styles/main.scss',
	'./patternlab/source/css/style.scss',
];

const backendStyles = [
	'./src/theme/assets/styles/backend-*.scss',
	'./src/theme/assets/styles/backend-*.css',
];

/* -------------------------------------------------------------------------------------------------
JavaScript Files
-------------------------------------------------------------------------------------------------- */
const scriptsFiles = [
	'./src/theme/assets/scripts/scripts.js',
	'./patternlab/source/js/main.js',
	'./src/theme/assets/scripts/backend.js',
];

/* -------------------------------------------------------------------------------------------------
Assets
-------------------------------------------------------------------------------------------------- */
const fontsFiles = ['./src/theme/assets/fonts/**', './patternlab/source/fonts/**'];

const imagesFiles = ['./src/theme/assets/images/**', './patternlab/source/images/**'];

/* -------------------------------------------------------------------------------------------------
Wordpress Theme files
-------------------------------------------------------------------------------------------------- */
const themeFiles = [
	'./src/theme/**',
	'!./src/theme/assets/**',
	'!./src/theme/blocks/**/*.scss',
	'!./src/theme/blocks/**/*.js',
	'!./src/theme/acf-json/**/*.json',
];

/* -------------------------------------------------------------------------------------------------
ACF JSON files
-------------------------------------------------------------------------------------------------- */
const acfJsonFiles = ['./build/wp-content/themes/' + themeName.toLowerCase() + '/acf-json/**'];

/* -------------------------------------------------------------------------------------------------
Wordpress Plugin files
-------------------------------------------------------------------------------------------------- */
const pluginsFiles = ['./src/plugins/**'];

/* -------------------------------------------------------------------------------------------------
Wordpress Languages files
-------------------------------------------------------------------------------------------------- */
const languagesFiles = ['./src/theme/assets/languages/**'];

/* -------------------------------------------------------------------------------------------------
Patternlab files
-------------------------------------------------------------------------------------------------- */
const patternlabFiles = [
	'./patternlab/source/_patterns/**/*.twig',
	'!./patternlab/source/_patterns/templates/**',
	'!./patternlab/source/_patterns/pages/**',
];

const patternlabTemplates = ['./patternlab/source/_patterns/templates/**/*.twig'];

const patternlabJson = [
	'./patternlab/source/_data/**/*.json',
	'./patternlab/source/_patterns/pages/*.json',
];

/* -------------------------------------------------------------------------------------------------
Environment Tasks
-------------------------------------------------------------------------------------------------- */

function registerCleanup(done) {
	process.on('SIGINT', () => {
		process.exit(0);
	});
	done();
}

function replaceThemeName() {
	const themeNameCapitalized = themeName.charAt(0).toUpperCase() + themeName.slice(1);
	const themeNameUppercase = themeName.toUpperCase();
	const themeNameLowercase = themeName.toLowerCase();

	return through2.obj(function (file, encoding, callback) {
		if (file.isBuffer()) {
			let content = file.contents.toString(encoding);

			content = content.replace(/Talampaya/g, themeNameCapitalized);
			content = content.replace(/talampaya/g, themeNameLowercase);
			content = content.replace(/TALAMPAYA/g, themeNameUppercase);
			content = content.replace(/talampaya-/g, themeNameLowercase + '-');
			content = content.replace(/talampaya_/g, themeNameLowercase + '_');

			file.contents = Buffer.from(content, encoding);
		}

		this.push(file);
		callback();
	});
}

function renameFile() {
	const themeNameCapitalized = themeName.charAt(0).toUpperCase() + themeName.slice(1);
	const themeNameUppercase = themeName.toUpperCase();
	const themeNameLowercase = themeName.toLowerCase();

	return rename(function (path) {
		// Reemplazo en el nombre del archivo (base) según PascalCase, camelCase, etc.
		path.basename = path.basename
			.replace(/Talampaya/g, themeNameCapitalized) // PascalCase
			.replace(/talampaya/g, themeNameLowercase) // camelCase
			.replace(/TALAMPAYA/g, themeNameUppercase); // Uppercase

		// Reemplazo en el nombre del archivo con kebab-case y snake_case
		path.basename = path.basename
			.replace(/talampaya-/g, themeNameLowercase + '-')
			.replace(/talampaya_/g, themeNameLowercase + '_');
	});
}

function isNotZip(file) {
	return !file.path.endsWith('.zip');
}

/* -------------------------------------------------------------------------------------------------
Development Tasks
-------------------------------------------------------------------------------------------------- */
function devServer() {
	const proxy_port = parseInt(process.env.PROXY_PORT) || 3000;
	const ui_port = parseInt(process.env.UI_PORT) || 3001;
	const nginx_port = parseInt(process.env.NGINX_PORT) || 80;
	const domain = process.env.DOMAIN || 'localhost';
	const protocol = process.env.PROTOCOL || 'http';

	browserSync({
		logPrefix: '🐳 WordPress',
		proxy: {
			target: `${protocol}://nginx:${nginx_port}`,
			proxyReq: [
				function (proxyReq, req, res) {
					proxyReq.setHeader('Host', req.headers.host);
				},
			],
		},
		host: domain,
		port: proxy_port,
		notify: true,
		open: false,
		logConnections: true,
		https: {
			key: `/var/www/ssl/${domain}-key.pem`,
			cert: `/var/www/ssl/${domain}.pem`,
		},
		files: ['**/*.php'],
		cors: true,
		reloadDelay: 0,
		ui: {
			port: ui_port,
		},
	});

	watchFiles();
}

function Reload() {
	browserSync.reload();
}

function copyAcfJsonFiles() {
	console.log('Copying ACF Json files...');
	if (!fs.existsSync('./src/theme/acf-json')) {
		log('No ACF Json dir found');
		process.exit(1);
	} else {
		return src(acfJsonFiles, { encoding: false }).pipe(dest('./src/theme/acf-json'));
	}
}

function copyPatternlabFiles() {
	console.log('Copying Patternlab files...');
	if (!fs.existsSync('./patternlab/source/_patterns')) {
		log('No Patternlab dir found');
		process.exit(1);
	} else {
		return src(patternlabFiles, { encoding: false }).pipe(
			dest('./build/wp-content/themes/' + themeName + '/views')
		);
	}
}

function copyPatternlabJson() {
	console.log('Copying Patternlab JSON files...');
	if (!fs.existsSync('./patternlab/source/_data')) {
		log('No Patternlab data dir found');
		process.exit(1);
	} else {
		return src(patternlabJson, { encoding: false }).pipe(
			dest('./build/wp-content/themes/' + themeName + '/inc/mockups')
		);
	}
}

function wrapWithTemplate(content) {
	// Verificar si el contenido ya tiene una directiva extends
	if (content.trim().startsWith('{% extends')) {
		// Si ya extiende una plantilla, no lo envolvemos
		return content;
	} else {
		// Si no tiene extends, aplicamos el wrapper
		const template = `{% extends "layouts/base.twig" %}

{% block content %}
###CONTENT###
{% endblock content %}
`;
		return template.replace('###CONTENT###', content);
	}
}

function copyPatternlabTemplates() {
	console.log('Copying and transforming Patternlab Templates files...');

	if (!fs.existsSync('./patternlab/source/_patterns/templates')) {
		log('No Patternlab dir found');
		process.exit(1);
	} else {
		return src(patternlabTemplates)
			.pipe(
				through2.obj(function (file, _, cb) {
					if (file.isBuffer()) {
						const fileContent = file.contents.toString();

						// Primero detectamos si ya tiene una directiva extends
						const hasExtends = fileContent.trim().startsWith('{% extends');

						// Solo aplicamos la transformación si es necesario
						const transformedContent = hasExtends
							? fileContent
							: wrapWithTemplate(fileContent);

						// Actualizar el contenido del archivo
						file.contents = Buffer.from(transformedContent);
					}
					cb(null, file);
				})
			)
			.pipe(dest('./build/wp-content/themes/' + themeName + '/views/templates'));
	}
}

function copyThemeDev() {
	console.log('Copying theme files..., themeName: ' + themeName);
	if (!fs.existsSync('./build')) {
		log(buildNotFound);
		process.exit(1);
	} else {
		return src(themeFiles, { encoding: false })
			.pipe(gulpIf(isNotZip, replaceThemeName()))
			.pipe(gulpIf(isNotZip, renameFile()))
			.pipe(dest('./build/wp-content/themes/' + themeName));
	}
}

function copyModifiedThemeFile(filePath) {
	if (!fs.existsSync('./build')) {
		log(buildNotFound);
		process.exit(1);
	} else {
		console.log('Copying file: ' + filePath);
		const extname = path.extname(filePath);
		if (extname !== '.scss' && extname !== '.js') {
			const relativePath = path.relative('./src/theme', filePath);
			const destination =
				'./build/wp-content/themes/' + themeName + '/' + path.dirname(relativePath);

			return src(filePath, { encoding: false })
				.pipe(gulpIf(isNotZip, replaceThemeName()))
				.pipe(gulpIf(isNotZip, renameFile()))
				.pipe(dest(destination));
		}
	}
}

function copyFontsDev() {
	return src(fontsFiles, { encoding: false }).pipe(
		dest('./build/wp-content/themes/' + themeName + '/fonts')
	);
}

function copyImagesDev() {
	return src(imagesFiles, { encoding: false }).pipe(
		dest('./build/wp-content/themes/' + themeName + '/images')
	);
}

function copyLanguagesDev() {
	return src(languagesFiles, { encoding: false })
		.pipe(
			rename(function (path) {
				if (path.basename === 'talampaya') {
					path.basename = themeName;
				}
			})
		)
		.pipe(replaceThemeName())
		.pipe(dest('./build/wp-content/themes/' + themeName + '/languages'));
}

function stylesDev() {
	console.log('Compiling styles...');
	return src(frontendStyles)
		.pipe(sourcemaps.init())
		.pipe(sass({ includePaths: 'node_modules' }).on('error', sass.logError))
		.pipe(concat('style.css'))
		.pipe(sourcemaps.write('.'))
		.pipe(replace('../../', './'))
		.pipe(replaceThemeName())
		.pipe(dest('./build/wp-content/themes/' + themeName))
		.pipe(browserSync.stream());
}

function backendStylesDev() {
	return src(backendStyles)
		.pipe(plumber({ errorHandler: onError }))
		.pipe(sass({ includePaths: 'node_modules' }).on('error', sass.logError))
		.pipe(concat('backend-styles.css'))
		.pipe(replace('../../', './'))
		.pipe(dest('./build/wp-content/themes/' + themeName + '/css'))
		.pipe(browserSync.stream());
}

function webpackScriptsDev() {
	return src('.')
		.pipe(webpack(require('./webpack.config.js')))
		.pipe(dest('./build/wp-content/themes/' + themeName + '/js'));
}

function pluginsDev() {
	return src(pluginsFiles).pipe(dest('./build/wp-content/plugins'));
}

function watchFiles() {
	watch(frontendStyles.concat(backendStyles), {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path, stats) {
		console.log(`File ${path} was changed`);
		stylesDev();
		backendStylesDev();
	});
	watch(scriptsFiles, {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path, stats) {
		console.log(`File ${path} was changed`);
		webpackScriptsDev();
		Reload();
	});
	watch(fontsFiles.concat(imagesFiles), {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path, stats) {
		console.log(`File ${path} was changed`);
		copyImagesDev();
		copyFontsDev();
		Reload();
	});
	watch(themeFiles, {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path, stats) {
		console.log(`File ${path} was changed`);
		copyModifiedThemeFile(path);
		stylesDev();
		backendStylesDev();
		Reload();
	});
	watch(pluginsFiles, {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path, stats) {
		console.log(`File ${path} was changed`);
		pluginsDev();
		Reload();
	});
	watch(acfJsonFiles, {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path, stats) {
		console.log(`File ${path} was changed`);
		copyAcfJsonFiles();
	});
	watch(patternlabFiles, {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path, stats) {
		console.log(`File ${path} was changed`);
		copyPatternlabFiles();
		copyPatternlabTemplates();
	});
}

const dev = series(
	registerCleanup,
	copyThemeDev,
	copyImagesDev,
	copyFontsDev,
	stylesDev,
	backendStylesDev,
	webpackScriptsDev,
	pluginsDev,
	copyLanguagesDev,
	copyAcfJsonFiles,
	copyPatternlabFiles,
	copyPatternlabJson,
	copyPatternlabTemplates,
	devServer
);
dev.displayName = 'dev';

exports.dev = dev;

/* -------------------------------------------------------------------------------------------------
Production Tasks
-------------------------------------------------------------------------------------------------- */
async function cleanProd() {
	await del(['./dist/*']);
}

function copyThemeProd() {
	return src(themeFiles, { encoding: false })
		.pipe(replaceThemeName())
		.pipe(renameFile())
		.pipe(dest('./dist/themes/' + themeName));
}

function copyFontsProd() {
	return src(fontsFiles, { encoding: false })
		.pipe(plumber({ errorHandler: onError }))
		.pipe(dest('./dist/themes/' + themeName + '/fonts'));
}

function copyImagesProd() {
	return src(imagesFiles, { encoding: false })
		.pipe(plumber({ errorHandler: onError }))
		.pipe(dest('./dist/themes/' + themeName + '/images'));
}

function copyLanguagesProd() {
	return src(['./src/theme/assets/languages/**'], { encoding: false })
		.pipe(
			rename(function (path) {
				if (path.basename === 'talampaya') {
					path.basename = themeName;
				}
			})
		)
		.pipe(replaceThemeName())
		.pipe(dest('./dist/themes/' + themeName + '/languages'));
}

function stylesProd() {
	return src(frontendStyles)
		.pipe(sass({ includePaths: 'node_modules' }).on('error', sass.logError))
		.pipe(concat('style.css'))
		.pipe(replace('../../', './'))
		.pipe(replaceThemeName())
		.pipe(dest('./dist/themes/' + themeName));
}

function backendStylesProd() {
	return src(backendStyles)
		.pipe(plumber({ errorHandler: onError }))
		.pipe(sass({ includePaths: 'node_modules' }).on('error', sass.logError))
		.pipe(concat('backend-styles.css'))
		.pipe(replace('../../', './'))
		.pipe(dest('./dist/themes/' + themeName + '/css'));
}

function webpackScriptsProd() {
	return src('.')
		.pipe(webpack(require('./webpack.config.js')))
		.pipe(dest('./dist/themes/' + themeName + '/js'));
}

function pluginsProd() {
	return src(pluginsFiles).pipe(dest('./dist/plugins'));
}

function patternlabProd() {
	return src(patternlabFiles).pipe(dest('./dist/themes/' + themeName + '/views'));
}

function patternlabTemplatesProd() {
	return src(patternlabTemplates)
		.pipe(
			through2.obj(function (file, _, cb) {
				if (file.isBuffer()) {
					const fileContent = file.contents.toString();

					// Detectar si ya tiene una directiva extends
					const hasExtends = fileContent.trim().startsWith('{% extends');

					// Solo aplicamos la transformación si es necesario
					const transformedContent = hasExtends
						? fileContent
						: wrapWithTemplate(fileContent);

					// Actualizar el contenido del archivo
					file.contents = Buffer.from(transformedContent);
				}
				cb(null, file);
			})
		)
		.pipe(dest('./dist/themes/' + themeName + '/views/templates'));
}

function zipProd() {
	return src('./dist/themes/' + themeName + '/**/*', { encoding: false })
		.pipe(zip.dest('./dist/' + themeName + '.zip'))
		.on('end', () => {
			log(pluginsGenerated);
			log(filesGenerated);
		});
}

const prod = series(
	cleanProd,
	copyThemeProd,
	copyFontsProd,
	copyImagesProd,
	stylesProd,
	backendStylesProd,
	webpackScriptsProd,
	pluginsProd,
	copyLanguagesProd,
	//copyAcfJsonFiles,
	patternlabProd,
	patternlabTemplatesProd,
	zipProd
);
prod.displayName = 'prod';

exports.prod = prod;

/* -------------------------------------------------------------------------------------------------
Utility Tasks
-------------------------------------------------------------------------------------------------- */
const onError = err => {
	log(errorMsg + ' ' + err.toString());
};

function Backup() {
	if (!fs.existsSync('./build')) {
		log(buildNotFound);
		process.exit(1);
	} else {
		return src('./build/**/*', { encoding: false })
			.pipe(zip.dest('./backups/' + date + '.zip'))
			.on('end', () => {
				log(backupsGenerated);
			});
	}
}

Backup.displayName = 'backup';

exports.backup = Backup;

/* -------------------------------------------------------------------------------------------------
Messages
-------------------------------------------------------------------------------------------------- */
const date = new Date().toLocaleDateString('es-ES').replace(/\//g, '.');
const errorMsg = '\x1b[41mError\x1b[0m';
const buildNotFound =
	errorMsg +
	' ⚠️　- You need to build the project first. Run the command: $ \x1b[1mdocker compose build\x1b[0m';
const filesGenerated =
	'Your ZIP template file was generated in: \x1b[1m' + '/dist/' + themeName + '.zip\x1b[0m - ✅';
const pluginsGenerated = 'Plugins are generated in: \x1b[1m' + '/dist/plugins/\x1b[0m - ✅';
const backupsGenerated =
	'Your backup was generated in: \x1b[1m' + '/backups/' + date + '.zip\x1b[0m - ✅';

/* -------------------------------------------------------------------------------------------------
End of all Tasks
-------------------------------------------------------------------------------------------------- */
