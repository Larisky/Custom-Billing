import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import BalanceCard from '../BalanceCard.vue'
import { apiService } from '../../services/api'
import { createWebSocket } from '../../services/websocket'

vi.mock('../../services/api', () => ({
  apiService: {
    getBalance: vi.fn(),
  },
}))

vi.mock('../../services/websocket', () => ({
  createWebSocket: vi.fn(),
}))

describe('BalanceCard', () => {
  let channelHandlers
  let mockPusher

  beforeEach(() => {
    channelHandlers = {}
    mockPusher = {
      connection: { bind: vi.fn() },
      subscribe: vi.fn(() => ({
        bind: vi.fn((event, handler) => { channelHandlers[event] = handler }),
      })),
      disconnect: vi.fn(),
    }

    createWebSocket.mockReturnValue(mockPusher)
    apiService.getBalance.mockResolvedValue({ balance: 100 })
  })

  it('subscribes to the user-specific private channel', async () => {
    mount(BalanceCard, { props: { userId: 1, initialBalance: 100 } })
    await flushPromises()

    expect(createWebSocket).toHaveBeenCalledWith(1)
    expect(mockPusher.subscribe).toHaveBeenCalledWith('private-payment.user.1')
  })

  it('updates the balance and emits payment-success on a successful payment event', async () => {
    const wrapper = mount(BalanceCard, { props: { userId: 1, initialBalance: 100 } })
    await flushPromises()

    expect(wrapper.text()).toContain('100')

    channelHandlers['payment.status.changed']({ new_status: 'success', amount: '50.00' })
    await flushPromises()

    expect(wrapper.text()).toContain('150')
    expect(wrapper.emitted('payment-success')).toBeTruthy()
    expect(wrapper.emitted('payment-success')[0][0]).toEqual({ new_status: 'success', amount: '50.00' })
  })

  it('ignores non-success status updates', async () => {
    const wrapper = mount(BalanceCard, { props: { userId: 1, initialBalance: 100 } })
    await flushPromises()

    channelHandlers['payment.status.changed']({ new_status: 'failed', amount: '50.00' })
    await flushPromises()

    expect(wrapper.text()).toContain('100')
    expect(wrapper.emitted('payment-success')).toBeFalsy()
  })

  it('emits ws-connected when the socket connects', async () => {
    const wrapper = mount(BalanceCard, { props: { userId: 1, initialBalance: 100 } })
    await flushPromises()

    const [, connectedHandler] = mockPusher.connection.bind.mock.calls.find(([event]) => event === 'connected')
    connectedHandler()
    await flushPromises()

    expect(wrapper.emitted('ws-connected')).toEqual([[true]])
    expect(wrapper.text()).toContain('Connected')
  })
})
