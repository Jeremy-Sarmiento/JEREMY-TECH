var monedaActual = "PEN";
var tasasDeCambio = null;

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

function convertirPrecio(precioPEN, moneda) {
  if (moneda === "PEN") return precioPEN;
  if (!tasasDeCambio || !tasasDeCambio[moneda]) return precioPEN;
  return Number((precioPEN * tasasDeCambio[moneda]).toFixed(2));
}

function actualizarPreciosEnTarjetas() {
  document.querySelectorAll(".price").forEach(function (el) {
    var precioPEN = parseFloat(el.getAttribute("data-precio"));
    if (isNaN(precioPEN)) return;
    var precio = convertirPrecio(precioPEN, monedaActual);
    var simbolo = MONEDAS[monedaActual] ? MONEDAS[monedaActual].simbolo : monedaActual;
    el.textContent = simbolo + " " + precio.toLocaleString();
  });
}

function initDropdownMoneda() {
  var btn = document.getElementById("monedaBtn");
  var dropdown = document.getElementById("monedaDropdown");
  if (!btn || !dropdown) return;

  btn.addEventListener("click", function (e) {
    e.stopPropagation();
    dropdown.classList.toggle("open");
  });

  document.addEventListener("click", function () {
    dropdown.classList.remove("open");
  });

  dropdown.addEventListener("click", function (e) {
    e.stopPropagation();
  });

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

document.addEventListener("DOMContentLoaded", function () {
  initDropdownMoneda();
  cargarTasas().then(function (ok) {
    if (ok && monedaActual !== "PEN") {
      actualizarPreciosEnTarjetas();
    }
  });
});
