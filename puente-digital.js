/**
 * @fileoverview Módulo Puente Digital - Conversor Multidivisa en Tiempo Real
 * @description Gestiona la obtención de tasas de cambio internacionales vía API,
 * la conversión dinámica de precios (PEN a USD, EUR, etc.) y la actualización
 * sincronizada en el catálogo de productos y carrito de compras.
 * @author Jeremy Tech
 * @version 1.0.0
 */

/**
 * Código ISO de la moneda seleccionada actualmente por el usuario.
 * @type {string}
 * @default "PEN"
 */
var monedaActual = "PEN";

/**
 * Objeto con las tasas de cambio relativas a PEN obtenidas desde la API externa.
 * @type {Object<string, number>|null}
 */
var tasasDeCambio = null;

/**
 * Diccionario de monedas soportadas por la plataforma con sus metadatos.
 * @type {Object<string, {simbolo: string, nombre: string, flag: string}>}
 */
var MONEDAS = {
  PEN: { simbolo: "PEN", nombre: "Sol peruano", flag: "\uD83C\uDDF5\uD83C\uDDEA" },
  USD: { simbolo: "USD", nombre: "Dolar", flag: "\uD83C\uDDFA\uD83C\uDDF8" },
  EUR: { simbolo: "EUR", nombre: "Euro", flag: "\uD83C\uDDEA\uD83C\uDDFA" },
  GBP: { simbolo: "GBP", nombre: "Libra", flag: "\uD83C\uDDEC\uD83C\uDDE7" },
  BRL: { simbolo: "BRL", nombre: "Real", flag: "\uD83C\uDDE7\uD83C\uDDF7" },
  ARS: { simbolo: "ARS", nombre: "Peso argentino", flag: "\uD83C\uDDE6\uD83C\uDDF7" },
  CLP: { simbolo: "CLP", nombre: "Peso chileno", flag: "\uD83C\uDDE8\uD83C\uDDF1" },
  COP: { simbolo: "COP", nombre: "Peso colombiano", flag: "\uD83C\uDDE8\uD83C\uDDF4" },
};

/**
 * Consulta la API pública Open Exchange Rates (base PEN) para obtener tasas actualizadas.
 * @async
 * @function cargarTasas
 * @returns {Promise<boolean>} Retorna true si la carga fue exitosa, false en caso de error.
 */
async function cargarTasas() {
  try {
    var respuesta = await fetch("https://open.er-api.com/v6/latest/PEN");
    if (!respuesta.ok) throw new Error("API error: " + respuesta.status);
    var datos = await respuesta.json();
    if (datos.result !== "success") throw new Error("Invalid rates");
    tasasDeCambio = datos.rates;
    return true;
  } catch (error) {
    console.error("Error cargando tasas:", error);
    return false;
  }
}

/**
 * Convierte un monto en Soles (PEN) a la divisa solicitada según las tasas de cambio.
 * @function convertirPrecio
 * @param {number} precioPEN - Monto original en Soles Peruanos.
 * @param {string} moneda - Código ISO de la divisa destino (ej. 'USD', 'EUR').
 * @returns {number} Monto convertido con 2 decimales de precisión, o el precio base si no hay tasa.
 */
function convertirPrecio(precioPEN, moneda) {
  if (moneda === "PEN") return precioPEN;
  if (!tasasDeCambio || !tasasDeCambio[moneda]) return precioPEN;
  return Number((precioPEN * tasasDeCambio[moneda]).toFixed(2));
}

/**
 * Recorre todas las etiquetas de precio en el DOM (`.price[data-precio]`)
 * y actualiza su texto visible según la moneda seleccionada actualmente.
 * También notifica al carrito de compras si la función global existe.
 * @function actualizarPreciosEnTarjetas
 * @returns {void}
 */
function actualizarPreciosEnTarjetas() {
  document.querySelectorAll(".price").forEach(function (el) {
    var precioPEN = parseFloat(el.getAttribute("data-precio"));
    if (isNaN(precioPEN)) return;
    var precio = convertirPrecio(precioPEN, monedaActual);
    var simbolo = MONEDAS[monedaActual] ? MONEDAS[monedaActual].simbolo : monedaActual;
    el.textContent = simbolo + " " + precio.toLocaleString();
  });
  if (typeof window.actualizarPreciosEnCarrito === "function") {
    window.actualizarPreciosEnCarrito();
  }
}

/**
 * Inicializa los controladores de eventos para el menú desplegable de selección de moneda.
 * Permite abrir/cerrar el dropdown, seleccionar divisas y disparar la re-conversión de precios.
 * @function initDropdownMoneda
 * @returns {void}
 */
function initDropdownMoneda() {
  var btn = document.getElementById("monedaBtn");
  var dropdown = document.getElementById("monedaDropdown");
  if (!btn || !dropdown) return;

  // Alternar apertura/cierre del dropdown al hacer clic en el botón
  btn.addEventListener("click", function (e) {
    e.stopPropagation();
    dropdown.classList.toggle("open");
  });

  // Cerrar el dropdown si se hace clic fuera de él
  document.addEventListener("click", function () {
    dropdown.classList.remove("open");
  });

  // Evitar cierre accidental al hacer clic dentro del contenedor
  dropdown.addEventListener("click", function (e) {
    e.stopPropagation();
  });

  // Escuchar selección de cada opción de divisa
  var opciones = dropdown.querySelectorAll(".moneda-option");
  opciones.forEach(function (opt) {
    opt.addEventListener("click", function () {
      var nuevaMoneda = this.getAttribute("data-moneda");
      monedaActual = nuevaMoneda;

      opciones.forEach(function (o) { o.classList.remove("active"); });
      this.classList.add("active");

      var info = MONEDAS[nuevaMoneda];
      btn.textContent = info.flag + " " + info.simbolo;

      actualizarPreciosEnTarjetas();
      dropdown.classList.remove("open");
    });
  });
}

// Inicialización al cargar el árbol DOM
document.addEventListener("DOMContentLoaded", function () {
  initDropdownMoneda();
  cargarTasas().then(function (ok) {
    if (ok && monedaActual !== "PEN") {
      actualizarPreciosEnTarjetas();
    }
  });
});
