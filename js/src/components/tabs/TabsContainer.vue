<template>
  <div class="">
    <ul class="flex cursor-pointer">
      <li v-for="tab in tabs" :key="`trigger-tab-${tab.name}`" class="m-0">
        <button
          @click="toggleTab(tab.name)"
          class="
            relative
            font-sans font-light
            py-2
            px-4
            rounded-t
            border-gray-300 border border-b-0
            mr-2
          "
          :style="[tab.name === activeTab ? { top: '1px' } : {}]"
          :class="{
            'bg-gray-100 tab-active z-10': tab.name === activeTab,
            'bg-white': tab.name !== activeTab,
          }"
        >
          {{ tab.title }}
        </button>
      </li>
    </ul>
    <div class="p-4 bg-gray-100 border-gray-300 rounded rounded-tl-none border" ref="tabs">
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
  data() {
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
    this.tabs = this.$slots.default().map((elem) => ({
      name: get(elem, ['props', 'name']),
      title: get(elem, ['props', 'title']),
    }))
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
      Array.from(this.$refs.tabs.children).forEach((tab: HTMLElement) => {
        if (tab.dataset.tab === name) {
          tab.classList.remove('hidden')
        } else {
          tab.classList.add('hidden')
        }
      })
    },
  },
})
</script>

<style>
.tab-active {
  text-shadow:0px 0px 1px currentColor;
}
</style>
