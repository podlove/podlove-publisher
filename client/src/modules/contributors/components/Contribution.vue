<template>
  <div class="block hover:bg-gray-50">
    <div class="flex items-center px-4 py-4 sm:px-6">
      <div class="flex min-w-0 flex-1 items-center">
        <div class="flex-shrink-0">
          <img
            v-if="data.avatar_url"
            class="h-12 w-12 rounded-full"
            :src="data.avatar_url"
            :alt="data.name"
            @error="data.avatar_url = ''"
          />
          <UserCircleIcon
            v-if="!data.avatar_url"
            class="h-12 w-12 flex-shrink-0 rounded-full text-gray-500"
          />
        </div>
        <div class="min-w-0 flex-1 px-2 md:grid md:gap-4">
          <div class="flex-shrink-0">
            <p class="truncate text-sm font-medium text-gray-900">
              {{ data.realname || data.publicname }}
            </p>
            <p class="flex items-center text-sm text-gray-500">
              <span class="truncate">{{ data.nickname }}</span>
            </p>
          </div>
        </div>
        <div class="min-w-0 flex-1 px-4 md:grid md:gap-4">
          <div>
            <label for="location" class="block text-sm font-medium leading-6 text-gray-900">{{
              __('Role', 'podlove-podcasting-plugin-for-wordpress')
            }}</label>
            <select
              class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
              @change="($event) => updateRole($event)"
            >
              <option
                v-for="role in state.roles"
                :value="role.id"
                :selected="role.id === data.role_id"
              >
                {{ role.title }}
              </option>
            </select>
          </div>
        </div>
        <div class="min-w-0 flex-1 px-4 md:grid md:gap-4">
          <div>
            <label for="location" class="block text-sm font-medium leading-6 text-gray-900">{{
              __('Group', 'podlove-podcasting-plugin-for-wordpress')
            }}</label>
            <select
              class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
              @change="($event) => updateGroup($event)"
            >
              <option
                v-for="group in state.groups"
                :value="group.id"
                :selected="group.id === data.group_id"
              >
                {{ group.title }}
              </option>
            </select>
          </div>
        </div>
        <div class="min-w-0 flex-1 px-4 md:grid md:gap-4">
          <div>
            <label for="email" class="block text-sm font-medium leading-6 text-gray-900">{{
              __('Comment', 'podlove-podcasting-plugin-for-wordpress')
            }}</label>
            <div class="mt-2">
              <input
                type="text"
                :value="data.comment"
                @input="updateComment($event)"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                :placeholder="__('Comment', 'podlove-podcasting-plugin-for-wordpress')"
              />
            </div>
          </div>
        </div>
        <div class="flex space-x-2 justify-end mt-[30px]">
          <button
            @click="moveContributionUp()"
            :disabled="first"
            :class="{
              'text-indigo-600': !first,
              'text-gray-500': first,
            }"
          >
            <arrow-up-icon class="w-5 h-5" />
          </button>
          <button
            @click="moveContributionDown()"
            :disabled="last"
            :class="{
              'text-indigo-600': !last,
              'text-gray-500': last,
            }"
          >
            <arrow-down-icon class="w-5 h-5" />
          </button>
          <a :href="editLink" target="_blank" class="text-gray-400">
            <pencil-icon class="w-5 h-5" />
          </a>
          <button class="text-red-600" @click="deleteContribution()">
            <x-icon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { injectStore, mapState } from 'redux-vuex'
import { defineComponent } from 'vue'
import { selectors } from '@store'
import * as episode from '@store/episode.store'
import { PodloveRole, PodloveGroup } from '../../../types/contributors.types'
import { PodloveEpisodeContribution } from '../../../types/episode.types'

import {
  ArrowUpIcon,
  ArrowDownIcon,
  XMarkIcon as XIcon,
  UserCircleIcon,
  PencilIcon,
} from '@heroicons/vue/24/outline'

import { get } from 'lodash'

export default defineComponent({
  components: {
    ArrowUpIcon,
    ArrowDownIcon,
    XIcon,
    UserCircleIcon,
    PencilIcon,
  },
  props: {
    data: {
      type: Object,
      default: () => ({
        id: null,
        contributor_id: null,
        role_id: null,
        group_id: null,
        position: null,
        comment: null,
        identifier: null,
        avatar: null,
        name: null,
        mail: null,
        department: null,
        organisation: null,
        jobtitle: null,
        gender: null,
        nickname: null,
        count: null,
      }),
    },
    first: {
      type: Boolean,
      default: false,
    },
    last: {
      type: Boolean,
      default: false,
    },
  },

  setup(): {
    dispatch: Function
    state: {
      roles: PodloveRole[]
      groups: PodloveGroup[]
      baseUrl: string
    }
  } {
    return {
      dispatch: injectStore().dispatch,
      state: mapState({
        roles: selectors.contributors.roles,
        groups: selectors.contributors.groups,
        baseUrl: selectors.runtime.baseUrl,
      }),
    }
  },

  computed: {
    editLink() {
      return `${this.state.baseUrl}/wp-admin/admin.php?page=podlove_contributor_settings&action=edit&contributor=${this.data.contributor_id}`
    },
  },

  methods: {
    moveContributionUp() {
      this.dispatch(episode.moveContributionUp(this.data as PodloveEpisodeContribution))
    },
    moveContributionDown() {
      this.dispatch(episode.moveContributionDown(this.data as PodloveEpisodeContribution))
    },
    deleteContribution() {
      this.dispatch(episode.deleteContribution(this.data as PodloveEpisodeContribution))
    },
    updateRole(event: Event) {
      const role_id = get(event, ['target', 'value'])

      this.dispatch(
        episode.updateContribution({
          ...(this.data as PodloveEpisodeContribution),
          role_id,
        })
      )
    },
    updateGroup(event: Event) {
      const group_id = get(event, ['target', 'value'])
      console.log(group_id)
      this.dispatch(
        episode.updateContribution({
          ...(this.data as PodloveEpisodeContribution),
          group_id,
        })
      )
    },
    updateComment(event: Event) {
      const comment = get(event, ['target', 'value'])

      this.dispatch(
        episode.updateContribution({
          ...(this.data as PodloveEpisodeContribution),
          comment,
        })
      )
    },
  },
})
</script>
