// Catálogo Oficial Extendido de Videojuegos Digitales PS4 / PS5
// Proveedores: Argentina Play (argentinaplay.com.ar) y Juegos Digitales Argentina (juegosdigitalesargentina.com)
// Regla: En la plataforma pública siempre se exhibe el MAYOR PRECIO entre ambos proveedores para cada modalidad (Primaria / Secundaria).

export const GAMING_PROVIDERS = {
  argentinaplay: {
    id: 'argentinaplay',
    name: 'Argentina Play',
    shortName: 'ArgPlay',
    url: 'https://www.argentinaplay.com.ar/',
    badge: '🇦🇷 Argentina Play',
    tagColor: 'bg-blue-500/20 text-blue-300 border-blue-500/30'
  },
  juegosdigitalesargentina: {
    id: 'juegosdigitalesargentina',
    name: 'Juegos Digitales Argentina',
    shortName: 'JDA',
    url: 'https://juegosdigitalesargentina.com/',
    badge: '🎮 JDA',
    tagColor: 'bg-amber-500/20 text-amber-300 border-amber-500/30'
  },
  digitalgamesshop: {
    id: 'digitalgamesshop',
    name: 'Digital Games Shop',
    shortName: 'DGS',
    url: 'https://digitalgamesshop.ar/',
    badge: '⚡ Digital Games',
    tagColor: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30'
  },
  tutiendagamer: {
    id: 'tutiendagamer',
    name: 'Tu Tienda Gamer',
    shortName: 'TTG',
    url: 'https://tutiendagamer.com.ar/',
    badge: '🕹️ Tu Tienda Gamer',
    tagColor: 'bg-purple-500/20 text-purple-300 border-purple-500/30'
  },
  fusiongames: {
    id: 'fusiongames',
    name: 'Fusion Games',
    shortName: 'Fusion',
    url: 'https://fusiongames.com.ar/',
    badge: '🔥 Fusion Games',
    tagColor: 'bg-rose-500/20 text-rose-300 border-rose-500/30'
  },
  mayorista_txt: {
    id: 'mayorista_txt',
    name: 'Lista Ofertas TXT',
    shortName: 'Lista TXT',
    url: '',
    badge: '📋 Lista TXT',
    tagColor: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
  }
}

export const DEFAULT_GAMING_PROVIDER_SITES = [
  {
    key: 'argentinaplay',
    name: 'Argentina Play',
    shortName: 'ArgPlay',
    badge: '🇦🇷 Argentina Play',
    url: 'https://www.argentinaplay.com.ar/',
    whatsapp: '+54 9 11 4050-6070',
    tagColor: 'bg-blue-500/20 text-blue-300 border-blue-500/30',
    enabled: true,
    includeInPvp: true,
    isDefault: true,
    notes: 'Catálogo base PS4 / PS5 Primarias & Secundarias'
  },
  {
    key: 'juegosdigitalesargentina',
    name: 'Juegos Digitales Argentina',
    shortName: 'JDA',
    badge: '🎮 JDA',
    url: 'https://juegosdigitalesargentina.com/',
    whatsapp: '+54 9 11 4050-6070',
    tagColor: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    enabled: true,
    includeInPvp: true,
    isDefault: true,
    notes: 'Catálogo mayorista JDA Primarias & Secundarias'
  },
  {
    key: 'digitalgamesshop',
    name: 'Digital Games Shop',
    shortName: 'DGS',
    badge: '⚡ Digital Games',
    url: 'https://digitalgamesshop.ar/',
    whatsapp: '',
    tagColor: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
    enabled: true,
    includeInPvp: true,
    isDefault: true,
    notes: 'Snapshot masivo PSN + Portadas oficiales Vulcan'
  },
  {
    key: 'tutiendagamer',
    name: 'Tu Tienda Gamer',
    shortName: 'TTG',
    badge: '🕹️ Tu Tienda Gamer',
    url: 'https://tutiendagamer.com.ar/juegos-ps5',
    whatsapp: '',
    tagColor: 'bg-purple-500/20 text-purple-300 border-purple-500/30',
    enabled: true,
    includeInPvp: true,
    isDefault: true,
    notes: 'Videojuegos digitales PS5 / PS4 / PS3 (sin consolas/accesorios)'
  },
  {
    key: 'fusiongames',
    name: 'Fusion Games',
    shortName: 'Fusion',
    badge: '🔥 Fusion Games',
    url: 'https://fusiongames.com.ar/tienda/',
    whatsapp: '',
    tagColor: 'bg-rose-500/20 text-rose-300 border-rose-500/30',
    enabled: true,
    includeInPvp: true,
    isDefault: true,
    notes: 'Catálogo digital PS3 / PS4 / PS5'
  },
  {
    key: 'mayorista_txt',
    name: 'Lista Ofertas TXT',
    shortName: 'Lista TXT',
    badge: '📋 Lista TXT',
    url: '',
    whatsapp: '+54 9 11 4050-6070',
    tagColor: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
    enabled: true,
    includeInPvp: true,
    isDefault: true,
    notes: 'Ofertas cargadas desde archivos .txt y listas WhatsApp'
  }
]

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
        costPrimary: 27900,
        costSecondary: 13500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 29999,
        secondaryPrice: 11900,
        costPrimary: 29999,
        costSecondary: 11900,
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
        costPrimary: 18500,
        costSecondary: 9900,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 19800,
        secondaryPrice: 8500,
        costPrimary: 19800,
        costSecondary: 8500,
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
        costPrimary: 34500,
        costSecondary: 18900,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 36000,
        secondaryPrice: 17500,
        costPrimary: 36000,
        costSecondary: 17500,
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
        costPrimary: 31900,
        costSecondary: 15500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 29500,
        secondaryPrice: 16900,
        costPrimary: 29500,
        costSecondary: 16900,
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
        costPrimary: 32000,
        costSecondary: 15000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 34500,
        secondaryPrice: 14200,
        costPrimary: 34500,
        costSecondary: 14200,
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
        costPrimary: 41000,
        costSecondary: 22000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 42500,
        secondaryPrice: 19900,
        costPrimary: 42500,
        costSecondary: 19900,
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
        costPrimary: 44900,
        costSecondary: 23500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 43000,
        secondaryPrice: 24900,
        costPrimary: 43000,
        costSecondary: 24900,
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
        costPrimary: 29900,
        costSecondary: 14500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 28500,
        secondaryPrice: 15800,
        costPrimary: 28500,
        costSecondary: 15800,
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
        costPrimary: 26500,
        costSecondary: 12800,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 27900,
        secondaryPrice: 11900,
        costPrimary: 27900,
        costSecondary: 11900,
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
        costPrimary: 25900,
        costSecondary: 12500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 24800,
        secondaryPrice: 13500,
        costPrimary: 24800,
        costSecondary: 13500,
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
        costPrimary: 38000,
        costSecondary: 19500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 39500,
        secondaryPrice: 18200,
        costPrimary: 39500,
        costSecondary: 18200,
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
        costPrimary: 34500,
        costSecondary: 18500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 36000,
        secondaryPrice: 17900,
        costPrimary: 36000,
        costSecondary: 17900,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-013',
    title: 'Black Myth: Wukong',
    slug: 'black-myth-wukong',
    platform: 'PS5',
    category: 'Rol & Aventura',
    badge: '🐒 FENÓMENO MUNDIAL',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202405/2117/bd406f42e9352fdb398efcf21a4ffe575b2306ac40089d21.png',
    description: 'El aclamado RPG de acción inspirado en la mitología china y la leyenda del Rey Mono. Combates vertiginosos con bastón mágico, transformaciones y gráficos cinematográficos Unreal Engine 5.',
    features: ['Exclusivo Consola PS5', 'Unreal Engine 5 Next-Gen', 'Decenas de Transformaciones', 'Jefes colosales míticos'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 38900,
        secondaryPrice: 21000,
        costPrimary: 38900,
        costSecondary: 21000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 41000,
        secondaryPrice: 19500,
        costPrimary: 41000,
        costSecondary: 19500,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-014',
    title: 'Silent Hill 2 Remake',
    slug: 'silent-hill-2-remake',
    platform: 'PS5',
    category: 'Survival Horror',
    badge: '🌫️ TERROR PSICOLÓGICO',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202210/2000/7cb6eP7FKb4PMq6ZnDDBAlzw.png',
    description: 'Habiendo recibido una carta de su difunta esposa, James se dirige a Silent Hill en una obra maestra del terror psicológico rehecha con gráficos hiperrealistas y audio 3D inmersivo.',
    features: ['Exclusivo Consola PS5', 'Audio 3D Tempest Aterrador', 'Cámara al hombro renovada', 'Atmósfera fotorrealista'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 36500,
        secondaryPrice: 19000,
        costPrimary: 36500,
        costSecondary: 19000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 38500,
        secondaryPrice: 18200,
        costPrimary: 38500,
        costSecondary: 18200,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-015',
    title: 'Red Dead Redemption 2',
    slug: 'red-dead-redemption-2',
    platform: 'PS4 / PS5',
    category: 'Acción & Aventura',
    badge: '🤠 LEYENDA DEL WESTERN',
    image: 'https://image.api.playstation.com/vulcan/img/rnd/202011/1215/WyHa1BM3ISDVqYSEUMB9VZJs.png',
    description: 'América, 1899. Arthur Morgan y la banda de Van der Linde huyen de la ley. Considerado uno de los mejores videojuegos de la historia con una narrativa y mundo abierto sin precedentes.',
    features: ['Modo Historia de +80 Horas', 'Incluye Red Dead Online', 'Mundo Vivo e Hiperrealista', 'Voces y música legendarias'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 19900,
        secondaryPrice: 9500,
        costPrimary: 19900,
        costSecondary: 9500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 21500,
        secondaryPrice: 8900,
        costPrimary: 21500,
        costSecondary: 8900,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-016',
    title: 'Cyberpunk 2077: Ultimate Edition',
    slug: 'cyberpunk-2077-ultimate-edition',
    platform: 'PS5',
    category: 'Rol & Aventura',
    badge: '🌆 NIGHT CITY FUTURISTA',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202111/3013/UjQ1pWQiHwymgQQ6q4pWQkMC.png',
    description: 'La experiencia definitiva de Cyberpunk 2077 para PS5. Conviértete en V en Night City, incluye la masiva expansión de suspenso y espionaje Phantom Liberty con Idris Elba.',
    features: ['Incluye Phantom Liberty DLC', 'Ray Tracing & 60FPS en PS5', 'Árboles de Habilidades 2.0', 'Combate con vehículos mejorado'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 32000,
        secondaryPrice: 16500,
        costPrimary: 32000,
        costSecondary: 16500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 34000,
        secondaryPrice: 15500,
        costPrimary: 34000,
        costSecondary: 15500,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-017',
    title: 'Tekken 8',
    slug: 'tekken-8',
    platform: 'PS5',
    category: 'Lucha',
    badge: '⚡ PUÑO DE HIERRO',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202309/2802/0a334d1fc5c00e6d65577ff65f6226bb708bf542ec038001.jpg',
    description: 'Siente el poder de cada golpe en Tekken 8 con el motor Unreal Engine 5. Nueva mecánica Heat System, 32 personajes icónicos y modo Arcade Quest competitivo online.',
    features: ['Unreal Engine 5 a 60FPS', 'Sistema Heat Agresivo', '32 Luchadores Icónicos', 'Modo Arcade Quest & Online'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 35000,
        secondaryPrice: 18000,
        costPrimary: 35000,
        costSecondary: 18000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 36900,
        secondaryPrice: 16900,
        costPrimary: 36900,
        costSecondary: 16900,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-018',
    title: "Marvel's Spider-Man: Miles Morales",
    slug: 'spider-man-miles-morales',
    platform: 'PS4 / PS5',
    category: 'Acción & Aventura',
    badge: '⚡ PODERES BIOELÉCTRICOS',
    image: 'https://image.api.playstation.com/vulcan/img/rnd/202010/2417/tnCutdREPv6Pa7atqb8MTxGW.png',
    description: 'Vive el ascenso de Miles Morales como nuevo Spider-Man dominando increíbles poderes de veneno bioeléctrico y camuflaje óptico en un nevado Nueva York navideño.',
    features: ['Poderes Veneno & Camuflaje', 'Modo Rendimiento 60FPS', 'Gráficos 4K HDR', 'Tiempo de carga ultra rápido'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 18900,
        secondaryPrice: 9500,
        costPrimary: 18900,
        costSecondary: 9500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 19900,
        secondaryPrice: 8900,
        costPrimary: 19900,
        costSecondary: 8900,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-019',
    title: 'Gran Turismo 7',
    slug: 'gran-turismo-7',
    platform: 'PS4 / PS5',
    category: 'Deportes',
    badge: '🏎️ SIMULADOR REAL',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202109/1404/PN9uW6n3oBMhwEjsA5fye3P0.png',
    description: 'El simulador de conducción real por excelencia. Más de 420 coches reproducidos al detalle y más de 90 circuitos con clima dinámico y compatibilidad con PlayStation VR2.',
    features: ['Más de 420 Vehículos Oficiales', 'Clima Dinámico Realista', 'Compatible con PS VR2', 'Físicas hiperrealistas de pista'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 28900,
        secondaryPrice: 14500,
        costPrimary: 28900,
        costSecondary: 14500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 30500,
        secondaryPrice: 13900,
        costPrimary: 30500,
        costSecondary: 13900,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-020',
    title: 'The Last of Us Part II Remastered',
    slug: 'the-last-of-us-part-ii-remastered',
    platform: 'PS5',
    category: 'Acción & Aventura',
    badge: '🏆 GANADOR DE +300 PREMIOS',
    image: 'https://image.api.playstation.com/vulcan/ap/rnd/202502/1302/a2fa690381bb625c8efca0bbd5210811b9e044ed6f116ab8.jpg',
    description: 'Experimenta la desgarradora historia de Ellie y Abby optimizada para PS5 con mejoras gráficas nativas, niveles inéditos y el frenético modo de supervivencia roguelike Sin Retorno.',
    features: ['Nuevo Modo Roguelike Sin Retorno', 'Niveles Perdidos Inéditos', 'Tiempos de Carga Instantáneos', 'Gatillos DualSense Mejorados'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 31000,
        secondaryPrice: 16500,
        costPrimary: 31000,
        costSecondary: 16500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 32900,
        secondaryPrice: 15500,
        costPrimary: 32900,
        costSecondary: 15500,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-021',
    title: 'PlayStation Plus Deluxe 12 Meses',
    slug: 'ps-plus-deluxe-12-meses',
    platform: 'PS4 / PS5',
    category: 'Membresías',
    badge: '💎 MEMBRESÍA VIP',
    image: 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80',
    description: 'Suscripción anual con cientos de juegos de PS4 y PS5, catálogo de clásicos legendarios (PS1, PS2, PSP), pruebas de juegos y multijugador online sin límites.',
    features: ['Multijugador Online Completo', 'Catálogo +400 Juegos Gratis', 'Clásicos PS1, PS2, PSP', 'Descuentos exclusivos PS Store'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 68000,
        secondaryPrice: 38000,
        costPrimary: 68000,
        costSecondary: 38000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 72000,
        secondaryPrice: 35000,
        costPrimary: 72000,
        costSecondary: 35000,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-022',
    title: 'PlayStation Plus Extra 12 Meses',
    slug: 'ps-plus-extra-12-meses',
    platform: 'PS4 / PS5',
    category: 'Membresías',
    badge: '⚡ CATÁLOGO DE JUEGOS',
    image: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
    description: 'Disfruta de multijugador online, juegos mensuales gratuitos y acceso a un catálogo de cientos de increíbles juegos de PS4 y PS5 con rotación constante de éxitos.',
    features: ['Multijugador Online Completo', 'Catálogo +400 Juegos PS4/PS5', 'Juegos Mensuales Gratis', 'Almacenamiento en la Nube PSN'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 59000,
        secondaryPrice: 32000,
        costPrimary: 59000,
        costSecondary: 32000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 62000,
        secondaryPrice: 30000,
        costPrimary: 62000,
        costSecondary: 30000,
        stock: 'Inmediato'
      }
    }
  },
  {
    id: 'GAME-023',
    title: 'PlayStation Plus Essential 12 Meses',
    slug: 'ps-plus-essential-12-meses',
    platform: 'PS4 / PS5',
    category: 'Membresías',
    badge: '🎮 ONLINE + JUEGOS MENSUALES',
    image: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&auto=format&fit=crop&q=80',
    description: 'La membresía esencial para jugar online en PS4 y PS5. Incluye multijugador competitivo, 3 a 4 juegos descargables todos los meses y descuentos exclusivos en PS Store.',
    features: ['Multijugador Online Ilimitado', '3-4 Juegos Gratis Todos los Meses', 'Descuentos Exclusivos en Tienda', 'Partidas Guardadas en Nube'],
    providers: {
      argentinaplay: {
        providerName: 'Argentina Play',
        providerUrl: 'https://www.argentinaplay.com.ar/',
        available: true,
        primaryPrice: 42000,
        secondaryPrice: 24000,
        costPrimary: 42000,
        costSecondary: 24000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 45000,
        secondaryPrice: 22000,
        costPrimary: 45000,
        costSecondary: 22000,
        stock: 'Inmediato'
      }
    }
  }
]

// Función de procesamiento y arbitraje:
// Calcula el MAYOR PRECIO entre TODOS los sitios agregados y scrapeados habilitados para PVP,
// e identifica tanto el sitio de mayor costo (base del PVP público) como el proveedor más conveniente para comprar.
export function getPlatformBucket(platform = '', title = '') {
  const s = `${platform} ${title}`.toLowerCase()
  if (s.includes('xbox')) return 'xbox'
  if (s.includes('switch') || s.includes('nintendo')) return 'switch'
  if (/\b(pc|steam)\b/i.test(s)) return 'pc'
  if (s.includes('ps3') && !s.includes('ps4') && !s.includes('ps5')) return 'ps3'
  return 'ps'
}

export function supportsSecondaryLicense(platform = '') {
  const bucket = getPlatformBucket(platform)
  return bucket === 'ps'
}

export function getPublicStoreBrand(platform = '') {
  const bucket = getPlatformBucket(platform)
  if (bucket === 'xbox') return { name: 'Xbox Store Oficial', shortName: 'Xbox Store', badge: '💚 Xbox Store Oficial' }
  if (bucket === 'switch') return { name: 'Nintendo eShop Oficial', shortName: 'eShop', badge: '❤️ Nintendo eShop Oficial' }
  if (bucket === 'pc') return { name: 'PC Digital Oficial', shortName: 'PC Digital', badge: '🖥️ PC Digital Oficial' }
  return { name: 'PlayStation Store Oficial', shortName: 'PS Store', badge: '🎮 PlayStation Store Oficial' }
}

export function processGamingProduct(product, storeMarginPercent = 20, resellerCommissionPercent = 10, minResellerProfit = 1000, gamingSitesConfig = null) {
  const pArg = product.providers?.argentinaplay || { primaryPrice: 0, secondaryPrice: 0, costPrimary: 0, costSecondary: 0, available: false }
  const pJda = product.providers?.juegosdigitalesargentina || { primaryPrice: 0, secondaryPrice: 0, costPrimary: 0, costSecondary: 0, available: false }

  const hasArg = Boolean(pArg.available !== false && (Number(pArg.primaryPrice) > 0 || Number(pArg.costPrimary) > 0))
  const hasJda = Boolean(pJda.available !== false && (Number(pJda.primaryPrice) > 0 || Number(pJda.costPrimary) > 0))

  // Mapa de configuración de sitios del administrador
  const siteCfgMap = {}
  if (Array.isArray(gamingSitesConfig)) {
    gamingSitesConfig.forEach(s => {
      if (s && s.key) siteCfgMap[s.key] = s
    })
  } else if (gamingSitesConfig && typeof gamingSitesConfig === 'object') {
    Object.assign(siteCfgMap, gamingSitesConfig)
  }

  // 1. Recopilar todos los proveedores activos y cotizados (respetando sitios desactivados por admin)
  const providersMap = product.providers || {}
  const activeProvidersList = []

  for (const [key, p] of Object.entries(providersMap)) {
    if (!p) continue
    const siteCfg = siteCfgMap[key]
    if (siteCfg && siteCfg.enabled === false) continue

    const cPri = Number(p.costPrimary || p.primaryPrice || 0)
    const cSec = Number(p.costSecondary || p.secondaryPrice || 0)
    if (p.available !== false && (cPri > 0 || cSec > 0)) {
      const meta = siteCfg || GAMING_PROVIDERS[key]
      const resolvedUrl = p.providerUrl || (key === 'digitalgamesshop' && product.title ? `https://digitalgamesshop.ar/?q=${encodeURIComponent(product.title)}` : (siteCfg?.url || meta?.url || ''))
      activeProvidersList.push({
        key,
        name: siteCfg?.name || p.providerName || meta?.name || 'Proveedor Acordado',
        shortName: siteCfg?.shortName || meta?.shortName || (p.providerName ? p.providerName.slice(0, 10) : 'Mayorista'),
        tagColor: siteCfg?.tagColor || meta?.tagColor || 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
        url: resolvedUrl,
        whatsapp: siteCfg?.whatsapp || p.providerWhatsapp || '',
        includeInPvp: siteCfg ? siteCfg.includeInPvp !== false : true,
        costPrimary: cPri,
        costSecondary: cSec,
        stock: p.stock || 'Inmediato'
      })
    }
  }
  activeProvidersList.sort((a, b) => (a.costPrimary || 9999999) - (b.costPrimary || 9999999))

  // 2. Sitios habilitados para fijar el MAYOR COSTO en PVP web
  const pvpCandidates = activeProvidersList.filter(p => p.includeInPvp !== false)
  const effectivePvpList = pvpCandidates.length > 0 ? pvpCandidates : activeProvidersList

  const pvpPrimaryCosts = effectivePvpList.map(p => p.costPrimary).filter(c => c > 0)
  const pvpSecondaryCosts = effectivePvpList.map(p => p.costSecondary).filter(c => c > 0)
  const allPrimaryCosts = activeProvidersList.map(p => p.costPrimary).filter(c => c > 0)
  const allSecondaryCosts = activeProvidersList.map(p => p.costSecondary).filter(c => c > 0)

  const allowSecondary = supportsSecondaryLicense(product.platform)

  let baseCostPrimary = 0
  let minCostPrimary = 0
  if (pvpPrimaryCosts.length > 0) {
    baseCostPrimary = Math.max(...pvpPrimaryCosts)
  } else {
    baseCostPrimary = Number(product.baseCostPrimary || product.effectivePrimaryPrice) || 20000
  }
  minCostPrimary = allPrimaryCosts.length > 0 ? Math.min(...allPrimaryCosts) : baseCostPrimary

  let baseCostSecondary = 0
  let minCostSecondary = 0
  if (allowSecondary) {
    if (pvpSecondaryCosts.length > 0) {
      baseCostSecondary = Math.max(...pvpSecondaryCosts)
    } else {
      baseCostSecondary = Number(product.baseCostSecondary || product.effectiveSecondaryPrice) || 0
    }
    minCostSecondary = allSecondaryCosts.length > 0 ? Math.min(...allSecondaryCosts) : baseCostSecondary
  }

  // Diferencia / Arbitraje de Costo entre el sitio más alto (PVP) y el más barato (Compra)
  const diffPrimary = Math.max(0, baseCostPrimary - minCostPrimary)
  const diffSecondary = Math.max(0, baseCostSecondary - minCostSecondary)
  const diffPrimaryPercent = minCostPrimary > 0 ? Math.round((diffPrimary / minCostPrimary) * 100) : 0
  const diffSecondaryPercent = minCostSecondary > 0 ? Math.round((diffSecondary / minCostSecondary) * 100) : 0
  const providerCount = activeProvidersList.length

  // Margen de ganancia de la tienda (% markup sobre costo) y comisión del revendedor (% de PVP)
  const marginRate = Number(storeMarginPercent || 0) / 100
  const resellerRate = Number(resellerCommissionPercent || 10) / 100
  const minProfit = Number(minResellerProfit || 1000)

  // Precios efectivos visibles en la Web (PVP al Cliente = MAYOR COSTO + Margen Tienda):
  let effectivePrimaryPrice = 0
  let effectiveSecondaryPrice = 0
  const hasCustomPrimary = Boolean(product.customPrimaryPrice && Number(product.customPrimaryPrice) > 0)
  const hasCustomSecondary = Boolean(product.customSecondaryPrice && Number(product.customSecondaryPrice) > 0)

  if (hasCustomPrimary) {
    effectivePrimaryPrice = Number(product.customPrimaryPrice)
  } else if (baseCostPrimary > 0) {
    const raw = baseCostPrimary * (1 + marginRate)
    effectivePrimaryPrice = Math.ceil(raw / 100) * 100
  } else {
    effectivePrimaryPrice = Number(product.effectivePrimaryPrice) || 25000
  }

  if (allowSecondary) {
    if (hasCustomSecondary) {
      effectiveSecondaryPrice = Number(product.customSecondaryPrice)
    } else if (baseCostSecondary > 0) {
      const raw = baseCostSecondary * (1 + marginRate)
      effectiveSecondaryPrice = Math.ceil(raw / 100) * 100
    } else if (Number(product.effectiveSecondaryPrice) > 0) {
      effectiveSecondaryPrice = Number(product.effectiveSecondaryPrice)
    }
  }

  // Enriquecer cada proveedor con ganancia real si se compra ahí
  activeProvidersList.forEach(p => {
    p.realProfitPrimary = Math.max(0, effectivePrimaryPrice - (p.costPrimary || 0))
    p.extraArbitrage = Math.max(0, baseCostPrimary - (p.costPrimary || 0))
    p.isHighestPvpDriver = pvpCandidates.some(pp => pp.key === p.key) && p.costPrimary === baseCostPrimary
    p.isCheapest = p.costPrimary === minCostPrimary
  })

  // Comisión / Ganancia del Revendedor:
  const resellerPrimaryProfit = effectivePrimaryPrice > 0 
    ? Math.max(minProfit, Math.round(effectivePrimaryPrice * resellerRate)) 
    : 0
  const resellerSecondaryProfit = effectiveSecondaryPrice > 0 
    ? Math.max(Math.round(minProfit * 0.75), Math.round(effectiveSecondaryPrice * resellerRate)) 
    : 0

  // Margen Bruto Teórico (PVP - Mayor Costo) y Neto (c/ Revendedor):
  const storeGrossMarginPrimary = Math.max(0, effectivePrimaryPrice - baseCostPrimary)
  const storeGrossMarginSecondary = Math.max(0, effectiveSecondaryPrice - baseCostSecondary)
  const storeNetPrimaryWithReseller = Math.max(0, storeGrossMarginPrimary - resellerPrimaryProfit)
  const storeNetSecondaryWithReseller = Math.max(0, storeGrossMarginSecondary - resellerSecondaryProfit)

  // Proveedor que marca el MAYOR COSTO (para el cálculo de PVP en web):
  const highestProvider = effectivePvpList.find(p => p.costPrimary === baseCostPrimary) || activeProvidersList[activeProvidersList.length - 1]
  const visibleProviderKey = highestProvider?.key || product.visibleProviderKey || 'argentinaplay'
  const visibleProvider = product.providers?.[visibleProviderKey] || pArg || pJda || {}

  // Proveedor MÁS CONVENIENTE para comprar (preferido por admin si está activo, sino el de menor costo):
  const cheapestP = activeProvidersList.find(p => p.costPrimary === minCostPrimary) || activeProvidersList[0]
  const preferredActive = product.preferredFulfillmentProvider
    ? activeProvidersList.find(p => p.key === product.preferredFulfillmentProvider)
    : null

  const chosenBuyer = preferredActive || cheapestP
  const bestFulfillmentPrimary = chosenBuyer ? chosenBuyer.key : (hasArg ? 'argentinaplay' : 'juegosdigitalesargentina')
  const bestFulfillmentPrimaryName = chosenBuyer ? chosenBuyer.name : 'Proveedor Óptimo'

  const actualPurchaseCostPrimary = chosenBuyer ? chosenBuyer.costPrimary : minCostPrimary
  const actualPurchaseCostSecondary = chosenBuyer ? chosenBuyer.costSecondary : minCostSecondary
  const actualStoreProfitPrimary = Math.max(0, effectivePrimaryPrice - actualPurchaseCostPrimary)
  const actualNetProfitWithReseller = Math.max(0, actualStoreProfitPrimary - resellerPrimaryProfit)
  const arbitrageExtraProfit = Math.max(0, baseCostPrimary - actualPurchaseCostPrimary)

  const cheapestSecP = activeProvidersList.find(p => p.costSecondary === minCostSecondary) || chosenBuyer
  const bestFulfillmentSecondary = preferredActive ? preferredActive.key : (cheapestSecP ? cheapestSecP.key : bestFulfillmentPrimary)
  const bestFulfillmentSecondaryName = preferredActive ? preferredActive.name : (cheapestSecP ? cheapestSecP.name : bestFulfillmentPrimaryName)

  const bestMarginPrimary = Math.max(0, effectivePrimaryPrice - minCostPrimary)
  const bestMarginSecondary = Math.max(0, effectiveSecondaryPrice - minCostSecondary)

  // Proveedor que marca el precio más alto en la plataforma
  const primaryPriceLeaderKey = visibleProviderKey
  const secondaryPriceLeaderKey = visibleProviderKey
  const brand = getPublicStoreBrand(product.platform)

  return {
    ...product,
    description: product.description || `Videojuego digital oficial para ${product.platform || 'PlayStation'}. Entrega garantizada y soporte técnico en consola.`,
    features: (Array.isArray(product.features) && product.features.length > 0)
      ? product.features
      : ['Licencia Digital Oficial', 'Entrega Rápida Garantizada', 'Soporte StreamePro'],
    baseCostPrimary,
    baseCostSecondary,
    effectivePrimaryPrice,
    effectiveSecondaryPrice,
    pvpMode: hasCustomPrimary ? 'custom' : 'auto_max',
    resellerPrimaryProfit,
    resellerSecondaryProfit,
    storeGrossMarginPrimary,
    storeGrossMarginSecondary,
    storeNetPrimaryWithReseller,
    storeNetSecondaryWithReseller,
    actualPurchaseCostPrimary,
    actualPurchaseCostSecondary,
    actualStoreProfitPrimary,
    actualNetProfitWithReseller,
    arbitrageExtraProfit,
    storeMarginPercent,
    resellerCommissionPercent,
    primaryPriceLeaderKey,
    primaryPriceLeaderName: brand.name,
    secondaryPriceLeaderKey,
    secondaryPriceLeaderName: brand.name,
    // Proveedor visible público estrictamente anónimo
    visibleProviderKey,
    visibleProviderName: brand.name,
    visibleProviderShortName: brand.shortName,
    visibleProviderBadge: brand.badge,
    visibleProviderTagColor: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
    visibleProvider,
    visibleCostPrimary: visibleProvider.costPrimary || visibleProvider.primaryPrice || baseCostPrimary,
    visibleCostSecondary: visibleProvider.costSecondary || visibleProvider.secondaryPrice || baseCostSecondary,
    visiblePrimaryPrice: visibleProvider.primaryPrice || baseCostPrimary,
    visibleSecondaryPrice: visibleProvider.secondaryPrice || baseCostSecondary,
    visibleProviderUrl: visibleProvider.providerUrl || '',
    bestFulfillmentPrimary,
    bestFulfillmentPrimaryName,
    bestMarginPrimary,
    bestFulfillmentSecondary,
    bestFulfillmentSecondaryName,
    bestMarginSecondary,
    minCostPrimary,
    minCostSecondary,
    cheapestProviderKey: cheapestP?.key || bestFulfillmentPrimary,
    cheapestProviderName: cheapestP?.name || bestFulfillmentPrimaryName || 'Proveedor Óptimo',
    highestProviderKey: highestProvider?.key || visibleProviderKey,
    highestProviderName: highestProvider?.name || 'Mayor Costo Base',
    diffPrimary,
    diffSecondary,
    diffPrimaryPercent,
    diffSecondaryPercent,
    providerCount,
    activeProvidersList,
    similarityScore: providerCount >= 2 ? 100 : 70
  }
}

// ========================================================
// FUNCIONES DE DEDUPLICACIÓN DE JUEGOS (SOLO MAYOR VALOR)
// ========================================================
export function normalizeGameTitle(title) {
  if (!title) return ''
  return title
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s*\((ps5|ps4|ps3|ps4\s*\/\s*ps5|xbox|switch|pc)\)\s*/gi, '')
    .replace(/\b(ps5|ps4|ps3|nintendo\s*switch\s*2?|switch\s*2?|xbox\s*(?:one|series\s*x\/?s?)?|steam|pc|digital|original|primari[oa]|secundari[oa])\b/gi, '')
    .replace(/[^a-z0-9]/gi, '')
    .trim()
}

export function getGamePrice(g) {
  if (!g) return 0
  const p1 = Number(g.effectivePrimaryPrice) || 0
  const p2 = Number(g.effectiveSecondaryPrice) || 0
  const pArg = Number(g.providers?.argentinaplay?.costPrimary || g.providers?.argentinaplay?.primaryPrice) || 0
  const pJda = Number(g.providers?.juegosdigitalesargentina?.costPrimary || g.providers?.juegosdigitalesargentina?.primaryPrice) || 0
  const pDirect = Number(g.price) || 0
  return Math.max(p1, p2, pArg, pJda, pDirect)
}

export function deduplicateGamingProducts(productsList) {
  if (!Array.isArray(productsList) || productsList.length === 0) return []
  
  const map = new Map()

  productsList.forEach(item => {
    const bucket = getPlatformBucket(item.platform, item.title)
    const clean = normalizeGameTitle(item.title) || normalizeGameTitle(item.slug) || String(item.id)
    const norm = `${bucket}::${clean}`
    const itemPrice = getGamePrice(item)

    if (!map.has(norm)) {
      map.set(norm, { ...item, _maxPrice: itemPrice })
    } else {
      const existing = map.get(norm)
      const existingPrice = existing._maxPrice

      // Combinar plataformas dentro del mismo bucket (ej. PS4 + PS5)
      let mergedPlatform = existing.platform || item.platform || 'PS4 / PS5'
      if (bucket === 'ps') {
        const platformsSet = new Set()
        ;[existing.platform, item.platform].forEach(p => {
          if (!p) return
          if (p.includes('PS5')) platformsSet.add('PS5')
          if (p.includes('PS4')) platformsSet.add('PS4')
        })
        if (platformsSet.has('PS4') && platformsSet.has('PS5')) {
          mergedPlatform = 'PS4 / PS5'
        } else if (platformsSet.size > 0) {
          mergedPlatform = Array.from(platformsSet).join(' / ')
        }
      }

      // Combinar TODOS los proveedores scrapeados y listas sin descartar ninguno
      const mergedProviders = { ...(existing.providers || {}) }
      if (item.providers && typeof item.providers === 'object') {
        for (const [pKey, pVal] of Object.entries(item.providers)) {
          mergedProviders[pKey] = {
            ...(mergedProviders[pKey] || {}),
            ...(pVal || {})
          }
        }
      }

      // Ganador: EXCLUSIVAMENTE EL DE MAYOR VALOR
      if (itemPrice > existingPrice) {
        map.set(norm, {
          ...existing,
          ...item,
          platform: mergedPlatform,
          providers: mergedProviders,
          _maxPrice: itemPrice
        })
      } else {
        map.set(norm, {
          ...item,
          ...existing,
          platform: mergedPlatform,
          providers: mergedProviders,
          _maxPrice: existingPrice
        })
      }
    }
  })

  return Array.from(map.values()).map(g => {
    const { _maxPrice, ...rest } = g
    return rest
  })
}
