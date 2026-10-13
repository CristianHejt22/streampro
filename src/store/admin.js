import { defineStore } from 'pinia'
import physicalCategoriesData from '../assets/physicalCategories.json'
import physicalProductsData from '../assets/physicalProducts.json'

// Configuración de Precios Sincronizados entre Proveedor, Revendedor y Cliente
export const PLATFORM_PLANS = {
  Netflix: {
    name: 'Netflix Premium Ultra HD 4K',
    plans: {
      '1_perfil': {
        label: '1 Perfil (1 Dispositivo)',
        units: 1,
        type: 'perfil',
        providerCost: 5500,
        resellerProfit: 2200,
        clientPrice: 7700
      },
      '2_dispositivos': {
        label: '2 Dispositivos simultáneos',
        units: 2,
        type: 'dispositivo',
        providerCost: 10000,
        resellerProfit: 3000,
        clientPrice: 13000
      },
      '3_dispositivos': {
        label: '3 Dispositivos simultáneos',
        units: 3,
        type: 'dispositivo',
        providerCost: 14500,
        resellerProfit: 4000,
        clientPrice: 18500
      },
      '4_dispositivos': {
        label: '4 Dispositivos simultáneos (Cuenta Completa)',
        units: 4,
        type: 'dispositivo',
        providerCost: 18500,
        resellerProfit: 5500,
        clientPrice: 24000
      }
    }
  },
  Max: {
    name: 'Max Platino Estándar',
    plans: {
      '1_perfil': {
        label: '1 Perfil individual',
        units: 1,
        type: 'perfil',
        providerCost: 4200,
        resellerProfit: 1800,
        clientPrice: 6000
      },
      '2_dispositivos': {
        label: '2 Dispositivos simultáneos',
        units: 2,
        type: 'dispositivo',
        providerCost: 7800,
        resellerProfit: 2700,
        clientPrice: 10500
      },
      '3_dispositivos': {
        label: '3 Dispositivos simultáneos',
        units: 3,
        type: 'dispositivo',
        providerCost: 11000,
        resellerProfit: 3500,
        clientPrice: 14500
      },
      '4_dispositivos': {
        label: '4 Dispositivos (Familia)',
        units: 4,
        type: 'dispositivo',
        providerCost: 14000,
        resellerProfit: 4500,
        clientPrice: 18500
      }
    }
  },
  Spotify: {
    name: 'Spotify Familiar Individual',
    plans: {
      '1_perfil': {
        label: '1 Slot / Perfil individual',
        units: 1,
        type: 'perfil',
        providerCost: 2500,
        resellerProfit: 1300,
        clientPrice: 3800
      },
      '2_dispositivos': {
        label: '2 Slots Familiares',
        units: 2,
        type: 'dispositivo',
        providerCost: 4800,
        resellerProfit: 2000,
        clientPrice: 6800
      },
      '4_dispositivos': {
        label: '4 Slots Familiares',
        units: 4,
        type: 'dispositivo',
        providerCost: 9000,
        resellerProfit: 3500,
        clientPrice: 12500
      }
    }
  },
  'Disney+': {
    name: 'Disney+ / Star+ Combo',
    plans: {
      '1_perfil': {
        label: '1 Perfil individual',
        units: 1,
        type: 'perfil',
        providerCost: 4500,
        resellerProfit: 1800,
        clientPrice: 6300
      },
      '2_dispositivos': {
        label: '2 Dispositivos simultáneos',
        units: 2,
        type: 'dispositivo',
        providerCost: 8200,
        resellerProfit: 2600,
        clientPrice: 10800
      },
      '4_dispositivos': {
        label: '4 Dispositivos simultáneos',
        units: 4,
        type: 'dispositivo',
        providerCost: 15000,
        resellerProfit: 4500,
        clientPrice: 19500
      }
    }
  },
  IPTV: {
    name: 'IPTV MagisTV Pro',
    plans: {
      '1_perfil': {
        label: '1 Dispositivo / Pantalla',
        units: 1,
        type: 'dispositivo',
        providerCost: 3500,
        resellerProfit: 2000,
        clientPrice: 5500
      },
      '2_dispositivos': {
        label: '2 Dispositivos simultáneos',
        units: 2,
        type: 'dispositivo',
        providerCost: 6500,
        resellerProfit: 3000,
        clientPrice: 9500
      },
      '3_dispositivos': {
        label: '3 Dispositivos simultáneos',
        units: 3,
        type: 'dispositivo',
        providerCost: 9000,
        resellerProfit: 4000,
        clientPrice: 13000
      }
    }
  }
}

export const useAdminStore = defineStore('admin', {
  state: () => ({
    pricingCatalog: PLATFORM_PLANS,
    // Revendedores registrados con usuario, contraseña, estado de activación y datos bancarios
    resellers: [
      { 
        id: 'RES-01', 
        name: 'TechStream Digital', 
        storeName: 'TechStream Digital',
        username: 'techstream',
        password: 'TechStream2026*',
        isActive: true,
        contactNumber: '+54 9 11 3344-5566',
        cbuAlias: 'TECHSTREAM.PAGOS.MP',
        bankName: 'Mercado Pago / Banco Santander',
        holderName: 'Lucas R. Distribuciones',
        cuit: '20-38491029-4',
        balance: 15000,
        walletHistory: [
          { date: '2026-10-10 12:00', amount: 15000, reason: 'Saldo Inicial Mayorista', receipt: 'TRANSF-100293' }
        ]
      },
      { 
        id: 'RES-02', 
        name: 'StreamLatam Oficial', 
        storeName: 'StreamLatam Oficial',
        username: 'streamlatam',
        password: 'LatamStream2026*',
        isActive: true,
        contactNumber: '+54 9 351 9988-7711',
        cbuAlias: 'STREAM.LATAM.OFICIAL',
        bankName: 'Banco Galicia',
        holderName: 'Federico M. Servicios',
        cuit: '27-41092834-8',
        balance: 8500,
        walletHistory: [
          { date: '2026-10-10 16:30', amount: 8500, reason: 'Saldo Inicial Mayorista', receipt: 'TRANSF-882910' }
        ]
      },
      { 
        id: 'RES-03', 
        name: 'MundoPro Streaming', 
        storeName: 'MundoPro Streaming',
        username: 'mundopro',
        password: 'Mundo2026*',
        isActive: true,
        contactNumber: '+54 9 261 4455-2233',
        cbuAlias: 'MUNDOPRO.STREAM.MP',
        bankName: 'Mercado Pago / Ualá',
        holderName: 'Agustina V. Medios',
        cuit: '27-39201928-3',
        balance: 5000,
        walletHistory: [
          { date: '2026-10-11 10:00', amount: 5000, reason: 'Saldo Inicial Mayorista', receipt: 'TRANSF-441092' }
        ]
      }
    ],
    // Historial global de transacciones de billetera cargadas desde Admin
    walletTransactions: [
      { id: 'WT-01', resellerId: 'RES-01', resellerName: 'TechStream Digital', amount: 15000, reason: 'Saldo Inicial Mayorista', receipt: 'TRANSF-100293', date: '2026-10-10 12:00' },
      { id: 'WT-02', resellerId: 'RES-02', resellerName: 'StreamLatam Oficial', amount: 8500, reason: 'Saldo Inicial Mayorista', receipt: 'TRANSF-882910', date: '2026-10-10 16:30' }
    ],
    // Datos de transferencia bancaria oficial del PROVEEDOR (para que el revendedor pague sus compras o muestre a sus clientes)
    providerBankInfo: {
      holderName: 'StreamPro Mayorista Central S.R.L.',
      bankName: 'Banco Macro / BMA',
      cuit: '30-71928374-2',
      cbuAlias: 'STREAMPRO.MAYORISTA.OFICIAL',
      cbuNumber: '2850590940090418820193',
      whatsapp: '+54 9 11 5566-7788',
      email: 'pagos.mayoristas@streampro.com'
    },
    // Alertas emitidas por el Proveedor hacia los Revendedores (por eventualidades o novedades de clientes)
    resellerAlerts: [
      {
        id: 'ALT-101',
        resellerId: 'RES-01',
        clientId: 'C001',
        clientNumber: 'CLI-8492',
        clientName: 'Juan Perez',
        type: 'password_update', // 'password_update' | 'incident' | 'general'
        title: 'Contraseña Actualizada por Proveedor',
        message: 'Se actualizó la contraseña para Juan Perez (CLI-8492). Nueva contraseña activa en sistema.',
        date: '2026-10-11 14:20',
        read: false,
        statusText: 'YA FUE AVISADO'
      }
    ],
    // Solicitudes de Renovación emitidas por clientes (pueden cambiar credenciales y notificar al revendedor)
    renewalRequests: [
      {
        id: 'REN-801',
        clientId: 'C002',
        clientNumber: 'CLI-9024',
        clientName: 'Maria Gomez',
        platform: 'Netflix',
        planName: '2 Dispositivos simultáneos',
        resellerId: 'RES-01',
        status: 'Pending Provider', // 'Pending Provider' | 'Provider Updated' | 'Confirmed By Reseller' | 'Renovada (Misma Cuenta)'
        dateRequested: '2026-10-11',
        newCredentials: {
          account: 'netflix.master.02@streampro.com',
          pass: 'StreamNx2026*',
          profiles: [
            { name: 'DISPOSITIVO 1 - LIVING', pin: '8810' },
            { name: 'DISPOSITIVO 2 - DORMITORIO', pin: '8810' }
          ]
        }
      }
    ],
    inventory: [
      { 
        id: 'INV-001', 
        platform: 'Netflix', 
        account: 'netflix.master.01@streampro.com', 
        pass: 'NetMaster99#', // Oculta al revendedor, solo vista por proveedor
        status: 'Available', 
        cost: 18500, 
        maxProfiles: 4,
        profiles: [
          { name: 'PERFIL 1 - LIVING', pin: '1122', isAssigned: true, assignedTo: 'Carlos Lopez (CLI-8492)' },
          { name: 'PERFIL 2 - DORMITORIO', pin: '4455', isAssigned: false, assignedTo: null },
          { name: 'PERFIL 3 - PLAYROOM', pin: '8899', isAssigned: false, assignedTo: null },
          { name: 'PERFIL 4 - KIDS', pin: '0000', isAssigned: false, assignedTo: null }
        ]
      },
      { 
        id: 'INV-002', 
        platform: 'Netflix', 
        account: 'netflix.master.02@streampro.com', 
        pass: 'StreamNx2026*', 
        status: 'Available', 
        cost: 18500, 
        maxProfiles: 4,
        profiles: [
          { name: '', pin: '3311', isAssigned: false, assignedTo: null },
          { name: '', pin: '7722', isAssigned: false, assignedTo: null },
          { name: '', pin: '9944', isAssigned: false, assignedTo: null },
          { name: '', pin: '1234', isAssigned: false, assignedTo: null }
        ]
      },
      { 
        id: 'INV-003', 
        platform: 'Max', 
        account: 'max.master@streampro.com', 
        pass: 'M4xUltra2026', 
        status: 'Available', 
        cost: 14000, 
        maxProfiles: 4,
        profiles: [
          { name: 'PERFIL 1 - MAX HD', pin: '7788', isAssigned: false, assignedTo: null },
          { name: 'PERFIL 2 - CINE', pin: '5522', isAssigned: false, assignedTo: null },
          { name: 'PERFIL 3 - KIDS', pin: '9900', isAssigned: false, assignedTo: null },
          { name: 'PERFIL 4 - SERIES', pin: '2345', isAssigned: false, assignedTo: null }
        ]
      },
      { 
        id: 'INV-004', 
        platform: 'Spotify', 
        account: 'spotify.family.vip@gmail.com', 
        pass: 'Sp0tyFam!2026', 
        status: 'Available', 
        cost: 9000, 
        maxProfiles: 6,
        profiles: [
          { name: 'SLOT 1 - PRINCIPAL', pin: 'N/A', isAssigned: false, assignedTo: null },
          { name: 'SLOT 2 - MIEMBRO 1', pin: 'N/A', isAssigned: false, assignedTo: null },
          { name: 'SLOT 3 - MIEMBRO 2', pin: 'N/A', isAssigned: false, assignedTo: null },
          { name: 'SLOT 4 - MIEMBRO 3', pin: 'N/A', isAssigned: false, assignedTo: null },
          { name: 'SLOT 5 - MIEMBRO 4', pin: 'N/A', isAssigned: false, assignedTo: null },
          { name: 'SLOT 6 - MIEMBRO 5', pin: 'N/A', isAssigned: false, assignedTo: null }
        ]
      },
      { 
        id: 'INV-005', 
        platform: 'Disney+', 
        account: 'disney.vip.account@gmail.com', 
        pass: 'D1sn3yStar!26', 
        status: 'Available', 
        cost: 15000, 
        maxProfiles: 4,
        profiles: [
          { name: 'PERFIL 1 - VIP', pin: '3322', isAssigned: false, assignedTo: null },
          { name: 'PERFIL 2 - MARVEL', pin: '4411', isAssigned: false, assignedTo: null },
          { name: 'PERFIL 3 - STAR WARS', pin: '7766', isAssigned: false, assignedTo: null },
          { name: 'PERFIL 4 - KIDS', pin: '0000', isAssigned: false, assignedTo: null }
        ]
      },
      { 
        id: 'INV-006', 
        platform: 'IPTV', 
        account: 'iptv.magistv.user99@iptv.server', 
        pass: 'M4g1sTvPro!', 
        status: 'Available', 
        cost: 9000, 
        maxProfiles: 3,
        profiles: [
          { name: 'PANTALLA 1 - SALA', pin: '1111', isAssigned: false, assignedTo: null },
          { name: 'PANTALLA 2 - PIEZA', pin: '2222', isAssigned: false, assignedTo: null },
          { name: 'PANTALLA 3 - TV 4K', pin: '3333', isAssigned: false, assignedTo: null }
        ]
      }
    ],
    orders: [
      { 
        id: 'ORD-100', 
        customerNumber: 'CLI-8492',
        customerName: 'Carlos Lopez', 
        customerPhone: '+54 9 11 4455-8899',
        platform: 'Netflix', 
        devices: 1,
        pinPreference: 'same',
        planKey: '1_perfil',
        planLabel: '1 Perfil (1 Dispositivo)',
        providerCost: 5500,
        resellerProfit: 2200,
        clientPrice: 7700,
        profileSelections: [
          { name: 'PERFIL 1 - SALA TV' }
        ],
        status: 'Waiting Provider', 
        resellerId: 'RES-01',
        createdAt: '18:30'
      },
      { 
        id: 'ORD-101', 
        customerNumber: 'CLI-9024',
        customerName: 'Sofia Gomez', 
        customerPhone: '+54 9 11 9988-1122',
        platform: 'Netflix', 
        devices: 2,
        pinPreference: 'same',
        planKey: '2_dispositivos',
        planLabel: '2 Dispositivos simultáneos',
        providerCost: 10000,
        resellerProfit: 3000,
        clientPrice: 13000,
        profileSelections: [
          { name: 'DISPOSITIVO 1 - LIVING' },
          { name: 'DISPOSITIVO 2 - DORMITORIO' }
        ],
        status: 'Waiting Provider', 
        resellerId: 'RES-01',
        createdAt: '19:15'
      }
    ],
    // CATÁLOGO Y PEDIDOS DE PRODUCTOS FÍSICOS (C:\Users\yo\catalogo)
    physicalProducts: physicalProductsData.map(p => ({
      ...p,
      image: p.image?.startsWith('http') ? p.image : `https://tua.ar/${p.image}`,
      priceNumeric: parseInt(p.price?.replace(/[$. ]/g, '').replace(',', '') || '0') / 100 || 0
    })),
    physicalCategories: physicalCategoriesData.filter(c => parseInt(c.cantidad || '0') > 0),
    physicalOrders: [
      {
        id: 'PED-FIS-101',
        customerNumber: 'CLI-9024',
        customerName: 'Maria Gomez',
        customerPhone: '+54 9 11 9988-1122',
        shippingAddress: 'Av. Corrientes 2450, Piso 4 B, CABA',
        resellerId: 'RES-01',
        resellerName: 'TechStream Digital',
        source: 'shared_url', // 'shared_url' (3% comisión) o 'store' (1% comisión)
        commissionRate: 0.03, // 3%
        commissionAmount: 540,
        totalAmount: 18000,
        paymentMethod: 'Transferencia Bancaria',
        status: 'Pendiente', // 'Pendiente' | 'Recibido' | 'Confirmado' | 'Cancelado'
        statusNote: 'Pendiente: No realices transferencias aún. Un asesor oficial se comunicará contigo por WhatsApp.',
        createdAt: '2026-10-12 11:20',
        items: [
          {
            id: '962',
            name: 'Máquina Cortadora De Pelo Barba Recargable Inalámbrica Usb',
            price: '$18.000,64',
            priceNumeric: 18000,
            image: 'https://tua.ar/rest/web/images/20241011024157-2945.jpg',
            quantity: 1
          }
        ]
      }
    ],
    // PAQUETES, OFERTAS Y COMBOS ARMADOS (PROVEEDOR & REVENDEDORES)
    packages: [
    {
      id: 'PKG-101',
      title: 'Combo Cine Total 4K (Netflix + Disney+ + Max)',
      description: 'Las 3 plataformas líderes de series, cine y estrenos en calidad Ultra HD.',
      badge: '🔥 25% OFF - RECOMENDADO',
      services: [
        { platform: 'Netflix', planKey: '1_perfil', planLabel: '1 Pantalla 4K', units: 1, regularPrice: 7700, cost: 5500 },
        { platform: 'Max', planKey: '1_perfil', planLabel: '1 Pantalla Platino', units: 1, regularPrice: 6000, cost: 4200 },
        { platform: 'Disney+', planKey: '1_perfil', planLabel: '1 Pantalla VIP', units: 1, regularPrice: 6500, cost: 4500 }
      ],
      originalPrice: 20200,
      promoPrice: 15900,
      providerCost: 14200,
      resellerProfit: 1700,
      createdBy: 'provider',
      resellerId: 'ALL',
      active: true,
      createdAt: '2026-10-10'
    },
    {
      id: 'PKG-102',
      title: 'Dúo Series & Música (Netflix + Spotify)',
      description: 'Tus series favoritas en Ultra HD y toda la música del mundo sin publicidad.',
      badge: '⚡ COMBO POPULAR',
      services: [
        { platform: 'Netflix', planKey: '1_perfil', planLabel: '1 Pantalla 4K', units: 1, regularPrice: 7700, cost: 5500 },
        { platform: 'Spotify', planKey: '1_perfil', planLabel: '1 Slot Familiar', units: 1, regularPrice: 3800, cost: 2500 }
      ],
      originalPrice: 11500,
      promoPrice: 9400,
      providerCost: 8000,
      resellerProfit: 1400,
      createdBy: 'provider',
      resellerId: 'ALL',
      active: true,
      createdAt: '2026-10-11'
    },
    {
      id: 'PKG-103',
      title: 'Pack Deportes & Música (IPTV + Spotify)',
      description: '+1200 canales en vivo de deportes, fútbol y eventos premium + Spotify sin límites.',
      badge: '👑 COMBO DEPORTIVO VIP',
      services: [
        { platform: 'IPTV', planKey: '1_perfil', planLabel: '1 Conexión FHD', units: 1, regularPrice: 5000, cost: 3500 },
        { platform: 'Spotify', planKey: '1_perfil', planLabel: '1 Slot', units: 1, regularPrice: 3800, cost: 2500 }
      ],
      originalPrice: 8800,
      promoPrice: 6900,
      providerCost: 6000,
      resellerProfit: 900,
      createdBy: 'reseller',
      resellerId: 'RES-01',
      active: true,
      createdAt: '2026-10-12'
    }
  ]
}),
  getters: {
    totalInvestment: (state) => state.inventory.reduce((acc, item) => acc + item.cost, 0),
    totalProviderRevenue: (state) => state.orders.filter(o => o.status === 'Dispatched').reduce((acc, o) => acc + (o.providerCost || 0), 0),
    availableStock: (state) => state.inventory.filter(i => i.status === 'Available').length,
    pendingOrders: (state) => state.orders.filter(o => o.status === 'Waiting Provider'),
    pendingRenewals: (state) => state.renewalRequests.filter(r => r.status === 'Pending Provider'),
    pendingPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Pendiente'),
    receivedPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Recibido'),
    confirmedPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Confirmado' || o.status === 'Aprobado'),
    approvedPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Confirmado' || o.status === 'Aprobado'),
    cancelledPhysicalOrders: (state) => state.physicalOrders.filter(o => o.status === 'Cancelado')
  },
  actions: {
    addStock(item) {
      const generatedProfiles = item.profiles && item.profiles.length > 0 
        ? item.profiles.map(p => ({
            name: p.name || '',
            pin: p.pin || String(Math.floor(1000 + Math.random() * 9000)),
            isAssigned: false,
            assignedTo: null
          }))
        : Array.from({ length: item.maxProfiles || 4 }, (_, idx) => ({
            name: '',
            pin: String(Math.floor(1000 + Math.random() * 9000)),
            isAssigned: false,
            assignedTo: null
          }))

      this.inventory.push({
        id: `INV-${Date.now().toString().slice(-4)}`,
        status: 'Available',
        maxProfiles: item.maxProfiles || 4,
        profiles: generatedProfiles,
        ...item
      })
    },
    // Cambios de contraseña exclusivos del Proveedor: No son vistos por el Revendedor hasta una nueva compra o reemplazo
    updateMasterPassword(invId, newPassword) {
      const inv = this.inventory.find(i => i.id === invId)
      if (inv) {
        inv.pass = newPassword
        inv.lastPasswordUpdate = new Date().toISOString()
      }
    },
    // Solicitud de Renovación creada por Cliente o Revendedor hacia el Proveedor
    requestRenewal({ clientId, clientNumber, clientName, platform, planName, resellerId, currentCredentials = null }) {
      const renewalId = `REN-${Date.now().toString().slice(-4)}`
      const newRenewal = {
        id: renewalId,
        clientId: clientId || `C-${Date.now().toString().slice(-3)}`,
        clientNumber: clientNumber || 'CLI-0000',
        clientName: clientName || 'Cliente',
        platform: platform || 'Streaming',
        planName: planName || 'Plan Oficial',
        resellerId: resellerId || 'RES-01',
        status: 'Pending Provider', // 'Pending Provider' | 'Provider Updated' | 'Confirmed By Reseller'
        dateRequested: new Date().toISOString().split('T')[0],
        newCredentials: {
          account: currentCredentials?.account || 'cuenta.por.renovar@streampro.com',
          pass: currentCredentials?.pass || 'StreamRenov2026*',
          profiles: currentCredentials?.profiles ? JSON.parse(JSON.stringify(currentCredentials.profiles)) : [
            { name: 'PERFIL 1', pin: '1234' }
          ]
        }
      }
      this.renewalRequests.unshift(newRenewal)
      return { success: true, renewal: newRenewal }
    },
    // Proveedor renueva la MISMA CUENTA (+días) manteniendo el mismo correo, contraseña y perfiles
    renewSameAccount(renewalId, days = 30) {
      const req = this.renewalRequests.find(r => r.id === renewalId)
      if (req) {
        req.status = 'Renovada (Misma Cuenta)'
        const resellerStore = useResellerStore()
        const client = resellerStore.clients.find(c => c.id === req.clientId)
        if (client) {
          client.status = 'Activa'
          const curExp = new Date(client.expiry)
          const baseDate = isNaN(curExp.getTime()) || curExp < new Date() ? new Date() : curExp
          baseDate.setDate(baseDate.getDate() + Number(days))
          client.expiry = baseDate.toISOString().split('T')[0]
          client.notes = `Misma cuenta renovada (+${days} días) el ${new Date().toLocaleDateString()}. Credenciales mantenidas.`
        }

        // Emitir alerta al revendedor informando la renovación
        this.sendAlertToReseller({
          resellerId: req.resellerId,
          clientId: req.clientId,
          clientNumber: req.clientNumber,
          clientName: req.clientName,
          type: 'general',
          title: `Renovación Confirmada: Misma Cuenta (+${days} días)`,
          message: `La cuenta de ${req.clientName} (${req.platform}) fue renovada con éxito por ${days} días más manteniendo las credenciales de acceso.`
        })

        return { success: true, req }
      }
      return { error: 'Solicitud no encontrada' }
    },
    // Proveedor actualiza datos de una renovación (puede cambiar correo o clave y perfiles)
    updateRenewalByProvider(renewalId, updatedAccountData) {
      const req = this.renewalRequests.find(r => r.id === renewalId)
      if (req) {
        req.status = 'Provider Updated'
        req.newCredentials = {
          account: updatedAccountData.account,
          pass: updatedAccountData.pass,
          profiles: updatedAccountData.profiles
        }

        // Emitir alerta formal al revendedor para que confirme y entregue las nuevas credenciales
        this.sendAlertToReseller({
          resellerId: req.resellerId,
          clientId: req.clientId,
          clientNumber: req.clientNumber,
          clientName: req.clientName,
          type: 'password_update',
          title: `Nuevas Credenciales de Renovación para ${req.clientName}`,
          message: `El Proveedor asignó nueva cuenta para ${req.clientName} (${req.platform}): ${updatedAccountData.account}. Confirma en tu panel para activar.`
        })
      }
    },
    // Despacho inteligente con control anti-sobreventa
    dispatchOrderWithInventory(orderId, inventoryId, customMatches = null) {
      const order = this.orders.find(o => o.id === orderId)
      const inv = this.inventory.find(i => i.id === inventoryId)
      
      if (!order || !inv) return null

      const freeSlots = inv.profiles.filter(p => !p.isAssigned)
      if (freeSlots.length < order.devices) {
        return { error: `La cuenta solo dispone de ${freeSlots.length} pantalla(s) libre(s). Se requieren ${order.devices}.` }
      }

      let deliveredProfiles = []
      if (customMatches && customMatches.length > 0) {
        deliveredProfiles = customMatches
      } else {
        const basePin = freeSlots[0]?.pin || String(Math.floor(1000 + Math.random() * 9000))
        deliveredProfiles = order.profileSelections.map((clientReq, idx) => {
          const slot = freeSlots[idx]
          const pinToUse = order.pinPreference === 'same' 
            ? basePin 
            : (slot?.pin || String(Math.floor(1000 + Math.random() * 9000)))

          return {
            name: clientReq.name || `PERFIL ${idx + 1}`,
            pin: pinToUse
          }
        })
      }

      let matchIdx = 0
      inv.profiles.forEach(p => {
        if (!p.isAssigned && matchIdx < deliveredProfiles.length) {
          p.isAssigned = true
          p.assignedTo = `${order.customerName} (${order.customerNumber})`
          p.name = deliveredProfiles[matchIdx].name
          p.pin = deliveredProfiles[matchIdx].pin
          matchIdx++
        }
      })

      const remainingFree = inv.profiles.filter(p => !p.isAssigned).length
      if (remainingFree === 0) {
        inv.status = 'Fully Assigned'
      }

      order.status = 'Dispatched'
      order.accountId = inv.id
      order.accountDetails = {
        email: inv.account,
        pass: inv.pass, // Se le entrega al cliente en este momento
        platform: inv.platform,
        devicesCount: order.devices,
        profiles: deliveredProfiles
      }

      const resellerStore = useResellerStore()
      resellerStore.addDeliveredOrderToClients(order)

      return { success: true, order }
    },
    // Administrar Revendedores desde el Panel Proveedor
    addReseller(resellerData) {
      const newId = `RES-${String(this.resellers.length + 1).padStart(2, '0')}`
      const baseUsername = resellerData.username?.trim() || `revendedor${this.resellers.length + 1}`
      const basePassword = resellerData.password?.trim() || `Stream${Math.floor(1000 + Math.random() * 9000)}*`
      
      this.resellers.push({
        id: newId,
        name: resellerData.name,
        username: baseUsername,
        password: basePassword,
        isActive: resellerData.isActive !== false,
        contactNumber: resellerData.contactNumber || '+54 9 11 0000-0000',
        cbuAlias: resellerData.cbuAlias || 'REVENDEDOR.ALIAS.MP',
        bankName: resellerData.bankName || 'Banco / Billetera Virtual',
        holderName: resellerData.holderName || resellerData.name,
        cuit: resellerData.cuit || '20-00000000-0',
        balance: Number(resellerData.balance || 0),
        walletHistory: []
      })
    },
    updateReseller(resellerId, updatedData) {
      const res = this.resellers.find(r => r.id === resellerId)
      if (res) {
        Object.assign(res, updatedData)
      }
    },
    // ACTIVACIÓN DE USUARIO Y CONTRASEÑA PARA REVENDEDORES DESDE ADMIN
    updateResellerAuth(resellerId, { username, password, isActive }) {
      const res = this.resellers.find(r => r.id === resellerId)
      if (res) {
        if (username) res.username = username.trim()
        if (password) res.password = password.trim()
        if (isActive !== undefined) res.isActive = Boolean(isActive)
        
        // Si se deshabilita, desconectar sesión si está activa
        const resellerStore = useResellerStore()
        if (resellerStore.activeResellerId === resellerId && !res.isActive) {
          resellerStore.logout()
        }
        return { success: true, reseller: res }
      }
      return { error: 'Revendedor no encontrado' }
    },
    toggleResellerStatus(resellerId) {
      const res = this.resellers.find(r => r.id === resellerId)
      if (res) {
        res.isActive = !res.isActive
        const resellerStore = useResellerStore()
        if (resellerStore.activeResellerId === resellerId && !res.isActive) {
          resellerStore.logout()
        }
        return { success: true, isActive: res.isActive }
      }
      return { error: 'Revendedor no encontrado' }
    },
    // CARGAR BILLETERA DE REVENDEDOR DESDE EL PANEL DE ADMINISTRACIÓN / PROVEEDOR
    rechargeResellerWallet(resellerId, amount, reason = 'Recarga autorizada por Administración', receiptNumber = '') {
      const res = this.resellers.find(r => r.id === resellerId)
      if (res) {
        const numAmount = Number(amount)
        res.balance = (res.balance || 0) + numAmount
        const receiptCode = receiptNumber || `REC-${Date.now().toString().slice(-6)}`
        const dateStr = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
        
        const tx = {
          id: `WT-${Date.now().toString().slice(-4)}`,
          resellerId,
          resellerName: res.name,
          amount: numAmount,
          reason,
          receipt: receiptCode,
          date: dateStr,
          balanceAfter: res.balance
        }

        if (!res.walletHistory) res.walletHistory = []
        res.walletHistory.unshift(tx)
        this.walletTransactions.unshift(tx)

        // Si el revendedor activo es el que se está recargando, sincronizar su wallet
        const resellerStore = useResellerStore()
        if (resellerStore.activeResellerId === resellerId) {
          resellerStore.wallet = res.balance
        }

        // Emitir alerta automática al revendedor informándole la recarga con [YA FUE AVISADO]
        this.sendAlertToReseller({
          resellerId,
          clientId: null,
          clientNumber: 'BILLETERA',
          clientName: res.name,
          type: 'general',
          title: `Carga de Saldo en Billetera: +$${numAmount} ARS`,
          message: `El Administrador acreditó $${numAmount} ARS a tu Billetera (Comprobante: ${receiptCode}). Motivo: ${reason}. Saldo actual: $${res.balance} ARS.`
        })

        return { success: true, newBalance: res.balance, tx }
      }
      return { error: 'Revendedor no encontrado' }
    },
    // Proveedor emite alerta formal al revendedor por cualquier eventualidad con un cliente
    sendAlertToReseller({ resellerId, clientId, clientNumber, clientName, title, message, type = 'general' }) {
      this.resellerAlerts.unshift({
        id: `ALT-${Date.now().toString().slice(-4)}`,
        resellerId,
        clientId,
        clientNumber,
        clientName,
        type,
        title,
        message,
        date: new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }),
        read: false,
        statusText: 'YA FUE AVISADO'
      })
    },
    // Borrar o cerrar notificación de forma persistente
    dismissAlert(alertId) {
      const idx = this.resellerAlerts.findIndex(a => a.id === alertId)
      if (idx !== -1) {
        this.resellerAlerts.splice(idx, 1)
        return true
      }
      return false
    },
    // Limpiar todas las alertas de un revendedor
    clearResellerAlerts(resellerId = null) {
      if (!resellerId || resellerId === 'All') {
        this.resellerAlerts = []
      } else {
        this.resellerAlerts = this.resellerAlerts.filter(a => a.resellerId !== resellerId)
      }
    },
    // Si el proveedor cambió contraseña, envía actualización de contraseña a clientes con suscripciones ACTIVAS
    // Excluyendo cuentas por vencer si no fueron cerradas sesiones ni solicitadas
    broadcastPasswordUpdateToActiveClients(accountEmail, newPassword, reason = 'Actualización preventiva de seguridad') {
      const resellerStore = useResellerStore()
      let updatedCount = 0
      
      resellerStore.clients.forEach(client => {
        // Solo para cuentas con el mismo correo y estado ACTIVA (no por vencer ni vencidas sin solicitud)
        if (client.account.toLowerCase() === accountEmail.toLowerCase() && client.status === 'Activa') {
          client.pass = newPassword
          client.notes = `Contraseña actualizada por Proveedor el ${new Date().toLocaleDateString()}. Motivo: ${reason}`
          updatedCount++
          
          // Registrar alerta automática para el revendedor con etiqueta 'YA FUE AVISADO'
          this.sendAlertToReseller({
            resellerId: client.resellerId,
            clientId: client.id,
            clientNumber: client.clientNumber,
            clientName: client.name,
            type: 'password_update',
            title: `Nueva Contraseña para ${client.name} (${client.clientNumber})`,
            message: `El Proveedor actualizó la contraseña para la cuenta ${client.account}. Nueva clave: ${newPassword}. Suscripción Activa vinculada.`,
          })
        }
      })
      
      return updatedCount
    },
    // GESTIÓN DE PEDIDOS DE PRODUCTOS FÍSICOS CON COMISIÓN (1% en tienda, 3% por link compartido)
    createPhysicalOrder({ customerNumber, customerName, customerPhone, shippingAddress, resellerId, source = 'store', items, paymentMethod = 'Transferencia Bancaria' }) {
      const targetResellerId = resellerId || 'RES-01'
      const resellerObj = this.resellers.find(r => r.id === targetResellerId)
      const isSharedUrl = source === 'shared_url'
      const commissionRate = isSharedUrl ? 0.03 : 0.01 // 3% si es por url compartido, 1% si es desde tienda
      
      const totalAmount = items.reduce((sum, item) => sum + (item.priceNumeric * item.quantity), 0)
      const commissionAmount = Math.round(totalAmount * commissionRate)
      const orderId = `PED-FIS-${Date.now().toString().slice(-4)}`

      const newOrder = {
        id: orderId,
        customerNumber: customerNumber || `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
        customerName: customerName || 'Cliente',
        customerPhone: customerPhone || '+54 9 11 0000-0000',
        shippingAddress: shippingAddress || 'Retiro en punto acordado / Envío a coordinar',
        resellerId: targetResellerId,
        resellerName: resellerObj?.name || 'Distribuidor Asignado',
        source, // 'shared_url' | 'store'
        commissionRate,
        commissionAmount,
        totalAmount,
        paymentMethod,
        status: 'Pendiente', // Pendiente en Admin y Reseller
        createdAt: new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }),
        items: items.map(i => ({
          id: i.id,
          name: i.name,
          price: i.price,
          priceNumeric: i.priceNumeric,
          image: i.image,
          quantity: i.quantity
        }))
      }

      this.physicalOrders.unshift(newOrder)

      // Alertar al revendedor que tiene un nuevo pedido físico pendiente con su comisión estimada
      this.sendAlertToReseller({
        resellerId: targetResellerId,
        clientId: null,
        clientNumber: newOrder.customerNumber,
        clientName: newOrder.customerName,
        type: 'general',
        title: `Nuevo Pedido Físico Pendiente (${isSharedUrl ? '3% Link Compartido' : '1% Tienda'})`,
        message: `El cliente ${newOrder.customerName} realizó el pedido ${orderId} por $${totalAmount.toLocaleString()} ARS. Comisión estimada: +$${commissionAmount.toLocaleString()} ARS (${commissionRate * 100}%). En espera de aprobación por Administración.`
      })

      return { success: true, order: newOrder }
    },
    // MARCAR PEDIDO FÍSICO COMO RECIBIDO DESDE ADMIN: Notifica que vendedor oficial contacta al cliente
    markPhysicalOrderReceived(orderId) {
      const order = this.physicalOrders.find(o => o.id === orderId)
      if (!order) return { error: 'Pedido no encontrado' }

      order.status = 'Recibido'
      order.receivedAt = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
      order.statusNote = 'Un vendedor oficial de la tienda física se comunicará para continuar con el pedido y asesorarte en stock y datos de pago.'

      this.sendAlertToReseller({
        resellerId: order.resellerId,
        clientId: null,
        clientNumber: order.customerNumber,
        clientName: order.customerName,
        type: 'general',
        title: `Pedido Físico Recibido (${order.id})`,
        message: `El pedido ${order.id} de ${order.customerName} pasó a RECIBIDO. Un vendedor oficial se pondrá en contacto directo para verificar stock y coordinar el pago/envío.`
      })

      return { success: true, order }
    },
    // CONFIRMAR PEDIDO FÍSICO DESDE ADMIN: Acredita comisión al revendedor y registra transacción
    confirmPhysicalOrder(orderId) {
      const order = this.physicalOrders.find(o => o.id === orderId)
      if (!order) return { error: 'Pedido no encontrado' }
      if (order.status === 'Confirmado' || order.status === 'Aprobado') {
        return { error: 'El pedido ya se encuentra confirmado' }
      }

      order.status = 'Confirmado'
      order.confirmedAt = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
      order.statusNote = 'Compra confirmada por el Administrador. Pedido en proceso de entrega y comisión disponible en billetera.'

      // Acreditar comisión del 1% o 3% a la billetera del revendedor como saldo a favor disponible
      const reseller = this.resellers.find(r => r.id === order.resellerId)
      if (reseller && order.commissionAmount > 0) {
        reseller.balance = (reseller.balance || 0) + order.commissionAmount
        const receiptCode = `COM-${order.id}`
        const dateStr = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })

        const tx = {
          id: `WT-${Date.now().toString().slice(-4)}`,
          resellerId: reseller.id,
          resellerName: reseller.name,
          amount: order.commissionAmount,
          reason: `Comisión ${order.commissionRate * 100}% por Venta Física Confirmada (${order.id})`,
          receipt: receiptCode,
          date: dateStr,
          balanceAfter: reseller.balance
        }

        if (!reseller.walletHistory) reseller.walletHistory = []
        reseller.walletHistory.unshift(tx)
        this.walletTransactions.unshift(tx)

        // Sincronizar billetera en tiempo real con el revendedor si está en sesión
        const resellerStore = useResellerStore()
        if (resellerStore.activeResellerId === reseller.id) {
          resellerStore.wallet = reseller.balance
        }

        this.sendAlertToReseller({
          resellerId: reseller.id,
          clientId: null,
          clientNumber: order.customerNumber,
          clientName: order.customerName,
          type: 'general',
          title: `¡Comisión Acreditada! +$${order.commissionAmount.toLocaleString()} ARS`,
          message: `El Administrador CONFIRMÓ la compra física ${order.id}. Se acreditaron $${order.commissionAmount.toLocaleString()} ARS a tu Billetera como saldo disponible. Saldo actual: $${reseller.balance.toLocaleString()} ARS.`
        })
      }

      return { success: true, order }
    },
    // Alias de compatibilidad hacia confirmPhysicalOrder
    approvePhysicalOrder(orderId) {
      return this.confirmPhysicalOrder(orderId)
    },
    // CANCELAR PEDIDO FÍSICO DESDE ADMIN
    cancelPhysicalOrder(orderId, reason = 'Cancelado por administración o falta de stock') {
      const order = this.physicalOrders.find(o => o.id === orderId)
      if (!order) return { error: 'Pedido no encontrado' }

      order.status = 'Cancelado'
      order.cancelReason = reason
      order.cancelledAt = new Date().toLocaleString([], { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })

      this.sendAlertToReseller({
        resellerId: order.resellerId,
        clientId: null,
        clientNumber: order.customerNumber,
        clientName: order.customerName,
        type: 'incident',
        title: `Pedido Físico Cancelado (${order.id})`,
        message: `El Administrador canceló el pedido ${order.id} de ${order.customerName}. Motivo: ${reason}.`
      })

      return { success: true, order }
    },
    // GESTIÓN DE PAQUETES Y OFERTAS COMBINADAS (PROVEEDOR & REVENDEDORES)
    createPackage({ title, description, badge = 'OFERTA ESPECIAL', services = [], promoPrice, createdBy = 'provider', resellerId = 'ALL' }) {
      const origPrice = services.reduce((sum, s) => sum + (Number(s.regularPrice) || 0), 0)
      const costTotal = services.reduce((sum, s) => sum + (Number(s.cost) || 0), 0)
      const finalPromo = Number(promoPrice || origPrice)
      const profit = finalPromo - costTotal
      const discountPct = origPrice > 0 ? Math.max(0, Math.round(((origPrice - finalPromo) / origPrice) * 100)) : 0

      const newPkg = {
        id: `PKG-${Date.now().toString().slice(-4)}`,
        title: title || 'Combo Especial de Streaming',
        description: description || 'Paquete exclusivo con múltiples plataformas combinadas.',
        badge: badge || (discountPct > 0 ? `🔥 ${discountPct}% OFF` : '⭐ COMBO VIP'),
        services: services.map(s => ({
          platform: s.platform,
          planKey: s.planKey || '1_perfil',
          planLabel: s.planLabel || '1 Pantalla',
          units: Number(s.units || 1),
          regularPrice: Number(s.regularPrice || 0),
          cost: Number(s.cost || 0)
        })),
        originalPrice: origPrice,
        promoPrice: finalPromo,
        providerCost: costTotal,
        resellerProfit: profit,
        discountPercent: discountPct,
        createdBy,
        resellerId: resellerId || 'ALL',
        active: true,
        createdAt: new Date().toISOString().split('T')[0]
      }

      this.packages.unshift(newPkg)

      if (createdBy === 'provider') {
        this.sendAlertToReseller({
          resellerId: 'All',
          clientId: null,
          clientNumber: 'OFERTAS',
          clientName: 'Novedad Mayorista',
          type: 'general',
          title: `¡Nuevo Paquete Oficial Mayorista: ${newPkg.title}!`,
          message: `El Proveedor lanzó el combo oficial ${newPkg.title}. Precio Oferta: $${finalPromo.toLocaleString()} ARS. Disponible para vender y promocionar.`
        })
      }

      return { success: true, package: newPkg }
    },
    togglePackage(pkgId) {
      const p = this.packages.find(pkg => pkg.id === pkgId)
      if (p) {
        p.active = !p.active
        return { success: true, active: p.active }
      }
      return { error: 'Paquete no encontrado' }
    },
    deletePackage(pkgId) {
      const idx = this.packages.findIndex(pkg => pkg.id === pkgId)
      if (idx !== -1) {
        this.packages.splice(idx, 1)
        return { success: true }
      }
      return { error: 'Paquete no encontrado' }
    }
  }
})

export const useResellerStore = defineStore('reseller', {
  state: () => ({
    activeResellerId: 'RES-01',
    isAuthenticated: false,
    sessionUser: null,
    clients: [
      { 
        id: 'C001', 
        clientNumber: 'CLI-8492',
        name: 'Juan Perez', 
        phone: '+54 9 11 4455-8899',
        account: 'netflix.master.01@streampro.com', 
        pass: 'NetMaster99#',
        platform: 'Netflix', 
        resellerId: 'RES-01',
        planName: '1 Perfil (1 Dispositivo)',
        clientPaid: 7700,
        resellerProfit: 2200,
        providerCost: 5500,
        status: 'Activa', 
        expiry: '2026-10-22',
        devices: 1,
        profiles: [
          { name: 'PERFIL 1 - LIVING', pin: '1122' }
        ],
        notes: 'Cliente #CLI-8492. Slot 1 verificado.'
      },
      { 
        id: 'C002', 
        clientNumber: 'CLI-9024',
        name: 'Maria Gomez', 
        phone: '+54 9 11 9988-1122',
        account: 'netflix.master.02@streampro.com', 
        pass: 'StreamNx2026*',
        platform: 'Netflix', 
        resellerId: 'RES-01',
        planName: '2 Dispositivos simultáneos',
        clientPaid: 13000,
        resellerProfit: 3000,
        providerCost: 10000,
        status: 'Por Vencer', 
        expiry: '2026-10-12',
        devices: 2,
        profiles: [
          { name: 'DISPOSITIVO 1 - LIVING', pin: '8810' },
          { name: 'DISPOSITIVO 2 - DORMITORIO', pin: '8810' }
        ],
        notes: 'Cliente #CLI-9024. Faltan 24hs para vencimiento.'
      },
      { 
        id: 'C003', 
        clientNumber: 'CLI-4411',
        name: 'Lucas Diaz', 
        phone: '+54 9 351 667-2233',
        account: 'disney.vip@stream.com', 
        pass: 'D1sn3yStar!26',
        platform: 'Disney+', 
        resellerId: 'RES-02',
        planName: '4 Dispositivos simultáneos',
        clientPaid: 19500,
        resellerProfit: 4500,
        providerCost: 15000,
        status: 'Vencida', 
        expiry: '2026-10-09',
        devices: 4,
        profiles: [
          { name: 'PERFIL 1 - LUCAS', pin: '1100' },
          { name: 'PERFIL 2 - ANDREA', pin: '2200' },
          { name: 'PERFIL 3 - NENES', pin: '0000' },
          { name: 'PERFIL 4 - SMART TV', pin: '9988' }
        ],
        notes: 'Cliente #CLI-4411. Cuenta vencida.'
      },
      { 
        id: 'C004', 
        clientNumber: 'CLI-7729',
        name: 'Esteban F.', 
        phone: '+54 9 261 412-9988',
        account: 'max.master@streampro.com', 
        pass: 'M4xUltra2026',
        platform: 'Max', 
        resellerId: 'RES-01',
        planName: '1 Perfil individual',
        clientPaid: 6000,
        resellerProfit: 1800,
        providerCost: 4200,
        status: 'En Reemplazo', 
        expiry: '2026-10-28',
        devices: 1,
        profiles: [
          { name: 'PERFIL 1 - ESTEBAN', pin: '7788' }
        ],
        notes: 'Cliente #CLI-7729. Reporte de caída activo.'
      }
    ],
    wallet: 8700 // Saldo acumulativo a favor del revendedor
  }),
  getters: {
    totalProfit: (state) => state.clients.reduce((acc, c) => acc + (c.resellerProfit || 0), 0),
    counts: (state) => ({
      total: state.clients.length,
      activas: state.clients.filter(c => c.status === 'Activa').length,
      porVencer: state.clients.filter(c => c.status === 'Por Vencer').length,
      vencidas: state.clients.filter(c => c.status === 'Vencida').length,
      enReemplazo: state.clients.filter(c => c.status === 'En Reemplazo').length
    }),
    // Pedidos físicos correspondientes al revendedor activo
    myPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId)
    },
    myPendingPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && o.status === 'Pendiente')
    },
    myReceivedPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && o.status === 'Recibido')
    },
    myConfirmedPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && (o.status === 'Confirmado' || o.status === 'Aprobado'))
    },
    myApprovedPhysicalOrders: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders.filter(o => o.resellerId === state.activeResellerId && (o.status === 'Confirmado' || o.status === 'Aprobado'))
    },
    myPhysicalCommissionsEarned: (state) => {
      const adminStore = useAdminStore()
      return adminStore.physicalOrders
        .filter(o => o.resellerId === state.activeResellerId && (o.status === 'Confirmado' || o.status === 'Aprobado'))
        .reduce((sum, o) => sum + (o.commissionAmount || 0), 0)
    },
    // Paquetes disponibles para este revendedor (Oficiales de Proveedor + Creados por este Revendedor)
    myPackages: (state) => {
      const adminStore = useAdminStore()
      return adminStore.packages.filter(p => (p.resellerId === 'ALL' || p.resellerId === state.activeResellerId) && p.active)
    },
    myCustomPackages: (state) => {
      const adminStore = useAdminStore()
      return adminStore.packages.filter(p => p.resellerId === state.activeResellerId)
    }
  },
  actions: {
    addDeliveredOrderToClients(order) {
      const expDate = new Date()
      expDate.setDate(expDate.getDate() + 30)

      this.clients.unshift({
        id: `C00${this.clients.length + 1}`,
        clientNumber: order.customerNumber || `CLI-${Math.floor(1000 + Math.random() * 9000)}`,
        name: order.customerName,
        phone: order.customerPhone || '+54 9 11 0000-0000',
        account: order.accountDetails?.email || 'cuenta.oficial@streampro.com',
        pass: order.accountDetails?.pass || 'Stream2026*',
        platform: order.platform,
        resellerId: order.resellerId || this.activeResellerId,
        planName: order.planLabel || `${order.devices} Pantalla(s)`,
        clientPaid: order.clientPrice,
        resellerProfit: order.resellerProfit,
        providerCost: order.providerCost,
        status: 'Activa',
        expiry: expDate.toISOString().split('T')[0],
        devices: order.devices,
        profiles: order.accountDetails?.profiles || [],
        notes: `Entregada automáticamente desde orden ${order.id}.`
      })
    },
    // Gestión de Billetera: Cargar saldo a favor o devoluciones sin recompensas arbitrarias
    topUpWallet(amount, reason = 'Carga de saldo a favor / Devolución') {
      this.wallet += Number(amount)
    },
    // Reporte de caída SIN recompensa monetaria automática
    reportFailureNoReward(clientId) {
      const client = this.clients.find(c => c.id === clientId)
      if (client) {
        client.status = 'En Reemplazo'
        client.notes = `Caída reportada el ${new Date().toLocaleDateString()}. En espera de solución.`
      }
    },
    // Revendedor confirma y acepta las nuevas credenciales modificadas por el Proveedor en una renovación
    confirmRenewalUpdate(renewalId) {
      const adminStore = useAdminStore()
      const req = adminStore.renewalRequests.find(r => r.id === renewalId)
      if (req) {
        req.status = 'Confirmed By Reseller'
        const client = this.clients.find(c => c.id === req.clientId)
        if (client) {
          client.account = req.newCredentials.account
          client.pass = req.newCredentials.pass
          client.profiles = req.newCredentials.profiles
          client.status = 'Activa'
          const date = new Date()
          date.setDate(date.getDate() + 30)
          client.expiry = date.toISOString().split('T')[0]
          client.notes = `Renovación confirmada con nuevas credenciales el ${new Date().toLocaleDateString()}.`
        }
      }
    },
    renewClient(clientId, days = 30) {
      const client = this.clients.find(c => c.id === clientId)
      if (client) {
        client.status = 'Activa'
        const date = new Date()
        date.setDate(date.getDate() + days)
        client.expiry = date.toISOString().split('T')[0]
      }
    },
    updateClient(clientId, data) {
      const client = this.clients.find(c => c.id === clientId)
      if (client) {
        Object.assign(client, data)
      }
    },
    // Solicitar compra manual desde el Panel de Revendedor
    requestManualPurchase({ platform, planKey, units, customerName, customerPhone, pinPreference = 'same', profiles = [], payWithWallet = false }) {
      const adminStore = useAdminStore()
      const catalog = adminStore.pricingCatalog[platform]
      if (!catalog) return { error: 'Plataforma no válida' }

      const plan = catalog.plans[planKey]
      if (!plan) return { error: 'Plan no válido' }

      // Si elige pagar con Billetera y tiene saldo suficiente
      if (payWithWallet) {
        if (this.wallet < plan.providerCost) {
          return { error: `Saldo insuficiente en Billetera ($${this.wallet} ARS). Costo mayorista: $${plan.providerCost} ARS` }
        }
        this.wallet -= plan.providerCost
      }

      const customerNumber = `CLI-${Math.floor(1000 + Math.random() * 9000)}`
      const orderId = `ORD-MAN-${Date.now().toString().slice(-4)}`

      const newOrder = {
        id: orderId,
        customerNumber,
        customerName: customerName || `Cliente de ${this.activeResellerId}`,
        customerPhone: customerPhone || '+54 9 11 0000-0000',
        platform,
        devices: Number(units || plan.units),
        pinPreference,
        planKey,
        planLabel: plan.label,
        providerCost: plan.providerCost,
        resellerProfit: plan.resellerProfit,
        clientPrice: plan.clientPrice,
        resellerId: this.activeResellerId,
        resellerName: adminStore.resellers.find(r => r.id === this.activeResellerId)?.name || 'Revendedor Autorizado',
        paymentMethod: payWithWallet ? 'Saldo Billetera Revendedor' : 'Transferencia Bancaria a Proveedor',
        voucher: payWithWallet ? 'Debitado de Billetera Virtual' : 'Pendiente Verificación Proveedor',
        profileSelections: profiles.length > 0 ? profiles : Array.from({ length: Number(units || plan.units) }, (_, i) => ({
          name: `PERFIL ${i + 1}`
        })),
        status: 'Waiting Provider',
        createdAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      }

      adminStore.orders.unshift(newOrder)
      return { success: true, order: newOrder }
    },
    markAlertAsRead(alertId) {
      const adminStore = useAdminStore()
      const alert = adminStore.resellerAlerts.find(a => a.id === alertId)
      if (alert) {
        alert.read = true
      }
    },
    dismissAlert(alertId) {
      const adminStore = useAdminStore()
      return adminStore.dismissAlert(alertId)
    },
    clearAllAlerts() {
      const adminStore = useAdminStore()
      return adminStore.clearResellerAlerts(this.activeResellerId)
    },
    // Autenticación de Revendedor
    login(username, password) {
      const adminStore = useAdminStore()
      const u = username.trim().toLowerCase()
      const p = password.trim()
      
      const found = adminStore.resellers.find(r => 
        (r.username?.toLowerCase() === u || r.id.toLowerCase() === u) &&
        r.password === p
      )

      if (!found) {
        return { error: 'Usuario o contraseña incorrectos. Verifica tus credenciales.' }
      }

      if (found.isActive === false) {
        return { error: 'Acceso denegado: Tu cuenta de distribuidor ha sido deshabilitada por Administración.' }
      }

      this.isAuthenticated = true
      this.activeResellerId = found.id
      this.sessionUser = { ...found }
      this.wallet = found.balance || 0
      return { success: true, reseller: found }
    },
    createResellerPackage(packageData) {
      const adminStore = useAdminStore()
      return adminStore.createPackage({
        ...packageData,
        createdBy: 'reseller',
        resellerId: this.activeResellerId
      })
    },
    logout() {
      this.isAuthenticated = false
      this.sessionUser = null
    }
  }
})
