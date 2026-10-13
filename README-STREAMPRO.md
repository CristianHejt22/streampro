# StreamPro PWA - Arquitectura Base

Se ha generado la estructura inicial de **StreamPro PWA** usando Vue 3 (Composition API), Vite, Pinia y TailwindCSS, cumpliendo con la Especificación Técnica:

## Módulos Implementados

1.  **Tienda Inteligente (HomeView.vue):**
    *   Catálogo de productos con filtro por categoría.
    *   Motor de carrito (`src/store/cart.js`) con cálculo automático de descuentos progresivos por volumen.
2.  **Portal Privado del Cliente (CustomerPortal.vue):**
    *   Bóveda de Credenciales con ingreso de PIN simulado (Anti-scraping básico, interfaz oculta).
    *   Tarjetas de cuentas con funciones de revelar contraseña y botones para "Reportar Falla" / "Renovación".
3.  **CRM de Revendedor (ResellerDashboard.vue):**
    *   Tabla de control de clientes y vencimientos.
    *   Billetera Virtual.
    *   Estructura UI para el "Generador Gráfico de Flyers" (listo para conectar con `html2canvas`).

## Tecnologías y Seguridad

*   **PWA Integrado:** `vite-plugin-pwa` configurado.
*   **Seguridad:** En `src/main.js` se ha implementado el bloqueo nativo del click derecho y F12 (DevTools).
*   **Estilos:** `tailwind.config.js` configurado con *Dark Mode Premium*.

## Siguientes Pasos (Backend & BD)
Para avanzar al despliegue en producción real, el siguiente paso es conectar estas interfaces a Firebase o un servidor Node.js/Python, donde se ejecutará el Motor SLA y la lógica de los 4 estados de la transacción, para no exponer datos sensibles en el front-end.
