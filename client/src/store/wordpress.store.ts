import { Action } from 'redux';
import { createAction } from 'redux-actions'

export const UPDATE = 'podlove/publisher/wordpress/UPDATE'
export const SELECT_IMAGE_FROM_LIBRARY = 'podlove/publisher/wordpress/SELECT_IMAGE_FROM_LIBRARY'

export const update = createAction<{ prop: string; value: any; }>(UPDATE)
export const selectImageFromLibrary = createAction<{ onSuccess: Action }>(SELECT_IMAGE_FROM_LIBRARY)
