import axios from 'axios'

const apiClient = axios.create({
  baseURL: '',
  headers: {
    'Content-Type': 'application/json',
  },
})

export const apiService = {
  async getTestUser() {
    const { data } = await apiClient.get('/api/users/test')
    return data
  },

  async getUser(userId) {
    const { data } = await apiClient.get(`/api/users/${userId}`)
    return data
  },

  async getBalance(userId) {
    const { data } = await apiClient.get(`/api/users/${userId}/balance`)
    return data
  },

  async quickPay(userId, amount, paymentMethod = 'card') {
    const { data } = await apiClient.post(`/api/users/${userId}/payments/quick`, {
      amount,
      payment_method: paymentMethod,
    })
    return data
  },

  async initiatePayment(userId, amount, paymentMethod = 'card') {
    const { data } = await apiClient.post(`/api/users/${userId}/payments`, {
      amount,
      payment_method: paymentMethod,
    })
    return data
  },

  async processPayment(userId, paymentId) {
    const { data } = await apiClient.post(`/api/users/${userId}/payments/${paymentId}/process`)
    return data
  },

  async getPaymentStatus(userId, paymentId) {
    const { data } = await apiClient.get(`/api/users/${userId}/payments/${paymentId}`)
    return data
  },

  async getPaymentHistory(userId) {
    const { data } = await apiClient.get(`/api/users/${userId}/payments`)
    return data
  },

  async refundPayment(userId, paymentId) {
    const { data } = await apiClient.post(`/api/users/${userId}/payments/${paymentId}/refund`)
    return data
  },
}

export default apiClient
