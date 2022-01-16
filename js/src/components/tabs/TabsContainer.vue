<template>
  <div>
    <div class="block">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 mx-2" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            @click="toggleTab(tab.name)"
            :key="tab.name"
            :class="[
              tab.name === activeTab
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm',
            ]"
          >
            <span>{{ tab.title }}</span>
          </button>
        </nav>
      </div>
    </div>
    <div ref="tabs">
      <slot></slot>
    </div>
  </div>
</template>

<script lang="ts">
import { get } from 'lodash'
import { defineComponent } from 'vue'

export interface Tab {
  title: string
  name: string
}

export default defineComponent({
  data(): { activeTab: string; tabs: Tab[] } {
    return {
      activeTab: '',
      tabs: [],
    }
  },

  props: {
    active: {
      type: String,
      default: null,
    },
  },

  created() {
    this.tabs =
      this.$slots.default?.().map((elem) => ({
        name: get(elem, ['props', 'name']),
        title: get(elem, ['props', 'title']),
      })) || []
  },

  mounted() {
    if (this.active) {
      this.toggleTab(this.active)
    } else {
      this.toggleTab(get(this.tabs, [0, 'name']))
    }
  },

  methods: {
    toggleTab(name: string) {
      this.activeTab = name
      ;(Array.from((this.$refs.tabs as HTMLElement).children) as HTMLElement[]).forEach(
        (tab: HTMLElement) => {
          if (tab.dataset.tab === name) {
            tab.classList.remove('hidden')
          } else {
            tab.classList.add('hidden')
          }
        }
      )
    },
  },
})
</script>

<style>
.tab-active {
  text-shadow: 0px 0px 1px currentColor;
}
</style>
