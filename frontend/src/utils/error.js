import { ElMessage } from 'element-plus'

export default function error(err) {
    if (err.response.statusText !== "Unauthorized") {
        return ElMessage({
            message: err.response.data || err.message || 'Error',
            type: 'error',
            duration: 10 * 1000,
        })
    }
}
