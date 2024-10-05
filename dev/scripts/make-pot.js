require('dotenv').config();
const { exec } = require('child_process');
const fs = require('fs');
const path = require('path');

const themeName = process.env.APP_NAME;

if (!themeName) {
	console.error("No se ha encontrado 'THEME_NAME' en el archivo .env.");
	process.exit(1);
}

console.log(`Generando archivo .pot para el tema: ${themeName}`);

const command = `docker exec ${themeName}-wp wp i18n make-pot ./wp-content/themes/${themeName} ./wp-content/themes/${themeName}/languages/${themeName}.pot --allow-root`;

exec(command, (error, stdout, stderr) => {
	if (error) {
		console.error(`Error: ${error.message}`);
		return;
	}
	if (stderr) {
		console.error(`stderr: ${stderr}`);
		//return;
	}
	console.log(`stdout: ${stdout}`);

	const sourcePath = path.join(
		__dirname,
		`../../build/wp-content/themes/${themeName}/languages/${themeName}.pot`
	);
	const destPath = path.join(__dirname, `../../src/theme/assets/languages/talampaya.pot`);

	fs.copyFile(sourcePath, destPath, err => {
		if (err) {
			console.error(`Error al copiar el archivo: ${err.message}`);
			return;
		}
		console.log(`Archivo .pot copiado a: ${destPath}`);
	});
});
