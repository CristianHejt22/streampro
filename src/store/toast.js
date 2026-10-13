import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: []
  }),
  actions: {
    add(message, type = 'success') {
      const id = Date.now() + Math.random()
      this.toasts.push({ id, message, type })
      setTimeout(() => {
        this.remove(id)
      }, 4000)
    },
    remove(id) {
      this.toasts = this.toasts.filter(t => t.id !== id)
    }
  }
})
