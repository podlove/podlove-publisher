import { Action } from 'redux'
import { createAction } from 'redux-actions'

export const UPDATE = 'podlove/publisher/wordpress/UPDATE'
export const SELECT_MEDIA_FROM_LIBRARY = 'podlove/publisher/wordpress/SELECT_MEDIA_FROM_LIBRARY'

export const update = createAction<{ prop: string; value: any }>(UPDATE)
export const selectMediaFromLibrary = createAction<{ onSuccess: Action }>(SELECT_MEDIA_FROM_LIBRARY)
