// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: public/assets/js/main.js
// DESCRIPCIÓN: Utilidades de interfaz, formateo monetario y helpers globales.
// =============================================================================

const SysVentas = {
    /**
     * Formatea un número a formato de moneda mexicana ($ 0,000.00)
     */
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(amount || 0);
    },

    /**
     * Realiza una petición asíncrona mediante Fetch API retornando JSON
     */
    fetchJson: async function(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                ...options
            });
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error("Error en petición SysVentas:", error);
            throw error;
        }
    }
};
