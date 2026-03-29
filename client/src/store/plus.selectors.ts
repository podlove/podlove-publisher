import { createSelector } from 'reselect'
import * as plusFileMigrationStore from './plusFileMigration.store'
import * as plusStore from './plus.store'

type RootState = {
  plus: plusStore.State
  plusFileMigration: plusFileMigrationStore.State
}

const root = {
  plus: (state: RootState) => state.plus,
  plusFileMigration: (state: RootState) => state.plusFileMigration,
}

const plusFileMigration = {
  totalState: createSelector(root.plusFileMigration, plusFileMigrationStore.selectors.totalState),
  progress: createSelector(root.plusFileMigration, plusFileMigrationStore.selectors.progress),
  currentEpisodeName: createSelector(
    root.plusFileMigration,
    plusFileMigrationStore.selectors.currentEpisodeName
  ),
  currentFileName: createSelector(
    root.plusFileMigration,
    plusFileMigrationStore.selectors.currentFileName
  ),
  episodesWithFiles: createSelector(
    root.plusFileMigration,
    plusFileMigrationStore.selectors.episodesWithFiles
  ),
  isMigrationComplete: createSelector(
    root.plusFileMigration,
    plusFileMigrationStore.selectors.isMigrationComplete
  ),
  showMigrationToolManually: createSelector(
    root.plusFileMigration,
    plusFileMigrationStore.selectors.showMigrationToolManually
  ),
}

const plus = {
  features: createSelector(root.plus, plusStore.selectors.features),
  token: createSelector(root.plus, plusStore.selectors.token),
  user: createSelector(root.plus, plusStore.selectors.user),
  isLoading: createSelector(root.plus, plusStore.selectors.isLoading),
  isSaving: createSelector(root.plus, plusStore.selectors.isSaving),
}

export default {
  plusFileMigration,
  plus,
}
