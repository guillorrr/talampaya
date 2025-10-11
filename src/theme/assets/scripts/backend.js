/**
 * Script principal para el panel de administración
 *
 * Este archivo importa y inicializa todos los módulos JavaScript
 * necesarios para el funcionamiento del panel de administración.
 */

// Importar módulos
import geolocationTest from './modules/geolocation';

// Inicializar módulos cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', () => {
	// Inicializar la funcionalidad de geolocalización
	geolocationTest.init();

	console.log('Backend scripts inicializados');
});
