import { defineStore } from 'pinia'
import { parseArgentinePrice } from '../utils/pricing'

const CART_STORAGE_KEY = 'streamepro_cart_v1'

function loadStoredCart() {
  try {
    const raw = localStorage.getItem(CART_STORAGE_KEY)
    if (raw) {
      const parsed = JSON.parse(raw)
      return {
        items: Array.isArray(parsed.items) ? parsed.items : [],
        appliedCoupon: parsed.appliedCoupon || null
      }
    }
  } catch {}
  return { items: [], appliedCoupon: null }
}

function saveStoredCart(items, appliedCoupon) {
  try {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify({ items, appliedCoupon }))
  } catch {}
}

export const useCartStore = defineStore('cart', {
  state: () => {
    const initial = loadStoredCart()
    return {
      items: initial.items,
      appliedCoupon: initial.appliedCoupon,
      isDrawerOpen: false
    }
  },
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
    couponDiscountAmount(state) {
      const coupon = state.appliedCoupon
      if (!coupon || coupon.active === false) return 0
      const scope = coupon.scope || 'all'
      const eligibleSubtotal = state.items.reduce((acc, item) => {
        if (scope === 'all') return acc + (item.clientPrice * item.quantity)
        if (scope === 'gaming' && item.itemType === 'gaming') return acc + (item.clientPrice * item.quantity)
        if (scope === 'streaming' && item.itemType !== 'physical' && item.itemType !== 'gaming') return acc + (item.clientPrice * item.quantity)
        if (scope === 'physical' && item.itemType === 'physical') return acc + (item.clientPrice * item.quantity)
        return acc
      }, 0)
      if (eligibleSubtotal <= 0) return 0
      if (coupon.minAmount && eligibleSubtotal < Number(coupon.minAmount)) return 0
      const val = Number(coupon.value || 0)
      if (val <= 0) return 0
      const rawDiscount = coupon.type === 'fixed' ? val : Math.round(eligibleSubtotal * (val / 100))
      return Math.min(eligibleSubtotal, Math.max(0, rawDiscount))
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
      
      const volumeDiscount = Math.round(streamingSubtotal * (this.discountPercentage / 100))
      const rawTotal = (streamingSubtotal - volumeDiscount) + gamingSubtotal + physicalSubtotal
      return Math.max(0, rawTotal - this.couponDiscountAmount)
    },
    hasPhysicalItems: (state) => state.items.some(i => i.itemType === 'physical'),
    hasGamingItems: (state) => state.items.some(i => i.itemType === 'gaming'),
    hasDigitalItems: (state) => state.items.some(i => i.itemType !== 'physical')
  },
  actions: {
    persistCart() {
      saveStoredCart(this.items, this.appliedCoupon)
    },
    applyCoupon(coupon) {
      this.appliedCoupon = coupon ? { ...coupon } : null
      this.persistCart()
    },
    removeCoupon() {
      this.appliedCoupon = null
      this.persistCart()
    },
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

      // Comprobar si ya existe un item idéntico (mismo producto, plan y correo de activación)
      const existingItem = this.items.find(i => 
        i.id === product.id && 
        i.planKey === planKey &&
        i.itemType !== 'physical' &&
        (i.activationEmail || '') === (activationEmail || '')
      )

      if (existingItem) {
        existingItem.quantity++
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
      this.persistCart()
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
      this.persistCart()
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
      this.persistCart()
    },
    // AGREGAR VIDEOJUEGO DIGITAL (PS4 / PS5 - Primaria o Secundaria con Mayor Precio)
    addGamingItem(game, licenseType = 'primary', quantity = 1) {
      const platLower = String(game.platform || '').toLowerCase()
      const isSingleMode = platLower.includes('pc') || platLower.includes('steam') || platLower.includes('ps3')
      const resolvedLicense = isSingleMode ? 'primary' : licenseType
      const isPrimary = resolvedLicense === 'primary'
      const effectivePrice = isPrimary ? (game.effectivePrimaryPrice || 25000) : (game.effectiveSecondaryPrice || 12000)
      const priceLeaderKey = 'streamepro'
      const priceLeaderName = 'StreamePro'
      // Proveedor conveniente interno para el Admin, manteniendo la fachada pública como PlayStation Store Oficial
      const assignedProviderKey = (isPrimary ? game.bestFulfillmentPrimary : game.bestFulfillmentSecondary) || game.visibleProviderKey || 'argentinaplay'
      const assignedProviderInternalName = (isPrimary ? game.bestFulfillmentPrimaryName : game.bestFulfillmentSecondaryName) || ''
      const assignedProviderName = 'PlayStation Store Oficial'
      
      const providerData = game.providers?.[assignedProviderKey] || {}
      const providerCost = isPrimary
        ? (game.actualPurchaseCostPrimary || providerData.costPrimary || providerData.primaryPrice || game.minCostPrimary || game.baseCostPrimary || effectivePrice)
        : (game.actualPurchaseCostSecondary || providerData.costSecondary || providerData.secondaryPrice || game.minCostSecondary || game.baseCostSecondary || effectivePrice)

      const cartItemKey = `gaming_${game.id}_${resolvedLicense}`
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
          planKey: `gaming_${resolvedLicense}`,
          planLabel: isSingleMode
            ? `🔑 Edición Digital Oficial (${game.platform})`
            : (isPrimary 
                ? '⭐️ Cuenta Primaria (Juegas con tu usuario personal)' 
                : '⚡️ Cuenta Secundaria (Juegas con usuario provisto)'),
          licenseType: resolvedLicense,
          licenseLabel: isSingleMode ? 'Licencia Digital Oficial' : (isPrimary ? 'Cuenta Primaria' : 'Cuenta Secundaria'),
          units: 1,
          clientPrice: effectivePrice,
          originalPrice: Math.round(effectivePrice * 1.25),
          providerCost: providerCost,
          resellerProfit: isPrimary 
            ? (game.resellerPrimaryProfit || Math.max(1000, Math.round(effectivePrice * 0.10))) 
            : (game.resellerSecondaryProfit || Math.max(800, Math.round(effectivePrice * 0.10))),
          storeProfit: Math.max(0, effectivePrice - providerCost),
          image: game.image,
          priceFormatted: `$${effectivePrice.toLocaleString()} ARS`,
          providerKey: assignedProviderKey,
          providerName: assignedProviderName,
          fulfillmentProviderName: assignedProviderInternalName || providerData.providerName || '',
          priceLeaderKey: priceLeaderKey,
          priceLeaderName: 'PlayStation Store Oficial',
          providerUrl: providerData.providerUrl || '',
          quantity: quantity,
          itemType: 'gaming'
        })
      }
      this.persistCart()
    },
    updateItemQuantity(index, delta) {
      if (this.items[index]) {
        this.items[index].quantity += delta
        if (this.items[index].quantity <= 0) {
          this.removeItem(index)
          return
        }
        this.persistCart()
      }
    },
    removeItem(index) {
      this.items.splice(index, 1)
      this.persistCart()
    },
    clearCart() {
      this.items = []
      this.appliedCoupon = null
      this.persistCart()
    }
  }
})
