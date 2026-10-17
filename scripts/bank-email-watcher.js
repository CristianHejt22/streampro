/**
 * =========================================================================
 * MICROSERVICIO NODE.JS: LECTOR IMAP DE TRANSFERENCIAS BANCARIAS
 * =========================================================================
 * Para ejecutar en un servidor VPS o en segundo plano conectándose por IMAP
 * a cualquier cuenta de Gmail, Outlook, Hostinger o cPanel.
 *
 * Requisitos:
 * npm install imap-simple mailparser
 * Ejecución:
 * node scripts/bank-email-watcher.js
 */

const fs = require('fs')
const path = require('path')

// Configuración de Conexión IMAP
const IMAP_CONFIG = {
  imap: {
    user: process.env.BANK_EMAIL_USER || 'tu-correo-banco@gmail.com',
    password: process.env.BANK_EMAIL_PASSWORD || 'tu-contrasena-de-aplicacion',
    host: process.env.BANK_EMAIL_HOST || 'imap.gmail.com',
    port: 993,
    tls: true,
    authTimeout: 10000,
    tlsOptions: { rejectUnauthorized: false }
  }
}

console.log('📡 [StreamPro IMAP Watcher] Iniciando monitor de correos bancarios...')
console.log('💡 Utiliza contraseñas de aplicación si usas Gmail o Outlook con 2FA.')
