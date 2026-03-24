<template>
  <div class="mt-4 overflow-hidden rounded-lg bg-white shadow" v-if="showPlusTransferStatus">
    <div class="p-6">
      <TransferHeader />

      <TransferStatusPanel
        :status="plusTransferStatus || 'waiting_for_webhook'"
        :files="plusTransferFiles"
        :errors="plusTransferErrors"
        @action="triggerManualTransfer"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore, mapState } from 'redux-vuex'
import { selectors } from '@store'
import { PlusTransferFile, triggerPlusTransfer, loadPlusTransferStatus } from '@store/auphonic.store'
import { verifyAll } from '@store/mediafiles.store'
import type { Production } from '@store/auphonic.store'

import TransferHeader from './TransferHeader.vue'
import TransferStatusPanel from './TransferStatusPanel.vue'

export default defineComponent({
  name: 'PlusTransferStatus',
  components: {
    TransferHeader,
    TransferStatusPanel,
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
        plusTransferStatus: selectors.auphonic.plusTransferStatus,
        plusTransferFiles: selectors.auphonic.plusTransferFiles,
        plusTransferErrors: selectors.auphonic.plusTransferErrors,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  mounted() {
    this.loadPlusTransferStatus()
  },

  methods: {
    loadPlusTransferStatus() {
      if (!this.production.uuid) return

      this.dispatch(loadPlusTransferStatus({
        production_uuid: this.production.uuid
      }))
    },

    triggerManualTransfer() {
      if (this.production.uuid) {
        this.dispatch(triggerPlusTransfer({ production_uuid: this.production.uuid }))
      }
    },

    refreshEpisodeData() {
      this.dispatch(verifyAll())
    },
  },

  computed: {
    production(): Production {
      return this.state.production || {}
    },
    plusTransferStatus(): 'waiting_for_webhook' | 'in_progress' | 'completed' | 'completed_with_errors' | 'failed' | undefined {
      return this.state.plusTransferStatus
    },
    plusTransferFiles(): PlusTransferFile[] | undefined {
      return this.state.plusTransferFiles
    },
    plusTransferErrors(): string | undefined {
      return this.state.plusTransferErrors
    },
    showPlusTransferStatus(): boolean {
      return this.production.status === 3
    },
  },
})
</script>
