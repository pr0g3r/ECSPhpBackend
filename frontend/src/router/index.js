import Dashboard from '../views/Dashboard.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from "../utils/auth"

const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: Dashboard,
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/Login',
    name: 'Login',
    component: () => import('../views/Login.vue')
  },
  {
    path: '/Pending',
    name: 'Pending',
    meta: {
      requiresAuth: true,
    },
    component: () => import('../views/Pending.vue')
  },
  {
    path: '/Resends',
    name: 'Resends',
    meta: {
      requiresAuth: true,
    },
    component: () => import('../views/Resends.vue')
  },
  {
    path: '/Returns',
    name: 'Returns',
    meta: {
      requiresAuth: true,
    },
    component: () => import('../views/Returns.vue')
  },
  {
    path: '/Refunds',
    name: 'Refunds',
    meta: {
      requiresAuth: true,
    },
    component: () => import('../views/Refunds.vue')
  },
  {
    path: '/Claims',
    name: 'Claims',
    meta: {
      requiresAuth: true,
    },
    component: () => import('../views/Claims.vue')
  },
  {
    path: '/Resend/:order_id',
    name: 'Resend Form',
    props: true,
    meta: {
      requiresAuth: true,
    },
    component: () => import('../views/ResendForm.vue')
  },
  {
    path: '/Return/:order_id',
    name: 'Return Form',
    props: true,
    meta: {
      requiresAuth: true,
    },
    component: () => import('../views/ReturnForm.vue')
  },
  {
    path: '/Refund/:order_id',
    name: 'Refund Form',
    props: true,
    meta: {
      requiresAuth: true,
    },
    component: () => import('../views/RefundForm.vue')
  },
  {
    path: "/OrderForm/:order_id",
    name: "Reorder",
    props: true,
    component: () => import('../views/OrderForm.vue')
  },
  {
    path: "/OrderForm",
    name: "Create Order",
    component: () => import('../views/OrderForm.vue')
  },
  {
    path: "/Admin",
    beforeEnter: () => location.href = import.meta.env.VITE_PHP_API_URL + 'admin/',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

router.beforeEach((to, from, next) => {
  if (to.meta.requiresAuth) {
    if (getToken("AccessToken") || getToken("RefreshToken")) {
      next()
    }
    else {
      sessionStorage.setItem('ECS_PRE_REDIRECT', to.fullPath);
      next({ name: 'Login' })
    }
  }
  else {
    next()
  }
})

export default router
