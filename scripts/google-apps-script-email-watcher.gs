/**
 * =========================================================================
 * GOOGLE APPS SCRIPT: LECTOR AUTOMÁTICO DE TRANSFERENCIAS POR CORREO
 * (Estilo Wanderlust Web Design para StreamPro)
 * =========================================================================
 *
 * ¿QUÉ HACE ESTE SCRIPT?
 * 1. Monitorea tu bandeja de Gmail cada 2 minutos en busca de correos de:
 *    - Mercado Pago (notificaciones@mercadopago.com.ar)
 *    - Banco Galicia (galicia_avisa@bancogalicia.com.ar)
 *    - Banco Santander (santander_avisa@santander.com.ar)
 *    - BBVA, Brubank, Ualá, Cuenta DNI, Banco Nación, Lemon Cash
 * 2. Extrae el Monto ($), Nombre del Pagador y N° de Operación.
 * 3. Envía los datos directamente a tu panel de StreamPro mediante Webhook
 *    o los guarda automáticamente para la conciliación.
 * 4. Marca el correo con la etiqueta "StreamPro/Procesado" para evitar duplicados.
 *
 * =========================================================================
 * INSTRUCCIONES DE INSTALACIÓN PASO A PASO:
 * =========================================================================
 * 1. Ingresa en tu navegador a: https://script.google.com con la cuenta de Gmail
 *    donde recibes los avisos bancarios o de Mercado Pago.
 * 2. Haz clic en "+ Nuevo Proyecto".
 * 3. Borra todo el código que aparezca y pega este archivo completo.
 * 4. Edita la variable 'STREAMPRO_WEBHOOK_URL' con la URL de tu API o Webhook.
 * 5. Haz clic en el ícono de reloj (Disparadores / Triggers) a la izquierda.
 * 6. Añade un disparador:
 *    - Función: 'checkIncomingBankEmails'
 *    - Origen del evento: 'Basado en tiempo'
 *    - Tipo de temporizador: 'Temporizador de minutos' -> 'Cada 2 minutos'.
 * 7. Guarda y concede los permisos de lectura de correo de Google. ¡Listo!
 */

// CONFIGURACIÓN PRINCIPAL
var STREAMPRO_WEBHOOK_URL = 'https://streamepro.me/api/webhook/bank-transfers'; // Tu Webhook o endpoint
var MAX_EMAILS_PER_RUN = 10;
var LABEL_PROCESSED = 'StreamPro/Procesado';

function checkIncomingBankEmails() {
  var processedLabel = getOrCreateLabel(LABEL_PROCESSED);
  
  // Buscar correos de transferencias NO leídos o NO procesados en las últimas 2 horas
  var query = 'newer_than:2h -label:' + LABEL_PROCESSED + ' (' +
    'from:mercadopago.com.ar OR from:mercadolibre.com OR ' +
    'from:bancogalicia.com.ar OR from:santander.com.ar OR ' +
    'from:bbva.com.ar OR from:brubank.com OR from:uala.com.ar OR ' +
    'subject:"Te transfirieron" OR subject:"Recibiste una transferencia" OR subject:"Acreditación de transferencia"' +
  ')';

  var threads = GmailApp.search(query, 0, MAX_EMAILS_PER_RUN);
  Logger.log('Hilos encontrados para revisar: ' + threads.length);

  for (var i = 0; i < threads.length; i++) {
    var messages = threads[i].getMessages();
    for (var j = 0; j < messages.length; j++) {
      var msg = messages[j];
      var subject = msg.getSubject();
      var body = msg.getPlainBody() || msg.getBody();
      var sender = msg.getFrom();
      var date = msg.getDate();

      var parsedData = parseBankTransferEmail(subject, body, sender, date);
      
      if (parsedData && parsedData.amount) {
        Logger.log('Transferencia detectada: $' + parsedData.amount + ' de ' + parsedData.senderName);
        sendTransferToStreamPro(parsedData);
      }
    }
    // Marcar como procesado para no volver a leerlo
    threads[i].addLabel(processedLabel);
  }
}

function parseBankTransferEmail(subject, body, fromHeader, date) {
  var combined = subject + '\n' + body;
  var amount = null;
  var senderName = 'Desconocido';
  var operationId = '';
  var bankName = 'Transferencia Bancaria';

  // Detección de Banco
  var lower = (fromHeader + ' ' + subject).toLowerCase();
  if (lower.indexOf('mercadopago') !== -1 || lower.indexOf('mercado pago') !== -1) {
    bankName = 'Mercado Pago';
  } else if (lower.indexOf('galicia') !== -1) {
    bankName = 'Banco Galicia';
  } else if (lower.indexOf('santander') !== -1) {
    bankName = 'Banco Santander';
  } else if (lower.indexOf('brubank') !== -1) {
    bankName = 'Brubank';
  } else if (lower.indexOf('bbva') !== -1) {
    bankName = 'BBVA';
  } else if (lower.indexOf('uala') !== -1) {
    bankName = 'Ualá';
  }

  // Regex de Monto ($ X.XXX,XX o $XXXX)
  var amountMatch = combined.match(/(?:transfirieron|recibiste|enviaron|ingresaron|importe|monto)[\s:]*\$?\s*([0-9]{1,3}(?:\.[0-9]{3})*(?:,[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)/i);
  if (amountMatch) {
    var rawAmt = amountMatch[1].replace(/\./g, '').replace(',', '.');
    amount = parseFloat(rawAmt);
  }

  // Regex de Remitente
  var senderMatch = combined.match(/([A-Za-zÁ-ÿ\s]{3,35})\s+(?:te transfiri[oó]|te envi[oó]|te acredit[oó])/i) ||
                    combined.match(/(?:De|Ordenante|Remitente|De parte de|Titular)[\s:]+([A-Za-zÁ-ÿ\s]{3,35})/i);
  if (senderMatch && senderMatch[1]) {
    senderName = senderMatch[1].trim();
  }

  // Regex de Número de Operación
  var opMatch = combined.match(/(?:Operaci[oó]n|Comprobante|Transferencia|Referencia|Id)[\s#n°:]*([0-9A-Za-z]{6,16})/i);
  if (opMatch) {
    operationId = opMatch[1];
  }

  if (!amount) return null;

  return {
    bankName: bankName,
    amount: amount,
    senderName: senderName,
    operationId: operationId || ('GAS-' + Math.floor(Math.random() * 899999 + 100000)),
    receivedAt: date ? date.toISOString() : new Date().toISOString(),
    rawSubject: subject
  };
}

function sendTransferToStreamPro(data) {
  try {
    var payload = JSON.stringify(data);
    var options = {
      method: 'post',
      contentType: 'application/json',
      payload: payload,
      muteHttpExceptions: true
    };
    var response = UrlFetchApp.fetch(STREAMPRO_WEBHOOK_URL, options);
    Logger.log('Respuesta del servidor StreamPro: ' + response.getResponseCode());
  } catch (err) {
    Logger.log('Error enviando transferencia a StreamPro: ' + err.toString());
  }
}

function getOrCreateLabel(name) {
  var label = GmailApp.getUserLabelByName(name);
  if (!label) {
    label = GmailApp.createLabel(name);
  }
  return label;
}
