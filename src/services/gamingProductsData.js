// Catálogo Oficial Extendido de Videojuegos Digitales PS4 / PS5
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
        costPrimary: 27500,
        costSecondary: 14500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 41000,
        secondaryPrice: 19500,
        costPrimary: 29000,
        costSecondary: 13500,
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
        costPrimary: 25500,
        costSecondary: 13000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 38500,
        secondaryPrice: 18200,
        costPrimary: 27000,
        costSecondary: 12500,
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
        costPrimary: 14000,
        costSecondary: 6500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 21500,
        secondaryPrice: 8900,
        costPrimary: 15000,
        costSecondary: 6000,
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
        costPrimary: 22500,
        costSecondary: 11000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 34000,
        secondaryPrice: 15500,
        costPrimary: 24000,
        costSecondary: 10500,
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
        costPrimary: 24500,
        costSecondary: 12000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 36900,
        secondaryPrice: 16900,
        costPrimary: 26000,
        costSecondary: 11500,
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
        costPrimary: 13000,
        costSecondary: 6500,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 19900,
        secondaryPrice: 8900,
        costPrimary: 14000,
        costSecondary: 6000,
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
        costPrimary: 20000,
        costSecondary: 9800,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 30500,
        secondaryPrice: 13900,
        costPrimary: 21500,
        costSecondary: 9200,
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
        costPrimary: 21500,
        costSecondary: 11000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 32900,
        secondaryPrice: 15500,
        costPrimary: 23000,
        costSecondary: 10500,
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
        costPrimary: 42000,
        costSecondary: 23000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 62000,
        secondaryPrice: 30000,
        costPrimary: 44000,
        costSecondary: 21500,
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
        costPrimary: 30000,
        costSecondary: 17000,
        stock: 'Inmediato'
      },
      juegosdigitalesargentina: {
        providerName: 'Juegos Digitales Argentina',
        providerUrl: 'https://juegosdigitalesargentina.com/',
        available: true,
        primaryPrice: 45000,
        secondaryPrice: 22000,
        costPrimary: 32000,
        costSecondary: 15500,
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
    primaryPriceLeaderName: 'StreamePro',
    secondaryPriceLeaderKey,
    secondaryPriceLeaderName: 'StreamePro',
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
