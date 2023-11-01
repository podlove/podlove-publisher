<template>
  <div>
    <SwitchGroup as="div" class="flex items-center">
      <Switch
        :modelValue="enabled"
        @update:modelValue="handleUpdate"
        :class="[
          enabled ? 'bg-indigo-600' : 'bg-gray-200',
          'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
        ]"
      >
        <span
          aria-hidden="true"
          :class="[
            enabled ? 'translate-x-5' : 'translate-x-0',
            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
          ]"
        />
      </Switch>
      <SwitchLabel as="span" class="ml-3">
        <span class="text-sm text-gray-900">{{
          __('Publish Episode when Production is done')
        }}</span>
      </SwitchLabel>
    </SwitchGroup>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue'
import { injectStore, mapState } from 'redux-vuex'
import { selectors } from '@store'
import * as auphonic from '@store/auphonic.store'

export default defineComponent({
  components: {
    Switch,
    SwitchGroup,
    SwitchLabel,
  },
  data() {
    return {
      enabled: false,
    }
  },
  methods: {
    handleUpdate(newValue: boolean) {
      this.enabled = newValue
      this.dispatch(auphonic.updateWebhook(newValue))
    },
  },
  setup() {
    return {
      state: mapState({
        publishWhenDone: selectors.auphonic.publishWhenDone,
      }),
      dispatch: injectStore().dispatch,
    }
  },
  // fixme: set state initially when episode data is available; somewhere in saga/store?
  mounted() {
    this.enabled = this.state.publishWhenDone
  },
})
</script>
