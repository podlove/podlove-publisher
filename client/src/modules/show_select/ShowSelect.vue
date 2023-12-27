<template>Hello World</template>

<script lang="ts">
import { defineComponent } from '@vue/runtime-core'
import { mapState, injectStore } from 'redux-vuex'

import selectors from '@store/selectors'

import * as shows from '@store/shows.store'

export default defineComponent({
  components: {},
  setup() {
    return {
      state: mapState({
        // showsList: selectors.shows.showList,
        // selectedShow: ...,
      }),
      dispatch: injectStore().dispatch,
    }
  },
  created() {
    this.dispatch(shows.init())
  },
})
</script>

<style></style>
