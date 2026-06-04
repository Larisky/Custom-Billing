<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center shadow-sm shadow-indigo-200">
          <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
        </div>
        <h2 class="text-sm font-bold text-gray-900">Payment history</h2>
      </div>

      <div class="flex items-center gap-3">
        <!-- Per-page dropdown -->
        <div v-if="payments.length > 0" class="flex items-center gap-1.5 text-xs text-gray-500 font-medium">
          <span>Show</span>
          <select
            v-model="perPage"
            @change="currentPage = 1"
            class="border border-gray-200 rounded-lg px-2 py-1 text-xs font-semibold text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200 cursor-pointer"
          >
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
          <span>per page</span>
        </div>
        <span class="text-xs text-gray-500 font-semibold bg-gray-100 px-2.5 py-1 rounded-full">
          {{ payments.length }} record{{ payments.length !== 1 ? 's' : '' }}
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-10 flex justify-center">
      <div class="w-6 h-6 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="payments.length === 0" class="py-14 flex flex-col items-center gap-3">
      <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-50 to-blue-50 flex items-center justify-center border border-indigo-100 shadow-sm">
        <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
      </div>
      <div class="text-center">
        <p class="text-sm font-bold text-gray-700">No payments yet</p>
        <p class="text-xs text-gray-500 mt-1 font-medium">Process your first payment above to see history here</p>
      </div>
    </div>

    <template v-else>
      <!-- Column headers -->
      <div class="grid grid-cols-6 px-6 py-2.5 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider gap-4">
        <span>Payment ID</span>
        <span>Amount</span>
        <span>Status</span>
        <span>Date</span>
        <span>Method</span>
        <span></span>
      </div>

      <!-- Rows -->
      <div v-for="payment in paginatedPayments" :key="payment.id">
        <div
          @click="toggle(payment.id)"
          class="grid grid-cols-6 px-6 py-3.5 items-center gap-4 cursor-pointer border-b border-gray-50 last:border-0 group transition-all"
          :class="expanded === payment.id
            ? 'bg-indigo-600'
            : 'hover:bg-gradient-to-r hover:from-indigo-50/50 hover:to-blue-50/30'"
        >
          <span class="font-mono text-xs font-bold px-2 py-1 rounded-lg w-fit"
            :class="expanded === payment.id ? 'bg-white/20 text-white' : 'bg-indigo-50 text-indigo-600'">
            #{{ payment.id }}
          </span>

          <span class="text-sm font-bold tabular"
            :class="expanded === payment.id ? 'text-white' : 'text-gray-900'">
            ${{ formatAmount(payment.amount) }}
          </span>

          <span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold"
              :class="expanded === payment.id ? 'bg-white/20 text-white' : chipClass(payment.status)">
              <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                :class="expanded === payment.id ? 'bg-white' : dotClass(payment.status)"></span>
              {{ capitalize(payment.status) }}
            </span>
          </span>

          <span class="text-xs font-semibold"
            :class="expanded === payment.id ? 'text-indigo-100' : 'text-gray-600'">
            {{ formatDate(payment.created_at) }}
          </span>

          <span class="text-xs font-semibold"
            :class="expanded === payment.id ? 'text-indigo-100' : 'text-gray-600'">
            {{ formatMethod(payment.payment_method) }}
          </span>

          <div class="flex justify-end">
            <div class="w-6 h-6 rounded-full flex items-center justify-center transition-all"
                 :class="expanded === payment.id ? 'bg-white/20' : 'bg-gray-100 group-hover:bg-indigo-100'">
              <svg class="w-3 h-3 transition-transform duration-200"
                :class="[expanded === payment.id ? 'rotate-180 text-white' : 'text-gray-500 group-hover:text-indigo-600']"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <polyline points="6 9 12 15 18 9" stroke-width="2.5" stroke-linecap="round"/>
              </svg>
            </div>
          </div>
        </div>

        <transition name="expand">
          <div v-if="expanded === payment.id" class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-indigo-100">
            <div class="grid grid-cols-4 gap-4">
              <div>
                <p class="text-xs text-gray-500 font-semibold mb-1">Status</p>
                <p class="text-sm font-bold text-gray-900 capitalize">{{ payment.status }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 font-semibold mb-1">Date</p>
                <p class="text-sm font-bold text-gray-900">{{ formatDate(payment.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 font-semibold mb-1">Reference</p>
                <code class="text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg border border-indigo-100 block truncate">
                  {{ payment.reference_id }}
                </code>
              </div>
              <div>
                <p class="text-xs text-gray-500 font-semibold mb-1">Amount</p>
                <p class="text-lg font-black text-gray-900 tabular">${{ formatAmount(payment.amount) }}</p>
              </div>
            </div>
          </div>
        </transition>
      </div>

      <!-- Pagination bar -->
      <div v-if="totalPages > 1" class="px-6 py-3 border-t border-gray-100 flex items-center justify-between bg-gray-50/60">
        <p class="text-xs text-gray-500 font-medium">
          Showing <span class="font-bold text-gray-700">{{ rangeStart }}–{{ rangeEnd }}</span> of
          <span class="font-bold text-gray-700">{{ payments.length }}</span> payments
        </p>
        <div class="flex items-center gap-1">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-500 hover:bg-white hover:text-indigo-600 hover:shadow-sm disabled:opacity-30 disabled:cursor-not-allowed transition-all border border-transparent hover:border-gray-100"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6" stroke-width="2.5" stroke-linecap="round"/></svg>
          </button>

          <button
            v-for="page in visiblePages"
            :key="page"
            @click="page !== '…' && (currentPage = page)"
            class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold transition-all"
            :class="page === currentPage
              ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200'
              : page === '…'
              ? 'text-gray-400 cursor-default'
              : 'text-gray-500 hover:bg-white hover:text-indigo-600 hover:shadow-sm border border-transparent hover:border-gray-100'"
          >
            {{ page }}
          </button>

          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-500 hover:bg-white hover:text-indigo-600 hover:shadow-sm disabled:opacity-30 disabled:cursor-not-allowed transition-all border border-transparent hover:border-gray-100"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6" stroke-width="2.5" stroke-linecap="round"/></svg>
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  payments: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const expanded = ref(null)
const perPage = ref(5)
const currentPage = ref(1)

watch(() => props.payments.length, () => { currentPage.value = 1 })

const totalPages = computed(() => Math.max(1, Math.ceil(props.payments.length / perPage.value)))

const paginatedPayments = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return props.payments.slice(start, start + perPage.value)
})

const rangeStart = computed(() => (currentPage.value - 1) * perPage.value + 1)
const rangeEnd = computed(() => Math.min(currentPage.value * perPage.value, props.payments.length))

const visiblePages = computed(() => {
  const total = totalPages.value
  const cur = currentPage.value
  if (total <= 5) return Array.from({ length: total }, (_, i) => i + 1)
  if (cur <= 3) return [1, 2, 3, '…', total]
  if (cur >= total - 2) return [1, '…', total - 2, total - 1, total]
  return [1, '…', cur, '…', total]
})

const toggle = (id) => { expanded.value = expanded.value === id ? null : id }

const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : ''
const formatAmount = (v) => parseFloat(v).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
const formatMethod = (m) => ({ card: 'Visa card', bank_transfer: 'Bank transfer', crypto: 'Crypto' }[m] || m || 'Card')

const chipClass = (s) => ({
  success:    'bg-emerald-100 text-emerald-700',
  failed:     'bg-red-100 text-red-700',
  pending:    'bg-amber-100 text-amber-700',
  processing: 'bg-blue-100 text-blue-700',
  refunded:   'bg-gray-100 text-gray-700',
}[s] || 'bg-gray-100 text-gray-600')

const dotClass = (s) => ({
  success: 'bg-emerald-500', failed: 'bg-red-500', pending: 'bg-amber-500',
  processing: 'bg-blue-500', refunded: 'bg-gray-400',
}[s] || 'bg-gray-400')
</script>

<style scoped>
.expand-enter-active, .expand-leave-active { transition: all 0.2s ease; }
.expand-enter-from, .expand-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
