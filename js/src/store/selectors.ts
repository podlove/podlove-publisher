import { createSelector } from 'reselect'
<<<<<<< HEAD

=======
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
import { State } from './index'
import * as lifecycleStore from './lifecycle.store'
import * as chaptersStore from './chapters.store'
import * as episodeStore from './episode.store'
<<<<<<< HEAD
import * as runtimeStore from './runtime.store'
import * as postStore from './post.store'
import * as transcriptsStore from './transcripts.store'
import * as contributorsStore from './contributors.store'
=======
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3

const root = {
  bootstrapped: (state: State) => state.lifecycle,
  chapters: (state: State) => state.chapters,
  episode: (state: State) => state.episode,
<<<<<<< HEAD
  runtime: (state: State) => state.runtime,
  post: (state: State) => state.post,
  transcripts: (state: State) => state.transcripts,
  contributors: (state: State) => state.contributors
=======
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
}

const lifecycle = {
  bootstrapped: createSelector(root.bootstrapped, lifecycleStore.selectors.bootstrapped)
}

const chapters = {
  list: createSelector(root.chapters, chaptersStore.selectors.chapters),
<<<<<<< HEAD
  selected: createSelector(root.chapters, chaptersStore.selectors.selected),
  selectedIndex: createSelector(root.chapters, chaptersStore.selectors.selectedIndex),
}

const episode = {
  id: createSelector(root.episode, episodeStore.selectors.id),
  duration: createSelector(root.episode, episodeStore.selectors.duration)
}

const runtime = {
  nonce: createSelector(root.runtime, runtimeStore.selectors.nonce),
  base: createSelector(root.runtime, runtimeStore.selectors.base),
  auth: createSelector(root.runtime, runtimeStore.selectors.auth),
  bearer: createSelector(root.runtime, runtimeStore.selectors.bearer),
}

const post = {
  id: createSelector(root.post, postStore.selectors.id)
}

const transcripts = {
  list: createSelector(root.transcripts, transcriptsStore.selectors.transcripts)
}

const contributors = {
  list: createSelector(root.contributors, contributorsStore.selectors.list)
}

export default { lifecycle, chapters, episode, runtime, post, transcripts, contributors }
=======
  selected: createSelector(root.chapters, chaptersStore.selectors.selected)
}

const episode = {
  duration: createSelector(root.episode, episodeStore.selectors.duration)
}

export default { lifecycle, chapters, episode }
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
