import { createSelector } from 'reselect'
import { State } from './index'
import * as lifecycleStore from './lifecycle.store'
import * as chaptersStore from './chapters.store'
import * as episodeStore from './episode.store'

const root = {
  bootstrapped: (state: State) => state.lifecycle,
  chapters: (state: State) => state.chapters,
  episode: (state: State) => state.episode,
}

const lifecycle = {
  bootstrapped: createSelector(root.bootstrapped, lifecycleStore.selectors.bootstrapped)
}

const chapters = {
  list: createSelector(root.chapters, chaptersStore.selectors.chapters),
  selected: createSelector(root.chapters, chaptersStore.selectors.selected)
}

const episode = {
  duration: createSelector(root.episode, episodeStore.selectors.duration)
}

export default { lifecycle, chapters, episode }
