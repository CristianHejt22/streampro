// Catálogo Oficial de Combos y Promociones StreamePro
// Modelo Económico Oficial:
// - Netflix: $4.500 costo proveedor por perfil en combos
// - Prime Video: $1.500 costo proveedor por perfil en combos
// - Ganancia de revendedor integrada y aumento general de lista aplicado con descuento final transparente.

export const OFFICIAL_COMBOS_CATALOG = [
  {
    "id": "C001",
    "sku": "C001",
    "title": "Promo Imperdible 1",
    "category": "streaming",
    "description": "Netflix Premium, Disney+ (7 ESPN), Prime Video · 1 Dispositivo · 30 Días",
    "badge": "🔥 28% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Netflix",
        "planLabel": "Ultra HD 4K",
        "units": 1,
        "regularPrice": 7700,
        "cost": 4500
      },
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
      }
    ],
    "logos": [
      "netflix.png",
      "disney_plus.png",
      "prime_video.png"
    ],
    "image": "/flyers/combos/promo-pantallas-total.png",
    "originalPrice": 18200,
    "promoPrice": 13100,
    "providerCost": 8000,
    "resellerProfit": 3300,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
        "cost": 4500
      }
    ],
    "logos": [
      "disney_plus.png",
      "netflix.png"
    ],
    "image": "/flyers/combos/promo-pantallas-total.png",
    "originalPrice": 14000,
    "promoPrice": 10100,
    "providerCost": 6500,
    "resellerProfit": 2350,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Netflix",
        "planLabel": "Ultra HD 4K",
        "units": 1,
        "regularPrice": 7700,
        "cost": 4500
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
    "promoPrice": 13200,
    "providerCost": 9000,
    "resellerProfit": 2750,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
    "promoPrice": 13500,
    "providerCost": 7000,
    "resellerProfit": 4250,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
    "promoPrice": 11900,
    "providerCost": 5500,
    "resellerProfit": 4150,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Pornhub",
        "planLabel": "Premium Privado",
        "units": 1,
        "regularPrice": 4500,
        "cost": 1800
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
    "originalPrice": 14300,
    "promoPrice": 10300,
    "providerCost": 5300,
    "resellerProfit": 3250,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
        "cost": 4500
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
        "planLabel": "Internet Personal (7 días)",
        "units": 1,
        "regularPrice": 5000,
        "cost": 1750
      }
    ],
    "logos": [
      "prime_video.png",
      "netflix.png",
      "max.png",
      "personal.png"
    ],
    "image": "",
    "originalPrice": 22900,
    "promoPrice": 16500,
    "providerCost": 9750,
    "resellerProfit": 4400,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
    "promoPrice": 8500,
    "providerCost": 4500,
    "resellerProfit": 2600,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Paramount+",
        "planLabel": "Premium",
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
    "promoPrice": 9800,
    "providerCost": 5000,
    "resellerProfit": 3100,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
    "promoPrice": 7300,
    "providerCost": 3500,
    "resellerProfit": 2450,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
    "durationDays": "60",
    "devices": "5",
    "services": [
      {
        "platform": "DramaBox",
        "planLabel": "5 Dispositivos",
        "units": 5,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "FreeReels",
        "planLabel": "Oficial",
        "units": 5,
        "regularPrice": 3200,
        "cost": 1200
      },
      {
        "platform": "NetShort",
        "planLabel": "Oficial",
        "units": 5,
        "regularPrice": 3200,
        "cost": 1200
      },
      {
        "platform": "ReelShort",
        "planLabel": "Oficial",
        "units": 5,
        "regularPrice": 3200,
        "cost": 1200
      }
    ],
    "logos": [
      "dramabox.png",
      "freereels.png",
      "netshort.png",
      "reelshort.png"
    ],
    "image": "",
    "originalPrice": 13400,
    "promoPrice": 9600,
    "providerCost": 5100,
    "resellerProfit": 2950,
    "discountPercent": 28,
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
    "description": "Spotify Premium, ChatGPT Plus, Canva Edu · 1 Dispositivo · 30 Días",
    "badge": "🔥 28% OFF",
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
        "planLabel": "Edu",
        "units": 1,
        "regularPrice": 3500,
        "cost": 1200
      }
    ],
    "logos": [
      "spotify.png",
      "chatgpt.png",
      "canva.png"
    ],
    "image": "",
    "originalPrice": 13800,
    "promoPrice": 9900,
    "providerCost": 5700,
    "resellerProfit": 2750,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
    "promoPrice": 11900,
    "providerCost": 5500,
    "resellerProfit": 4150,
    "discountPercent": 28,
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
    "description": "Canva Edu (6 meses), CapCut Pro (1 mes) · 1 Dispositivo · 30 Días",
    "badge": "🔥 23% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Canva Pro",
        "planLabel": "6 Meses Edu",
        "units": 1,
        "regularPrice": 3500,
        "cost": 1200
      },
      {
        "platform": "CapCut Pro",
        "planLabel": "1 Mes Pro",
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
    "originalPrice": 7000,
    "promoPrice": 5400,
    "providerCost": 2400,
    "resellerProfit": 1950,
    "discountPercent": 23,
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
    "description": "ChatGPT Plus, Canva Edu, CapCut Pro · 1 Dispositivo · 30 Días",
    "badge": "🔥 28% OFF",
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
        "planLabel": "Edu",
        "units": 1,
        "regularPrice": 3500,
        "cost": 1200
      },
      {
        "platform": "CapCut Pro",
        "planLabel": "1 Mes Pro",
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
    "originalPrice": 13500,
    "promoPrice": 9700,
    "providerCost": 5400,
    "resellerProfit": 2800,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
        "planLabel": "Pass Standard",
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
    "promoPrice": 9800,
    "providerCost": 5000,
    "resellerProfit": 3100,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
        "planLabel": "Premium",
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
    "promoPrice": 13000,
    "providerCost": 6500,
    "resellerProfit": 4250,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
    "promoPrice": 7300,
    "providerCost": 3500,
    "resellerProfit": 2450,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
        "planLabel": "Premium",
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
    "promoPrice": 9800,
    "providerCost": 5000,
    "resellerProfit": 3100,
    "discountPercent": 28,
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
    "description": "YouTube Premium APK (5 disp), Prime Video (1 disp) · 1 Dispositivo · 30 Días",
    "badge": "🔥 28% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "YouTube",
        "planLabel": "Premium",
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
    "promoPrice": 6200,
    "providerCost": 3000,
    "resellerProfit": 2100,
    "discountPercent": 28,
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
    "badge": "🔥 28% OFF",
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
        "planLabel": "Pass Standard",
        "units": 1,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "DramaBox",
        "planLabel": "5 Dispositivos",
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
    "promoPrice": 8200,
    "providerCost": 4500,
    "resellerProfit": 2400,
    "discountPercent": 28,
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
    "badge": "🔥 27% OFF",
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
    "image": "/flyers/combos/combo-duo-series.png",
    "originalPrice": 10300,
    "promoPrice": 7500,
    "providerCost": 4500,
    "resellerProfit": 1950,
    "discountPercent": 27,
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
    "badge": "🔥 28% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Netflix",
        "planLabel": "Perfil Extra",
        "units": 1,
        "regularPrice": 7700,
        "cost": 4500
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
    "promoPrice": 12900,
    "providerCost": 8000,
    "resellerProfit": 3200,
    "discountPercent": 28,
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
    "badge": "🔥 31% OFF",
    "durationDays": "30",
    "devices": "1",
    "services": [
      {
        "platform": "Claro",
        "planLabel": "Claro Internet",
        "units": 1,
        "regularPrice": 5000,
        "cost": 1750
      },
      {
        "platform": "Personal",
        "planLabel": "Personal Internet",
        "units": 1,
        "regularPrice": 5000,
        "cost": 1750
      }
    ],
    "logos": [
      "claro.png",
      "personal.png"
    ],
    "image": "/flyers/combos/streamepro-square.jpg",
    "originalPrice": 10000,
    "promoPrice": 6900,
    "providerCost": 3500,
    "resellerProfit": 2400,
    "discountPercent": 31,
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
    "badge": "🔥 29% OFF",
    "durationDays": "90",
    "devices": "3",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 3,
        "regularPrice": 5000,
        "cost": 1750
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 14000,
    "promoPrice": 9900,
    "providerCost": 5000,
    "resellerProfit": 3500,
    "discountPercent": 29,
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
    "badge": "🔥 29% OFF",
    "durationDays": "90",
    "devices": "2",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 2,
        "regularPrice": 5000,
        "cost": 1750
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 12000,
    "promoPrice": 8500,
    "providerCost": 4000,
    "resellerProfit": 3000,
    "discountPercent": 29,
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
        "regularPrice": 5000,
        "cost": 1750
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 6000,
    "promoPrice": 4500,
    "providerCost": 2000,
    "resellerProfit": 1800,
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
    "badge": "🔥 28% OFF",
    "durationDays": "60",
    "devices": "3",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 3,
        "regularPrice": 5000,
        "cost": 1750
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 16000,
    "promoPrice": 11500,
    "providerCost": 5500,
    "resellerProfit": 4000,
    "discountPercent": 28,
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
        "regularPrice": 5000,
        "cost": 1750
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 10000,
    "promoPrice": 7500,
    "providerCost": 3500,
    "resellerProfit": 2800,
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
    "badge": "🔥 28% OFF",
    "durationDays": "60",
    "devices": "2",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 2,
        "regularPrice": 5000,
        "cost": 1750
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 11000,
    "promoPrice": 7900,
    "providerCost": 3500,
    "resellerProfit": 3000,
    "discountPercent": 28,
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
    "badge": "🔥 31% OFF",
    "durationDays": "55",
    "devices": "2",
    "services": [
      {
        "platform": "Personal",
        "planLabel": "Línea Personal (Android)",
        "units": 2,
        "regularPrice": 5000,
        "cost": 1750
      }
    ],
    "logos": [
      "personal.png"
    ],
    "image": "",
    "originalPrice": 8000,
    "promoPrice": 5500,
    "providerCost": 2500,
    "resellerProfit": 2000,
    "discountPercent": 31,
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
    "badge": "🔥 25% OFF",
    "durationDays": "Ilimitado",
    "devices": "3",
    "services": [
      {
        "platform": "Panel sin vencimiento",
        "planLabel": "Oficial",
        "units": 3,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "Demos 1 hora",
        "planLabel": "Oficial",
        "units": 3,
        "regularPrice": 3800,
        "cost": 1500
      },
      {
        "platform": "1 crédito=3 disp",
        "planLabel": "Oficial",
        "units": 3,
        "regularPrice": 3800,
        "cost": 1500
      }
    ],
    "logos": [
      "panel_admin.png"
    ],
    "image": "/og-banner.jpg",
    "originalPrice": 24000,
    "promoPrice": 18000,
    "providerCost": 12000,
    "resellerProfit": 4500,
    "discountPercent": 25,
    "createdBy": "provider",
    "resellerId": "ALL",
    "active": true,
    "createdAt": "2026-11-14"
  }
]

export function getFreshOfficialCombos() {
  return JSON.parse(JSON.stringify(OFFICIAL_COMBOS_CATALOG))
}
