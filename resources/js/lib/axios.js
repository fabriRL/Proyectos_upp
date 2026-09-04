import axios from 'axios'

axios.interceptors.request.use((config) => {
  const token =
    localStorage.getItem('sipip-token') ||
    sessionStorage.getItem('sipip-token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

export default axios