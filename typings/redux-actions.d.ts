declare module 'redux-actions' {
  export const handleActions: (bindings: any, state: any) => any;
  export const createAction: <T>(type: string) => (payload: T) => { type: string, payload: T };
}
