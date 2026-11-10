import { defineStore } from 'pinia'
import { parseArgentinePrice } from '../utils/pricing'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    isDrawerOpen: false
  }),
  getters: {
    totalItems: (state) => {
      // Suma total de unidades
      return state.items.reduce((acc, item) => acc + ((item.units || 1) * item.quantity), 0)
    },
    subtotal: (state) => {
      // clientPrice unitario * cantidad de paquetes
      return state.items.reduce((acc, item) => acc + (item.clientPrice * item.quantity), 0)
    },
    discountPercentage(state) {
      // Aplica solo a productos digitales de streaming por volumen
      const streamingUnits = state.items
        .filter(i => i.itemType !== 'physical' && i.itemType !== 'gaming')
        .reduce((acc, item) => acc + ((item.units || 1) * item.quantity), 0)
      if (streamingUnits >= 8) return 15
      if (streamingUnits >= 4) return 7
      if (streamingUnits >= 2) return 5
      return 0
    },
    total(state) {
      const streamingSubtotal = state.items
        .filter(i => i.itemType !== 'physical' && i.itemType !== 'gaming')
        .reduce((acc, item) => acc + (item.clientPrice * item.quantity), 0)
      const gamingSubtotal = state.items
        .filter(i => i.itemType === 'gaming')
        .reduce((acc, item) => acc + (item.clientPrice * item.quantity), 0)
      const physicalSubtotal = state.items
        .filter(i => i.itemType === 'physical')
        .reduce((acc, item) => acc + (item.clientPrice * item.quantity), 0)
      
      const discount = streamingSubtotal * (this.discountPercentage / 100)
      return (streamingSubtotal - discount) + gamingSubtotal + physicalSubtotal
    },
    hasPhysicalItems: (state) => state.items.some(i => i.itemType === 'physical'),
    hasGamingItems: (state) => state.items.some(i => i.itemType === 'gaming'),
    hasDigitalItems: (state) => state.items.some(i => i.itemType !== 'physical')
  },
  actions: {
    openDrawer() {
      this.isDrawerOpen = true
    },
    closeDrawer() {
      this.isDrawerOpen = false
    },
    toggleDrawer() {
      this.isDrawerOpen = !this.isDrawerOpen
    },
    addItem(product, planKey, planData, profiles = [], activationEmail = '') {
      const defaultProfiles = profiles.length > 0 ? profiles : Array.from({ length: planData.units }, (_, idx) => ({
        name: planData.type === 'perfil' 
          ? `PERFIL ${idx + 1} - ${product.platform.toUpperCase()}`
          : `DISPOSITIVO ${idx + 1} - ${product.platform.toUpperCase()}`
      }))

      // Comprobar si ya existe un item idéntico (mismo producto y misma opción de plan)
      const existingItem = this.items.find(i => 
        i.id === product.id && 
        i.planKey === planKey &&
        i.itemType !== 'physical'
      )

      if (existingItem) {
        existingItem.quantity++
        if (activationEmail) existingItem.activationEmail = activationEmail
      } else {
        this.items.push({ 
          id: product.id,
          name: product.name,
          platform: product.platform,
          category: product.category,
          planKey: planKey,
          planLabel: planData.label,
          units: planData.units,
          planType: planData.type,
          clientPrice: planData.clientPrice,
          providerCost: planData.providerCost,
          resellerProfit: planData.resellerProfit,
          profiles: defaultProfiles,
          activationEmail: activationEmail || '',
          quantity: 1,
          itemType: 'digital'
        })
      }
    },
    // AGREGAR PRODUCTO FÍSICO DEL CATÁLOGO (revende.com.ar con soporte de SKU y variantes)
    addPhysicalItem(product, quantity = 1, selectedVariant = null) {
      const variantId = selectedVariant ? selectedVariant.id : null
      const variantName = selectedVariant ? selectedVariant.name : ''
      const sku = selectedVariant?.sku || product.sku || ''
      const priceNumeric = selectedVariant?.price || product.priceNumeric || parseArgentinePrice(product.price)
      const priceFormatted = selectedVariant?.priceFormatted || product.price
      const image = selectedVariant?.image || product.image
      const originalPrice = selectedVariant?.comparePrice || product.originalPriceNumeric || null

      const cartItemKey = variantId ? `phys_${product.id}_${variantId}` : `phys_${product.id}`
      const existingItem = this.items.find(i => 
        (i.cartItemKey === cartItemKey || (!i.cartItemKey && i.id === product.id && !variantId)) && 
        i.itemType === 'physical'
      )

      if (existingItem) {
        existingItem.quantity += quantity
      } else {
        this.items.push({
          cartItemKey,
          id: product.id,
          name: variantName && variantName !== 'Variante 1' ? `${product.name} (${variantName})` : product.name,
          baseName: product.name,
          platform: 'Producto Físico',
          category: product.category || 'Catálogo Físico',
          planKey: 'fisico_unitario',
          planLabel: variantName && variantName !== 'Variante 1' ? `Variante: ${variantName}` : 'Producto Físico',
          units: 1,
          clientPrice: priceNumeric,
          originalPrice: originalPrice,
          image: image,
          priceFormatted: priceFormatted,
          sku: sku,
          variantId: variantId,
          variantName: variantName,
          stock: selectedVariant?.stock ?? product.stock ?? 20,
          quantity: quantity,
          itemType: 'physical'
        })
      }
    },
    // AGREGAR PAQUETE / COMBO PROMOCIONAL
    addPackageItem(pkg) {
      const existing = this.items.find(i => i.id === pkg.id && i.itemType === 'package')
      if (existing) {
        existing.quantity++
      } else {
        const totalUnits = pkg.services.reduce((acc, s) => acc + (s.units || 1), 0)
        const allProfiles = pkg.services.flatMap(s => 
          Array.from({ length: s.units || 1 }, (_, idx) => ({
            name: `PERFIL ${idx + 1} - ${s.platform}`
          }))
        )

        this.items.push({
          id: pkg.id,
          name: pkg.title,
          platform: pkg.services.map(s => s.platform).join(' + '),
          category: 'Combo Especial',
          planKey: 'combo_bundle',
          planLabel: pkg.services.map(s => `${s.platform} (${s.planLabel})`).join(' + '),
          units: totalUnits,
          clientPrice: pkg.promoPrice,
          providerCost: pkg.providerCost,
          resellerProfit: pkg.resellerProfit,
          profiles: allProfiles,
          quantity: 1,
          itemType: 'package',
          packageDetails: pkg
        })
      }
    },
    // AGREGAR VIDEOJUEGO DIGITAL (PS4 / PS5 - Primaria o Secundaria con Mayor Precio)
    addGamingItem(game, licenseType = 'primary', quantity = 1) {
      const isPrimary = licenseType === 'primary'
      const effectivePrice = isPrimary ? (game.effectivePrimaryPrice || 25000) : (game.effectiveSecondaryPrice || 12000)
      const priceLeaderKey = 'streamepro'
      const priceLeaderName = 'StreamePro'
      // REGLA: Usar proveedor público oficial (PlayStation Store Oficial) sin filtrar nombres de terceros
      const assignedProviderKey = game.visibleProviderKey || (isPrimary ? game.bestFulfillmentPrimary : game.bestFulfillmentSecondary)
      const assignedProviderName = 'PlayStation Store Oficial'
      
      const providerData = game.providers?.[assignedProviderKey] || {}
      const providerCost = isPrimary ? (providerData.costPrimary || providerData.primaryPrice || effectivePrice) : (providerData.costSecondary || providerData.secondaryPrice || effectivePrice)

      const cartItemKey = `gaming_${game.id}_${licenseType}`
      const existingItem = this.items.find(i => i.cartItemKey === cartItemKey)

      if (existingItem) {
        existingItem.quantity += quantity
      } else {
        this.items.push({
          cartItemKey,
          id: game.id,
          gameId: game.id,
          name: `${game.title} (${game.platform})`,
          baseName: game.title,
          title: game.title,
          platform: game.platform,
          category: 'Videojuegos Digitales',
          planKey: `gaming_${licenseType}`,
          planLabel: isPrimary 
            ? '⭐️ Cuenta Primaria (Juegas con tu usuario personal)' 
            : '⚡️ Cuenta Secundaria (Juegas con usuario provisto)',
          licenseType: licenseType, // 'primary' | 'secondary'
          licenseLabel: isPrimary ? 'Cuenta Primaria' : 'Cuenta Secundaria',
          units: 1,
          clientPrice: effectivePrice,
          originalPrice: Math.round(effectivePrice * 1.25),
          providerCost: providerCost,
          resellerProfit: isPrimary 
            ? (game.resellerPrimaryProfit || Math.max(1000, Math.round(effectivePrice * 0.10))) 
            : (game.resellerSecondaryProfit || Math.max(800, Math.round(effectivePrice * 0.10))),
          storeProfit: isPrimary 
            ? (game.storeGrossMarginPrimary || Math.max(0, effectivePrice - providerCost)) 
            : (game.storeGrossMarginSecondary || Math.max(0, effectivePrice - providerCost)),
          image: game.image,
          priceFormatted: `$${effectivePrice.toLocaleString()} ARS`,
          providerKey: assignedProviderKey,
          providerName: assignedProviderName,
          priceLeaderKey: priceLeaderKey,
          priceLeaderName: 'PlayStation Store Oficial',
          providerUrl: '',
          quantity: quantity,
          itemType: 'gaming'
        })
      }
    },
    updateItemQuantity(index, delta) {
      if (this.items[index]) {
        this.items[index].quantity += delta
        if (this.items[index].quantity <= 0) {
          this.removeItem(index)
        }
      }
    },
    removeItem(index) {
      this.items.splice(index, 1)
    },
    clearCart() {
      this.items = []
    }
  }
})
