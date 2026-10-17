import { computed, reactive } from 'vue'
import api from '../services/api'

const TOKEN_KEY = 'stokku_token'
const USER_KEY = 'stokku_user'

function readStoredUser() {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY) || 'null')
  } catch {
    return null
  }
}

const state = reactive({
  token: localStorage.getItem(TOKEN_KEY),
  user: readStoredUser(),
  initialized: false,
})

const isAuthenticated = computed(() => Boolean(state.token))

function setSession(token, user) {
  state.token = token
  state.user = user
  localStorage.setItem(TOKEN_KEY, token)
  localStorage.setItem(USER_KEY, JSON.stringify(user))
}

function clearSession() {
  state.token = null
  state.user = null
  state.initialized = false
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(USER_KEY)
}

async function login(credentials) {
  const response = await api.post('/login', credentials)
  setSession(response.data.data.token, response.data.data.user)
  state.initialized = true

  return response.data.data.user
}

async function fetchUser() {
  const response = await api.get('/me')
  state.user = response.data.data
  localStorage.setItem(USER_KEY, JSON.stringify(state.user))

  return state.user
}

async function ensureInitialized() {
  if (state.initialized) {
    return
  }

  if (!state.token) {
    state.initialized = true
    return
  }

  try {
    await fetchUser()
  } catch {
    clearSession()
  } finally {
    state.initialized = true
  }
}

async function logout() {
  try {
    await api.post('/logout')
  } finally {
    clearSession()
  }
}

export function useAuth() {
  return {
    state,
    isAuthenticated,
    login,
    logout,
    fetchUser,
    ensureInitialized,
    clearSession,
  }
}
