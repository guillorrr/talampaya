const fs = require('fs');
const path = require('path');

// Función para sanitizar el nombre del bloque
function sanitizeName(name) {
	return name.replace(/[^a-zA-Z0-9-_]/g, '');
}

// Funciones para diferentes casos
function toCamelCase(str) {
	return str.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
}

function toPascalCase(str) {
	const camelCase = toCamelCase(str);
	return camelCase.charAt(0).toUpperCase() + camelCase.slice(1);
}

function toUpperCase(str) {
	return str.toUpperCase();
}

function toLowerCase(str) {
	return str.toLowerCase();
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
		? path.join(__dirname, '../examples/blocks/example')
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

	// Reemplaza el nombre de la plantilla por el nuevo nombre del bloque en el contenido del archivo
	const newFileContent = fileContent
		.replace(new RegExp(sanitizedTemplateBlock, 'g'), sanitizedBlockName)
		.replace(
			new RegExp(toCamelCase(sanitizedTemplateBlock), 'g'),
			toCamelCase(sanitizedBlockName)
		)
		.replace(
			new RegExp(toPascalCase(sanitizedTemplateBlock), 'g'),
			toPascalCase(sanitizedBlockName)
		)
		.replace(
			new RegExp(toUpperCase(sanitizedTemplateBlock), 'g'),
			toUpperCase(sanitizedBlockName)
		)
		.replace(
			new RegExp(toLowerCase(sanitizedTemplateBlock), 'g'),
			toLowerCase(sanitizedBlockName)
		);

	fs.writeFileSync(newFilePath, newFileContent);
});

console.log(`Bloque '${sanitizedBlockName}' creado exitosamente.`);
