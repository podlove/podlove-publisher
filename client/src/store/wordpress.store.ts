import { createAction } from 'redux-actions'

export const UPDATE = 'podlove/publisher/wordpress/UPDATE'

export const update = createAction<{ prop: string; value: any; }>(UPDATE)
