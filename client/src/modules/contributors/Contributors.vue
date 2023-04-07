<template>
  <module name="chapters" :title="__('Contributors')" class="overflow-hidden">
    <template v-slot:actions> </template>
    <ul role="list" class="divide-y divide-gray-200">
      <li v-for="(contribution, index) in state.contributions" :key="contribution.position">
        <Contribution
          v-if="contribution?.contributor_id"
          :data="contribution"
          :first="index === 0"
          :last="index === state.contributions.length - 1"
        />
      </li>
      <li v-if="addContributorInput">
        <AddContribution
          @addContributor="addContributor($event)"
          @createContributor="createContributor($event)"
          @close="closeAddContributor()"
        />
      </li>
    </ul>
    <div class="py-5 px-6 border-t border-gray-200">
      <podlove-button variant="primary" @click="showAddContributor()">
        <plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> {{ __('Add Contributor') }}
      </podlove-button>
    </div>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Module from '@components/module/Module.vue'
import PodloveButton from '@components/button/Button.vue'
import Contribution from './components/Contribution.vue'
import AddContribution from './components/AddContribution.vue'

import { injectStore, mapState } from 'redux-vuex'
import { PlusSmIcon } from '@heroicons/vue/outline'

import { selectors } from '@store'
import * as contributors from '@store/contributors.store'
import * as episode from '@store/episode.store'
import { PodloveEpisodeContribution } from '@types/episode.types'
import { PodloveContributor, PodloveRole, PodloveGroup } from '@types/contributors.types'

export default defineComponent({
  components: {
    Module,
    PodloveButton,
    PlusSmIcon,
    Contribution,
    AddContribution,
  },

  data() {
    return {
      addContributorInput: false,
    }
  },

  setup(): {
    dispatch: Function
    state: {
      contributions: (PodloveContributor & PodloveEpisodeContribution)[]
      roles: PodloveRole[]
      groups: PodloveGroup[]
    }
  } {
    return {
      dispatch: injectStore().dispatch,
      state: mapState({
        contributions: selectors.episode.contributions,
        roles: selectors.contributors.roles,
        groups: selectors.contributors.groups,
      }),
    }
  },

  created() {
    this.dispatch(contributors.init())
  },

  methods: {
    showAddContributor() {
      this.addContributorInput = true
    },
    addContributor(contributor: PodloveContributor) {
      this.addContributorInput = false
      this.dispatch(episode.addContribution(contributor))
    },
    createContributor(name: string) {
      this.addContributorInput = false
      this.dispatch(episode.createContribution(name))
    },
    closeAddContributor() {
      this.addContributorInput = false
    },
  },
})
</script>

<style lang="postcss"></style>
