/**
 * Módulo para manejar la funcionalidad de prueba de geolocalización
 *
 */

const geolocationTest = {
	buttonElement: null,

	init() {
		this.buttonElement = document.getElementById('test-geolocation');
		if (!this.buttonElement) return;

		this.buttonElement.addEventListener('click', this.handleTestClick.bind(this));
	},

	handleTestClick() {
		const resultElement = document.getElementById('geolocation-test-result');
		if (!resultElement || !this.buttonElement) return;

		resultElement.innerHTML = '<p>Consultando API de geolocalización...</p>';

		const apiUrl = this.buttonElement.dataset.apiUrl;
		const nonce = this.buttonElement.dataset.nonce;

		fetch(apiUrl, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': nonce,
				'Content-Type': 'application/json',
			},
		})
			.then(response => {
				if (!response.ok) {
					throw new Error(`Error de red: ${response.status}`);
				}
				return response.json();
			})
			.then(response => {
				if (response.success) {
					this.renderSuccessResult(resultElement, response);
				} else {
					resultElement.innerHTML = `<p class="error">Error: ${response.message}</p>`;
				}
			})
			.catch(error => {
				resultElement.innerHTML = `<p class="error">Error: ${error.message || 'Error desconocido al contactar el API'}</p>`;
			});
	},

	/**
	 * Renderiza el resultado exitoso de la prueba
	 *
	 * @param {HTMLElement} resultElement - Elemento donde mostrar el resultado
	 * @param {Object} response - Respuesta de la API
	 */
	renderSuccessResult(resultElement, response) {
		let html = '<h4>Resultado exitoso</h4>';
		html += '<table class="widefat" style="margin-top: 10px;">';
		html += `<tr><th>IP</th><td>${response.ip}</td></tr>`;

		if (response.data.country) {
			html += `<tr><th>País</th><td>${response.data.country.name} (${response.data.country.code})</td></tr>`;
		}

		if (response.data.city && response.data.city !== 'Unknown') {
			html += `<tr><th>Ciudad</th><td>${response.data.city}</td></tr>`;
		}

		if (response.data.subdivision) {
			html += `<tr><th>Región</th><td>${response.data.subdivision.name}</td></tr>`;
		}

		if (response.data.location) {
			html += `<tr><th>Coordenadas</th><td>${response.data.location.latitude}, ${response.data.location.longitude}</td></tr>`;
			html += `<tr><th>Zona horaria</th><td>${response.data.location.timezone}</td></tr>`;
		}

		html += `<tr><th>Proveedor</th><td>${response.data.provider}</td></tr>`;

		if (response.data.error) {
			html += `<tr><th>Error</th><td>${response.data.error}</td></tr>`;
		}

		html += '</table>';
		resultElement.innerHTML = html;
	},
};

export default geolocationTest;
