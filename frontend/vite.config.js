import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default ({ mode }) => {
  return defineConfig({
    base: process.env.NODE_ENV === "production" ? "/ecs/frontend/dist/" : "/",
    build: {
      outDir: 'dist',
      assetsDir: 'assets',
      emptyOutDir: true,
      rollupOptions: {
        output: {
          manualChunks: {
            lodash: ['lodash'],
            elementPlus: ['element-plus'],
            pinia: ['pinia'],
            'vue-router': ['vue-router'],
            'vue3-apexcharts': ['vue3-apexcharts'],
            apexcharts: ['apexcharts'],
            axios: ['axios'],
          }
        }
      },
    },
    // optimizeDeps: {
    //   include: [
    //     'vue',
    //     'vue-router',
    //     '@vueuse/core',
    //     '@vueuse/head',
    //   ],
    //   exclude: [
    //     'vue-demi',
    //   ],
    // },
    plugins: [
      vue({}),
    ],
  })
}
