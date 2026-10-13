import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import CustomerPortal from '../views/CustomerPortal.vue'
import ResellerDashboard from '../views/ResellerDashboard.vue'
import AdminDashboard from '../views/AdminDashboard.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView
    },
    {
      path: '/p',
      redirect: to => ({
        path: '/',
        query: { ...to.query, sec: 'physical' }
      })
    },
    {
      path: '/portal',
      alias: ['/cliente', '/clientes', '/boveda', '/portal-cliente'],
      name: 'portal',
      component: CustomerPortal
    },
    {
      path: '/crm',
      alias: ['/reseller', '/revendedor', '/cm', '/panel', '/c'],
      name: 'crm',
      component: ResellerDashboard
    },
    {
      path: '/myprove',
      alias: ['/myprov', '/proveedor', '/admin-proveedor', '/prove', '/admin'],
      name: 'myprove',
      component: AdminDashboard
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ]
})

export default router
