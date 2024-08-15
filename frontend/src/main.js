import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'
import { createApp } from 'vue'

import './index.css'
import 'element-plus/dist/index.css'
import ElementPlus from 'element-plus'

createApp(App)
  .use(router)
  .use(createPinia())
  .use(ElementPlus)
  .mount('#app')
