<template>
  <div class="relative rounded-2xl overflow-hidden flex flex-col justify-between min-h-[188px] p-6 shimmer"
       style="background: linear-gradient(135deg, #0f1648 0%, #1e2f8f 45%, #2d46cc 80%, #3b5bd9 100%)">

    <!-- Decorative orbs -->
    <div class="absolute -bottom-8 -right-8 w-44 h-44 rounded-full pointer-events-none"
         style="background: radial-gradient(circle, #f5c842cc 0%, transparent 65%)"></div>
    <div class="absolute top-0 left-0 w-64 h-full pointer-events-none opacity-10"
         style="background: radial-gradient(ellipse at 20% 50%, #818cf8 0%, transparent 60%)"></div>
    <div class="absolute top-4 right-16 w-16 h-16 rounded-full pointer-events-none opacity-20"
         style="background: radial-gradient(circle, #fff 0%, transparent 70%)"></div>

    <!-- Star badge -->
    <div class="absolute top-5 right-5">
      <div class="w-11 h-11 rounded-2xl flex items-center justify-center"
           style="background: rgba(255,255,255,0.12); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.15)">
        <svg class="w-5 h-5 text-yellow-300 drop-shadow-sm" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
      </div>
    </div>

    <!-- Top: label + connection -->
    <div class="relative z-10">
      <div class="flex items-center justify-between mb-3">
        <p class="text-blue-200/90 text-xs font-bold uppercase tracking-widest">Current Balance</p>
        <span
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border"
          :class="isConnected
            ? 'border-emerald-400/40 text-emerald-300' : 'border-white/20 text-white/50'"
          style="background: rgba(255,255,255,0.1); backdrop-filter: blur(4px)"
        >
          <span class="w-1.5 h-1.5 rounded-full"
            :class="isConnected ? 'bg-emerald-400 animate-pulse' : 'bg-white/30'"></span>
          {{ isConnected ? 'Connected' : 'Disconnected' }}
        </span>
      </div>

      <div
        class="flex items-baseline gap-1 transition-all duration-300"
        :class="{ 'scale-[1.03]': justUpdated }"
      >
        <span class="text-white font-bold tracking-tight tabular leading-none"
              style="font-size: 42px">${{ wholeAmount }}</span>
        <span class="text-blue-200/80 font-bold" style="font-size: 22px">.{{ centsAmount }}</span>
      </div>
    </div>

    <!-- Bottom: live update indicator -->
    <div class="relative z-10 flex items-center justify-between mt-4">
      <div>
        <p class="text-blue-200/80 text-xs font-semibold">Updated {{ lastUpdated }}</p>
        <p class="text-blue-300/60 text-xs mt-0.5">via PaymentStatusChanged event</p>
      </div>
      <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold"
           style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.15)">
        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
          :class="isConnected ? 'bg-emerald-400 animate-pulse' : 'bg-white/40'"></span>
        <span class="text-white/80">{{ isConnected ? 'Live' : 'Offline' }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { apiService } from '../services/api'
import { createWebSocket } from '../services/websocket'

const props = defineProps({
  userId: { type: [String, Number], required: true },
  initialBalance: { type: [String, Number], default: 0 },
})
const emit = defineEmits(['payment-success', 'ws-connected'])

const balance = ref(parseFloat(props.initialBalance) || 0)
const isConnected = ref(false)
const lastUpdated = ref('just now')
const loading = ref(false)
const justUpdated = ref(false)
const pusher = ref(null)

const wholeAmount = computed(() => Math.floor(balance.value).toLocaleString())
const centsAmount = computed(() => (balance.value % 1).toFixed(2).slice(2))

const tick = () => { lastUpdated.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }
const flash = () => { justUpdated.value = true; setTimeout(() => { justUpdated.value = false }, 400) }

const refreshBalance = async () => {
  loading.value = true
  try {
    const data = await apiService.getBalance(props.userId)
    balance.value = parseFloat(data.balance) || 0
    tick()
  } finally { loading.value = false }
}

onMounted(async () => {
  await refreshBalance()
  pusher.value = createWebSocket(props.userId)
  pusher.value.connection.bind('connected', () => { isConnected.value = true; emit('ws-connected', true) })
  pusher.value.connection.bind('disconnected', () => { isConnected.value = false; emit('ws-connected', false) })
  const ch = pusher.value.subscribe(`private-payment.user.${props.userId}`)
  ch.bind('payment.status.changed', (data) => {
    if (data.new_status === 'success') {
      balance.value = parseFloat(balance.value) + parseFloat(data.amount)
      tick(); flash(); emit('payment-success', data)
    }
  })
})
onUnmounted(() => { if (pusher.value) pusher.value.disconnect() })
</script>
