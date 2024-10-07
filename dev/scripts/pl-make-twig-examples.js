const fs = require('fs');
const path = require('path');

const atomic = process.argv[2] || 'molecules';
const design = process.argv[3] || 'example';

if (!atomic) {
	console.error('No se ha enviado el parametro para utilizar como tipo de contenido.');
	process.exit(1);
}

// Directorios
const imageDir = path.resolve(
	__dirname,
	`./../../patternlab/source/images/examples/${atomic}/${design}`
);
const outputDir = path.resolve(
	__dirname,
	`./../../patternlab/source/_patterns/${atomic}/${design}`
);
const atomsExampleDir = path.resolve(
	__dirname,
	'./../../patternlab/source/_patterns/atoms/example'
);

// Archivos de ejemplo
const exampleMdFile = path.join(atomsExampleDir, '_example.md');
const exampleTwigFile = path.join(atomsExampleDir, 'example.twig');

// Crear el directorio de salida si no existe
if (!fs.existsSync(outputDir)) {
	fs.mkdirSync(outputDir, { recursive: true });
}

// Comprobar y crear el directorio y archivos de ejemplo si no existen
if (!fs.existsSync(atomsExampleDir)) {
	fs.mkdirSync(atomsExampleDir, { recursive: true });
}

if (!fs.existsSync(exampleMdFile)) {
	fs.writeFileSync(exampleMdFile, '---\nhidden: true\n---', 'utf8');
	console.log(`Archivo creado: ${exampleMdFile}`);
}

if (!fs.existsSync(exampleTwigFile)) {
	fs.writeFileSync(
		exampleTwigFile,
		`<div class="example">\n    <img src="{{ image }}" alt="{{ alt }}">\n    <p>{{ description }}</p>\n</div>`,
		'utf8'
	);
	console.log(`Archivo creado: ${exampleTwigFile}`);
}

// Leer archivos del directorio de imágenes
fs.readdir(imageDir, (err, files) => {
	if (err) {
		console.error(`Error al leer el directorio de imágenes: ${err.message}`);
		return;
	}

	// Filtrar solo los archivos .png
	files
		.filter(file => file.endsWith('.png'))
		.forEach(file => {
			// Convertir el nombre del archivo a minúsculas y reemplazar mayúsculas por guiones
			const twigName = file
				.replace('.png', '')
				.replace(/([A-Z])/g, '-$1')
				.toLowerCase();
			const twigFile = path.join(outputDir, `${twigName}.twig`);

			// Contenido del archivo .twig
			const content = `{% include '@atoms/example/example.twig' with {
    image: '../../images/examples/${atomic}/${design}/${file}',
    alt: '',
    description: ''
} %}`;

			// Escribir el contenido en el archivo .twig
			fs.writeFile(twigFile, content, writeErr => {
				if (writeErr) {
					console.error(`Error al escribir el archivo ${twigFile}: ${writeErr.message}`);
				} else {
					console.log(`Archivo .twig generado: ${twigFile}`);
				}
			});
		});
});
