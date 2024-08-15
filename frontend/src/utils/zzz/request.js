import axios from 'axios'
import { getToken, destorySession, setToken } from "../utils/auth"
import { ElMessage } from 'element-plus'

/* Create axios instance */
const service = axios.create({
  baseURL: import.meta.env.VITE_PHP_API_URL,
  timeout: 5000,
  headers: {
    'Authorization': `Bearer ${getToken('AccessToken')}`,
    'Content-Type': 'application/json',
    'accept': 'application/json'
  },
})

/* Request interceptor. */
service.interceptors.request.use(
  config => config,
  error => {
    console.log(error)
    ElMessage({
      message: error.message || 'Error',
      type: 'error',
      duration: 5 * 1000,
    })
    return Promise.reject(error)
  }
)

/* Response interceptor. */
service.interceptors.response.use(
  response => response,
  error => {
    const originalRequest = error.config

    /* Direct user to login page, refresh failed. */
    if (error.response.status === 401
      && originalRequest.url === "api/token/refresh/") {
      destorySession();
      sessionStorage.setItem('ECS_PRE_REDIRECT',
        window.location.href.replace(
          `${import.meta.env.VITE_BASE_URL}/`, ""));

      window.location = import.meta.env.VITE_BASE_URL + "Login";
    }
    /* Attempt to refresh token. */
    else if (error.response.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      service
        .post("api/token/refresh/", { "refresh": getToken("RefreshToken") })
        .then((res) => {
          setToken("AccessToken", res.data.access)
          return location.reload();
        })
        .catch((err) => console.log(err));
    }

    console.log(error)
    return Promise.reject(error)
  },
)

export default service
