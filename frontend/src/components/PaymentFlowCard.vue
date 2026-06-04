<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col min-h-[188px]">

    <!-- Top accent bar -->
    <div class="h-1 bg-gradient-to-r from-indigo-500 via-blue-500 to-cyan-400 flex-shrink-0"></div>

    <div class="p-6 flex flex-col flex-1 gap-4">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-0.5">Initiate Payment</p>
          <p class="text-sm font-bold text-gray-900">Process a new transaction</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-400 flex items-center justify-center flex-shrink-0 shadow-md shadow-amber-200">
          <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
          </svg>
        </div>
      </div>

      <!-- Inputs -->
      <div class="flex gap-2">
        <div class="relative flex-1">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">$</span>
          <input
            v-model.number="amount"
            type="number"
            placeholder="0.00"
            min="0.01"
            step="0.01"
            :disabled="processing"
            class="w-full pl-8 pr-3 py-2.5 border rounded-xl text-sm text-gray-900 font-medium placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-transparent disabled:bg-gray-50 disabled:cursor-not-allowed transition"
            :class="amount > 0 ? 'border-indigo-300 bg-indigo-50/30' : 'border-gray-200'"
          />
        </div>
        <select
          v-model="method"
          :disabled="processing"
          class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 bg-white font-medium focus:outline-none focus:ring-2 focus:ring-indigo-300 disabled:bg-gray-50 disabled:cursor-not-allowed transition"
        >
          <option value="card">Card</option>
          <option value="bank_transfer">Bank</option>
          <option value="crypto">Crypto</option>
        </select>
      </div>

      <!-- Result -->
      <div v-if="lastResult"
        class="flex items-center gap-3 p-3 rounded-xl border text-xs"
        :class="lastResult.status === 'success'
          ? 'bg-emerald-50 border-emerald-200'
          : lastResult.status === 'failed'
          ? 'bg-red-50 border-red-200'
          : 'bg-amber-50 border-amber-200'"
      >
        <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0"
             :class="lastResult.status === 'success' ? 'bg-emerald-200' : lastResult.status === 'failed' ? 'bg-red-200' : 'bg-amber-200'">
          <svg v-if="lastResult.status === 'success'" class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
          <svg v-else-if="lastResult.status === 'failed'" class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
          <span v-else class="w-2 h-2 rounded-full bg-amber-600"></span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span class="font-bold capitalize"
              :class="lastResult.status === 'success' ? 'text-emerald-700' : lastResult.status === 'failed' ? 'text-red-700' : 'text-amber-700'">
              {{ lastResult.status }}
            </span>
            <span class="font-bold text-gray-800">${{ parseFloat(lastResult.amount).toFixed(2) }}</span>
          </div>
          <p v-if="lastResult.reference_id" class="font-mono text-gray-500 text-xs mt-0.5 truncate">{{ lastResult.reference_id }}</p>
        </div>
        <span v-if="lastResult.status === 'success'" class="text-emerald-600 text-xs font-bold whitespace-nowrap">↑ Reverb sent</span>
      </div>

      <!-- Button -->
      <button
        @click="handleProcess"
        :disabled="!amount || amount <= 0 || processing"
        class="mt-auto w-full flex items-center justify-center gap-2 py-3 px-4 text-sm font-bold rounded-xl transition-all active:scale-[0.98] shadow-lg"
        :class="amount > 0 && !processing
          ? 'bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-indigo-200 hover:from-indigo-700 hover:to-blue-700 hover:shadow-indigo-300'
          : 'bg-gray-100 text-gray-400 shadow-none cursor-not-allowed'"
      >
        <svg v-if="processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <svg v-else-if="amount > 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        {{ processing ? 'Processing...' : 'Process Payment' }}
      </button>

      <p v-if="error" class="text-xs text-red-600 font-medium text-center">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { apiService } from '../services/api'

const props = defineProps({ userId: { type: [String, Number], required: true } })
const emit = defineEmits(['payment-complete'])

const amount = ref('')
const method = ref('card')
const processing = ref(false)
const lastResult = ref(null)
const error = ref('')

const handleProcess = async () => {
  if (!amount.value || amount.value <= 0) return
  error.value = ''; processing.value = true; lastResult.value = null
  try {
    const result = await apiService.quickPay(props.userId, amount.value, method.value)
    lastResult.value = result
    emit('payment-complete', result)
    amount.value = ''
  } catch (e) { error.value = e.message }
  finally { processing.value = false }
}
</script>
