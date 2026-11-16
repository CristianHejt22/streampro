// Catálogo Oficial de Combos y Promociones StreamePro
// Modelo Económico Oficial de 3 Niveles:
// 1. providerCost: (PRECIO PROVEEDOR)
// 2. resellerCost: (PRECIO CON GANANCIA PROPIA = providerCost + platformProfit)
// 3. resellerProfit: (LO QUE GANA EL REVENDEDOR = promoPrice - resellerCost)
// 4. promoPrice: (PRECIO PROMO VENTA AL CLIENTE)
// 5. originalPrice: (PRECIO REAL DE UNIDAD EN TIENDA STREAMING SUMADO)
// 6. discountPercent: (DESCUENTO DEL 15 AL 35% Y ESPECIALES DEL 45%)

export const STORE_STREAMING_UNIT_PRICES = {
  "Netflix": {
    "name": "Netflix Premium Ultra HD 4K",
    "unitPrice": 8500,
    "cost": 4300
  },
  "Netflix Perfil Extra": {
    "name": "Netflix Perfil Extra (Sin Bloqueo)",
    "unitPrice": 6500,
    "cost": 4500
  },
  "Disney+": {
    "name": "Disney+ Premium con ESPN",
    "unitPrice": 7200,
    "cost": 2000
  },
  "Prime Video": {
    "name": "Amazon Prime Video 4K",
    "unitPrice": 4200,
    "cost": 1500
  },
  "Max": {
    "name": "Max Platino Estándar",
    "unitPrice": 6000,
    "cost": 2000
  },
  "Spotify": {
    "name": "Spotify Premium Individual",
    "unitPrice": 3800,
    "cost": 1500
  },
  "ChatGPT Plus": {
    "name": "ChatGPT Plus IA",
    "unitPrice": 6500,
    "cost": 3000
  },
  "IPTV": {
    "name": "IPTV MagisTV Pro",
    "unitPrice": 5500,
    "cost": 2000
  },
  "YouTube": {
    "name": "YouTube Premium & Music",
    "unitPrice": 4400,
    "cost": 1500
  },
  "YouTube Premium": {
    "name": "YouTube Premium & Music",
    "unitPrice": 4400,
    "cost": 1500
  },
  "Canva Pro": {
    "name": "Canva Pro Activación",
    "unitPrice": 4500,
    "cost": 1200
  },
  "CapCut Pro": {
    "name": "CapCut Pro Edición",
    "unitPrice": 3500,
    "cost": 1200
  },
  "Paramount+": {
    "name": "Paramount+ Premium",
    "unitPrice": 3800,
    "cost": 1500
  },
  "Crunchyroll": {
    "name": "Crunchyroll Mega Fan",
    "unitPrice": 3800,
    "cost": 1500
  },
  "ViX": {
    "name": "ViX Premium",
    "unitPrice": 3800,
    "cost": 1500
  },
  "Viki Rakuten": {
    "name": "Viki Rakuten Pass",
    "unitPrice": 3800,
    "cost": 1500
  },
  "DramaBox": {
    "name": "DramaBox VIP",
    "unitPrice": 3800,
    "cost": 1500
  },
  "FreeReels": {
    "name": "FreeReels VIP",
    "unitPrice": 3800,
    "cost": 1500
  },
  "NetShort": {
    "name": "NetShort VIP",
    "unitPrice": 3800,
    "cost": 1500
  },
  "ReelShort": {
    "name": "ReelShort VIP",
    "unitPrice": 3800,
    "cost": 1500
  },
  "Pornhub": {
    "name": "Pornhub Premium",
    "unitPrice": 4000,
    "cost": 1500
  },
  "Claro": {
    "name": "Claro Internet Ilimitado",
    "unitPrice": 4800,
    "cost": 2000
  },
  "Personal": {
    "name": "Personal Internet Ilimitado",
    "unitPrice": 5400,
    "cost": 2500
  }
}

export const OFFICIAL_COMBOS_CATALOG = [
  {
    "id": "C001",
    "sku": "C001",
    "title": "Promo Imperdible 1",
    "category": "streaming",
    "description": "Netflix Premium, Disney+ (7 ESPN), Prime Video · 1 Dispositivo · 30 Días",
    "badge": "🔥 45% OFF ESPECIAL",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Netflix",
        "planLabel": "Ultra HD 4K",
        "units": 1,
        "regularPrice": 8500,
        "cost": 4300
      },
      {
        "platform": "Disney+",
        "planLabel": "Premium con ESPN",
        "units": 1,
        "regularPrice": 7200,
        "cost": 2000
      },
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      }
    ],
    "logos": [
      "netflix.png",
      "disney_plus.png",
      "prime_video.png"
    ],
    "image": "/flyers/combos/promo-pantallas-total.png",
    "originalPrice": 19900,
    "promoPrice": 10950,
    "providerCost": 4300,
    "platformProfit": 4200,
    "resellerCost": 8500,
    "resellerProfit": 2450,
    "discountPercent": 45,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C002",
    "sku": "C002",
    "title": "Promo Imperdible 2",
    "category": "streaming",
    "description": "Disney+ 7 + ESPN, Netflix Extra · 1 Dispositivo · 30 Días",
    "badge": "🔥 35% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Disney+",
        "planLabel": "Premium con ESPN",
        "units": 1,
        "regularPrice": 6300,
        "cost": 2000
      },
      {
        "platform": "Netflix",
        "planLabel": "Perfil Extra",
        "units": 1,
        "regularPrice": 7700,
        "cost": 4300
      }
    ],
    "logos": [
      "disney_plus.png",
      "netflix.png"
    ],
    "image": "/flyers/combos/promo-pantallas-total.png",
    "originalPrice": 14000,
    "promoPrice": 9100,
    "providerCost": 4700,
    "platformProfit": 1800,
    "resellerCost": 6500,
    "resellerProfit": 2600,
    "discountPercent": 35,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C003",
    "sku": "C003",
    "title": "El Combo Más Poderoso",
    "category": "streaming",
    "description": "Netflix Premium, ChatGPT Plus, Prime Video · 1 Dispositivo · 30 Días",
    "badge": "🔥 45% OFF ESPECIAL",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Netflix",
        "planLabel": "Ultra HD 4K",
        "units": 1,
        "regularPrice": 7700,
        "cost": 4300
      },
      {
        "platform": "ChatGPT Plus",
        "planLabel": "IA Premium Plus",
        "units": 1,
        "regularPrice": 6500,
        "cost": 3000
      },
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      }
    ],
    "logos": [
      "netflix.png",
      "chatgpt.png",
      "prime_video.png"
    ],
    "image": "",
    "originalPrice": 18400,
    "promoPrice": 10100,
    "providerCost": 4000,
    "platformProfit": 1500,
    "resellerCost": 5500,
    "resellerProfit": 4600,
    "discountPercent": 45,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C004",
    "sku": "C004",
    "title": "Combo del Futuro",
    "category": "streaming",
    "description": "Max Standard, Disney Standard, ChatGPT Plus · 1 Dispositivo · 30 Días",
    "badge": "🔥 35% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      },
      {
        "platform": "Disney+",
        "planLabel": "Estándar",
        "units": 1,
        "regularPrice": 6300,
        "cost": 2000
      },
      {
        "platform": "ChatGPT Plus",
        "planLabel": "IA Premium Plus",
        "units": 1,
        "regularPrice": 6500,
        "cost": 3000
      }
    ],
    "logos": [
      "max.png",
      "disney_plus.png",
      "chatgpt.png"
    ],
    "image": "",
    "originalPrice": 18800,
    "promoPrice": 12200,
    "providerCost": 2200,
    "platformProfit": 800,
    "resellerCost": 3000,
    "resellerProfit": 9200,
    "discountPercent": 35,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C005",
    "sku": "C005",
    "title": "Promo Bajo el Mar",
    "category": "streaming",
    "description": "Disney+ Standard, Max Standard, Prime Video · 1 Dispositivo · 30 Días",
    "badge": "🔥 35% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Disney+",
        "planLabel": "Estándar",
        "units": 1,
        "regularPrice": 6300,
        "cost": 2000
      },
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      },
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      }
    ],
    "logos": [
      "disney_plus.png",
      "max.png",
      "prime_video.png"
    ],
    "image": "",
    "originalPrice": 16500,
    "promoPrice": 10750,
    "providerCost": 1300,
    "platformProfit": 550,
    "resellerCost": 1850,
    "resellerProfit": 8900,
    "discountPercent": 35,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C007",
    "sku": "C007",
    "title": "Combo para Adultos",
    "category": "streaming",
    "description": "Pornhub Premium, Crunchyroll Premium, Max Standard · 1 Dispositivo · 30 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Pornhub",
        "planLabel": "VIP Premium",
        "units": 1,
        "regularPrice": 4000,
        "cost": 1500
      },
      {
        "platform": "Crunchyroll",
        "planLabel": "Mega Fan",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      }
    ],
    "logos": [
      "pornhub.png",
      "crunchyroll.png",
      "max.png"
    ],
    "image": "",
    "originalPrice": 13800,
    "promoPrice": 9650,
    "providerCost": 1400,
    "platformProfit": 500,
    "resellerCost": 1900,
    "resellerProfit": 7750,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C008",
    "sku": "C008",
    "title": "Combo Épico",
    "category": "streaming",
    "description": "Prime Video, Netflix Extra, Max Standard, Internet Personal (7 días) · 1 Dispositivo · 30 Días",
    "badge": "🔥 45% OFF ESPECIAL",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      },
      {
        "platform": "Netflix",
        "planLabel": "Perfil Extra",
        "units": 1,
        "regularPrice": 7700,
        "cost": 4300
      },
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      },
      {
        "platform": "Personal",
        "planLabel": "Internet Ilimitado (7 días)",
        "units": 1,
        "regularPrice": 5400,
        "cost": 2500
      }
    ],
    "logos": [
      "prime_video.png",
      "netflix.png",
      "max.png",
      "personal.png"
    ],
    "image": "",
    "originalPrice": 23300,
    "promoPrice": 12800,
    "providerCost": 4300,
    "platformProfit": 1650,
    "resellerCost": 5950,
    "resellerProfit": 6850,
    "discountPercent": 45,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C009",
    "sku": "C009",
    "title": "Promo Nivel Super Saiyajin",
    "category": "streaming",
    "description": "Spotify Premium, Crunchyroll Premium, Prime Video · 1 Dispositivo · 30 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Spotify",
        "planLabel": "Individual Premium",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Crunchyroll",
        "planLabel": "Mega Fan",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      }
    ],
    "logos": [
      "spotify.png",
      "crunchyroll.png",
      "prime_video.png"
    ],
    "image": "",
    "originalPrice": 11800,
    "promoPrice": 8250,
    "providerCost": 2200,
    "platformProfit": 800,
    "resellerCost": 3000,
    "resellerProfit": 5250,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C010",
    "sku": "C010",
    "title": "Promo con Poder de Gemas",
    "category": "streaming",
    "description": "Paramount+ Premium, Max Standard, Crunchyroll Premium · 1 Dispositivo · 30 Días",
    "badge": "🔥 35% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Paramount+",
        "planLabel": "Premium Oficial",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      },
      {
        "platform": "Crunchyroll",
        "planLabel": "Mega Fan",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      }
    ],
    "logos": [
      "paramount_plus.png",
      "max.png",
      "crunchyroll.png"
    ],
    "image": "",
    "originalPrice": 13600,
    "promoPrice": 8850,
    "providerCost": 1600,
    "platformProfit": 650,
    "resellerCost": 2250,
    "resellerProfit": 6600,
    "discountPercent": 35,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C011",
    "sku": "C011",
    "title": "Aventura Matemática",
    "category": "streaming",
    "description": "Max Standard, Prime Video · 1 Dispositivo · 30 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      },
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      }
    ],
    "logos": [
      "max.png",
      "prime_video.png"
    ],
    "image": "",
    "originalPrice": 10200,
    "promoPrice": 7150,
    "providerCost": 900,
    "platformProfit": 350,
    "resellerCost": 1250,
    "resellerProfit": 5900,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C012",
    "sku": "C012",
    "title": "Especial Dramas y Series",
    "category": "streaming",
    "description": "DramaBox, FreeReels, NetShort, ReelShort · 5 Dispositivos · 60 Días",
    "badge": "🔥 45% OFF ESPECIAL",
    "durationDays": "60",
    "devices": "5",
    "services": [
      {
        "platform": "DramaBox",
        "planLabel": "VIP Short Series",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "FreeReels",
        "planLabel": "VIP Drama",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "NetShort",
        "planLabel": "Acceso VIP",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "ReelShort",
        "planLabel": "Pase VIP",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      }
    ],
    "logos": [
      "dramabox.png",
      "freereels.png",
      "netshort.png",
      "reelshort.png"
    ],
    "image": "",
    "originalPrice": 15200,
    "promoPrice": 8350,
    "providerCost": 4000,
    "platformProfit": 1500,
    "resellerCost": 5500,
    "resellerProfit": 2850,
    "discountPercent": 45,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C013",
    "sku": "C013",
    "title": "Combo Premium con Estilo",
    "category": "streaming",
    "description": "Spotify Premium, ChatGPT Plus, Canva Pro · 1 Dispositivo · 30 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Spotify",
        "planLabel": "Individual Premium",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "ChatGPT Plus",
        "planLabel": "IA Premium Plus",
        "units": 1,
        "regularPrice": 6500,
        "cost": 3000
      },
      {
        "platform": "Canva Pro",
        "planLabel": "Pro Activación",
        "units": 1,
        "regularPrice": 4500,
        "cost": 1200
      }
    ],
    "logos": [
      "spotify.png",
      "chatgpt.png",
      "canva.png"
    ],
    "image": "",
    "originalPrice": 14800,
    "promoPrice": 10350,
    "providerCost": 3200,
    "platformProfit": 1300,
    "resellerCost": 4500,
    "resellerProfit": 5850,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C014",
    "sku": "C014",
    "title": "Promo Streaming Intergaláctica",
    "category": "streaming",
    "description": "Disney+ 7 ESPN, Prime Video, Max Standard · 1 Dispositivo · 30 Días",
    "badge": "🔥 35% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Disney+",
        "planLabel": "Premium con ESPN",
        "units": 1,
        "regularPrice": 6300,
        "cost": 2000
      },
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      },
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      }
    ],
    "logos": [
      "disney_plus.png",
      "prime_video.png",
      "max.png"
    ],
    "image": "",
    "originalPrice": 16500,
    "promoPrice": 10750,
    "providerCost": 2600,
    "platformProfit": 950,
    "resellerCost": 3550,
    "resellerProfit": 7200,
    "discountPercent": 35,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C015",
    "sku": "C015",
    "title": "Combo Aventurero",
    "category": "streaming",
    "description": "Canva Pro (6 meses), CapCut Pro (1 mes) · 1 Dispositivo · 30 Días",
    "badge": "🔥 25% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Canva Pro",
        "planLabel": "Pro 6 Meses",
        "units": 1,
        "regularPrice": 4500,
        "cost": 1200
      },
      {
        "platform": "CapCut Pro",
        "planLabel": "Pro 1 Mes",
        "units": 1,
        "regularPrice": 3500,
        "cost": 1200
      }
    ],
    "logos": [
      "canva.png",
      "capcut.png"
    ],
    "image": "",
    "originalPrice": 8000,
    "promoPrice": 6000,
    "providerCost": 1900,
    "platformProfit": 750,
    "resellerCost": 2650,
    "resellerProfit": 3350,
    "discountPercent": 25,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C016",
    "sku": "C016",
    "title": "Combo Inteligente",
    "category": "streaming",
    "description": "ChatGPT Plus, Canva Pro, CapCut Pro · 1 Dispositivo · 30 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "ChatGPT Plus",
        "planLabel": "IA Premium Plus",
        "units": 1,
        "regularPrice": 6500,
        "cost": 3000
      },
      {
        "platform": "Canva Pro",
        "planLabel": "Pro Activación",
        "units": 1,
        "regularPrice": 4500,
        "cost": 1200
      },
      {
        "platform": "CapCut Pro",
        "planLabel": "Pro Edición",
        "units": 1,
        "regularPrice": 3500,
        "cost": 1200
      }
    ],
    "logos": [
      "chatgpt.png",
      "canva.png",
      "capcut.png"
    ],
    "image": "",
    "originalPrice": 14500,
    "promoPrice": 10150,
    "providerCost": 2500,
    "platformProfit": 1000,
    "resellerCost": 3500,
    "resellerProfit": 6650,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C017",
    "sku": "C017",
    "title": "Combo Anime",
    "category": "streaming",
    "description": "Crunchyroll Premium, Viki Rakuten, Max Standard · 1 Dispositivo · 30 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Crunchyroll",
        "planLabel": "Mega Fan",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Viki Rakuten",
        "planLabel": "Viki Pass Standard",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      }
    ],
    "logos": [
      "crunchyroll.png",
      "viki_rakuten.png",
      "max.png"
    ],
    "image": "",
    "originalPrice": 13600,
    "promoPrice": 9500,
    "providerCost": 1300,
    "platformProfit": 550,
    "resellerCost": 1850,
    "resellerProfit": 7650,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C018",
    "sku": "C018",
    "title": "Misión Agente Secreto",
    "category": "streaming",
    "description": "Disney+ Standard, Crunchyroll Premium, Paramount+ Premium, Prime Video · 1 Dispositivo · 30 Días",
    "badge": "🔥 35% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Disney+",
        "planLabel": "Estándar",
        "units": 1,
        "regularPrice": 6300,
        "cost": 2000
      },
      {
        "platform": "Crunchyroll",
        "planLabel": "Mega Fan",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Paramount+",
        "planLabel": "Premium Oficial",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      }
    ],
    "logos": [
      "disney_plus.png",
      "crunchyroll.png",
      "paramount_plus.png",
      "prime_video.png"
    ],
    "image": "",
    "originalPrice": 18100,
    "promoPrice": 11750,
    "providerCost": 2600,
    "platformProfit": 950,
    "resellerCost": 3550,
    "resellerProfit": 8200,
    "discountPercent": 35,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C019",
    "sku": "C019",
    "title": "Promo Interdimensional",
    "category": "streaming",
    "description": "Disney+ Standard, Spotify Individual · 1 Dispositivo · 30 Días",
    "badge": "🔥 25% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Disney+",
        "planLabel": "Estándar",
        "units": 1,
        "regularPrice": 6300,
        "cost": 2000
      },
      {
        "platform": "Spotify",
        "planLabel": "Individual Premium",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      }
    ],
    "logos": [
      "disney_plus.png",
      "spotify.png"
    ],
    "image": "",
    "originalPrice": 10100,
    "promoPrice": 7600,
    "providerCost": 2200,
    "platformProfit": 800,
    "resellerCost": 3000,
    "resellerProfit": 4600,
    "discountPercent": 25,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C020",
    "sku": "C020",
    "title": "Promo Brutal",
    "category": "streaming",
    "description": "Max Standard, ViX Premium, Crunchyroll Premium · 1 Dispositivo · 30 Días",
    "badge": "🔥 45% OFF ESPECIAL",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      },
      {
        "platform": "ViX",
        "planLabel": "Premium Oficial",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Crunchyroll",
        "planLabel": "Mega Fan",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      }
    ],
    "logos": [
      "max.png",
      "vix.png",
      "crunchyroll.png"
    ],
    "image": "",
    "originalPrice": 13600,
    "promoPrice": 7500,
    "providerCost": 1300,
    "platformProfit": 550,
    "resellerCost": 1850,
    "resellerProfit": 5650,
    "discountPercent": 45,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C021",
    "sku": "C021",
    "title": "Promo EDM",
    "category": "streaming",
    "description": "YouTube Premium (5 disp), Prime Video (1 disp) · 1 Dispositivo · 30 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "YouTube",
        "planLabel": "Premium 5 Dispositivos",
        "units": 1,
        "regularPrice": 4400,
        "cost": 1500
      },
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      }
    ],
    "logos": [
      "youtube.png",
      "prime_video.png"
    ],
    "image": "",
    "originalPrice": 8600,
    "promoPrice": 6000,
    "providerCost": 700,
    "platformProfit": 300,
    "resellerCost": 1000,
    "resellerProfit": 5000,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C022",
    "sku": "C022",
    "title": "Triple Promo",
    "category": "streaming",
    "description": "Crunchyroll Premium, Viki Rakuten, DramaBox (5 disp) · 1 Dispositivo · 30 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Crunchyroll",
        "planLabel": "Mega Fan",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Viki Rakuten",
        "planLabel": "Viki Pass Standard",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "DramaBox",
        "planLabel": "VIP Short Series",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      }
    ],
    "logos": [
      "crunchyroll.png",
      "viki_rakuten.png",
      "dramabox.png"
    ],
    "image": "",
    "originalPrice": 11400,
    "promoPrice": 8000,
    "providerCost": 1300,
    "platformProfit": 550,
    "resellerCost": 1850,
    "resellerProfit": 6150,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C023",
    "sku": "C023",
    "title": "Promo Dúo",
    "category": "streaming",
    "description": "Spotify Individual Premium, ChatGPT Plus · 1 Dispositivo · 30 Días",
    "badge": "🔥 25% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Spotify",
        "planLabel": "Individual Premium",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "ChatGPT Plus",
        "planLabel": "IA Premium Plus",
        "units": 1,
        "regularPrice": 6500,
        "cost": 3000
      }
    ],
    "logos": [
      "spotify.png",
      "chatgpt.png"
    ],
    "image": "",
    "originalPrice": 10300,
    "promoPrice": 7750,
    "providerCost": 2200,
    "platformProfit": 800,
    "resellerCost": 3000,
    "resellerProfit": 4750,
    "discountPercent": 25,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "C024",
    "sku": "C024",
    "title": "Promo Monstruosa",
    "category": "streaming",
    "description": "Netflix Extra, Prime Video, Max Standard · 1 Dispositivo · 30 Días",
    "badge": "🔥 45% OFF ESPECIAL",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Netflix",
        "planLabel": "Perfil Extra",
        "units": 1,
        "regularPrice": 7700,
        "cost": 4300
      },
      {
        "platform": "Prime Video",
        "planLabel": "1 Pantalla HD",
        "units": 1,
        "regularPrice": 4200,
        "cost": 1500
      },
      {
        "platform": "Max",
        "planLabel": "Estándar HD",
        "units": 1,
        "regularPrice": 6000,
        "cost": 2000
      }
    ],
    "logos": [
      "netflix.png",
      "prime_video.png",
      "max.png"
    ],
    "image": "",
    "originalPrice": 17900,
    "promoPrice": 9850,
    "providerCost": 4200,
    "platformProfit": 1650,
    "resellerCost": 5850,
    "resellerProfit": 4000,
    "discountPercent": 45,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "I001",
    "sku": "I001",
    "title": "Internet Ilimitado (2 Líneas)",
    "category": "internet",
    "description": "Claro Internet, Personal Internet · 1 Dispositivo · 30 Días",
    "badge": "🔥 25% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Claro",
        "planLabel": "Línea Claro SSH",
        "units": 1,
        "regularPrice": 4800,
        "cost": 2000
      },
      {
        "platform": "Personal",
        "planLabel": "Línea Personal SSH",
        "units": 1,
        "regularPrice": 5400,
        "cost": 2500
      }
    ],
    "logos": [
      "claro.png",
      "personal.png"
    ],
    "image": "",
    "originalPrice": 10200,
    "promoPrice": 7650,
    "providerCost": 3500,
    "platformProfit": 1350,
    "resellerCost": 4850,
    "resellerProfit": 2800,
    "discountPercent": 25,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "I002",
    "sku": "I002",
    "title": "Bender - Internet Ilimitado",
    "category": "internet",
    "description": "Línea Personal (Android) · 3 Dispositivos · 90 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "90",
    "devices": "3",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 3,
        "regularPrice": 14500,
        "cost": 2500
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 14500,
    "promoPrice": 10150,
    "providerCost": 5000,
    "platformProfit": 2000,
    "resellerCost": 7000,
    "resellerProfit": 3150,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "I003",
    "sku": "I003",
    "title": "Nibbler - Internet Ilimitado",
    "category": "internet",
    "description": "Línea Personal (Android) · 2 Dispositivos · 90 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "90",
    "devices": "2",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 2,
        "regularPrice": 13500,
        "cost": 2500
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 13500,
    "promoPrice": 9450,
    "providerCost": 4900,
    "platformProfit": 1950,
    "resellerCost": 6850,
    "resellerProfit": 2600,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "I004",
    "sku": "I004",
    "title": "Zapp - Internet Ilimitado",
    "category": "internet",
    "description": "Línea Personal (Android) · 1 Dispositivo · 90 Días",
    "badge": "🔥 25% OFF",
    "durationDays": "90",
    "devices": "1",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 1,
        "regularPrice": 8500,
        "cost": 2500
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 8500,
    "promoPrice": 6400,
    "providerCost": 2900,
    "platformProfit": 1100,
    "resellerCost": 4000,
    "resellerProfit": 2400,
    "discountPercent": 25,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "I005",
    "sku": "I005",
    "title": "Zoidberg - Internet Ilimitado",
    "category": "internet",
    "description": "Línea Personal (Android) · 3 Dispositivos · 60 Días",
    "badge": "🔥 30% OFF",
    "durationDays": "60",
    "devices": "3",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 3,
        "regularPrice": 13500,
        "cost": 2500
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 13500,
    "promoPrice": 9450,
    "providerCost": 4900,
    "platformProfit": 1950,
    "resellerCost": 6850,
    "resellerProfit": 2600,
    "discountPercent": 30,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "I006",
    "sku": "I006",
    "title": "Fry - Internet Ilimitado",
    "category": "internet",
    "description": "Línea Personal (Android) · 3 Dispositivos · 30 Días",
    "badge": "🔥 25% OFF",
    "durationDays": "30",
    "devices": "3",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 3,
        "regularPrice": 11500,
        "cost": 2500
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 11500,
    "promoPrice": 8650,
    "providerCost": 4300,
    "platformProfit": 1700,
    "resellerCost": 6000,
    "resellerProfit": 2650,
    "discountPercent": 25,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "I007",
    "sku": "I007",
    "title": "Profesor - Internet Ilimitado",
    "category": "internet",
    "description": "Línea Personal (Android) · 2 Dispositivos · 60 Días",
    "badge": "🔥 25% OFF",
    "durationDays": "60",
    "devices": "2",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 2,
        "regularPrice": 11000,
        "cost": 2500
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 11000,
    "promoPrice": 8250,
    "providerCost": 4200,
    "platformProfit": 1650,
    "resellerCost": 5850,
    "resellerProfit": 2400,
    "discountPercent": 25,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "I008",
    "sku": "I008",
    "title": "Conectate a Otro Nivel",
    "category": "internet",
    "description": "Línea Personal (Android) · 2 Dispositivos · 55 Días",
    "badge": "🔥 25% OFF",
    "durationDays": "55",
    "devices": "2",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 2,
        "regularPrice": 9800,
        "cost": 2500
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 9800,
    "promoPrice": 7350,
    "providerCost": 3600,
    "platformProfit": 1400,
    "resellerCost": 5000,
    "resellerProfit": 2350,
    "discountPercent": 25,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  },
  {
    "id": "P001",
    "sku": "P001",
    "title": "TV Digital Plus (Panel)",
    "category": "panel",
    "description": "Panel sin vencimiento, Demos 1 hora, 1 crédito=3 disp · 3 Dispositivos · Ilimitado",
    "badge": "🔥 45% OFF ESPECIAL",
    "durationDays": "Ilimitado",
    "devices": "3",
    "services": [
      {
        "platform": "Panel sin vencimiento",
        "planLabel": "Oficial",
        "units": 1,
        "regularPrice": 8000,
        "cost": 1500
      },
      {
        "platform": "Demos 1 hora",
        "planLabel": "Oficial",
        "units": 1,
        "regularPrice": 4000,
        "cost": 1500
      },
      {
        "platform": "1 crédito=3 disp",
        "planLabel": "Oficial",
        "units": 1,
        "regularPrice": 12000,
        "cost": 1500
      }
    ],
    "logos": [
      "panel_admin.png"
    ],
    "image": "/og-banner.jpg",
    "originalPrice": 24000,
    "promoPrice": 13200,
    "providerCost": 12000,
    "platformProfit": 6000,
    "resellerCost": 18000,
    "resellerProfit": -4800,
    "discountPercent": 45,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  }
]

/**
 * Extrae la cantidad de dispositivos de un combo a partir del texto o de su configuración manual.
 * Ejemplo: "Netflix Premium, Disney+ (7 ESPN), Prime Video · 1 Dispositivo · 30 Días" -> 1
 *          "Línea Personal (Android) · 2 Dispositivos · 90 Días" -> 2
 * Si fue configurado manualmente por el admin, respeta el valor manual.
 */
export function parseComboDevices(pkg) {
  if (!pkg) return 1

  // 1. Si se configuró manualmente de forma explícita
  if (pkg.isManualDevices || pkg.devicesManual) {
    const manualVal = parseInt(pkg.devices, 10)
    if (!isNaN(manualVal) && manualVal > 0) return manualVal
  }

  // 2. Extraer automáticamente si se menciona en descripción, título o notas:
  // Coincide con ej: "1 Dispositivo", "2 Dispositivos", "3 Disp", "5 Pantallas", etc.
  const text = `${pkg.title || ''} ${pkg.description || ''} ${pkg.notes || ''}`
  const match = text.match(/(\d+)\s*(?:dispositivos?|disp\.?|pantallas?)/i)
  if (match && match[1]) {
    const num = parseInt(match[1], 10)
    if (!isNaN(num) && num > 0) return num
  }

  // 3. Fallback al valor de pkg.devices si ya existe
  if (pkg.devices !== undefined && pkg.devices !== null && pkg.devices !== '') {
    const parsed = parseInt(pkg.devices, 10)
    if (!isNaN(parsed) && parsed > 0) return parsed
  }

  return 1
}

/**
 * Formatea la etiqueta de dispositivos de un combo
 * 1 -> "1 Dispositivo"
 * 2 -> "2 Dispositivos"
 */
export function formatComboDevicesLabel(devicesCount) {
  const count = Number(devicesCount) || 1
  return count === 1 ? '1 Dispositivo' : `${count} Dispositivos`
}

export function calculatePackageFromCatalog(pkg, pricingCatalog = {}) {
  if (!pkg) return pkg

  const services = (pkg.services || []).map(srv => {
    const rawPlat = (srv.platform || '').trim()
    const catalogKeys = Object.keys(pricingCatalog || {})
    const matchedKey = catalogKeys.find(k => k.toLowerCase() === rawPlat.toLowerCase()) || rawPlat
    const platData = pricingCatalog?.[matchedKey]

    let unitRegular = Number(srv.regularPrice || 0)
    let unitCost = Number(srv.cost || srv.providerCost || 0)

    if (platData && platData.plans) {
      let matchedPlan = null
      if (srv.planKey && platData.plans[srv.planKey]) {
        matchedPlan = platData.plans[srv.planKey]
      } else {
        const planEntries = Object.entries(platData.plans)
        if (srv.planLabel) {
          const sLabel = srv.planLabel.toLowerCase()
          matchedPlan = planEntries.find(([_, p]) => {
            const pLabel = (p.label || '').toLowerCase()
            return pLabel.includes(sLabel) || sLabel.includes(pLabel)
          })?.[1]
        }
        if (!matchedPlan && planEntries.length > 0) {
          matchedPlan = planEntries[0][1]
        }
      }

      if (matchedPlan) {
        if (Number(matchedPlan.clientPrice) > 0) {
          unitRegular = Number(matchedPlan.clientPrice)
        }
        if (Number(matchedPlan.providerCost) > 0) {
          unitCost = Number(matchedPlan.providerCost)
        }
      }
    } else if (STORE_STREAMING_UNIT_PRICES[rawPlat]) {
      const ref = STORE_STREAMING_UNIT_PRICES[rawPlat]
      unitRegular = unitRegular || ref.unitPrice
      unitCost = unitCost || ref.cost
    }

    const units = Number(srv.units || 1)
    return {
      ...srv,
      regularPrice: unitRegular,
      cost: unitCost,
      totalRegular: unitRegular * units,
      totalCost: unitCost * units
    }
  })

  // 1. PRECIO LISTA (ANTES): Suma de precios individuales de las cuentas de streaming
  const totalRegular = services.reduce((acc, s) => acc + (s.totalRegular || s.regularPrice || 0), 0)
  const originalPrice = totalRegular > 0 ? totalRegular : Number(pkg.originalPrice || 0)

  // Descuento ofrecido (-15% al -45%)
  const discountPercent = Number(
    pkg.discountPercent || (originalPrice > pkg.promoPrice && originalPrice > 0 ? Math.round(((originalPrice - pkg.promoPrice) / originalPrice) * 100) : 45)
  )

  // PRECIO PROMO VENTA: Redondeado a los 50 pesos
  const promoPrice = originalPrice > 0 
    ? (Math.round((originalPrice * (1 - discountPercent / 100)) / 50) * 50)
    : Number(pkg.promoPrice || 0)

  // 1. PROVEEDOR (Costo Base)
  const providerCost = Number(pkg.providerCost) > 0 ? Number(pkg.providerCost) : services.reduce((acc, s) => acc + (s.totalCost || s.cost || 0), 0)

  // MARGEN BRUTO PARA EQUILIBRIO
  const grossMargin = Math.max(0, promoPrice - providerCost)

  // 2. CON GANANCIA PROPIA (StreamePro) ~63% del margen para equilibrio justo (ej: $4,200 en $6,650)
  let platformProfit = Number(pkg.platformProfit || 0)
  if (!platformProfit || platformProfit === 0) {
    platformProfit = Math.round((grossMargin * 0.63) / 50) * 50 || Math.round(grossMargin * 0.6)
  }
  const resellerCost = providerCost + platformProfit

  // 3. GANA REVENDEDOR (promoPrice - resellerCost) (ej: $2,450 en $6,650)
  const resellerProfit = Math.max(0, promoPrice - resellerCost)

  // Ahorro
  const savingsAmount = Math.max(0, originalPrice - promoPrice)
  const savingsPercent = originalPrice > 0 ? Math.round((savingsAmount / originalPrice) * 100) : discountPercent

  const resolvedDevices = parseComboDevices(pkg)
  const devicesLabel = formatComboDevicesLabel(resolvedDevices)

  return {
    ...pkg,
    services,
    originalPrice,
    promoPrice,
    providerCost,
    platformProfit,
    resellerCost,
    resellerProfit,
    discountPercent: savingsPercent,
    savingsAmount,
    savingsPercent,
    devices: String(resolvedDevices),
    devicesNumber: resolvedDevices,
    devicesLabel: devicesLabel,
    badge: pkg.badge || `🔥 ${savingsPercent}% OFF`
  }
}

export function getFreshOfficialCombos(pricingCatalog = null) {
  const catalog = JSON.parse(JSON.stringify(OFFICIAL_COMBOS_CATALOG));
  if (pricingCatalog && Object.keys(pricingCatalog).length > 0) {
    return catalog.map(pkg => calculatePackageFromCatalog(pkg, pricingCatalog));
  }
  return catalog;
}
