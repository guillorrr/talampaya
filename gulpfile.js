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

/* -------------------------------------------------------------------------------------------------
ACF Json
-------------------------------------------------------------------------------------------------- */

const acfJsonFiles = ['./build/wp-content/themes/' + themeName.toLowerCase() + '/acf-json/**'];

function devCopyAcfJson() {
	return copyFiles(acfJsonFiles, '', {
		checkDir: './src/theme/acf-json',
		customDestPath: './src/theme/acf-json',
		extraMessage: 'ACF Json files',
	});
}

function prodCopyAcfJson() {
	return copyFiles(acfJsonFiles, '', {
		checkDir: './src/theme/acf-json',
		customDestPath: './src/theme/acf-json',
		extraMessage: 'ACF Json files',
		isDev: false,
		skipPlumber: true,
	});
}

/* -------------------------------------------------------------------------------------------------
Generic File Copy Function
-------------------------------------------------------------------------------------------------- */
function copyFiles(sourceFiles, destinationPath, options = {}) {
	const {
		checkDir = null,
		encoding = false,
		isDev = true,
		transforms = [],
		customDestPath = null,
		extraMessage = '',
	} = options;

	if (checkDir && !fs.existsSync(checkDir)) {
		log(`No ${checkDir} dir found`);
		process.exit(1);
	}

	// Determinar la ruta de destino
	let targetPath;
	if (customDestPath) {
		targetPath = customDestPath;
	} else {
		targetPath = isDev
			? `./build/wp-content/themes/${themeName}${destinationPath}`
			: `./dist/themes/${themeName}${destinationPath}`;
	}

	console.log(`Copying files to ${targetPath}...${extraMessage ? ' ' + extraMessage : ''}`);

	// Crear el pipeline inicial
	let pipeline = src(sourceFiles, { encoding });

	// Aplicar las transformaciones si existen
	if (transforms && transforms.length > 0) {
		for (const transform of transforms) {
			pipeline = pipeline.pipe(transform);
		}
	}

	// Aplicar plumber en producción si es necesario
	if (!isDev && !options.skipPlumber) {
		pipeline = pipeline.pipe(plumber({ errorHandler: onError }));
	}

	// Finalizar el pipeline con el destino
	pipeline = pipeline.pipe(dest(targetPath));

	// Añadir browserSync.stream() si es desarrollo y se especifica
	if (isDev && options.browserSyncStream) {
		pipeline = pipeline.pipe(browserSync.stream());
	}

	return pipeline;
}

/* -------------------------------------------------------------------------------------------------
Patternlab Twig files
-------------------------------------------------------------------------------------------------- */

const patternsTwig = [
	'./patternlab/source/_patterns/**/*.twig',
	'!./patternlab/source/_patterns/templates/**',
	'!./patternlab/source/_patterns/pages/**',
];

function devCopyPatterns() {
	return copyFiles(patternsTwig, '/views', './patternlab/source/_patterns');
}

function prodCopyPatterns() {
	return copyFiles(patternsTwig, '/views', './patternlab/source/_patterns');
}

/* -------------------------------------------------------------------------------------------------
Patternlab Json files
-------------------------------------------------------------------------------------------------- */

const patternsJson = [
	'./patternlab/source/_data/**/*.json',
	'./patternlab/source/_patterns/pages/*.json',
];

// Función auxiliar para procesar objetos JSON (declarada fuera para evitar inner-declarations)
function processJsonObject(obj) {
	const domain = process.env.DOMAIN || 'localhost';
	const protocol = process.env.PROTOCOL || 'http';
	const themeUrl = `${protocol}://${domain}/wp-content/themes/${themeName}`;

	if (!obj || typeof obj !== 'object') return;

	// Si es un array, procesamos cada elemento
	if (Array.isArray(obj)) {
		obj.forEach(item => processJsonObject(item));
		return;
	}

	// Iteramos por las propiedades del objeto
	for (const key in obj) {
		// Si el valor es un objeto o array, procesamos recursivamente
		if (obj[key] && typeof obj[key] === 'object') {
			processJsonObject(obj[key]);
		}
		// Si es una propiedad src o href y su valor es un string
		else if ((key === 'src' || key === 'href') && typeof obj[key] === 'string') {
			const value = obj[key];

			// Verificamos que no comience con http o https
			if (!value.match(/^https?:\/\//)) {
				// Eliminamos patrones como "../../" o "./" del inicio
				let cleanPath = value.replace(/^(?:\.\.\/)+|^\.\//, '');

				// Agregamos el prefijo de la URL del tema de WordPress
				obj[key] = `${themeUrl}/${cleanPath}`;
				console.log(`Transformed URL: ${value} -> ${obj[key]}`);
			}
		}
	}
}

function transformJsonUrls() {
	console.log('Iniciando transformación de URLs en archivos JSON');
	return through2.obj(function (file, encoding, callback) {
		console.log(`Procesando archivo: ${file.path}`);

		if (file.isBuffer()) {
			try {
				// Convertir el contenido del archivo a string
				let content = file.contents.toString(encoding);

				// Verificar si el contenido parece ser JSON válido
				if (!content.trim().startsWith('{') && !content.trim().startsWith('[')) {
					console.log(`El archivo no parece contener JSON válido: ${file.path}`);
					this.push(file);
					return callback();
				}

				// Parsear el JSON
				let jsonData = JSON.parse(content);
				console.log(`JSON parseado correctamente: ${file.path}`);

				// Procesar el objeto JSON con la función externa
				processJsonObject(jsonData);

				// Convertir de nuevo a string con formato
				const newContent = JSON.stringify(jsonData, null, 2);

				// Actualizar el contenido del archivo
				file.contents = Buffer.from(newContent, encoding);

				console.log(`JSON URLs transformadas en: ${file.path}`);
			} catch (error) {
				console.error(`Error procesando archivo JSON: ${file.path}`, error.message);
			}
		} else {
			console.log(`El archivo no es un buffer: ${file.path}`);
		}

		this.push(file);
		callback();
	});
}

function devCopyJson() {
	return copyFiles(patternsJson, '/inc/mockups', {
		checkDir: './patternlab/source/_data',
		transforms: [transformJsonUrls()],
		extraMessage: 'and transforming JSON URLs',
	});
}

function prodCopyJson() {
	return copyFiles(patternsJson, '/inc/mockups', {
		isDev: false,
		transforms: [transformJsonUrls()],
	});
}

/* -------------------------------------------------------------------------------------------------
Patternlab Templates Twig files
-------------------------------------------------------------------------------------------------- */

const patternsTemplates = ['./patternlab/source/_patterns/templates/**/*.twig'];

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

function transformTemplates() {
	return through2.obj(function (file, _, cb) {
		if (file.isBuffer()) {
			const fileContent = file.contents.toString();

			// Detectar si ya tiene una directiva extends
			const hasExtends = fileContent.trim().startsWith('{% extends');

			// Solo aplicamos la transformación si es necesario
			const transformedContent = hasExtends ? fileContent : wrapWithTemplate(fileContent);

			// Actualizar el contenido del archivo
			file.contents = Buffer.from(transformedContent);
		}
		cb(null, file);
	});
}

function devCopyPatternsTemplates() {
	return copyFiles(patternsTemplates, '/views/templates', {
		isDev: true,
		checkDir: './patternlab/source/_patterns/templates',
		transforms: [transformTemplates()],
	});
}

function prodCopyPatternsTemplates() {
	return copyFiles(patternsTemplates, '/views/templates', {
		isDev: false,
		checkDir: './patternlab/source/_patterns/templates',
		transforms: [transformTemplates()],
	});
}

/* -------------------------------------------------------------------------------------------------
Theme Files
-------------------------------------------------------------------------------------------------- */

const themeFiles = [
	'./src/theme/**',
	'!./src/theme/assets/**',
	'!./src/theme/blocks/**/*.scss',
	'!./src/theme/blocks/**/*.js',
	'!./src/theme/acf-json/**/*.json',
];

function devCopyTheme() {
	return copyFiles(themeFiles, '', {
		isDev: true,
		transforms: [gulpIf(isNotZip, replaceThemeName()), gulpIf(isNotZip, renameFile())],
	});
}

function prodCopyTheme() {
	return copyFiles(themeFiles, '', {
		isDev: false,
		transforms: [replaceThemeName(), renameFile()],
	});
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

/* -------------------------------------------------------------------------------------------------
Fonts
-------------------------------------------------------------------------------------------------- */

const fontsFiles = ['./src/theme/assets/fonts/**', './patternlab/source/fonts/**'];

function devCopyFonts() {
	return copyFiles(fontsFiles, '/fonts', { isDev: true });
}

function prodCopyFonts() {
	return copyFiles(fontsFiles, '/fonts', { isDev: false });
}

/* -------------------------------------------------------------------------------------------------
Images
-------------------------------------------------------------------------------------------------- */

const imagesFiles = ['./src/theme/assets/images/**', './patternlab/source/images/**'];

function devCopyImages() {
	return copyFiles(imagesFiles, '/images', { isDev: true });
}

function prodCopyImages() {
	return copyFiles(imagesFiles, '/images', { isDev: false });
}

/* -------------------------------------------------------------------------------------------------
Languages
-------------------------------------------------------------------------------------------------- */

const languagesFiles = ['./src/theme/assets/languages/**'];

function processLanguages(isDev = true) {
	const renameLanguageFiles = rename(function (path) {
		if (path.basename === 'talampaya') {
			path.basename = themeName;
		}
	});

	return copyFiles(languagesFiles, '/languages', {
		isDev,
		transforms: [renameLanguageFiles, replaceThemeName()],
	});
}

function devCopyLanguages() {
	return processLanguages(true);
}

function prodCopyLanguages() {
	return processLanguages(false);
}

/* -------------------------------------------------------------------------------------------------
Styles
-------------------------------------------------------------------------------------------------- */

const frontendStyles = [
	'./src/theme/assets/styles/main.scss',
	'./patternlab/source/css/style.scss',
];

const backendStyles = [
	'./src/theme/assets/styles/backend-*.scss',
	'./src/theme/assets/styles/backend-*.css',
];

function processStyles(files, outputFile, subDir = '', isDev = true) {
	console.log(`Compiling ${outputFile} styles...`);

	const domain = process.env.DOMAIN || 'localhost';
	const protocol = process.env.PROTOCOL || 'http';
	const themeUrl = `${protocol}://${domain}/wp-content/themes/${themeName}`;

	let pipeline = src(files);

	if (isDev) {
		pipeline = pipeline.pipe(sourcemaps.init());
	}

	// Configuración común
	pipeline = pipeline
		.pipe(sass({ includePaths: 'node_modules' }).on('error', sass.logError))
		.pipe(concat(outputFile))
		.pipe(replace(/(\.\.\/)+/g, `${themeUrl}/`));

	// Configuraciones específicas
	if (outputFile === 'style.css') {
		pipeline = pipeline.pipe(replaceThemeName());
	}

	if (isDev) {
		pipeline = pipeline.pipe(sourcemaps.write('.'));
	}

	// Destino
	const destPath = isDev
		? `./build/wp-content/themes/${themeName}${subDir ? '/' + subDir : ''}`
		: `./dist/themes/${themeName}${subDir ? '/' + subDir : ''}`;

	pipeline = pipeline.pipe(dest(destPath));

	// Stream para browserSync
	if (isDev) {
		pipeline = pipeline.pipe(browserSync.stream());
	}

	return pipeline;
}

function devCopyStylesFront() {
	return processStyles(frontendStyles, 'style.css', '', true);
}

function devCopyStylesBack() {
	return processStyles(backendStyles, 'backend-styles.css', 'css', true);
}

function prodCopyStylesFront() {
	return processStyles(frontendStyles, 'style.css', '', false);
}

function prodCopyStylesBack() {
	return processStyles(backendStyles, 'backend-styles.css', 'css', false);
}

/* -------------------------------------------------------------------------------------------------
Scripts
-------------------------------------------------------------------------------------------------- */

const scriptsFiles = [
	'./src/theme/assets/scripts/scripts.js',
	'./patternlab/source/js/main.js',
	'./src/theme/assets/scripts/backend.js',
];

function processScripts(isDev = true) {
	const destPath = isDev
		? `./build/wp-content/themes/${themeName}/js`
		: `./dist/themes/${themeName}/js`;

	return src('.')
		.pipe(webpack(require('./webpack.config.js')))
		.pipe(dest(destPath));
}

function devWebpackScripts() {
	return processScripts(true);
}

function prodWebpackScripts() {
	return processScripts(false);
}

/* -------------------------------------------------------------------------------------------------
Plugins
-------------------------------------------------------------------------------------------------- */

const pluginsFiles = ['./src/plugins/**'];

function processPlugins(isDev = true) {
	const destPath = isDev ? './build/wp-content/plugins' : './dist/plugins';

	return src(pluginsFiles).pipe(dest(destPath));
}

function devCopyPlugins() {
	return processPlugins(true);
}

function prodCopyPlugins() {
	return processPlugins(false);
}

/* -------------------------------------------------------------------------------------------------
Watch Files
-------------------------------------------------------------------------------------------------- */

function watchFiles() {
	watch(
		frontendStyles
			.concat(backendStyles)
			.concat([
				'./patternlab/source/css/scss/**/*.scss',
				'./patternlab/source/_patterns/**/*.scss',
			]),
		{
			interval: 1000,
			usePolling: true,
		}
	).on('change', function (path) {
		console.log(`File ${path} was changed`);
		devCopyStylesFront();
		devCopyStylesBack();
		Reload();
	});
	watch(scriptsFiles, {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path) {
		console.log(`File ${path} was changed`);
		devWebpackScripts();
		Reload();
	});
	watch(fontsFiles.concat(imagesFiles), {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path) {
		console.log(`File ${path} was changed`);
		devCopyImages();
		devCopyFonts();
		Reload();
	});
	watch(themeFiles, {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path) {
		console.log(`File ${path} was changed`);
		copyModifiedThemeFile(path);
		devCopyStylesFront();
		devCopyStylesBack();
		Reload();
	});
	watch(pluginsFiles, {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path) {
		console.log(`File ${path} was changed`);
		devCopyPlugins();
		Reload();
	});
	watch(acfJsonFiles, {
		interval: 1000,
		usePolling: true,
	}).on('change', function (path) {
		console.log(`File ${path} was changed`);
		devCopyAcfJson();
	});
	watch(
		[
			'./patternlab/source/_patterns/**/*.twig',
			'./patternlab/source/_patterns/**/*.json',
			'./patternlab/source/_data/**/*.json',
		],
		{
			interval: 1000,
			usePolling: true,
		}
	).on('change', function (path) {
		console.log(`File ${path} was changed`);
		devCopyPatterns();
		devCopyPatternsTemplates();
		devCopyJson();
		Reload();
	});
}

const dev = series(
	registerCleanup,
	devCopyTheme,
	devCopyImages,
	devCopyFonts,
	devCopyStylesFront,
	devCopyStylesBack,
	devWebpackScripts,
	devCopyPlugins,
	devCopyLanguages,
	devCopyAcfJson,
	devCopyPatterns,
	devCopyJson,
	devCopyPatternsTemplates,
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
	prodCopyTheme,
	prodCopyFonts,
	prodCopyImages,
	prodCopyStylesFront,
	prodCopyStylesBack,
	prodWebpackScripts,
	prodCopyPlugins,
	prodCopyLanguages,
	prodCopyAcfJson,
	prodCopyPatterns,
	prodCopyJson,
	prodCopyPatternsTemplates,
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
