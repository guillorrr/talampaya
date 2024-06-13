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
	const fileContent = fs.readFileSync(filePath, 'utf8');
	const newFileName = file.replace(new RegExp(sanitizedTemplateBlock, 'g'), sanitizedBlockName);
	const newFilePath = path.join(newBlockDir, newFileName);

	// Reemplazos específicos según la extensión del archivo
	let newFileContent;
	if (['.css', '.json', '.twig'].some(ext => file.endsWith(ext))) {
		newFileContent = fileContent.replace(
			new RegExp(sanitizedTemplateBlock, 'g'),
			toKebabCase(sanitizedBlockName)
		);
	} else if (file.endsWith('.php')) {
		newFileContent = fileContent.replace(
			new RegExp(sanitizedTemplateBlock, 'g'),
			toSnakeCase(sanitizedBlockName)
		);
	} else {
		newFileContent = fileContent.replace(
			new RegExp(sanitizedTemplateBlock, 'g'),
			sanitizedBlockName
		);
	}

	fs.writeFileSync(newFilePath, newFileContent);
});

console.log(`Bloque '${sanitizedBlockName}' creado exitosamente.`);
