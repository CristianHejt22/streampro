import { createRouter, createWebHistory } from 'vue-router'

const HomeView = () => import('../views/HomeView.vue')
const CustomerPortal = () => import('../views/CustomerPortal.vue')
const ResellerDashboard = () => import('../views/ResellerDashboard.vue')
const AdminDashboard = () => import('../views/AdminDashboard.vue')
const ResellerRecruitmentView = () => import('../views/ResellerRecruitmentView.vue')

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
      alias: ['/catalogo', '/tienda', '/productos'],
      redirect: to => ({
        path: '/',
        query: { ...to.query, sec: 'physical' }
      })
    },
    {
      path: '/producto/:id',
      alias: ['/p/:id', '/item/:id', '/articulo/:id'],
      redirect: to => ({
        path: '/',
        query: { ...to.query, prod: to.params.id, sec: 'physical' }
      })
    },
    {
      path: '/producto/:id/:ref',
      redirect: to => ({
        path: '/',
        query: { ...to.query, prod: to.params.id, ref: to.params.ref, sec: 'physical' }
      })
    },
    {
      path: '/ref/:ref',
      alias: ['/tienda/:ref', '/r/:ref'],
      redirect: to => ({
        path: '/',
        query: { ...to.query, ref: to.params.ref }
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
      path: '/revendedores',
      alias: ['/unete', '/distribuidores', '/quiero-ser-revendedor', '/trabaja-con-nosotros'],
      name: 'recruitment',
      component: ResellerRecruitmentView
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ]
})

// Manejo inteligente de fallos de carga de chunks cuando se despliega una nueva versión
router.onError((error, to) => {
  const isChunkError = 
    error?.message?.includes('Failed to fetch dynamically imported module') ||
    error?.message?.includes('Importing a module script failed') ||
    error?.message?.includes('Expected a JavaScript-or-Wasm module script')

  if (isChunkError) {
    const lastReload = sessionStorage.getItem('last_chunk_reload')
    const now = Date.now()
    if (!lastReload || now - parseInt(lastReload) > 10000) {
      sessionStorage.setItem('last_chunk_reload', now.toString())
      if (to?.fullPath) {
        window.location.href = to.fullPath
      } else {
        window.location.reload()
      }
    }
  }
})

export default router
