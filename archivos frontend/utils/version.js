/**
 * Utilidad para obtener la versión de la aplicación
 */

// Importar la versión desde package.json
import packageInfo from '../../package.json'

/**
 * Obtiene la versión actual de la aplicación
 * @returns {string} La versión de la aplicación
 */
export const getAppVersion = () => {
  return packageInfo.version
}

/**
 * Obtiene información completa de la versión
 * @returns {object} Objeto con información de la versión
 */
export const getVersionInfo = () => {
  return {
    version: packageInfo.version,
    name: packageInfo.name,
    buildTime: new Date().toISOString().split('T')[0] // Fecha de build
  }
}

/**
 * Obtiene la versión formateada para mostrar
 * @returns {string} Versión formateada (ej: "v1.0.1")
 */
export const getFormattedVersion = () => {
  return `v${packageInfo.version}`
}