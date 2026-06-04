<template>
  <div class="flex h-screen overflow-hidden bg-mesh" style="font-family:'Inter',sans-serif">

    <!-- ════════════ LEFT SIDEBAR ════════════ -->
    <aside class="w-64 bg-white/90 backdrop-blur-sm flex flex-col h-screen flex-shrink-0 border-r border-indigo-100/60 shadow-[2px_0_24px_0_rgba(99,102,241,0.08)]">

      <!-- Logo -->
      <div class="px-5 py-4 flex-shrink-0 border-b border-gray-100">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-200/60 flex-shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>
          <div>
            <p class="text-sm font-bold text-gray-900 leading-none tracking-tight">Real-Time Billing</p>
            <p class="text-xs text-indigo-500 font-semibold mt-0.5">Dashboard</p>
          </div>
        </div>
      </div>

      <!-- User Card -->
      <div class="px-4 py-4 flex-shrink-0 border-b border-gray-100">
        <div v-if="user" class="flex flex-col items-center text-center gap-2">
          <div class="relative">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-400 via-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-xl shadow-indigo-200">
              {{ user.name.charAt(0) }}
            </div>
            <span
              class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white flex-shrink-0"
              :class="wsConnected ? 'bg-emerald-500 glow-dot' : 'bg-gray-300'"
            >
              <span v-if="wsConnected" class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-75"></span>
            </span>
          </div>
          <div>
            <p class="font-bold text-gray-900 text-sm leading-none">{{ user.name }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ user.email }}</p>
          </div>
          <div
            class="w-full px-3 py-1.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all"
            :class="wsConnected
              ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
              : 'bg-gray-50 text-gray-500 border border-gray-200'"
          >
            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="wsConnected ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
            {{ wsConnected ? 'WebSocket Connected' : 'Connecting...' }}
          </div>
        </div>
        <div v-else class="flex justify-center py-3">
          <div class="w-6 h-6 border-2 border-indigo-300 border-t-transparent rounded-full animate-spin"></div>
        </div>
      </div>

      <!-- Session Stats -->
      <div class="px-4 py-3 flex-shrink-0 border-b border-gray-100">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2.5">Session</p>
        <div class="grid grid-cols-3 gap-1.5 mb-2.5">
          <div class="bg-gray-50 rounded-xl p-2 text-center border border-gray-100">
            <p class="text-base font-bold text-gray-800 tabular">{{ payments.length }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Total</p>
          </div>
          <div class="bg-emerald-50 rounded-xl p-2 text-center border border-emerald-100">
            <p class="text-base font-bold text-emerald-600 tabular">{{ successCount }}</p>
            <p class="text-xs text-emerald-600 mt-0.5">Done</p>
          </div>
          <div class="bg-red-50 rounded-xl p-2 text-center border border-red-100">
            <p class="text-base font-bold text-red-500 tabular">{{ failedCount }}</p>
            <p class="text-xs text-red-400 mt-0.5">Failed</p>
          </div>
        </div>
        <div v-if="payments.length > 0">
          <div class="flex justify-between text-xs mb-1">
            <span class="text-gray-500 font-medium">Success rate</span>
            <span class="font-bold" :class="successRate >= 70 ? 'text-emerald-600' : 'text-amber-600'">{{ successRate }}%</span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
            <div
              class="h-1.5 rounded-full transition-all duration-700"
              :class="successRate >= 70 ? 'bg-gradient-to-r from-emerald-400 to-emerald-500' : 'bg-gradient-to-r from-amber-400 to-amber-500'"
              :style="{ width: successRate + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- How it works — boxed card -->
      <div class="px-3 py-3 flex-1 min-h-0 overflow-hidden">
        <div class="h-full rounded-2xl border border-indigo-100 overflow-hidden flex flex-col"
             style="background: linear-gradient(145deg, #eef2ff 0%, #e0e7ff 100%)">
          <div class="px-3 py-2.5 border-b border-indigo-100/60 flex items-center gap-2 flex-shrink-0">
            <div class="w-5 h-5 rounded-lg bg-indigo-600 flex items-center justify-center">
              <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
            </div>
            <p class="text-xs font-bold text-indigo-700 uppercase tracking-wider">How it works</p>
          </div>
          <div class="flex-1 min-h-0 overflow-hidden px-3 py-2.5">
            <div v-for="(step, i) in steps" :key="i" class="flex gap-2">
              <div class="flex flex-col items-center flex-shrink-0">
                <div
                  class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-500 flex-shrink-0 border"
                  :class="step.done
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-300'
                    : 'bg-white text-gray-400 border-gray-200'"
                >
                  <svg v-if="step.done" class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                  </svg>
                  <span v-else class="text-xs">{{ i + 1 }}</span>
                </div>
                <div v-if="i < steps.length - 1" class="w-px flex-1 mt-0.5 mb-0.5 transition-colors duration-500"
                     :class="step.done ? 'bg-indigo-300' : 'bg-indigo-100'" style="min-height:6px"></div>
              </div>
              <div class="pb-2 min-w-0">
                <p class="text-xs font-bold leading-none transition-colors duration-300"
                   :class="step.done ? 'text-indigo-800' : 'text-indigo-300'">{{ step.title }}</p>
                <p class="text-xs mt-0.5 leading-relaxed"
                   :class="step.done ? 'text-indigo-600' : 'text-indigo-300'">{{ step.desc }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- ════════════ MAIN CONTENT ════════════ -->
    <main class="flex-1 flex flex-col overflow-hidden min-w-0 relative">
      <div class="px-8 pt-6 pb-4 flex-shrink-0">
        <div class="flex items-end justify-between">
          <div>
            <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1.5">Overview</p>
            <h1 class="text-[22px] font-bold leading-none bg-gradient-to-r from-gray-900 via-indigo-700 to-gray-800 bg-clip-text text-transparent">
              Real-Time Billing Dashboard
            </h1>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500 font-medium">
            <span class="w-1.5 h-1.5 rounded-full" :class="wsConnected ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300'"></span>
            PaymentStatusChanged broadcasting
          </div>
        </div>
      </div>

      <div class="flex-1 px-8 pb-6 overflow-y-auto space-y-5">
        <!-- Cards render as soon as user loads — payments load in background -->
        <div v-if="!user" class="grid grid-cols-2 gap-5">
          <div class="rounded-2xl min-h-[188px] animate-pulse" style="background:linear-gradient(135deg,#1e2f8f,#2d46cc)">
            <div class="p-6 space-y-3">
              <div class="h-3 w-28 bg-white/20 rounded-full"></div>
              <div class="h-10 w-40 bg-white/20 rounded-xl"></div>
              <div class="h-3 w-36 bg-white/10 rounded-full mt-6"></div>
            </div>
          </div>
          <div class="bg-white rounded-2xl min-h-[188px] animate-pulse border border-gray-100">
            <div class="p-6 space-y-4">
              <div class="h-3 w-24 bg-gray-100 rounded-full"></div>
              <div class="h-8 w-full bg-gray-100 rounded-xl"></div>
              <div class="h-10 w-full bg-gray-100 rounded-xl mt-4"></div>
            </div>
          </div>
        </div>
        <template v-else>
          <div class="grid grid-cols-2 gap-5">
            <BalanceCard
              :user-id="user.id"
              :initial-balance="user.balance"
              @payment-success="onPaymentSuccess"
              @ws-connected="onWsConnected"
            />
            <PaymentFlowCard
              :user-id="user.id"
              @payment-complete="onPaymentComplete"
            />
          </div>
          <PaymentHistory :payments="payments" :loading="historyLoading" />
        </template>
      </div>
    </main>

    <!-- ════════════ RIGHT PANEL ════════════ -->
    <aside class="w-64 flex-shrink-0 flex flex-col h-screen overflow-y-auto px-4 py-5 gap-4 border-l border-indigo-100/60">

      <!-- Real-time Status -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-4 shadow-sm border border-white flex-shrink-0">
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-bold text-gray-800">Real-time Status</p>
          <span
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border"
            :class="wsConnected ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200'"
          >
            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
              :class="wsConnected ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"></span>
            {{ wsConnected ? 'Online' : 'Offline' }}
          </span>
        </div>
        <div class="space-y-2.5">
          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500 font-medium">Transport</span>
            <span class="text-xs font-bold text-gray-800">WebSocket</span>
          </div>
          <!-- Channel — stacked to avoid overflow -->
          <div class="space-y-1">
            <span class="text-xs text-gray-500 font-medium">Channel</span>
            <code class="block text-xs font-mono text-indigo-600 bg-indigo-50 px-2.5 py-1.5 rounded-lg break-all leading-relaxed border border-indigo-100">
              private-payment.user.{{ user?.id }}
            </code>
          </div>
          <div class="space-y-1">
            <span class="text-xs text-gray-500 font-medium">Event</span>
            <code class="block text-xs font-mono text-indigo-600 bg-indigo-50 px-2.5 py-1.5 rounded-lg break-all border border-indigo-100">
              payment.status.changed
            </code>
          </div>
        </div>
      </div>

      <!-- Event Log -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-4 shadow-sm border border-white flex-shrink-0">
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-bold text-gray-800">Event Log</p>
          <span class="text-xs font-bold text-white bg-indigo-500 w-5 h-5 rounded-full flex items-center justify-center shadow-sm shadow-indigo-200">
            {{ eventLog.length }}
          </span>
        </div>

        <div v-if="eventLog.length === 0" class="py-5 flex flex-col items-center gap-2">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-gray-50 to-indigo-50 flex items-center justify-center border border-indigo-100">
            <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>
          <p class="text-xs font-bold text-gray-600">No events yet</p>
          <p class="text-xs text-gray-400 text-center leading-relaxed">Process a payment to see<br>real-time events here</p>
        </div>

        <div v-else class="space-y-2 max-h-44 overflow-y-auto">
          <transition-group name="event-item">
            <div
              v-for="event in eventLog"
              :key="event.id"
              class="flex items-start gap-2.5 p-2.5 rounded-xl border transition-all"
              :class="event.status === 'success' ? 'bg-emerald-50 border-emerald-100' : event.status === 'failed' ? 'bg-red-50 border-red-100' : 'bg-amber-50 border-amber-100'"
            >
              <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                   :class="event.status === 'success' ? 'bg-emerald-200' : event.status === 'failed' ? 'bg-red-200' : 'bg-amber-200'">
                <svg v-if="event.status === 'success'" class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                <svg v-else-if="event.status === 'failed'" class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                <span v-else class="w-2 h-2 rounded-full bg-amber-600"></span>
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="text-xs font-bold capitalize"
                    :class="event.status === 'success' ? 'text-emerald-700' : event.status === 'failed' ? 'text-red-700' : 'text-amber-700'">
                    {{ event.status }}
                  </span>
                  <span class="text-xs font-bold text-gray-800">${{ parseFloat(event.amount).toFixed(2) }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 font-medium">{{ event.time }}</p>
              </div>
            </div>
          </transition-group>
        </div>
      </div>

      <!-- Tech Stack -->
      <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-4 shadow-sm border border-white flex-shrink-0">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Tech Stack</p>
        <div class="space-y-2">
          <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl border border-indigo-100 hover:shadow-md hover:shadow-indigo-100 transition-all cursor-default">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center flex-shrink-0 shadow-md shadow-indigo-200">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <p class="text-xs font-bold text-indigo-800">Laravel Reverb</p>
              <p class="text-xs text-indigo-500 font-medium">WebSocket server</p>
            </div>
          </div>
          <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl border border-orange-100 hover:shadow-md hover:shadow-orange-100 transition-all cursor-default">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center flex-shrink-0 shadow-md shadow-orange-200">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
              <p class="text-xs font-bold text-orange-800">Redis Queue</p>
              <p class="text-xs text-orange-500 font-medium">Async broadcasting</p>
            </div>
          </div>
          <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-100 hover:shadow-md hover:shadow-emerald-100 transition-all cursor-default">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center flex-shrink-0 shadow-md shadow-emerald-200">
              <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M2 6l10.455-6L22.91 6 23 17.95 12.455 24 2 17.95 2 6zm2.088 2.481v4.757l3.345 1.86v3.516l3.972 2.296v-8.272L4.088 8.481zm16.739 0l-7.317 4.157v8.272l3.972-2.296V15.1l3.345-1.861V8.48zM12.22 2.187L4.88 6.366l7.342 4.173 7.312-4.173-7.314-4.179z"/></svg>
            </div>
            <div>
              <p class="text-xs font-bold text-emerald-800">Vue 3 + Pusher.js</p>
              <p class="text-xs text-emerald-500 font-medium">Real-time SPA</p>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- ════════════ TOASTS ════════════ -->
    <div class="fixed bottom-5 right-72 space-y-2 z-50 pointer-events-none">
      <transition-group name="toast">
        <div v-for="n in toasts" :key="n.id"
          class="flex items-center gap-3 text-white text-sm px-4 py-3 rounded-2xl shadow-2xl pointer-events-auto max-w-xs font-medium"
          :class="n.type === 'success' ? 'bg-emerald-600 shadow-emerald-200' : n.type === 'error' ? 'bg-red-600 shadow-red-200' : 'bg-gray-900'"
        >
          <svg v-if="n.type === 'success'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
          <svg v-else-if="n.type === 'error'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
          <svg v-else class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          {{ n.message }}
        </div>
      </transition-group>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import BalanceCard from './components/BalanceCard.vue'
import PaymentFlowCard from './components/PaymentFlowCard.vue'
import PaymentHistory from './components/PaymentHistory.vue'
import { apiService } from './services/api'

const user = ref(null)
const payments = ref([])
const loading = ref(false)
const historyLoading = ref(false)
const toasts = ref([])
const eventLog = ref([])
const wsConnected = ref(false)
const paymentDone = ref(false)

const successCount = computed(() => payments.value.filter(p => p.status === 'success').length)
const failedCount = computed(() => payments.value.filter(p => p.status === 'failed').length)
const successRate = computed(() => {
  if (!payments.value.length) return 0
  return Math.round((successCount.value / payments.value.length) * 100)
})

const steps = computed(() => [
  { title: 'Enter & process payment', desc: 'Fill amount, click Process', done: true },
  { title: 'Payment created', desc: 'Stored in MySQL as pending', done: true },
  { title: 'Dispatched to Redis', desc: 'PaymentStatusChanged queued', done: paymentDone.value },
  { title: 'Reverb broadcasts', desc: 'Pushed to private WS channel', done: wsConnected.value && paymentDone.value },
  { title: 'Balance updates live', desc: 'SPA receives event instantly', done: wsConnected.value && paymentDone.value },
])

const toast = (message, type = 'success') => {
  const id = Date.now()
  toasts.value.push({ id, message, type })
  setTimeout(() => { toasts.value = toasts.value.filter(n => n.id !== id) }, 4000)
}

const logEvent = (data) => {
  eventLog.value.unshift({
    id: Date.now(),
    status: data.new_status || data.status,
    amount: data.amount,
    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
  })
  if (eventLog.value.length > 8) eventLog.value.pop()
}

const loadPayments = async () => {
  historyLoading.value = true
  try {
    const data = await apiService.getPaymentHistory(user.value.id)
    payments.value = data.payments || []
  } finally {
    historyLoading.value = false
  }
}

const onWsConnected = (val) => { wsConnected.value = val }
const onPaymentSuccess = (data) => { logEvent(data); paymentDone.value = true; toast(`Reverb: +$${parseFloat(data.amount).toFixed(2)} received`, 'success'); loadPayments() }
const onPaymentComplete = (result) => { logEvent(result); paymentDone.value = true; toast(`Payment ${result.status} — $${parseFloat(result.amount).toFixed(2)}`, result.status === 'success' ? 'success' : result.status === 'failed' ? 'error' : 'info'); loadPayments() }

onMounted(async () => {
  // Load user first so cards render immediately, then fetch payments in parallel
  user.value = await apiService.getTestUser()

  // Payments load in background — cards are already visible
  loadPayments().then(() => {
    if (payments.value.length > 0) paymentDone.value = true
  })
})
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-enter-from { opacity: 0; transform: translateX(20px) scale(0.9); }
.toast-leave-to { opacity: 0; transform: translateX(20px) scale(0.9); }
.event-item-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.event-item-enter-from { opacity: 0; transform: translateY(-10px) scale(0.95); }
</style>
