/**
 * @fileoverview El Plano Maestro de la Memoria (DER) - Sesión 3: Negocios y Empresas Digitales (B2B, B2C, C2C)
 * @description Implementación del modelo relacional normalizado (hasta 3FN) para JEREMY TECH.
 * Simula una base de datos relacional de 5 tablas sobre LocalStorage con soporte para clientes
 * individuales (B2C) y corporativos (B2B con RUC), cálculo atómico de pedidos y registro de pagos.
 * @author Jeremy Tech — Jeremy Sarmiento
 * @version 3.0.0
 * 
 * Esquema Relacional:
 *   Cliente (1) ─────< (N) Pedido (1) ─────< (N) DetallePedido >───── (N) Producto
 *                           Pedido (1) ─────|| (1) Pago
 */

// Clave única de almacenamiento persistente para Jeremy Tech
const BD_CLAVE_JT = "jt_relational_db_v3";

/**
 * Esquema inicial vacío con correlativos independientes para autoincremento.
 */
const BD_TABLAS_VACIAS = {
  clientes: [],
  pedidos: [],
  detallePedido: [],
  pagos: [],
  _correlativos: { cliente: 0, pedido: 0, detalle: 0, pago: 0 },
};

/**
 * Lee el estado actual de la base de datos relacional desde LocalStorage.
 * @returns {typeof BD_TABLAS_VACIAS}
 */
function bdLeer() {
  try {
    if (typeof localStorage === "undefined") {
      return structuredClone(BD_TABLAS_VACIAS);
    }
    const guardado = localStorage.getItem(BD_CLAVE_JT);
    if (!guardado) return structuredClone(BD_TABLAS_VACIAS);
    return JSON.parse(guardado);
  } catch (e) {
    console.error("Error al leer la base de datos relacional:", e);
    return structuredClone(BD_TABLAS_VACIAS);
  }
}

/**
 * Guarda el estado actual de la base de datos relacional en LocalStorage.
 * @param {typeof BD_TABLAS_VACIAS} bd
 */
function bdGuardar(bd) {
  try {
    if (typeof localStorage !== "undefined") {
      localStorage.setItem(BD_CLAVE_JT, JSON.stringify(bd));
    }
  } catch (e) {
    console.error("Error al guardar la base de datos relacional:", e);
  }
}

/**
 * Reinicia la base de datos relacional eliminando todas las tuplas de prueba.
 */
function bdReiniciar() {
  try {
    if (typeof localStorage !== "undefined") {
      localStorage.removeItem(BD_CLAVE_JT);
    }
  } catch (e) {
    console.error("Error al reiniciar la base de datos:", e);
  }
}

/**
 * Inserta un nuevo cliente en la tabla `clientes`.
 * Soporta clientes finales (B2C) y empresas/instituciones (B2B).
 * 
 * @param {Object} datos
 * @param {string} datos.nombre - Nombre del cliente o Razón Social de la empresa.
 * @param {"B2C"|"B2B"} [datos.tipo="B2C"] - Tipo de cliente (B2C o B2B).
 * @param {string} [datos.empresa=""] - Nombre comercial de la empresa (B2B).
 * @param {string} [datos.ruc=""] - RUC de 11 dígitos (B2B).
 * @param {string} [datos.dni=""] - DNI de 8 dígitos (B2C).
 * @param {string} [datos.email=""] - Correo electrónico de contacto.
 * @param {string} [datos.telefono=""] - Teléfono o WhatsApp de contacto.
 * @param {string} [datos.direccion=""] - Dirección física o sede de entrega en La Joya.
 * @returns {Object} El registro de cliente creado con su clave primaria `id`.
 */
function crearCliente(datos) {
  const bd = bdLeer();
  const id = ++bd._correlativos.cliente;
  const tipo = datos.tipo === "B2B" ? "B2B" : "B2C";

  const cliente = {
    id,
    nombre: datos.nombre || (tipo === "B2B" ? "Empresa no especificada" : "Cliente Particular"),
    tipo,
    empresa: tipo === "B2B" ? (datos.empresa || datos.nombre || "") : "",
    ruc: tipo === "B2B" ? (datos.ruc || "") : "",
    dni: tipo === "B2C" ? (datos.dni || "") : "",
    email: datos.email || "",
    telefono: datos.telefono || "",
    direccion: datos.direccion || "Distrito de La Joya, Arequipa",
    fechaRegistro: new Date().toISOString(),
  };

  bd.clientes.push(cliente);
  bdGuardar(bd);
  return cliente;
}

/**
 * Registra una transacción completa de pedido de forma atómica:
 * 1. Inserta el registro en `pedidos` con FK hacia `clientes`.
 * 2. Desglosa los ítems en `detallePedido` con FK hacia `pedidos` y FK hacia `productos`.
 * 3. Registra el comprobante en `pagos` con FK hacia `pedidos`.
 * 
 * @param {number} clienteId - Clave foránea del cliente (FK -> clientes.id).
 * @param {Array<{productoId: string, cantidad: number, precioUnitario?: number}>} items - Lista de productos y cantidades.
 * @param {Array<Object>} [catalogoProductos=[]] - Catálogo maestro para obtener precios base (PEN).
 * @param {Object} [infoPago={}] - Metadatos opcionales del pago (método, estado, comprobante).
 * @returns {{pedido: Object, detalle: Array<Object>, pago: Object}}
 */
function crearPedido(clienteId, items, catalogoProductos = [], infoPago = {}) {
  const bd = bdLeer();

  // Validar cliente existente
  const clienteExiste = bd.clientes.some((c) => c.id === clienteId);
  if (!clienteExiste && clienteId !== 0) {
    console.warn(`Cliente con ID ${clienteId} no encontrado en la BD. Se procesará la orden.`);
  }

  // Desglose de Detalle de Pedido
  const detalle = items.map((item) => {
    let precioUnitario = item.precioUnitario;
    if (precioUnitario === undefined || precioUnitario === null) {
      const prod = (catalogoProductos || []).find((p) => p.id === item.productoId);
      precioUnitario = prod ? (prod.precioPEN || prod.precioReferencialPEN || 0) : 0;
    }
    const subtotal = Number((precioUnitario * item.cantidad).toFixed(2));

    return {
      id: ++bd._correlativos.detalle,
      pedidoId: null, // Asignado tras generar el pedido
      productoId: item.productoId,
      cantidad: Math.max(1, parseInt(item.cantidad, 10) || 1),
      precioUnitario: Number(precioUnitario),
      subtotal,
    };
  });

  const total = Number(detalle.reduce((acc, d) => acc + d.subtotal, 0).toFixed(2));

  // Inserción en la tabla `pedidos`
  const pedido = {
    id: ++bd._correlativos.pedido,
    codigoSeguimiento: `#JT-${String(bd._correlativos.pedido).padStart(5, "0")}`,
    clienteId,
    fecha: new Date().toISOString(),
    tipoComprobante: infoPago.tipoComprobante || (infoPago.ruc ? "Factura" : "Boleta"),
    tipoEntrega: infoPago.tipoEntrega || "Envío a Domicilio (La Joya)",
    estado: infoPago.estadoPedido || "Pendiente", // "Pendiente" | "En Preparación" | "En Camino" | "Entregado"
    total,
  };

  // Vincular FK pedidoId en cada detalle
  detalle.forEach((d) => (d.pedidoId = pedido.id));

  // Inserción en la tabla `pagos`
  const pago = {
    id: ++bd._correlativos.pago,
    pedidoId: pedido.id,
    metodo: infoPago.metodo || "Por Confirmar (WhatsApp / Pasarela)",
    monto: total,
    estado: infoPago.estadoPago || "Pendiente", // "Pendiente" | "Pagado" (Sesión 7 conectará Webhook)
    codigoOperacion: infoPago.codigoOperacion || null,
    fecha: infoPago.estadoPago === "Pagado" ? new Date().toISOString() : null,
  };

  bd.pedidos.push(pedido);
  bd.detallePedido.push(...detalle);
  bd.pagos.push(pago);
  bdGuardar(bd);

  return { pedido, detalle, pago };
}

/**
 * Actualiza el estado de un pago (ej. al recibir confirmación por WhatsApp o Webhook en Sesión 7).
 * @param {number} pagoId - Identificador del pago.
 * @param {"Pendiente"|"Pagado"|"Rechazado"} nuevoEstado - Nuevo estado del pago.
 * @param {string} [metodo] - Método de pago aplicado.
 * @param {string} [codigoOp] - Código de operación bancaria.
 * @returns {Object|null} Registro de pago actualizado o null si no se encuentra.
 */
function actualizarEstadoPago(pagoId, nuevoEstado, metodo, codigoOp) {
  const bd = bdLeer();
  const pago = bd.pagos.find((p) => p.id === pagoId);
  if (!pago) return null;

  pago.estado = nuevoEstado;
  if (metodo) pago.metodo = metodo;
  if (codigoOp) pago.codigoOperacion = codigoOp;
  if (nuevoEstado === "Pagado" && !pago.fecha) {
    pago.fecha = new Date().toISOString();
  }

  // Sincronizar estado del pedido correspondiente si se pagó
  const pedido = bd.pedidos.find((p) => p.id === pago.pedidoId);
  if (pedido && nuevoEstado === "Pagado" && pedido.estado === "Pendiente") {
    pedido.estado = "En Preparación";
  }

  bdGuardar(bd);
  return pago;
}

/**
 * Devuelve las 5 tablas del modelo relacional completas y estructuradas.
 * @param {Array<Object>} [catalogoProductos=[]]
 * @returns {{
 *   clientes: Array<Object>,
 *   productos: Array<Object>,
 *   pedidos: Array<Object>,
 *   detallePedido: Array<Object>,
 *   pagos: Array<Object>,
 *   estadisticas: { totalClientes: number, totalPedidos: number, volumenVentasPEN: number }
 * }}
 */
function obtenerTablas(catalogoProductos = []) {
  const bd = bdLeer();
  const volumenVentasPEN = bd.pedidos.reduce((acc, p) => acc + (p.total || 0), 0);

  return {
    clientes: bd.clientes,
    productos: catalogoProductos && catalogoProductos.length > 0
      ? catalogoProductos
      : (typeof productos !== "undefined" ? productos : []),
    pedidos: bd.pedidos,
    detallePedido: bd.detallePedido,
    pagos: bd.pagos,
    estadisticas: {
      totalClientes: bd.clientes.length,
      totalPedidos: bd.pedidos.length,
      volumenVentasPEN: Number(volumenVentasPEN.toFixed(2)),
    },
  };
}

// Objeto Global para navegador
if (typeof window !== "undefined") {
  window.JT_DB = {
    bdLeer,
    bdGuardar,
    bdReiniciar,
    crearCliente,
    crearPedido,
    actualizarEstadoPago,
    obtenerTablas,
    BD_CLAVE_JT,
  };
}

// Compatibilidad con Node.js (Pruebas unitarias)
if (typeof module !== "undefined" && module.exports) {
  module.exports = {
    bdLeer,
    bdGuardar,
    bdReiniciar,
    crearCliente,
    crearPedido,
    actualizarEstadoPago,
    obtenerTablas,
    BD_CLAVE_JT,
  };
}
