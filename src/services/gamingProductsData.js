// Catálogo Oficial de Videojuegos Digitales PS4 / PS5
// Proveedores: Argentina Play (argentinaplay.com.ar) y Juegos Digitales Argentina (juegosdigitalesargentina.com)
// Regla: En la plataforma pública siempre se exhibe el MAYOR PRECIO entre ambos proveedores para cada modalidad (Primaria / Secundaria).

export const GAMING_PROVIDERS = {
  argentinaplay: {
    id: 'argentinaplay',
    name: 'Argentina Play',
    shortName: 'ArgPlay',
    url: 'https://www.argentinaplay.com.ar/',
    badge: '🇦🇷 Argentina Play Oficial',
    tagColor: 'bg-blue-500/20 text-blue-300 border-blue-500/30'
  },
  juegosdigitalesargentina: {
    id: 'juegosdigitalesargentina',
    name: 'Juegos Digitales Argentina',
    shortName: 'JDA',
    url: 'https://juegosdigitalesargentina.com/',
    badge: '🎮 Juegos Digitales Arg (JDA)',
    tagColor: 'bg-amber-500/20 text-amber-300 border-amber-500/30'
  }
}

export const INITIAL_GAMING_PRODUCTS = [
  {
    id: 'GAME-001',
    title: 'EA Sports FC 25',
    slug: 'ea-sports-fc-25',
    platform: 'PS4 / PS5',
    category: 'Deportes',
    badge: '⚽ MÁS VENDIDO 2026',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202503/2520/09767c0f0551c8a3edf9ab4727eb084c7cae88fecef16a09.png',
    description: 'La experiencia futbolística definitiva de EA SPORTS con tecnología FC IQ, tácticas renovadas, modo Carrera de mánager y jugador, y Ultimate Team con leyendas actualizadas para PS4 y PS5.',
    features: ['Audio Español Latino', 'Ultimate Team & Rivals Online', 'Gráficos 4K 60FPS', 'Multijugador local y online'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 27900,
        secondaryPrice: 13500,
        costPrimary: 19500,
        costSecondary: 9200,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 29999,
        secondaryPrice: 11900,
        costPrimary: 21000,
        costSecondary: 8000,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-002',
    title: 'Grand Theft Auto V (GTA V Premium)',
    slug: 'gta-v-premium',
    platform: 'PS4 / PS5',
    category: 'Acción & Aventura',
    badge: '🔥 CLÁSICO IMPERDIBLE',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202203/0302/bZ1JTRXzoyl3hkcsloKcCgdB.jpg',
    description: 'Explora el gigantesco mundo abierto de Los Santos y el condado de Blaine en la historia entrelazada de Michael, Franklin y Trevor. Incluye acceso completo a GTA Online con contenido actualizado.',
    features: ['GTA Online Incluido', 'Modo Historia Completo', 'Resolución 4K Ultra HD', 'Tiempos de carga reducidos'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 18500,
        secondaryPrice: 9900,
        costPrimary: 13000,
        costSecondary: 6800,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 19800,
        secondaryPrice: 8500,
        costPrimary: 14000,
        costSecondary: 5900,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-003',
    title: "Marvel's Spider-Man 2",
    slug: 'marvels-spider-man-2',
    platform: 'PS5',
    category: 'Acción & Aventura',
    badge: '🕸️ EXCLUSIVO PS5',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202306/1219/60eca3ac155247e21850c7d075d01ebf0f3f5dbf19ccd2a1.jpg',
    description: 'Los Spider-Men Peter Parker y Miles Morales regresan en una aventura aclamada para PS5. Balancéate, salta y usa las nuevas alas de telaraña para recorrer la Nueva York de Marvel frente a Venom y Kraven.',
    features: ['Exclusivo PlayStation 5', 'Gráficos Ray Tracing', 'Gatillos Adaptativos DualSense', 'Sin tiempos de carga'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 34500,
        secondaryPrice: 18900,
        costPrimary: 24000,
        costSecondary: 13000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 36000,
        secondaryPrice: 17500,
        costPrimary: 25500,
        costSecondary: 12000,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-004',
    title: 'God of War Ragnarök',
    slug: 'god-of-war-ragnarok',
    platform: 'PS4 / PS5',
    category: 'Acción & Aventura',
    badge: '⚡ OBRA MAESTRA',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202207/1210/4xJ8XB3bi888QTLZYdl7Oi0s.png',
    description: 'Kratos y Atreus emprenden un viaje mítico en busca de respuestas antes de que llegue el profetizado Ragnarök a través de los Nueve Reinos nórdicos con combate visceral y gráficos deslumbrantes.',
    features: ['Incluye DLC Valhalla Gratis', 'Combate visceral nórdico', 'Voces en Español Latino', 'Audio 3D Tempest'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 31900,
        secondaryPrice: 15500,
        costPrimary: 22000,
        costSecondary: 10500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 29500,
        secondaryPrice: 16900,
        costPrimary: 20500,
        costSecondary: 11500,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-005',
    title: 'Mortal Kombat 1',
    slug: 'mortal-kombat-1',
    platform: 'PS5',
    category: 'Lucha',
    badge: '🩸 FATALITY BRUTAL',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202305/1515/c8388641302e1fd8f8f1044f13b398027f188eb695a0d652.jpg',
    description: 'Descubre un universo de Mortal Kombat renacido por Liu Kang, Dios del Fuego. Nuevos personajes Kameo asistentes, Fatalities cinematográficas brutales y modo Invasiones RPG.',
    features: ['Luchadores Kameo Asistentes', 'Modo Invasiones RPG', 'Multijugador Competitivo', 'Gráficos Next-Gen 60FPS'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 32000,
        secondaryPrice: 15000,
        costPrimary: 22500,
        costSecondary: 10000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 34500,
        secondaryPrice: 14200,
        costPrimary: 24000,
        costSecondary: 9500,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-006',
    title: 'Call of Duty: Black Ops 6',
    slug: 'call-of-duty-black-ops-6',
    platform: 'PS4 / PS5',
    category: 'Acción & Aventura',
    badge: '💥 LANZAMIENTO TOP',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202405/2921/05a08ddb076656e71324f6d578bdbaa16c8925ab01dc3046.png',
    description: 'Guerra de espionaje en los años 90 con movimiento omnidireccional revolucionario, campaña épica y el regreso del mítico modo Zombis por rondas clásico.',
    features: ['Movimiento Omnidireccional', 'Zombis por Rondas Clásico', 'Multijugador Frenético', 'Campaña Cinematográfica'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 41000,
        secondaryPrice: 22000,
        costPrimary: 29000,
        costSecondary: 15500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 42500,
        secondaryPrice: 19900,
        costPrimary: 30000,
        costSecondary: 14000,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-007',
    title: 'Dragon Ball: Sparking! ZERO',
    slug: 'dragon-ball-sparking-zero',
    platform: 'PS5',
    category: 'Lucha',
    badge: '🐉 FUROR BUDOKAI',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202405/2306/f0b11d4e9198adcf80d7dc0ec61bc2bdfb31c6c7afc3a366.jpg',
    description: 'El legendario sucesor de la serie Budokai Tenkaichi lleva el combate 3D a nuevos niveles con más de 180 personajes y transformaciones del universo Dragon Ball Z, Super y GT.',
    features: ['Más de 182 Luchadores', 'Batallas de Episodios What-If', 'Destrucción de Escenarios', 'Combate aéreo en 3D'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 44900,
        secondaryPrice: 23500,
        costPrimary: 31500,
        costSecondary: 16500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 43000,
        secondaryPrice: 24900,
        costPrimary: 30000,
        costSecondary: 17500,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-008',
    title: 'The Last of Us Part I (Remake)',
    slug: 'the-last-of-us-part-i',
    platform: 'PS5',
    category: 'Acción & Aventura',
    badge: '🏆 HISTORIA ÉPICA',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202206/0720/JKqkaz5Sy6AvH2fZAVdjTxR8.png',
    description: 'Revive la emotiva e inolvidable historia de supervivencia de Joel y Ellie reconstruida fielmente desde cero para la consola PlayStation 5 con tecnologías modernas de Naughty Dog.',
    features: ['Reconstruido para PS5', 'Audio 3D Inmersivo', 'Físicas y animaciones realistas', 'Incluye precuela Left Behind'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 29900,
        secondaryPrice: 14500,
        costPrimary: 21000,
        costSecondary: 10000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 28500,
        secondaryPrice: 15800,
        costPrimary: 20000,
        costSecondary: 11000,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-009',
    title: 'Resident Evil 4 Remake',
    slug: 'resident-evil-4-remake',
    platform: 'PS4 / PS5',
    category: 'Survival Horror',
    badge: '🧟 TERROR & ACCIÓN',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202302/2409/1f465484e5e930c5a7b55ee28effb150f900c86a8f1eb50d.png',
    description: 'La obra maestra del survival horror reinventada con gráficos fotorrealistas RE Engine, atmósfera aterradora y jugabilidad modernizada en la misión de Leon en España.',
    features: ['RE Engine Gráficos 4K', 'Jugabilidad modernizada', 'Modo Mercenarios Gratis', 'Modo PS VR2 Compatible'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 26500,
        secondaryPrice: 12800,
        costPrimary: 18500,
        costSecondary: 9000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 27900,
        secondaryPrice: 11900,
        costPrimary: 19500,
        costSecondary: 8200,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-010',
    title: 'Hogwarts Legacy',
    slug: 'hogwarts-legacy',
    platform: 'PS4 / PS5',
    category: 'Rol & Aventura',
    badge: '🧙 MUNDO MÁGICO',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202208/0921/3XopdGAJGRy3xNQKnQDvaCRs.png',
    description: 'Un RPG de acción inmersivo en mundo abierto ambientado en el siglo XIX del universo de Harry Potter. Forja tu legado, domina hechizos, elabora pociones y explora Hogwarts libremente.',
    features: ['Mundo Abierto Masivo', 'Combate con varita y pociones', 'Criaturas Mágicas y Escobas', 'Vuela libre por Hogwarts'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 25900,
        secondaryPrice: 12500,
        costPrimary: 18000,
        costSecondary: 8500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 24800,
        secondaryPrice: 13500,
        costPrimary: 17200,
        costSecondary: 9200,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-011',
    title: 'Elden Ring: Shadow of the Erdtree',
    slug: 'elden-ring-shadow-of-the-erdtree',
    platform: 'PS4 / PS5',
    category: 'Rol & Aventura',
    badge: '👑 JUEGO DEL AÑO',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202402/0817/48330e9df5755a91ae1ff7626e07c398ffd9e0afce393e34.png',
    description: 'El aclamado RPG de acción y mundo abierto de FromSoftware. Explora las Tierras Intermedias y la misteriosa Tierra Sombría con decenas de nuevas armas, jefes y magia.',
    features: ['Incluye Juego Base + Expansión', 'Cientos de armas y hechizos', 'Multijugador Cooperativo/PvP', 'Dificultad legendaria'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 38000,
        secondaryPrice: 19500,
        costPrimary: 26500,
        costSecondary: 13500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 39500,
        secondaryPrice: 18200,
        costPrimary: 27500,
        costSecondary: 12500,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-012',
    title: 'ASTRO BOT',
    slug: 'astro-bot-ps5',
    platform: 'PS5',
    category: 'Acción & Aventura',
    badge: '🏆 JUEGO DEL AÑO 2024',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202406/0500/8f15268257b878597757fcc5f2c9545840867bc71fc863b1.png',
    description: 'Acompaña a Astro en una aventura espacial épica para rescatar a la tripulación perdida de PlayStation. Más de 50 planetas, homenajes nostálgicos y aprovechamiento total del mando DualSense.',
    features: ['Exclusivo PlayStation 5', 'Aprovechamiento 100% DualSense', '+50 Planetas con Cameos VIP', 'Juego del Año (GOTY)'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 34500,
        secondaryPrice: 18500,
        costPrimary: 24000,
        costSecondary: 12500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 36000,
        secondaryPrice: 17900,
        costPrimary: 25000,
        costSecondary: 12000,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-013',
    title: 'PlayStation Plus Deluxe 12 Meses',
    slug: 'ps-plus-deluxe-12-meses',
    platform: 'PS4 / PS5',
    category: 'Membresías',
    badge: '💎 MEMBRESÍA VIP',
    image: 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80',
    description: 'Suscripción anual con cientos de juegos de PS4 y PS5, catálogo de clásicos (PS1, PS2, PSP), pruebas de juegos y multijugador online sin límites.',
    features: ['Multijugador Online Completo', 'Catálogo +400 Juegos Gratis', 'Clásicos PS1, PS2, PSP', 'Descuentos exclusivos PS Store'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 68000,
        secondaryPrice: 38000,
        costPrimary: 49000,
        costSecondary: 27000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 72000,
        secondaryPrice: 35000,
        costPrimary: 52000,
        costSecondary: 25000,
        stock: 'Inmediato'
      }
    }
  }
]

// Función de procesamiento y arbitraje:
// Calcula el MAYOR PRECIO entre ambos proveedores para Primaria y Secundaria,
// e identifica el proveedor que fija el precio y el proveedor con mayor margen.
export function processGamingProduct(product) {
  const pArg = product.providers?.argentinaplay || { primaryPrice: 0, secondaryPrice: 0, costPrimary: 0, costSecondary: 0 }
  const pJda = product.providers?.juegosdigitalesargentina || { primaryPrice: 0, secondaryPrice: 0, costPrimary: 0, costSecondary: 0 }

  // REGLA SOLICITADA POR EL USUARIO:
  // "en plataforma mostrara solo los de mayor precio"
  const effectivePrimaryPrice = Math.max(Number(pArg.primaryPrice) || 0, Number(pJda.primaryPrice) || 0)
  const effectiveSecondaryPrice = Math.max(Number(pArg.secondaryPrice) || 0, Number(pJda.secondaryPrice) || 0)

  // Proveedor que marca el precio más alto en la plataforma
  const primaryPriceLeaderKey = (Number(pArg.primaryPrice) >= Number(pJda.primaryPrice)) ? 'argentinaplay' : 'juegosdigitalesargentina'
  const secondaryPriceLeaderKey = (Number(pArg.secondaryPrice) >= Number(pJda.secondaryPrice)) ? 'argentinaplay' : 'juegosdigitalesargentina'

  // Proveedor óptimo de compra (menor costo para mayor ganancia neta para StreamePro)
  const costPrimaryArg = Number(pArg.costPrimary) || Infinity
  const costPrimaryJda = Number(pJda.costPrimary) || Infinity
  const bestFulfillmentPrimary = costPrimaryArg <= costPrimaryJda ? 'argentinaplay' : 'juegosdigitalesargentina'
  const bestMarginPrimary = effectivePrimaryPrice - Math.min(costPrimaryArg, costPrimaryJda)

  const costSecondaryArg = Number(pArg.costSecondary) || Infinity
  const costSecondaryJda = Number(pJda.costSecondary) || Infinity
  const bestFulfillmentSecondary = costSecondaryArg <= costSecondaryJda ? 'argentinaplay' : 'juegosdigitalesargentina'
  const bestMarginSecondary = effectiveSecondaryPrice - Math.min(costSecondaryArg, costSecondaryJda)

  // Similitud entre catálogos:
  // Diferencia de precio en $ y %
  const diffPrimary = Math.abs(pArg.primaryPrice - pJda.primaryPrice)
  const diffSecondary = Math.abs(pArg.secondaryPrice - pJda.secondaryPrice)

  return {
    ...product,
    effectivePrimaryPrice,
    effectiveSecondaryPrice,
    primaryPriceLeaderKey,
    primaryPriceLeaderName: GAMING_PROVIDERS[primaryPriceLeaderKey].name,
    secondaryPriceLeaderKey,
    secondaryPriceLeaderName: GAMING_PROVIDERS[secondaryPriceLeaderKey].name,
    bestFulfillmentPrimary,
    bestFulfillmentPrimaryName: GAMING_PROVIDERS[bestFulfillmentPrimary].name,
    bestMarginPrimary: Math.max(0, bestMarginPrimary),
    bestFulfillmentSecondary,
    bestFulfillmentSecondaryName: GAMING_PROVIDERS[bestFulfillmentSecondary].name,
    bestMarginSecondary: Math.max(0, bestMarginSecondary),
    diffPrimary,
    diffSecondary,
    similarityScore: 100 // Título idéntico en ambos catálogos
  }
}
