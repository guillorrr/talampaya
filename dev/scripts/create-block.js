const fs = require('fs');
const path = require('path');

// Función para sanitizar el nombre del bloque
function sanitizeName(name) {
	return name.replace(/[^a-zA-Z0-9-_]/g, '');
}

// Función para convertir cualquier formato a snake_case
function toSnakeCase(str) {
	return str
		.replace(/([a-z])([A-Z])/g, '$1_$2') // camelCase or PascalCase to snake_case
		.replace(/-/g, '_') // kebab-case to snake_case
		.toLowerCase();
}

// Función para convertir cualquier formato a kebab-case
function toKebabCase(str) {
	return str
		.replace(/([a-z])([A-Z])/g, '$1-$2') // camelCase or PascalCase to kebab-case
		.replace(/_/g, '-') // snake_case to kebab-case
		.toLowerCase();
}

// Función para convertir a camelCase con espacios en blanco
function toCamelCaseWithSpaces(str) {
	return str
		.replace(/[_-]/g, ' ') // Replace underscores and hyphens with spaces
		.replace(/\b\w/g, char => char.toUpperCase()) // Capitalize each word
		.replace(/\s+/g, ' '); // Remove extra spaces
}

const blockName = process.argv[2];
const templateBlock = process.argv[3] || 'example';

if (!blockName) {
	console.error('Por favor, proporciona un nombre para el nuevo bloque.');
	process.exit(1);
}

const sanitizedBlockName = sanitizeName(blockName);
const sanitizedTemplateBlock = sanitizeName(templateBlock);

const blocksDir = path.join(__dirname, '../../src/theme/blocks');
const newBlockDir = path.join(blocksDir, sanitizedBlockName);
const templateBlockDir =
	templateBlock === 'example'
		? path.join(__dirname, '../../dev/examples/blocks/example')
		: path.join(blocksDir, sanitizedTemplateBlock);

if (!fs.existsSync(templateBlockDir)) {
	console.error(`El bloque plantilla '${sanitizedTemplateBlock}' no existe.`);
	process.exit(1);
}

// Crea el nuevo directorio
fs.mkdirSync(newBlockDir, { recursive: true });

// Archivos base a copiar
const filesToCopy = fs.readdirSync(templateBlockDir);

filesToCopy.forEach(file => {
	const filePath = path.join(templateBlockDir, file);
	let fileContent = fs.readFileSync(filePath, 'utf8');
	const newFileName = file.replace(new RegExp(sanitizedTemplateBlock, 'g'), sanitizedBlockName);
	const newFilePath = path.join(newBlockDir, newFileName);

	// Reemplazos específicos según la extensión del archivo
	if (['.css', '.json'].some(ext => file.endsWith(ext))) {
		fileContent = fileContent.replace(
			new RegExp(sanitizedTemplateBlock, 'g'),
			toKebabCase(sanitizedBlockName)
		);
	} else if (file.endsWith('.php')) {
		fileContent = fileContent.replace(
			new RegExp(sanitizedTemplateBlock, 'g'),
			toSnakeCase(sanitizedBlockName)
		);
	} else if (file.endsWith('.twig')) {
		// Reemplazar solo la primera ocurrencia con kebab-case y las siguientes con snake_case
		let isFirst = true;
		fileContent = fileContent.replace(new RegExp(sanitizedTemplateBlock, 'g'), match => {
			if (isFirst) {
				isFirst = false;
				return toKebabCase(sanitizedBlockName);
			} else {
				return toSnakeCase(sanitizedBlockName);
			}
		});
	} else {
		fileContent = fileContent.replace(
			new RegExp(sanitizedTemplateBlock, 'g'),
			sanitizedBlockName
		);
	}

	// Si el archivo es JSON, reemplazar el campo "title"
	if (file.endsWith('.json')) {
		const jsonContent = JSON.parse(fileContent);
		if (jsonContent.title) {
			jsonContent.title = toCamelCaseWithSpaces(sanitizedBlockName);
			fileContent = JSON.stringify(jsonContent, null, 2);
		}
	}

	fs.writeFileSync(newFilePath, fileContent);
});

console.log(`Bloque '${sanitizedBlockName}' creado exitosamente.`);
